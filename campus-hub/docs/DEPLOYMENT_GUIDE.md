# Campus Hub Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the Campus Hub Portal in different environments (development, staging, production).

## System Requirements

### Minimum Requirements

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4+ (Recommended: PHP 8.1+)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Memory**: 512MB RAM (Recommended: 2GB+)
- **Storage**: 10GB free space (Recommended: 50GB+)
- **SSL Certificate**: Required for production

### PHP Extensions Required

```bash
php-mysqli
php-pdo
php-json
php-session
php-mbstring
php-curl
php-gd
php-fileinfo
php-zip
php-xml
```

### Recommended Tools

- **Git**: For version control
- **Composer**: For PHP dependency management
- **Node.js**: For frontend build tools
- **PM2**: For process management (if using Node.js)

## Pre-Deployment Checklist

### ✅ Code Preparation

- [ ] All code committed to version control
- [ ] Environment configurations separated
- [ ] Security credentials removed from code
- [ ] Database migrations ready
- [ ] Frontend assets built for production
- [ ] Error logging configured
- [ ] Backup scripts prepared

### ✅ Infrastructure Preparation

- [ ] Server provisioned and accessible
- [ ] Domain name configured
- [ ] SSL certificate obtained
- [ ] Database server installed and configured
- [ ] Web server installed and configured
- [ ] PHP and extensions installed
- [ ] Firewall rules configured

## Environment Setup

### 1. Development Environment

#### Using XAMPP (Windows)

```bash
# 1. Install XAMPP
# Download from https://www.apachefriends.org/

# 2. Clone repository
cd C:\xampp\htdocs
git clone <repository-url> BreyerApps\campus-hub

# 3. Configure database
# Open http://localhost/phpmyadmin
# Create database: campus_hub_dev
# Import schema: database/enhanced_schema.sql

# 4. Configure environment
cp config/config.example.php config/config.php
# Edit config.php with local settings

# 5. Set permissions
# Ensure uploads/ and logs/ directories are writable

# 6. Start services
# Start Apache and MySQL via XAMPP Control Panel
```

#### Using Docker

```bash
# 1. Clone repository
git clone <repository-url> campus-hub
cd campus-hub

# 2. Create Docker environment
cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  web:
    image: php:8.1-apache
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/php.ini:/usr/local/etc/php/php.ini
    depends_on:
      - db
    environment:
      - DATABASE_HOST=db
      - DATABASE_NAME=campus_hub
      - DATABASE_USER=root
      - DATABASE_PASSWORD=root_password

  db:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: campus_hub
      MYSQL_USER: campus_user
      MYSQL_PASSWORD: campus_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/enhanced_schema.sql:/docker-entrypoint-initdb.d/init.sql

  phpmyadmin:
    image: phpmyadmin:latest
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: root_password

volumes:
  mysql_data:
EOF

# 3. Start containers
docker-compose up -d

# 4. Access application
# Web: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

### 2. Production Environment

#### Server Setup (Ubuntu 20.04)

```bash
# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install web server
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# 3. Install PHP
sudo apt install php8.1 php8.1-cli php8.1-common \
    php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring \
    php8.1-curl php8.1-xml php8.1-bcmath php8.1-json -y

# 4. Install MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# 5. Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 6. Install Git
sudo apt install git -y

# 7. Configure firewall
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable
```

#### Application Deployment

```bash
# 1. Create application directory
sudo mkdir -p /var/www/campus-hub
sudo chown $USER:$USER /var/www/campus-hub

# 2. Clone repository
cd /var/www/campus-hub
git clone <repository-url> .

# 3. Set permissions
sudo chown -R www-data:www-data /var/www/campus-hub
sudo chmod -R 755 /var/www/campus-hub
sudo chmod -R 777 /var/www/campus-hub/uploads
sudo chmod -R 777 /var/www/campus-hub/logs

# 4. Configure Apache virtual host
sudo tee /etc/apache2/sites-available/campus-hub.conf << 'EOF'
<VirtualHost *:80>
    ServerName campus-hub.yourdomain.com
    DocumentRoot /var/www/campus-hub
    
    <Directory /var/www/campus-hub>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/campus-hub_error.log
    CustomLog ${APACHE_LOG_DIR}/campus-hub_access.log combined
</VirtualHost>
EOF

# 5. Enable site and modules
sudo a2ensite campus-hub.conf
sudo a2enmod rewrite
sudo systemctl reload apache2

# 6. Configure database
mysql -u root -p << 'EOF'
CREATE DATABASE campus_hub_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'campus_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON campus_hub_prod.* TO 'campus_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# 7. Import database schema
mysql -u campus_user -p campus_hub_prod < database/enhanced_schema.sql

# 8. Configure environment
cp config/config.example.php config/config.php
# Edit config.php with production settings
```

## Configuration Files

### 1. Database Configuration

```php
<?php
// config/database.php
return [
    'development' => [
        'host' => 'localhost',
        'database' => 'campus_hub_dev',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    'production' => [
        'host' => 'localhost',
        'database' => 'campus_hub_prod',
        'username' => 'campus_user',
        'password' => getenv('DB_PASSWORD'),
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ]
];
?>
```

### 2. Environment Configuration

```php
<?php
// config/config.php
define('ENVIRONMENT', 'production'); // development, staging, production

$config = [
    'app' => [
        'name' => 'Campus Hub Portal',
        'version' => '2.0.0',
        'timezone' => 'Asia/Kuala_Lumpur',
        'debug' => ENVIRONMENT !== 'production',
        'url' => 'https://campus-hub.yourdomain.com',
    ],
    
    'security' => [
        'encryption_key' => getenv('ENCRYPTION_KEY'),
        'session_lifetime' => 86400, // 24 hours
        'csrf_protection' => true,
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 200,
            'time_window' => 3600, // 1 hour
        ],
    ],
    
    'email' => [
        'smtp_host' => getenv('SMTP_HOST'),
        'smtp_port' => getenv('SMTP_PORT'),
        'smtp_username' => getenv('SMTP_USERNAME'),
        'smtp_password' => getenv('SMTP_PASSWORD'),
        'from_email' => 'noreply@yourdomain.com',
        'from_name' => 'Campus Hub Portal',
    ],
    
    'file_upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'upload_path' => __DIR__ . '/../uploads/',
        'url_path' => '/uploads/',
    ],
    
    'cache' => [
        'enabled' => true,
        'default_ttl' => 3600, // 1 hour
        'driver' => 'file', // file, redis, memcached
        'path' => __DIR__ . '/../cache/',
    ],
    
    'logging' => [
        'enabled' => true,
        'level' => ENVIRONMENT === 'production' ? 'ERROR' : 'DEBUG',
        'path' => __DIR__ . '/../logs/',
        'max_size' => 10 * 1024 * 1024, // 10MB
        'max_files' => 10,
    ],
];

return $config;
?>
```

### 3. Environment Variables (.env)

```bash
# Database
DB_HOST=localhost
DB_NAME=campus_hub_prod
DB_USER=campus_user
DB_PASSWORD=secure_password_here

# Security
ENCRYPTION_KEY=your-32-character-encryption-key-here
JWT_SECRET=your-jwt-secret-key-here

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password

# External Services
GOOGLE_ANALYTICS_ID=GA-XXXXXXXXX
RECAPTCHA_SITE_KEY=your-recaptcha-site-key
RECAPTCHA_SECRET_KEY=your-recaptcha-secret-key

# CDN (Optional)
CDN_URL=https://cdn.yourdomain.com
```

## SSL Certificate Setup

### Using Let's Encrypt (Free)

```bash
# 1. Install Certbot
sudo apt install certbot python3-certbot-apache -y

# 2. Obtain certificate
sudo certbot --apache -d campus-hub.yourdomain.com

# 3. Test automatic renewal
sudo certbot renew --dry-run

# 4. Set up automatic renewal cron job
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

### Using Commercial Certificate

```bash
# 1. Generate private key
openssl genrsa -out campus-hub.key 2048

# 2. Generate certificate signing request
openssl req -new -key campus-hub.key -out campus-hub.csr

# 3. Submit CSR to certificate authority
# Follow your CA's instructions

# 4. Install certificate
# Place certificate files in /etc/ssl/certs/
# Update Apache configuration with SSL directives
```

## Performance Optimization

### 1. Apache Configuration

```apache
# /etc/apache2/conf-enabled/performance.conf
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### 2. PHP Configuration

```ini
; /etc/php/8.1/apache2/php.ini (Production optimizations)
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 20M
upload_max_filesize = 10M
max_file_uploads = 20

; OPcache settings
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

; Session settings
session.gc_maxlifetime = 86400
session.cookie_secure = 1
session.cookie_httponly = 1
session.use_strict_mode = 1
```

### 3. Database Optimization

```sql
-- Add indexes for better performance
USE campus_hub_prod;

-- News table indexes
CREATE INDEX idx_news_category ON news(category);
CREATE INDEX idx_news_status ON news(status);
CREATE INDEX idx_news_featured ON news(is_featured);
CREATE INDEX idx_news_created ON news(created_at);
CREATE FULLTEXT INDEX idx_news_search ON news(title, content, summary);

-- Events table indexes
CREATE INDEX idx_events_type ON events(event_type);
CREATE INDEX idx_events_date ON events(start_date, end_date);
CREATE INDEX idx_events_location ON events(location);
CREATE FULLTEXT INDEX idx_events_search ON events(title, description);

-- Users table indexes
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_student_id ON users(student_id);
CREATE INDEX idx_users_email ON users(email);

-- Activity logs indexes
CREATE INDEX idx_activity_user ON activity_logs(user_id);
CREATE INDEX idx_activity_action ON activity_logs(action);
CREATE INDEX idx_activity_created ON activity_logs(created_at);

-- Session cleanup
DELETE FROM sessions WHERE expires_at < NOW();
```

## Backup Strategy

### 1. Database Backup Script

```bash
#!/bin/bash
# scripts/backup_database.sh

# Configuration
DB_NAME="campus_hub_prod"
DB_USER="campus_user"
DB_PASS="secure_password_here"
BACKUP_DIR="/var/backups/campus-hub"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/database_$DATE.sql"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    $DB_NAME > $BACKUP_FILE

# Compress backup
gzip $BACKUP_FILE

# Remove backups older than 30 days
find $BACKUP_DIR -name "database_*.sql.gz" -mtime +30 -delete

echo "Database backup completed: ${BACKUP_FILE}.gz"
```

### 2. File Backup Script

```bash
#!/bin/bash
# scripts/backup_files.sh

# Configuration
SOURCE_DIR="/var/www/campus-hub"
BACKUP_DIR="/var/backups/campus-hub"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/files_$DATE.tar.gz"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create file backup (excluding logs and cache)
tar -czf $BACKUP_FILE \
    --exclude='logs/*' \
    --exclude='cache/*' \
    --exclude='.git' \
    --exclude='node_modules' \
    $SOURCE_DIR

# Remove backups older than 30 days
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +30 -delete

echo "File backup completed: $BACKUP_FILE"
```

### 3. Automated Backup Cron Jobs

```bash
# Add to crontab (crontab -e)

# Database backup daily at 2 AM
0 2 * * * /var/www/campus-hub/scripts/backup_database.sh

# File backup weekly on Sunday at 3 AM
0 3 * * 0 /var/www/campus-hub/scripts/backup_files.sh

# Log cleanup daily at 1 AM
0 1 * * * find /var/www/campus-hub/logs -name "*.log" -mtime +7 -delete
```

## Monitoring and Logging

### 1. System Monitoring

```bash
# Install monitoring tools
sudo apt install htop iotop nethogs -y

# Monitor system resources
htop           # CPU and memory usage
iotop          # Disk I/O
nethogs        # Network usage
df -h          # Disk space
```

### 2. Application Monitoring

```bash
# Monitor Apache access logs
tail -f /var/log/apache2/campus-hub_access.log

# Monitor Apache error logs
tail -f /var/log/apache2/campus-hub_error.log

# Monitor application logs
tail -f /var/www/campus-hub/logs/app.log

# Monitor MySQL slow queries
tail -f /var/log/mysql/mysql-slow.log
```

### 3. Log Rotation Configuration

```bash
# /etc/logrotate.d/campus-hub
/var/www/campus-hub/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

## Security Hardening

### 1. Server Security

```bash
# Update system regularly
sudo apt update && sudo apt upgrade -y

# Configure firewall
sudo ufw deny ssh from 0.0.0.0/0
sudo ufw allow ssh from your.trusted.ip.address
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable

# Install fail2ban
sudo apt install fail2ban -y

# Configure fail2ban for Apache
sudo tee /etc/fail2ban/jail.local << 'EOF'
[apache-auth]
enabled = true

[apache-badbots]
enabled = true

[apache-noscript]
enabled = true

[apache-overflows]
enabled = true
EOF

sudo systemctl restart fail2ban
```

### 2. Application Security

```apache
# .htaccess security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
</IfModule>

# Hide sensitive files
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

<FilesMatch "\.(sql|log|ini|conf|json)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed

```bash
# Check MySQL service
sudo systemctl status mysql

# Check database credentials
mysql -u campus_user -p campus_hub_prod

# Check PHP MySQL extension
php -m | grep mysql
```

#### 2. File Upload Issues

```bash
# Check directory permissions
ls -la uploads/
sudo chmod 755 uploads/
sudo chown www-data:www-data uploads/

# Check PHP settings
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

#### 3. Session Issues

```bash
# Check session directory permissions
ls -la /var/lib/php/sessions/
sudo chmod 733 /var/lib/php/sessions/

# Check PHP session settings
php -i | grep session.save_path
```

#### 4. Performance Issues

```sql
-- Check slow queries
SHOW PROCESSLIST;
SHOW FULL PROCESSLIST;

-- Check table sizes
SELECT 
    table_name AS "Table",
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.tables
WHERE table_schema = 'campus_hub_prod'
ORDER BY (data_length + index_length) DESC;
```

### Log Analysis

```bash
# Check for errors in application logs
grep -i error /var/www/campus-hub/logs/app.log

# Check for failed login attempts
grep -i "login failed" /var/www/campus-hub/logs/app.log

# Check for slow database queries
grep -i "slow query" /var/log/mysql/mysql-slow.log

# Monitor real-time access
tail -f /var/log/apache2/campus-hub_access.log | grep -v ".css\|.js\|.png\|.jpg"
```

## Post-Deployment Tasks

### 1. Verification Checklist

- [ ] Website loads correctly
- [ ] Database connection working
- [ ] User registration/login functional
- [ ] File uploads working
- [ ] Email notifications sending
- [ ] SSL certificate valid
- [ ] All API endpoints responding
- [ ] Admin panel accessible
- [ ] Mobile responsiveness working
- [ ] Search functionality working

### 2. Performance Testing

```bash
# Install Apache Bench
sudo apt install apache2-utils -y

# Test homepage performance
ab -n 100 -c 10 https://campus-hub.yourdomain.com/

# Test API endpoint performance
ab -n 50 -c 5 https://campus-hub.yourdomain.com/php/api/news.php?action=list
```

### 3. Initial Data Setup

```sql
-- Create default admin user
INSERT INTO users (username, name, email, password, role, status) VALUES
('admin', 'System Administrator', 'admin@yourdomain.com', 
 '$2y$10$encrypted_password_hash_here', 'admin', 'active');

-- Create sample news categories
INSERT INTO news_categories (name, description) VALUES
('Academic', 'Academic related news'),
('Events', 'Campus events and activities'),
('Announcements', 'General announcements'),
('Student Life', 'Student life and activities');

-- Create sample event types
INSERT INTO event_types (name, description) VALUES
('Academic', 'Academic events'),
('Cultural', 'Cultural activities'),
('Sports', 'Sports events'),
('Career', 'Career development');
```

## Maintenance

### Daily Tasks

- [ ] Check system resources (CPU, memory, disk)
- [ ] Review error logs
- [ ] Monitor application performance
- [ ] Check backup completion

### Weekly Tasks

- [ ] Review security logs
- [ ] Update system packages
- [ ] Clean up old log files
- [ ] Review database performance
- [ ] Test backup restoration

### Monthly Tasks

- [ ] Security audit
- [ ] Performance optimization review
- [ ] Update documentation
- [ ] Review user access and permissions
- [ ] Plan capacity upgrades if needed

## Support

### Documentation

- API Documentation: `/docs/API_DOCUMENTATION.md`
- User Manual: `/docs/USER_MANUAL.md`
- Development Guide: `/docs/DEVELOPMENT_GUIDE.md`

### Emergency Contacts

- System Administrator: admin@yourdomain.com
- Database Administrator: dba@yourdomain.com
- Security Team: security@yourdomain.com

### Backup Restoration

In case of system failure, follow these steps:

1. **Database Restoration**:
   ```bash
   # Stop application
   sudo systemctl stop apache2
   
   # Restore database
   zcat /var/backups/campus-hub/database_YYYYMMDD_HHMMSS.sql.gz | \
   mysql -u campus_user -p campus_hub_prod
   ```

2. **File Restoration**:
   ```bash
   # Extract files backup
   cd /var/www
   sudo tar -xzf /var/backups/campus-hub/files_YYYYMMDD_HHMMSS.tar.gz
   
   # Set permissions
   sudo chown -R www-data:www-data campus-hub
   sudo chmod -R 755 campus-hub
   ```

3. **Start Services**:
   ```bash
   sudo systemctl start mysql
   sudo systemctl start apache2
   ```

This deployment guide provides comprehensive instructions for deploying the Campus Hub Portal in production environments with proper security, monitoring, and maintenance procedures.
