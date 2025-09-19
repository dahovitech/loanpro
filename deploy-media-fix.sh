#!/bin/bash

# Script pour deployer le fichier Media.php corrigé via FTP
echo "🔧 Déploiement du fichier Media.php corrigé..."

# Configuration FTP
FTP_SERVER="46.202.129.197"
FTP_USER="mrjoker_loanpro"
FTP_PASS="eAaGl6vpl\|c7Gv5P9"
REMOTE_PATH="/public_html/src/Entity/"

# Installation de lftp si nécessaire
if ! command -v lftp &> /dev/null; then
    echo "Installation de lftp..."
    apt-get update && apt-get install -y lftp
fi

echo "📤 Upload du fichier Media.php via FTP..."

lftp -c "
set ssl:verify-certificate no
set ftp:passive-mode yes
open ftp://$FTP_USER:$FTP_PASS@$FTP_SERVER
cd $REMOTE_PATH
put src/Entity/Media.php
quit
"

if [ $? -eq 0 ]; then
    echo "✅ Fichier Media.php déployé avec succès!"
    
    # Nettoyage du cache sur le serveur après le déploiement
    echo "🧹 Nettoyage du cache sur le serveur..."
    sshpass -p "j20U5HrazAo|0F9dwmAUY" ssh -o StrictHostKeyChecking=no mrjoker@46.202.129.197 << 'EOF'
cd ~/web/loanpro.achatrembourse.online/public_html
rm -rf var/cache/prod/*
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod
echo "✅ Cache nettoyé!"
EOF
    
    echo "🌐 Testez maintenant: https://loanpro.achatrembourse.online/admin/"
else
    echo "❌ Erreur lors du déploiement du fichier."
fi
