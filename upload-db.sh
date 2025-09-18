#!/bin/bash

# Configuration FTP
FTP_HOST="46.202.129.197"
FTP_USER="mrjoker_loanpro"
FTP_PASS="eAaGl6vpl|c7Gv5P9"
REMOTE_PATH="/domains/loanpro.achatrembourse.online/public_html/var"
LOCAL_DB="/workspace/loanpro/var/data.db"

echo "🔧 Upload de la base de données SQLite vers le serveur..."

# Créer le script FTP
cat << EOF > ftp_upload.txt
open $FTP_HOST
user $FTP_USER $FTP_PASS
binary
cd $REMOTE_PATH
put $LOCAL_DB data.db
quit
EOF

# Exécuter le transfert FTP
ftp -n < ftp_upload.txt

# Nettoyer
rm ftp_upload.txt

echo "✅ Base de données transférée avec succès !"
echo "🌐 Testez votre application sur: https://loanpro.achatrembourse.online/fr/"
