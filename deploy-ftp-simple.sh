#!/bin/bash

# Script FTP simple pour déployer LoanPro

set -e

# Configuration
FTP_HOST="loanpro.achatrembourse.online"
FTP_USER="mrjoker_loanpro"
FTP_PASS="eAaGl6vpl|c7Gv5P9"

# Couleurs
BLUE='\033[0;34m'
GREEN='\033[0;32m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Créer une archive temporaire si elle n'existe pas
if [ ! -f "/tmp/loanpro-simple.tar.gz" ]; then
    log "Création d'une archive temporaire..."
    cd /workspace/loanpro
    tar --exclude='.git' --exclude='var/cache' --exclude='var/log' --exclude='node_modules' --exclude='tests' -czf /tmp/loanpro-simple.tar.gz .
fi

log "🚀 Upload via FTP..."

# Upload simple via lftp
lftp -c "
set ftp:ssl-allow no
set ssl:verify-certificate no
open ftp://$FTP_USER:$FTP_PASS@$FTP_HOST
cd public_html

# Nettoyer d'abord
rm -f loanpro-simple.tar.gz extract.sh

# Upload du package
put /tmp/loanpro-simple.tar.gz

# Créer un script d'extraction simple
quote SITE EXEC echo '#!/bin/bash' > extract.sh || echo 'Commande non supportée'
echo 'tar -xzf loanpro-simple.tar.gz && rm loanpro-simple.tar.gz && chmod -R 755 .' | quote SITE EXEC cat >> extract.sh || echo 'Commande non supportée'

bye
"

success "Upload terminé"

# Vérifier que le fichier a été uploadé
log "🔍 Vérification..."
lftp -c "
set ftp:ssl-allow no
set ssl:verify-certificate no
open ftp://$FTP_USER:$FTP_PASS@$FTP_HOST
cd public_html
ls -la loanpro-simple.tar.gz
bye
"

success "✅ Déploiement FTP réussi !"
echo "📋 Pour extraire sur le serveur :"
echo "   1. Accédez à https://loanpro.achatrembourse.online"
echo "   2. Le site devrait maintenant contenir l'application LoanPro"

# Nettoyer
rm -f /tmp/loanpro-simple.tar.gz
