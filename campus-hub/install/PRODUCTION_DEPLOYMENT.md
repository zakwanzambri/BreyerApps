# Campus Hub Portal - Production Deployment Guide

## Overview
This guide provides comprehensive instructions for deploying the Campus Hub Portal to a production environment with proper security, monitoring, and backup systems.

## Prerequisites

### Server Requirements
- **Operating System**: Ubuntu 20.04 LTS or higher / CentOS 8+ / Windows Server 2019+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **Memory**: Minimum 4GB RAM (8GB+ recommended)
- **Storage**: Minimum 50GB SSD storage
- **CPU**: 2+ cores recommended

### Domain & SSL
- Registered domain name
- SSL certificate (Let's Encrypt recommended for free SSL)
- DNS configuration access

### Third-party Services
- Email service (SMTP) for notifications
- Optional: CDN service (Cloudflare, AWS CloudFront)
- Optional: External monitoring service

## Pre-Deployment Checklist

### 1. Code Preparation
- [ ] Run all tests (`php tests/run_tests.php`)
- [ ] Optimize assets (minify CSS/JS)
- [ ] Update configuration files
- [ ] Generate production database schema
- [ ] Create deployment package

### 2. Security Configuration
- [ ] Change all default passwords
- [ ] Generate secure API keys
- [ ] Configure HTTPS redirection
- [ ] Set up firewall rules
- [ ] Configure rate limiting

### 3. Database Setup
- [ ] Create production database
- [ ] Import schema and initial data
- [ ] Set up database user with minimal privileges
- [ ] Configure database backups

## Deployment Steps

### Step 1: Server Setup (Linux)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip unzip curl -y

# Enable Apache modules
sudo a2enmod rewrite ssl headers

# Start and enable services
sudo systemctl start apache2 mysql
sudo systemctl enable apache2 mysql
```

### Step 2: MySQL Configuration

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE campus_hub_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'campus_hub_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT SELECT, INSERT, UPDATE, DELETE ON campus_hub_prod.* TO 'campus_hub_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: SSL Certificate Setup (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain SSL certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### Step 4: Apache Virtual Host Configuration

Create `/etc/apache2/sites-available/campus-hub.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/campus-hub
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    # Security Headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net;"
    
    # Directory Configuration
    <Directory /var/www/campus-hub>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # PHP Configuration
        php_admin_value upload_max_filesize 10M
        php_admin_value post_max_size 10M
        php_admin_value memory_limit 256M
        php_admin_value max_execution_time 30
    </Directory>
    
    # Protect sensitive files
    <FilesMatch "\.(env|json|md|lock)$">
        Require all denied
    </FilesMatch>
    
    # Protect config directories
    <DirectoryMatch "/(config|logs|backups)">
        Require all denied
    </DirectoryMatch>
    
    # Enable compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    </IfModule>
    
    # Cache static files
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 month"
        ExpiresByType image/jpeg "access plus 1 month"
        ExpiresByType image/gif "access plus 1 month"
        ExpiresByType image/png "access plus 1 month"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/pdf "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
        ExpiresByType application/x-javascript "access plus 1 month"
        ExpiresByType application/x-shockwave-flash "access plus 1 month"
        ExpiresByType image/x-icon "access plus 1 year"
    </IfModule>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/campus-hub-error.log
    CustomLog ${APACHE_LOG_DIR}/campus-hub-access.log combined
</VirtualHost>
```

### Step 5: Deploy Application Files

```bash
# Create application directory
sudo mkdir -p /var/www/campus-hub
sudo chown -R www-data:www-data /var/www/campus-hub

# Upload application files (example using rsync)
rsync -avz --exclude='.git' --exclude='tests' --exclude='*.md' ./campus-hub/ user@server:/var/www/campus-hub/

# Set proper permissions
sudo chown -R www-data:www-data /var/www/campus-hub
sudo chmod -R 755 /var/www/campus-hub
sudo chmod -R 775 /var/www/campus-hub/uploads
sudo chmod -R 775 /var/www/campus-hub/logs
```

### Step 6: Environment Configuration

Create `/var/www/campus-hub/.env`:

```env
# Environment
APP_ENV=production
APP_DEBUG=false

# Database
DB_HOST=localhost
DB_NAME=campus_hub_prod
DB_USER=campus_hub_user
DB_PASS=STRONG_PASSWORD_HERE

# Security
APP_KEY=YOUR_32_CHARACTER_SECRET_KEY_HERE
JWT_SECRET=YOUR_JWT_SECRET_HERE
CSRF_TOKEN=YOUR_CSRF_TOKEN_HERE

# Email
MAIL_HOST=smtp.yourmailserver.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=EMAIL_PASSWORD_HERE
MAIL_FROM=noreply@yourdomain.com

# URLs
APP_URL=https://yourdomain.com
API_URL=https://yourdomain.com/api

# File Uploads
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,pdf,doc,docx

# Rate Limiting
RATE_LIMIT_REQUESTS=60
RATE_LIMIT_WINDOW=60

# Session
SESSION_LIFETIME=7200
SESSION_NAME=campus_hub_session

# Cache
CACHE_DRIVER=file
CACHE_LIFETIME=3600
```

### Step 7: Database Migration

```bash
# Navigate to application directory
cd /var/www/campus-hub

# Run database migrations
php install/database_setup.php production

# Import initial data if needed
mysql -u campus_hub_user -p campus_hub_prod < install/initial_data.sql
```

### Step 8: Enable Site

```bash
# Enable the site
sudo a2ensite campus-hub.conf

# Disable default site
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

## Security Hardening

### 1. Firewall Configuration (UFW)

```bash
# Enable UFW
sudo ufw enable

# Allow SSH (adjust port if needed)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Deny all other incoming traffic
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Check status
sudo ufw status verbose
```

### 2. PHP Security Configuration

Edit `/etc/php/8.0/apache2/php.ini`:

```ini
# Hide PHP version
expose_php = Off

# Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

# File upload security
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 10

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

# Error handling
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# Memory and execution limits
memory_limit = 256M
max_execution_time = 30
max_input_time = 30
```

### 3. MySQL Security

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
# Bind to localhost only
bind-address = 127.0.0.1

# Security settings
skip-networking = 0
skip-name-resolve
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO

# Performance settings
innodb_buffer_pool_size = 2G
max_connections = 100
```

## Monitoring Setup

### 1. System Monitoring Script

Create `/opt/campus-hub/monitor.sh`:

```bash
#!/bin/bash

# Campus Hub System Monitor
LOG_FILE="/var/log/campus-hub-monitor.log"
DATE=$(date "+%Y-%m-%d %H:%M:%S")

# Check disk usage
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    echo "[$DATE] WARNING: Disk usage is ${DISK_USAGE}%" >> $LOG_FILE
fi

# Check memory usage
MEM_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100)}')
if [ $MEM_USAGE -gt 90 ]; then
    echo "[$DATE] WARNING: Memory usage is ${MEM_USAGE}%" >> $LOG_FILE
fi

# Check CPU load
CPU_LOAD=$(uptime | awk -F'load average:' '{print $2}' | cut -d, -f1 | sed 's/ //g')
if (( $(echo "$CPU_LOAD > 4.0" | bc -l) )); then
    echo "[$DATE] WARNING: CPU load is ${CPU_LOAD}" >> $LOG_FILE
fi

# Check MySQL status
if ! systemctl is-active --quiet mysql; then
    echo "[$DATE] ERROR: MySQL is not running" >> $LOG_FILE
fi

# Check Apache status
if ! systemctl is-active --quiet apache2; then
    echo "[$DATE] ERROR: Apache is not running" >> $LOG_FILE
fi

# Check SSL certificate expiry
SSL_DAYS=$(echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2 | xargs -I {} date -d {} +%s)
CURRENT_DATE=$(date +%s)
DAYS_LEFT=$(( (SSL_DAYS - CURRENT_DATE) / 86400 ))

if [ $DAYS_LEFT -lt 30 ]; then
    echo "[$DATE] WARNING: SSL certificate expires in ${DAYS_LEFT} days" >> $LOG_FILE
fi

# Log system status
echo "[$DATE] System check completed - Disk: ${DISK_USAGE}%, Memory: ${MEM_USAGE}%, CPU: ${CPU_LOAD}, SSL: ${DAYS_LEFT} days" >> $LOG_FILE
```

Add to crontab:
```bash
# Run every 5 minutes
*/5 * * * * /opt/campus-hub/monitor.sh
```

### 2. Log Rotation

Create `/etc/logrotate.d/campus-hub`:

```
/var/log/campus-hub-monitor.log
/var/www/campus-hub/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

## Backup Strategy

### 1. Database Backup Script

Create `/opt/campus-hub/backup-db.sh`:

```bash
#!/bin/bash

# Campus Hub Database Backup
BACKUP_DIR="/opt/backups/campus-hub"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="campus_hub_prod"
DB_USER="campus_hub_user"
DB_PASS="STRONG_PASSWORD_HERE"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Remove backups older than 30 days
find $BACKUP_DIR -name "db_backup_*.sql.gz" -type f -mtime +30 -delete

# Log backup completion
echo "$(date): Database backup completed: db_backup_$DATE.sql.gz" >> /var/log/backup.log
```

### 2. File Backup Script

Create `/opt/campus-hub/backup-files.sh`:

```bash
#!/bin/bash

# Campus Hub Files Backup
BACKUP_DIR="/opt/backups/campus-hub"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/campus-hub"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup uploads and user files
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz -C $APP_DIR uploads/ logs/ .env

# Remove file backups older than 7 days
find $BACKUP_DIR -name "files_backup_*.tar.gz" -type f -mtime +7 -delete

# Log backup completion
echo "$(date): Files backup completed: files_backup_$DATE.tar.gz" >> /var/log/backup.log
```

### 3. Automated Backup Schedule

Add to crontab:
```bash
# Database backup daily at 2 AM
0 2 * * * /opt/campus-hub/backup-db.sh

# Files backup daily at 3 AM
0 3 * * * /opt/campus-hub/backup-files.sh
```

## Performance Optimization

### 1. Enable OpCache

Add to `/etc/php/8.0/apache2/php.ini`:

```ini
; OpCache settings
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
```

### 2. MySQL Optimization

Add to `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
# Performance tuning
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 64M
tmp_table_size = 64M
max_heap_table_size = 64M
```

## Health Checks

### 1. Application Health Check

Create `/var/www/campus-hub/health.php`:

```php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'OK',
    'timestamp' => date('c'),
    'checks' => []
];

// Database check
try {
    $pdo = new PDO("mysql:host=localhost;dbname=campus_hub_prod", "campus_hub_user", "STRONG_PASSWORD_HERE");
    $health['checks']['database'] = 'OK';
} catch (Exception $e) {
    $health['checks']['database'] = 'FAIL';
    $health['status'] = 'FAIL';
}

// Disk space check
$disk_free = disk_free_space('/');
$disk_total = disk_total_space('/');
$disk_usage = 100 - ($disk_free / $disk_total * 100);

if ($disk_usage > 90) {
    $health['checks']['disk'] = 'CRITICAL';
    $health['status'] = 'FAIL';
} elseif ($disk_usage > 80) {
    $health['checks']['disk'] = 'WARNING';
} else {
    $health['checks']['disk'] = 'OK';
}

// Memory check
$memory = sys_getloadavg();
if ($memory[0] > 4.0) {
    $health['checks']['load'] = 'HIGH';
} else {
    $health['checks']['load'] = 'OK';
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
```

## Post-Deployment Verification

### 1. Functional Tests
- [ ] User registration and login
- [ ] News article creation and viewing
- [ ] Event creation and management
- [ ] Media upload functionality
- [ ] Search functionality
- [ ] Admin dashboard access
- [ ] Email notifications

### 2. Performance Tests
- [ ] Page load times < 3 seconds
- [ ] Database query performance
- [ ] File upload performance
- [ ] Concurrent user handling

### 3. Security Tests
- [ ] HTTPS enforcement
- [ ] SQL injection protection
- [ ] XSS protection
- [ ] CSRF protection
- [ ] File upload security
- [ ] Admin access restrictions

## Maintenance Procedures

### Daily Tasks
- Review error logs
- Check system health status
- Verify backup completion
- Monitor disk usage

### Weekly Tasks
- Update system packages
- Review security logs
- Performance optimization review
- User access audit

### Monthly Tasks
- SSL certificate renewal check
- Database optimization
- Log file cleanup
- Security updates review

## Troubleshooting

### Common Issues

1. **502 Bad Gateway**
   - Check PHP-FPM status: `sudo systemctl status php8.0-fpm`
   - Check Apache error logs: `sudo tail -f /var/log/apache2/error.log`

2. **Database Connection Errors**
   - Verify MySQL status: `sudo systemctl status mysql`
   - Check database credentials in `.env`
   - Review MySQL error logs: `sudo tail -f /var/log/mysql/error.log`

3. **High CPU/Memory Usage**
   - Check running processes: `htop`
   - Review slow query log
   - Optimize database queries

4. **SSL Certificate Issues**
   - Check certificate validity: `openssl x509 -in /etc/letsencrypt/live/yourdomain.com/fullchain.pem -text -noout`
   - Renew certificate: `sudo certbot renew`

## Support & Documentation

- **Application Logs**: `/var/www/campus-hub/logs/`
- **System Logs**: `/var/log/`
- **Configuration Files**: `/var/www/campus-hub/config/`
- **Backup Location**: `/opt/backups/campus-hub/`

For additional support, refer to the technical documentation or contact the development team.

---

**Important**: Always test the deployment process in a staging environment before applying to production. Keep this documentation updated as the system evolves.
