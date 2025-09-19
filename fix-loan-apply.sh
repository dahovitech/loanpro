#!/bin/bash

# Script pour corriger l'erreur de demande de prêt
echo "🔧 Correction de l'erreur sur la page de demande de prêt..."

# Configuration FTP
FTP_SERVER="46.202.129.197"
FTP_USER="mrjoker_loanpro"
FTP_PASS="eAaGl6vpl\|c7Gv5P9"
REMOTE_PATH="/public_html/src/"

echo "📤 Déploiement des fichiers corrigés via FTP..."

# Upload de l'entity Loan corrigée
lftp -c "
set ssl:verify-certificate no
set ftp:passive-mode yes
open ftp://$FTP_USER:$FTP_PASS@$FTP_SERVER
cd ${REMOTE_PATH}Entity/
put src/Entity/Loan.php
quit
"

# Upload du formulaire LoanType
lftp -c "
set ssl:verify-certificate no
set ftp:passive-mode yes
open ftp://$FTP_USER:$FTP_PASS@$FTP_SERVER
cd ${REMOTE_PATH}Form/
put src/Form/LoanType.php
quit
"

if [ $? -eq 0 ]; then
    echo "✅ Fichiers déployés avec succès!"
    
    # Mise à jour du schéma de base de données et nettoyage du cache
    echo "🗄️ Mise à jour de la base de données et du cache..."
    sshpass -p "j20U5HrazAo|0F9dwmAUY" ssh -o StrictHostKeyChecking=no mrjoker@46.202.129.197 << 'EOF'
cd ~/web/loanpro.achatrembourse.online/public_html

echo "🗄️ Mise à jour du schéma de base de données..."
php bin/console doctrine:schema:update --force --env=prod

echo "🧹 Nettoyage du cache..."
rm -rf var/cache/prod/*
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod

echo "✅ Correction terminée!"
EOF
    
    echo "🌐 Testez maintenant: https://loanpro.achatrembourse.online/fr/apply"
else
    echo "❌ Erreur lors du déploiement."
fi
