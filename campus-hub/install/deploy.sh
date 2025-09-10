#!/bin/bash

# Campus Hub Portal - Production Deployment Script
# Automated deployment with security checks and rollback capability

set -e  # Exit on any error

# Configuration
APP_NAME="campus-hub"
DEPLOY_USER="deploy"
BACKUP_DIR="/opt/backups/$APP_NAME"
WEB_DIR="/var/www/$APP_NAME"
TEMP_DIR="/tmp/$APP_NAME-deploy"
LOG_FILE="/var/log/$APP_NAME-deploy.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Check if running as correct user
check_user() {
    if [ "$USER" != "$DEPLOY_USER" ] && [ "$USER" != "root" ]; then
        error "This script must be run as $DEPLOY_USER or root user"
        exit 1
    fi
}

# Pre-deployment checks
pre_deployment_checks() {
    log "Running pre-deployment checks..."
    
    # Check disk space
    DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -gt 85 ]; then
        error "Disk usage is ${DISK_USAGE}% - not enough space for deployment"
        exit 1
    fi
    
    # Check if web server is running
    if ! systemctl is-active --quiet apache2; then
        error "Apache is not running"
        exit 1
    fi
    
    # Check if database is running
    if ! systemctl is-active --quiet mysql; then
        error "MySQL is not running"
        exit 1
    fi
    
    # Check if backup directory exists
    if [ ! -d "$BACKUP_DIR" ]; then
        log "Creating backup directory: $BACKUP_DIR"
        mkdir -p "$BACKUP_DIR"
        chown -R www-data:www-data "$BACKUP_DIR"
    fi
    
    success "Pre-deployment checks passed"
}

# Create backup before deployment
create_backup() {
    log "Creating backup before deployment..."
    
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_FILE="$BACKUP_DIR/pre_deploy_backup_$TIMESTAMP.tar.gz"
    DB_BACKUP_FILE="$BACKUP_DIR/pre_deploy_db_$TIMESTAMP.sql.gz"
    
    # Backup application files
    if [ -d "$WEB_DIR" ]; then
        tar -czf "$BACKUP_FILE" -C "$(dirname "$WEB_DIR")" "$(basename "$WEB_DIR")"
        success "Application files backed up to: $BACKUP_FILE"
    fi
    
    # Backup database
    mysqldump --single-transaction --routines --triggers campus_hub_prod | gzip > "$DB_BACKUP_FILE"
    success "Database backed up to: $DB_BACKUP_FILE"
    
    # Store backup info for potential rollback
    echo "$BACKUP_FILE" > /tmp/last_backup_file
    echo "$DB_BACKUP_FILE" > /tmp/last_db_backup_file
}

# Download and prepare new version
prepare_deployment() {
    log "Preparing deployment files..."
    
    # Clean up any existing temp directory
    if [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
    
    mkdir -p "$TEMP_DIR"
    
    # For this example, we'll assume files are uploaded via rsync/scp
    # In a real scenario, you might pull from Git or download from CI/CD
    if [ -z "$DEPLOYMENT_SOURCE" ]; then
        error "DEPLOYMENT_SOURCE environment variable not set"
        error "Please set DEPLOYMENT_SOURCE to the path of new application files"
        exit 1
    fi
    
    if [ ! -d "$DEPLOYMENT_SOURCE" ]; then
        error "Deployment source directory does not exist: $DEPLOYMENT_SOURCE"
        exit 1
    fi
    
    # Copy new files to temp directory
    cp -r "$DEPLOYMENT_SOURCE"/* "$TEMP_DIR/"
    
    # Set proper permissions
    chown -R www-data:www-data "$TEMP_DIR"
    chmod -R 755 "$TEMP_DIR"
    chmod -R 775 "$TEMP_DIR/uploads" 2>/dev/null || true
    chmod -R 775 "$TEMP_DIR/logs" 2>/dev/null || true
    
    success "Deployment files prepared in: $TEMP_DIR"
}

# Run security checks on new version
security_checks() {
    log "Running security checks on new deployment..."
    
    # Check for sensitive files that shouldn't be deployed
    SENSITIVE_FILES=(".git" "tests" "*.md" "composer.json" "package.json")
    
    for pattern in "${SENSITIVE_FILES[@]}"; do
        if find "$TEMP_DIR" -name "$pattern" -type f -o -name "$pattern" -type d 2>/dev/null | grep -q .; then
            warning "Found sensitive files/directories: $pattern"
            find "$TEMP_DIR" -name "$pattern" -type f -delete 2>/dev/null || true
            find "$TEMP_DIR" -name "$pattern" -type d -exec rm -rf {} + 2>/dev/null || true
        fi
    done
    
    # Check file permissions
    find "$TEMP_DIR" -type f -perm /o+w -exec chmod o-w {} \;
    
    # Validate PHP syntax
    if ! find "$TEMP_DIR" -name "*.php" -exec php -l {} \; > /dev/null 2>&1; then
        error "PHP syntax errors found in deployment files"
        exit 1
    fi
    
    success "Security checks passed"
}

# Database migrations
run_migrations() {
    log "Running database migrations..."
    
    MIGRATION_SCRIPT="$TEMP_DIR/install/migrate.php"
    
    if [ -f "$MIGRATION_SCRIPT" ]; then
        cd "$TEMP_DIR"
        php "$MIGRATION_SCRIPT" production
        success "Database migrations completed"
    else
        log "No migration script found, skipping migrations"
    fi
}

# Deploy new version
deploy_application() {
    log "Deploying new application version..."
    
    # Put site in maintenance mode
    create_maintenance_page
    
    # Create a backup of current .env file
    if [ -f "$WEB_DIR/.env" ]; then
        cp "$WEB_DIR/.env" /tmp/backup_env
    fi
    
    # Remove old application files (keeping uploads and logs)
    if [ -d "$WEB_DIR" ]; then
        # Preserve important directories
        [ -d "$WEB_DIR/uploads" ] && mv "$WEB_DIR/uploads" /tmp/preserve_uploads
        [ -d "$WEB_DIR/logs" ] && mv "$WEB_DIR/logs" /tmp/preserve_logs
        
        # Remove old files
        rm -rf "$WEB_DIR"/*
    fi
    
    # Copy new application files
    cp -r "$TEMP_DIR"/* "$WEB_DIR/"
    
    # Restore preserved directories
    [ -d "/tmp/preserve_uploads" ] && mv /tmp/preserve_uploads "$WEB_DIR/uploads"
    [ -d "/tmp/preserve_logs" ] && mv /tmp/preserve_logs "$WEB_DIR/logs"
    
    # Restore .env file
    if [ -f "/tmp/backup_env" ]; then
        cp /tmp/backup_env "$WEB_DIR/.env"
        rm /tmp/backup_env
    fi
    
    # Set proper permissions
    chown -R www-data:www-data "$WEB_DIR"
    chmod -R 755 "$WEB_DIR"
    chmod -R 775 "$WEB_DIR/uploads" 2>/dev/null || true
    chmod -R 775 "$WEB_DIR/logs" 2>/dev/null || true
    
    # Create necessary directories
    mkdir -p "$WEB_DIR/logs"
    mkdir -p "$WEB_DIR/uploads"
    chown -R www-data:www-data "$WEB_DIR/logs" "$WEB_DIR/uploads"
    
    success "Application deployed successfully"
}

# Create maintenance page
create_maintenance_page() {
    cat > "$WEB_DIR/maintenance.html" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>Site Maintenance</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; 
                  border-radius: 50%; width: 50px; height: 50px; 
                  animation: spin 2s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Site Under Maintenance</h1>
        <div class="spinner"></div>
        <p>We're updating Campus Hub Portal to serve you better. Please check back in a few minutes.</p>
        <p>Expected completion: $(date -d '+10 minutes' '+%H:%M')</p>
    </div>
</body>
</html>
EOF
}

# Post-deployment tests
post_deployment_tests() {
    log "Running post-deployment tests..."
    
    # Wait a moment for services to stabilize
    sleep 5
    
    # Test database connection
    if ! php -r "try { new PDO('mysql:host=localhost;dbname=campus_hub_prod', 'campus_hub_user', 'PASSWORD'); echo 'DB OK'; } catch(Exception \$e) { echo 'DB FAIL'; exit(1); }"; then
        error "Database connection test failed"
        return 1
    fi
    
    # Test web server response
    if ! curl -f -s -o /dev/null "http://localhost/health.php"; then
        error "Web server health check failed"
        return 1
    fi
    
    # Test SSL if configured
    if [ -f "/etc/letsencrypt/live/yourdomain.com/fullchain.pem" ]; then
        if ! curl -f -s -o /dev/null "https://yourdomain.com/health.php"; then
            warning "HTTPS health check failed"
        fi
    fi
    
    success "Post-deployment tests passed"
    return 0
}

# Remove maintenance mode
remove_maintenance_mode() {
    log "Removing maintenance mode..."
    
    if [ -f "$WEB_DIR/maintenance.html" ]; then
        rm "$WEB_DIR/maintenance.html"
    fi
    
    # Restart web server to clear any caches
    systemctl reload apache2
    
    success "Maintenance mode removed"
}

# Rollback function
rollback() {
    error "Deployment failed, initiating rollback..."
    
    if [ -f "/tmp/last_backup_file" ] && [ -f "/tmp/last_db_backup_file" ]; then
        BACKUP_FILE=$(cat /tmp/last_backup_file)
        DB_BACKUP_FILE=$(cat /tmp/last_db_backup_file)
        
        # Restore application files
        if [ -f "$BACKUP_FILE" ]; then
            log "Restoring application files from backup..."
            rm -rf "$WEB_DIR"/*
            tar -xzf "$BACKUP_FILE" -C "$(dirname "$WEB_DIR")"
            chown -R www-data:www-data "$WEB_DIR"
        fi
        
        # Restore database
        if [ -f "$DB_BACKUP_FILE" ]; then
            log "Restoring database from backup..."
            gunzip -c "$DB_BACKUP_FILE" | mysql campus_hub_prod
        fi
        
        # Remove maintenance mode
        remove_maintenance_mode
        
        warning "Rollback completed"
    else
        error "No backup files found for rollback"
    fi
}

# Cleanup function
cleanup() {
    log "Cleaning up temporary files..."
    
    if [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
    
    # Clean up backup references
    rm -f /tmp/last_backup_file
    rm -f /tmp/last_db_backup_file
    rm -f /tmp/backup_env
    rm -rf /tmp/preserve_uploads
    rm -rf /tmp/preserve_logs
    
    success "Cleanup completed"
}

# Send notification
send_notification() {
    local status=$1
    local message=$2
    
    # Log to application log
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] DEPLOYMENT $status: $message" >> "$WEB_DIR/logs/deployment.log"
    
    # Send email notification if configured
    if command -v mail >/dev/null 2>&1; then
        echo "$message" | mail -s "Campus Hub Deployment $status" admin@yourdomain.com 2>/dev/null || true
    fi
}

# Main deployment function
main() {
    log "Starting Campus Hub Portal deployment..."
    
    # Set trap for cleanup on exit
    trap cleanup EXIT
    
    # Set trap for rollback on error
    trap 'rollback; exit 1' ERR
    
    check_user
    pre_deployment_checks
    create_backup
    prepare_deployment
    security_checks
    run_migrations
    deploy_application
    
    if post_deployment_tests; then
        remove_maintenance_mode
        success "Deployment completed successfully!"
        send_notification "SUCCESS" "Campus Hub Portal deployed successfully at $(date)"
    else
        error "Post-deployment tests failed"
        exit 1
    fi
}

# Script usage
usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "Options:"
    echo "  -s, --source PATH    Path to deployment source files"
    echo "  -h, --help          Show this help message"
    echo ""
    echo "Environment Variables:"
    echo "  DEPLOYMENT_SOURCE   Path to new application files"
    echo ""
    echo "Example:"
    echo "  DEPLOYMENT_SOURCE=/tmp/new-release $0"
    echo "  $0 --source /tmp/new-release"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -s|--source)
            DEPLOYMENT_SOURCE="$2"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            error "Unknown option: $1"
            usage
            exit 1
            ;;
    esac
done

# Run main function
main

success "Campus Hub Portal deployment script completed!"
log "Deployment log available at: $LOG_FILE"
