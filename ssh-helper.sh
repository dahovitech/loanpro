#!/bin/bash

# Script SSH Helper pour LoanPro
# Facilite l'exécution de commandes sur le serveur distant

set -e

# Configuration SSH
SSH_HOST="46.202.129.197"
SSH_USER="mrjoker"
SSH_PASS="j20U5HrazAo|0F9dwmAUY"
WEB_DIR="~/public_html"

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

# Exécuter une commande SSH
ssh_exec() {
    local cmd="$1"
    log "Exécution: $cmd"
    
    sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" "$cmd"
}

# Extraction du déploiement
extract_deployment() {
    log "🔄 Extraction du déploiement sur le serveur..."
    
    ssh_exec "cd $WEB_DIR && if [ -f extract.sh ]; then chmod +x extract.sh && ./extract.sh; else echo 'Fichier extract.sh non trouvé'; fi"
    
    success "Extraction terminée"
}

# Configuration de la base de données
setup_database() {
    log "🗄️ Configuration de la base de données..."
    
    # Créer la base de données si elle n'existe pas
    ssh_exec "mysql -u root -p -e 'CREATE DATABASE IF NOT EXISTS loanpro_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'"
    
    # Exécuter les migrations
    ssh_exec "cd $WEB_DIR && php bin/console doctrine:migrations:migrate --no-interaction --env=prod"
    
    success "Base de données configurée"
}

# Configurer les permissions
setup_permissions() {
    log "🔐 Configuration des permissions..."
    
    ssh_exec "cd $WEB_DIR && chmod -R 755 public/ && chmod -R 777 var/ && chown -R www-data:www-data var/"
    
    success "Permissions configurées"
}

# Vider le cache
clear_cache() {
    log "🧹 Vidage du cache..."
    
    ssh_exec "cd $WEB_DIR && php bin/console cache:clear --env=prod"
    
    success "Cache vidé"
}

# Vérifier l'état de l'application
check_status() {
    log "🔍 Vérification de l'état de l'application..."
    
    echo "--- Informations du serveur ---"
    ssh_exec "uname -a"
    
    echo "--- Version PHP ---"
    ssh_exec "php --version"
    
    echo "--- Espace disque ---"
    ssh_exec "df -h $WEB_DIR"
    
    echo "--- Contenu du répertoire web ---"
    ssh_exec "ls -la $WEB_DIR"
    
    echo "--- Status Symfony ---"
    ssh_exec "cd $WEB_DIR && php bin/console about" || true
    
    success "Vérification terminée"
}

# Créer un utilisateur admin
create_admin_user() {
    log "👤 Création d'un utilisateur administrateur..."
    
    echo -n "Email de l'administrateur: "
    read -r admin_email
    
    echo -n "Mot de passe de l'administrateur: "
    read -rs admin_password
    echo
    
    ssh_exec "cd $WEB_DIR && php bin/console app:create-admin '$admin_email' '$admin_password'" || true
    
    success "Utilisateur administrateur créé"
}

# Redémarrer les services web
restart_services() {
    log "🔄 Redémarrage des services web..."
    
    ssh_exec "sudo systemctl reload nginx" || true
    ssh_exec "sudo systemctl reload php8.2-fpm" || true
    
    success "Services redémarrés"
}

# Menu principal
show_menu() {
    echo
    echo "================================"
    echo "   🚀 LoanPro SSH Helper"
    echo "================================"
    echo "1. Extraire le déploiement"
    echo "2. Configurer la base de données"
    echo "3. Configurer les permissions"
    echo "4. Vider le cache"
    echo "5. Vérifier l'état"
    echo "6. Créer un utilisateur admin"
    echo "7. Redémarrer les services"
    echo "8. Déploiement complet (1-7)"
    echo "9. Shell SSH interactif"
    echo "0. Quitter"
    echo "================================"
    echo -n "Votre choix (0-9): "
}

# Déploiement complet
full_deployment() {
    log "🚀 Déploiement complet en cours..."
    
    extract_deployment
    setup_permissions
    clear_cache
    setup_database
    restart_services
    check_status
    
    success "🎉 Déploiement complet terminé !"
    echo "🌐 Application disponible sur: https://loanpro.achatrembourse.online"
}

# Shell SSH interactif
interactive_shell() {
    log "🖥️ Ouverture du shell SSH interactif..."
    sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST"
}

# Fonction principale
main() {
    install_sshpass
    
    while true; do
        show_menu
        read -r choice
        
        case $choice in
            1) extract_deployment ;;
            2) setup_database ;;
            3) setup_permissions ;;
            4) clear_cache ;;
            5) check_status ;;
            6) create_admin_user ;;
            7) restart_services ;;
            8) full_deployment ;;
            9) interactive_shell ;;
            0) 
                log "Au revoir !"
                exit 0
                ;;
            *)
                error "Choix invalide. Veuillez choisir entre 0 et 9."
                ;;
        esac
        
        echo
        echo -n "Appuyez sur Entrée pour continuer..."
        read -r
    done
}

# Lancer le script
main "$@"
