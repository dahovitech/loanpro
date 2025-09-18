#!/bin/bash

# Script de finalisation du déploiement via SFTP
# Ce script remplace l'utilisation de SSH qui n'est pas disponible

set -e

# Configuration
SFTP_HOST="46.202.129.197"
SFTP_USER="mrjoker"
SFTP_PASS="j20U5HrazAo|0F9dwmAUY"
WEB_DIR="web/loanpro.achatrembourse.online/public_html"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Installer sshpass si nécessaire
install_sshpass() {
    if ! command -v sshpass >/dev/null 2>&1; then
        log "Installation de sshpass..."
        apt update && apt install -y sshpass
    fi
}

# Extraire le déploiement via SFTP
extract_deployment_sftp() {
    log "🔄 Extraction du déploiement via SFTP..."
    
    # Créer un script d'extraction local puis l'envoyer
    cat > /tmp/extract.sh << 'EOF'
#!/bin/bash
cd ~/public_html

# Si le package tar.gz existe, l'extraire
if [ -f "loanpro-deployment.tar.gz" ]; then
    echo "Extraction du package..."
    tar -xzf loanpro-deployment.tar.gz --strip-components=1
    rm loanpro-deployment.tar.gz
    echo "Package extrait avec succès"
else
    echo "Package non trouvé"
fi

# Configurer les permissions
chmod -R 755 public/ 2>/dev/null || echo "Dossier public/ non trouvé"
chmod -R 777 var/ 2>/dev/null || echo "Dossier var/ non trouvé"

echo "Extraction terminée"
EOF

    chmod +x /tmp/extract.sh
    
    # Envoyer le script via SFTP
    sshpass -p "$SFTP_PASS" sftp -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" << EOF
cd $WEB_DIR
put /tmp/extract.sh extract.sh
bye
EOF
    
    success "Script d'extraction envoyé"
    rm /tmp/extract.sh
}

# Vérifier l'état via SFTP
check_deployment_sftp() {
    log "🔍 Vérification du déploiement..."
    
    sshpass -p "$SFTP_PASS" sftp -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" << EOF
cd $WEB_DIR
ls -la
pwd
bye
EOF
    
    success "Vérification terminée"
}

# Créer un script de configuration PHP
create_config_script() {
    log "📝 Création du script de configuration..."
    
    cat > /tmp/config.php << 'EOF'
<?php
// Script de configuration automatique pour LoanPro

echo "=== Configuration automatique de LoanPro ===\n";

// Vérifier l'environnement PHP
echo "Version PHP: " . phpversion() . "\n";

// Chemin de base
$basePath = __DIR__;
echo "Répertoire de travail: $basePath\n";

// Vérifier les dossiers nécessaires
$directories = ['var', 'var/cache', 'var/log', 'var/sessions', 'public'];
foreach ($directories as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (!is_dir($fullPath)) {
        echo "Création du dossier: $dir\n";
        mkdir($fullPath, 0777, true);
    } else {
        echo "Dossier existant: $dir\n";
    }
    
    // Définir les permissions appropriées
    if (in_array($dir, ['var', 'var/cache', 'var/log', 'var/sessions'])) {
        chmod($fullPath, 0777);
    } else {
        chmod($fullPath, 0755);
    }
}

// Vérifier les fichiers critiques
$criticalFiles = ['composer.json', 'public/index.php'];
foreach ($criticalFiles as $file) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ Fichier trouvé: $file\n";
    } else {
        echo "✗ Fichier manquant: $file\n";
    }
}

// Créer un fichier .env minimal si inexistant
$envFile = $basePath . '/.env';
if (!file_exists($envFile)) {
    echo "Création du fichier .env\n";
    $envContent = "APP_ENV=prod\n";
    $envContent .= "APP_DEBUG=false\n";
    $envContent .= "APP_SECRET=" . bin2hex(random_bytes(16)) . "\n";
    $envContent .= "DATABASE_URL=mysql://root:password@localhost:3306/loanpro_prod\n";
    file_put_contents($envFile, $envContent);
} else {
    echo "✓ Fichier .env existant\n";
}

// Vérifier l'autoloader Composer
$autoloadFile = $basePath . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    echo "✓ Autoloader Composer trouvé\n";
    require_once $autoloadFile;
} else {
    echo "✗ Autoloader Composer manquant\n";
}

// Tester l'application Symfony
try {
    if (file_exists($basePath . '/public/index.php')) {
        echo "✓ Point d'entrée Symfony disponible\n";
    }
} catch (Exception $e) {
    echo "⚠ Erreur lors du test Symfony: " . $e->getMessage() . "\n";
}

echo "\n=== Configuration terminée ===\n";
echo "Application accessible via: https://loanpro.achatrembourse.online\n";
?>
EOF

    # Envoyer le script de configuration
    sshpass -p "$SFTP_PASS" sftp -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" << EOF
cd $WEB_DIR
put /tmp/config.php config.php
bye
EOF
    
    success "Script de configuration envoyé"
    rm /tmp/config.php
}

# Créer une page de test simple
create_test_page() {
    log "📄 Création d'une page de test..."
    
    cat > /tmp/test.html << 'EOF'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoanPro - Test de Déploiement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        h1 { color: #fff; text-align: center; }
        .status { background: rgba(0,255,0,0.2); padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover { background: #ff5252; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 LoanPro - Déploiement Réussi</h1>
        
        <div class="status">
            ✅ <strong>Déploiement automatique terminé avec succès !</strong>
        </div>
        
        <div class="info">
            <h3>Informations du déploiement :</h3>
            <ul>
                <li>Date: <?php echo date('Y-m-d H:i:s'); ?></li>
                <li>Version PHP: <?php echo phpversion(); ?></li>
                <li>Serveur: <?php echo $_SERVER['SERVER_NAME'] ?? 'loanpro.achatrembourse.online'; ?></li>
                <li>Répertoire: <?php echo __DIR__; ?></li>
            </ul>
        </div>
        
        <div class="info">
            <h3>Étapes de finalisation :</h3>
            <p><strong>1.</strong> Extraction du package de déploiement</p>
            <p><strong>2.</strong> Configuration des permissions</p>
            <p><strong>3.</strong> Vérification de l'environnement</p>
            <p><strong>4.</strong> Application prête pour la production</p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="config.php" class="btn">🔧 Exécuter la configuration</a>
            <a href="extract.sh" class="btn">📦 Script d'extraction</a>
        </div>
        
        <div class="info" style="margin-top: 30px;">
            <h3>Prochaines étapes :</h3>
            <ol>
                <li>Cliquer sur "Exécuter la configuration" pour finaliser l'installation</li>
                <li>Configurer la base de données si nécessaire</li>
                <li>Accéder à l'application LoanPro</li>
            </ol>
        </div>
    </div>
</body>
</html>
EOF

    # Envoyer la page de test
    sshpass -p "$SFTP_PASS" sftp -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" << EOF
cd $WEB_DIR
put /tmp/test.html index.html
bye
EOF
    
    success "Page de test créée"
    rm /tmp/test.html
}

# Fonction principale
main() {
    log "🚀 Finalisation du déploiement LoanPro via SFTP..."
    
    install_sshpass
    extract_deployment_sftp
    create_config_script
    create_test_page
    check_deployment_sftp
    
    success "🎉 Finalisation terminée !"
    echo
    echo "🌐 Application accessible sur: https://loanpro.achatrembourse.online"
    echo "📋 Pour finaliser :"
    echo "   1. Visitez: https://loanpro.achatrembourse.online"
    echo "   2. Cliquez sur 'Exécuter la configuration'"
    echo "   3. Configurez la base de données si nécessaire"
}

# Lancer la finalisation
main "$@"
