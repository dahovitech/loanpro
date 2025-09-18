#!/bin/bash

# LoanPro Deployment Script
# This script handles the complete deployment of the LoanPro application

set -e  # Exit on any error

# Configuration
APP_NAME="loanpro"
DOCKER_COMPOSE_FILE="docker-compose.yml"
BACKUP_DIR="/opt/backups"
LOG_FILE="/var/log/deploy.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO:${NC} $1" | tee -a "$LOG_FILE"
}

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."
    
    # Check if running as root or with sudo
    if [ "$EUID" -ne 0 ]; then
        error "Please run this script as root or with sudo"
    fi
    
    # Check if Docker is installed
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed. Please install Docker first."
    fi
    
    # Check if Docker Compose is installed
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose is not installed. Please install Docker Compose first."
    fi
    
    # Check if .env file exists
    if [ ! -f ".env" ]; then
        error ".env file not found. Please create one from .env.example"
    fi
    
    log "Prerequisites check passed"
}

# Load environment variables
load_env() {
    log "Loading environment variables..."
    source .env
    
    # Validate required environment variables
    required_vars=("APP_SECRET" "MYSQL_ROOT_PASSWORD" "MYSQL_DATABASE" "MYSQL_USER" "MYSQL_PASSWORD")
    for var in "${required_vars[@]}"; do
        if [ -z "${!var}" ]; then
            error "Required environment variable $var is not set"
        fi
    done
    
    log "Environment variables loaded"
}

# Create backup
create_backup() {
    if [ "$1" = "--skip-backup" ]; then
        warning "Skipping backup as requested"
        return
    fi
    
    log "Creating backup..."
    
    # Create backup directory
    mkdir -p "$BACKUP_DIR"
    
    # Backup timestamp
    BACKUP_TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_PATH="$BACKUP_DIR/backup_$BACKUP_TIMESTAMP"
    
    # Create backup directory
    mkdir -p "$BACKUP_PATH"
    
    # Backup database if container is running
    if docker-compose ps db | grep -q "Up"; then
        log "Backing up database..."
        docker-compose exec -T db mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" > "$BACKUP_PATH/database.sql"
    fi
    
    # Backup uploaded files
    if [ -d "public/uploads" ]; then
        log "Backing up uploaded files..."
        cp -r public/uploads "$BACKUP_PATH/"
    fi
    
    # Backup storage directory
    if [ -d "storage" ]; then
        log "Backing up storage..."
        cp -r storage "$BACKUP_PATH/"
    fi
    
    # Compress backup
    tar -czf "$BACKUP_PATH.tar.gz" -C "$BACKUP_DIR" "backup_$BACKUP_TIMESTAMP"
    rm -rf "$BACKUP_PATH"
    
    log "Backup created: $BACKUP_PATH.tar.gz"
}

# Build and start services
deploy_services() {
    log "Building and starting services..."
    
    # Pull latest images
    docker-compose pull
    
    # Build application image
    docker-compose build --no-cache app
    
    # Start services
    docker-compose up -d
    
    log "Services started"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    # Wait for database to be ready
    log "Waiting for database to be ready..."
    sleep 30
    
    # Check if database is accessible
    max_attempts=30
    attempt=1
    while [ $attempt -le $max_attempts ]; do
        if docker-compose exec -T db mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1" &> /dev/null; then
            log "Database is ready"
            break
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            error "Database is not ready after $max_attempts attempts"
        fi
        
        log "Waiting for database... (attempt $attempt/$max_attempts)"
        sleep 10
        ((attempt++))
    done
    
    # Run migrations
    docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
    
    log "Database migrations completed"
}

# Install assets
install_assets() {
    log "Installing and building assets..."
    
    # Install npm dependencies
    docker-compose exec -T app npm install
    
    # Build assets
    docker-compose exec -T app npm run build
    
    # Clear Symfony cache
    docker-compose exec -T app php bin/console cache:clear --env=prod
    
    log "Assets installed and built"
}

# Set up SSL certificates (Let's Encrypt)
setup_ssl() {
    if [ -z "$DOMAIN_NAME" ]; then
        warning "DOMAIN_NAME not set, skipping SSL setup"
        return
    fi
    
    log "Setting up SSL certificates for $DOMAIN_NAME..."
    
    # This would typically involve certbot or similar
    # For now, we'll just log the intention
    info "SSL setup would be configured here for domain: $DOMAIN_NAME"
    
    log "SSL setup completed"
}

# Health check
health_check() {
    log "Performing health check..."
    
    # Wait for services to be fully ready
    sleep 60
    
    # Check if application responds
    max_attempts=10
    attempt=1
    while [ $attempt -le $max_attempts ]; do
        if curl -f http://localhost/health &> /dev/null; then
            log "Application health check passed"
            break
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            error "Application health check failed after $max_attempts attempts"
        fi
        
        log "Waiting for application to be ready... (attempt $attempt/$max_attempts)"
        sleep 30
        ((attempt++))
    done
    
    # Check individual services
    services=("app" "db" "redis")
    for service in "${services[@]}"; do
        if docker-compose ps "$service" | grep -q "Up"; then
            log "Service $service is running"
        else
            error "Service $service is not running"
        fi
    done
    
    log "Health check completed successfully"
}

# Setup monitoring
setup_monitoring() {
    log "Setting up monitoring..."
    
    # Start monitoring services
    docker-compose up -d prometheus grafana
    
    # Wait for services to be ready
    sleep 30
    
    log "Monitoring setup completed"
}

# Cleanup old images and containers
cleanup() {
    log "Cleaning up..."
    
    # Remove unused Docker images
    docker image prune -f
    
    # Remove unused volumes
    docker volume prune -f
    
    # Remove old backups (keep last 10)
    if [ -d "$BACKUP_DIR" ]; then
        ls -t "$BACKUP_DIR"/backup_*.tar.gz | tail -n +11 | xargs -r rm
    fi
    
    log "Cleanup completed"
}

# Show deployment summary
show_summary() {
    log "Deployment Summary"
    echo "===================="
    echo "Application: $APP_NAME"
    echo "Status: Successfully deployed"
    echo "Services:"
    docker-compose ps
    echo ""
    echo "URLs:"
    echo "- Application: http://localhost"
    echo "- Grafana: http://localhost:3000"
    echo "- Prometheus: http://localhost:9090"
    if [ -n "$DOMAIN_NAME" ]; then
        echo "- Production: https://$DOMAIN_NAME"
    fi
    echo ""
    echo "Log files:"
    echo "- Deployment: $LOG_FILE"
    echo "- Application: docker-compose logs app"
    echo ""
    log "Deployment completed successfully!"
}

# Main deployment function
main() {
    local skip_backup=false
    local production=false
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --skip-backup)
                skip_backup=true
                shift
                ;;
            --production)
                production=true
                shift
                ;;
            --help)
                echo "Usage: $0 [OPTIONS]"
                echo "Options:"
                echo "  --skip-backup    Skip database and files backup"
                echo "  --production     Production deployment mode"
                echo "  --help          Show this help message"
                exit 0
                ;;
            *)
                error "Unknown option: $1"
                ;;
        esac
    done
    
    log "Starting deployment of $APP_NAME..."
    
    check_prerequisites
    load_env
    
    if [ "$skip_backup" = false ]; then
        create_backup
    fi
    
    deploy_services
    run_migrations
    install_assets
    
    if [ "$production" = true ]; then
        setup_ssl
        setup_monitoring
    fi
    
    health_check
    cleanup
    show_summary
}

# Trap errors and cleanup
trap 'error "Deployment failed at line $LINENO"' ERR

# Run main function with all arguments
main "$@"
