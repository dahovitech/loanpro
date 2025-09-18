#!/bin/bash

# Script de déploiement rapide pour mise à jour de la base de données
# Date: 2025-09-19

echo "🔧 Création de la base de données SQLite sur le serveur..."

# Configuration SSH
SSH_USER="mrjoker"
SSH_HOST="46.202.129.197"
SSH_PASSWORD="j20U5HrazAo|0F9dwmAUY"
REMOTE_DIR="/home/mrjoker/web/loanpro.achatrembourse.online/public_html"

# Commandes à exécuter sur le serveur
cat << 'EOF' > temp_db_setup.sh
#!/bin/bash
cd /home/mrjoker/web/loanpro.achatrembourse.online/public_html

echo "Création de la base de données..."
php bin/console doctrine:database:create --if-not-exists

echo "Création du schéma..."
php bin/console doctrine:schema:create

echo "Installation des dépendances fixtures..."
php composer.phar require --dev doctrine/doctrine-fixtures-bundle --no-interaction

echo "Chargement des données de test..."
php bin/console doctrine:fixtures:load --no-interaction

echo "Base de données configurée avec succès !"
EOF

# Transférer le script sur le serveur et l'exécuter
echo "Transfert et exécution du script de configuration BD..."
sshpass -p "$SSH_PASSWORD" scp -o StrictHostKeyChecking=no temp_db_setup.sh $SSH_USER@$SSH_HOST:$REMOTE_DIR/

# Exécuter le script
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST "cd $REMOTE_DIR && chmod +x temp_db_setup.sh && bash temp_db_setup.sh && rm temp_db_setup.sh"

# Nettoyer
rm temp_db_setup.sh

echo "✅ Configuration de la base de données terminée !"
echo "🌐 Testez votre application sur: https://loanpro.achatrembourse.online/fr/"
