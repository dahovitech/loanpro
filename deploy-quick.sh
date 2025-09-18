#!/bin/bash

# Script de déploiement rapide pour LoanPro
# Usage: ./deploy-quick.sh

set -e

# Configuration
PROJECT_DIR="/workspace/loanpro"
TEMP_DIR=$(mktemp -d)
FTP_HOST="loanpro.achatrembourse.online"
FTP_USER="mrjoker_loanpro"
FTP_PASS="eAaGl6vpl|c7Gv5P9"
REMOTE_DIR="public_html"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Vérifier les prérequis
check_requirements() {
    log "🔍 Vérification des prérequis..."
    
    if [ ! -f "/workspace/composer.phar" ]; then
        curl -sS https://getcomposer.org/installer | php -d memory_limit=-1
        mv composer.phar /workspace/composer.phar
    fi
    command -v php >/dev/null 2>&1 || error "PHP n'est pas installé"
    command -v lftp >/dev/null 2>&1 || warning "lftp n'est pas installé, installation en cours..."
    
    if ! command -v lftp >/dev/null 2>&1; then
        apt update && apt install -y lftp
    fi
    
    if [ ! -d "$PROJECT_DIR" ]; then
        error "Projet non trouvé: $PROJECT_DIR"
    fi
    
    success "Prérequis validés"
}

# Préparer l'application
prepare_app() {
    log "🔧 Préparation de l'application..."
    
    BUILD_DIR="$TEMP_DIR/loanpro"
    cp -r "$PROJECT_DIR" "$BUILD_DIR"
    cd "$BUILD_DIR"
    
    # Nettoyer les fichiers de développement
    rm -rf .git var/cache/* var/log/* var/sessions/* node_modules/ tests/
    rm -f .env.dev .env.test compose.yaml compose.override.yaml
    
    # Créer .env de production
    cat > .env << EOF
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=$(openssl rand -hex 32)
DATABASE_URL=mysql://root:password@localhost:3306/loanpro_prod?serverVersion=8.0
MAILER_DSN=smtp://localhost
EOF
    
    # Installer les dépendances de production
    log "📦 Installation des dépendances..."
    php /workspace/composer.phar install --no-dev --optimize-autoloader --no-interaction
    
    # Créer les dossiers nécessaires
    mkdir -p var/{cache,log,sessions}
    chmod -R 777 var/
    
    # Créer le build directory et entrypoints.json pour Webpack Encore
    mkdir -p public/build
    cat > public/build/entrypoints.json << 'ENTRYPOINTS_EOF'
{
    "entrypoints": {
        "app": {
            "css": ["/build/app.css"],
            "js": ["/build/app.js"]
        }
    }
}
ENTRYPOINTS_EOF

    # Créer manifest.json pour Webpack Encore
    cat > public/build/manifest.json << 'MANIFEST_EOF'
{
    "build/app.css": "/build/app.css",
    "build/app.js": "/build/app.js"
}
MANIFEST_EOF

    # Créer fichier CSS minimal
    cat > public/build/app.css << 'CSS_EOF'
/* CSS minimal pour LoanPro en attendant la compilation Webpack */
body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; }
.btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
.btn:hover { background: #0056b3; }
CSS_EOF

    # Créer fichier JS minimal
    cat > public/build/app.js << 'JS_EOF'
// JavaScript minimal pour LoanPro en attendant la compilation Webpack
console.log('LoanPro application loaded');
JS_EOF
    
    # Créer .htaccess à la racine (pour redirection vers public/)
    cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    # remove "www" from URI
    RewriteCond %{HTTP_HOST} ^www\.(.+) [NC]
    RewriteRule ^ http://%1%{REQUEST_URI} [L,R=301]
    # force HTTPS
    RewriteCond %{HTTPS} !on
    RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    # use public as starting point
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>
EOF
    
    # Créer .htaccess pour Symfony (dans public/)
    cat > public/.htaccess << 'EOF'
DirectoryIndex index.php

<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]

    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]

    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
EOF
    
    success "Application préparée"
}

# Créer l'archive de déploiement
create_package() {
    log "📦 Création du package de déploiement..."
    
    cd "$TEMP_DIR"
    tar -czf loanpro-deployment.tar.gz loanpro/
    
    success "Package créé: loanpro-deployment.tar.gz"
}

# Déployer via FTP
deploy_ftp() {
    log "🚀 Déploiement via FTP..."
    
    cd "$TEMP_DIR"
    
    # Échapper le mot de passe pour éviter les problèmes avec les caractères spéciaux
    FTP_PASS_ESCAPED="eAaGl6vpl\\|c7Gv5P9"
    
    # Script lftp pour le déploiement
    cat > ftp_script.lftp << EOF
set ftp:ssl-allow no
set ssl:verify-certificate no
open ftp://mrjoker_loanpro:$FTP_PASS_ESCAPED@loanpro.achatrembourse.online
cd public_html

# Nettoyer le dossier de destination (garder quelques fichiers de backup)
glob -a rm -rf *.tar.gz *.zip extract.sh

# Uploader le package
put loanpro-deployment.tar.gz

# Créer le script d'extraction
put /dev/stdin -o extract.sh <<EOD
#!/bin/bash
cd ~/public_html
tar -xzf loanpro-deployment.tar.gz --strip-components=1
rm loanpro-deployment.tar.gz
chmod -R 755 public/
chmod -R 777 var/
echo 'Déploiement extrait avec succès !'
EOD

chmod 755 extract.sh
bye
EOF
    
    lftp -f ftp_script.lftp
    rm ftp_script.lftp
    
    success "Déploiement FTP terminé"
}

# Nettoyer les fichiers temporaires
cleanup() {
    log "🧹 Nettoyage..."
    rm -rf "$TEMP_DIR"
    success "Nettoyage terminé"
}

# Fonction principale
main() {
    log "🚀 Démarrage du déploiement de LoanPro..."
    
    # Demander confirmation
    echo -n "🤔 Confirmer le déploiement en production ? (y/N): "
    read -r response
    if [[ ! "$response" =~ ^[Yy]([Ee][Ss])?$ ]]; then
        error "Déploiement annulé"
    fi
    
    # Étapes de déploiement
    check_requirements
    prepare_app
    create_package
    deploy_ftp
    cleanup
    
    success "🎉 Déploiement terminé avec succès !"
    echo
    echo "🌐 Application accessible sur: https://loanpro.achatrembourse.online"
    echo "📋 Prochaines étapes:"
    echo "   1. Connectez-vous en SSH: ssh mrjoker@46.202.129.197"
    echo "   2. Extrayez le déploiement: cd ~/public_html && ./extract.sh"
    echo "   3. Configurez la base de données si nécessaire"
}

# Gestionnaire d'erreurs
trap cleanup EXIT

# Lancer le déploiement
main "$@"
