#!/bin/bash

# Script pour corriger l'erreur Media::$status sur le serveur de production
# Connexion SSH et nettoyage du cache Doctrine

echo "🔧 Correction de l'erreur Media::\$status sur le dashboard admin..."

# Configuration
SERVER="46.202.129.197"
USER="mrjoker" 
PASSWORD="j20U5HrazAo|0F9dwmAUY"
APP_PATH="~/web/loanpro.achatrembourse.online/public_html"

echo "📡 Connexion au serveur de production..."

# Commandes à exécuter sur le serveur
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no $USER@$SERVER << 'EOF'
cd ~/web/loanpro.achatrembourse.online/public_html

echo "🧹 Nettoyage du cache Doctrine..."
rm -rf var/cache/prod/*
rm -rf var/cache/dev/*

echo "🔄 Mise à jour du schéma de base de données..."
# Doctrine schema update pour s'assurer que la base est à jour
php bin/console doctrine:schema:update --force --env=prod

echo "♻️ Régénération du cache de production..."
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod

echo "✅ Correction terminée !"
EOF

if [ $? -eq 0 ]; then
    echo "✅ Correction réussie ! Le dashboard admin devrait maintenant fonctionner."
    echo "🌐 Testez l'accès: https://loanpro.achatrembourse.online/admin/"
else
    echo "❌ Erreur lors de la correction."
fi
