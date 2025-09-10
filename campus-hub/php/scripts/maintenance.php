<?php
/**
 * Backup and Maintenance Scripts
 * Campus Hub Portal - Enhanced Version
 */

class BackupManager {
    private $dbHost = 'localhost';
    private $dbName = 'campus_hub';
    private $dbUser = 'root';
    private $dbPass = '';
    private $backupDir = '../backups/';
    
    public function __construct() {
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Create database backup
     */
    public function createDatabaseBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "campus_hub_backup_$timestamp.sql";
        $filepath = $this->backupDir . $filename;
        
        try {
            // Use mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                $this->dbHost,
                $this->dbUser,
                $this->dbPass,
                $this->dbName,
                $filepath
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0 && file_exists($filepath)) {
                $this->logBackup($filename, filesize($filepath));
                return [
                    'success' => true,
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath),
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                throw new Exception('mysqldump command failed');
            }
        } catch (Exception $e) {
            error_log("Backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create files backup (uploads, logs, etc.)
     */
    public function createFilesBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "campus_hub_files_$timestamp.zip";
        $filepath = $this->backupDir . $filename;
        
        try {
            $zip = new ZipArchive();
            
            if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Cannot create zip file');
            }
            
            // Add uploads directory
            $this->addDirectoryToZip('../uploads/', $zip, 'uploads/');
            
            // Add logs directory
            $this->addDirectoryToZip('../logs/', $zip, 'logs/');
            
            // Add cache directory
            $this->addDirectoryToZip('../cache/', $zip, 'cache/');
            
            // Add configuration files
            if (file_exists('../config/database.php')) {
                $zip->addFile('../config/database.php', 'config/database.php');
            }
            
            $zip->close();
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Files backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create complete backup (database + files)
     */
    public function createCompleteBackup() {
        $dbBackup = $this->createDatabaseBackup();
        $filesBackup = $this->createFilesBackup();
        
        return [
            'database' => $dbBackup,
            'files' => $filesBackup,
            'success' => $dbBackup['success'] && $filesBackup['success']
        ];
    }
    
    /**
     * List available backups
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . '*.{sql,zip}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'type' => pathinfo($file, PATHINFO_EXTENSION) === 'sql' ? 'database' : 'files'
            ];
        }
        
        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }
    
    /**
     * Delete old backups (keep only specified number)
     */
    public function cleanupOldBackups($keepCount = 10) {
        $backups = $this->listBackups();
        $deleted = 0;
        
        if (count($backups) > $keepCount) {
            $toDelete = array_slice($backups, $keepCount);
            
            foreach ($toDelete as $backup) {
                if (unlink($backup['filepath'])) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Restore database from backup
     */
    public function restoreDatabase($backupFile) {
        $filepath = $this->backupDir . $backupFile;
        
        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'error' => 'Backup file not found'
            ];
        }
        
        try {
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s',
                $this->dbHost,
                $this->dbUser,
                $this->dbPass,
                $this->dbName,
                $filepath
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0) {
                return [
                    'success' => true,
                    'message' => 'Database restored successfully'
                ];
            } else {
                throw new Exception('mysql restore command failed');
            }
        } catch (Exception $e) {
            error_log("Restore failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Add directory to zip recursively
     */
    private function addDirectoryToZip($dir, $zip, $zipDir = '') {
        if (!is_dir($dir)) return;
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipDir . substr($filePath, strlen(realpath($dir)) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Log backup information
     */
    private function logBackup($filename, $size) {
        $logEntry = date('Y-m-d H:i:s') . " - Backup created: $filename (Size: " . 
                   $this->formatBytes($size) . ")" . PHP_EOL;
        file_put_contents($this->backupDir . 'backup.log', $logEntry, FILE_APPEND);
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

class MaintenanceManager {
    private $db;
    
    public function __construct() {
        require_once 'config.php';
        $this->db = Database::getInstance();
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanExpiredSessions() {
        $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Clean up old activity logs
     */
    public function cleanOldActivityLogs($days = 90) {
        $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Clean up old notifications
     */
    public function cleanOldNotifications($days = 30) {
        $sql = "DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Optimize database tables
     */
    public function optimizeTables() {
        $tables = ['users', 'news', 'events', 'event_registrations', 'activity_logs', 'notifications'];
        $optimized = [];
        
        foreach ($tables as $table) {
            $sql = "OPTIMIZE TABLE $table";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $optimized[$table] = $result['Msg_text'] ?? 'OK';
        }
        
        return $optimized;
    }
    
    /**
     * Update user statistics
     */
    public function updateUserStatistics() {
        // Update last login for active sessions
        $sql = "
            UPDATE users u 
            SET last_login = (
                SELECT MAX(last_activity) 
                FROM user_sessions s 
                WHERE s.user_id = u.id 
                AND s.expires_at > NOW()
            )
            WHERE EXISTS (
                SELECT 1 FROM user_sessions s 
                WHERE s.user_id = u.id 
                AND s.expires_at > NOW()
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Clean up orphaned files
     */
    public function cleanOrphanedFiles() {
        $uploadDir = '../uploads/';
        $deleted = 0;
        
        if (!is_dir($uploadDir)) return $deleted;
        
        // Get all files in upload directory
        $files = array_diff(scandir($uploadDir), ['.', '..']);
        
        // Get referenced files from database
        $sql = "
            SELECT DISTINCT SUBSTRING_INDEX(image_url, '/', -1) as filename
            FROM (
                SELECT image_url FROM news WHERE image_url IS NOT NULL
                UNION
                SELECT image_url FROM events WHERE image_url IS NOT NULL
                UNION
                SELECT avatar_url as image_url FROM users WHERE avatar_url IS NOT NULL
            ) as all_files
            WHERE filename IS NOT NULL AND filename != ''
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $referencedFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete orphaned files
        foreach ($files as $file) {
            if (!in_array($file, $referencedFiles)) {
                $filepath = $uploadDir . $file;
                if (is_file($filepath) && unlink($filepath)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Generate system report
     */
    public function generateSystemReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->getDatabaseInfo(),
            'users' => $this->getUserStats(),
            'content' => $this->getContentStats(),
            'storage' => $this->getStorageInfo(),
            'performance' => $this->getPerformanceInfo()
        ];
        
        return $report;
    }
    
    private function getDatabaseInfo() {
        $sql = "
            SELECT 
                COUNT(*) as total_tables,
                SUM(data_length + index_length) as total_size
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $info = $stmt->fetch();
        
        $info['total_size_formatted'] = $this->formatBytes($info['total_size']);
        
        return $info;
    }
    
    private function getUserStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_active,
                SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as monthly_active
            FROM users
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    private function getContentStats() {
        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM news WHERE status = 'published') as published_news,
                (SELECT COUNT(*) FROM events WHERE status = 'active') as active_events,
                (SELECT COUNT(*) FROM event_registrations WHERE status = 'registered') as total_registrations,
                (SELECT COUNT(*) FROM activity_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as recent_activities
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    private function getStorageInfo() {
        $uploadDir = '../uploads/';
        $logDir = '../logs/';
        $cacheDir = '../cache/';
        
        return [
            'uploads_size' => $this->getDirectorySize($uploadDir),
            'logs_size' => $this->getDirectorySize($logDir),
            'cache_size' => $this->getDirectorySize($cacheDir),
            'uploads_count' => $this->getFileCount($uploadDir),
            'logs_count' => $this->getFileCount($logDir),
            'cache_count' => $this->getFileCount($cacheDir)
        ];
    }
    
    private function getPerformanceInfo() {
        // Get slow queries and other performance metrics
        $sql = "SHOW STATUS LIKE 'Slow_queries'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $slowQueries = $stmt->fetch();
        
        $sql = "SHOW STATUS LIKE 'Uptime'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $uptime = $stmt->fetch();
        
        return [
            'slow_queries' => $slowQueries['Value'] ?? 0,
            'uptime_seconds' => $uptime['Value'] ?? 0,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
    }
    
    private function getDirectorySize($dir) {
        $size = 0;
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($files as $file) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
    
    private function getFileCount($dir) {
        $count = 0;
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    private function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

// CLI interface for running maintenance tasks
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'help';
    
    switch ($action) {
        case 'backup':
            $backup = new BackupManager();
            echo "Creating complete backup...\n";
            $result = $backup->createCompleteBackup();
            
            if ($result['success']) {
                echo "Backup completed successfully!\n";
                echo "Database: " . $result['database']['filename'] . "\n";
                echo "Files: " . $result['files']['filename'] . "\n";
            } else {
                echo "Backup failed!\n";
                if (!$result['database']['success']) {
                    echo "Database error: " . $result['database']['error'] . "\n";
                }
                if (!$result['files']['success']) {
                    echo "Files error: " . $result['files']['error'] . "\n";
                }
            }
            break;
            
        case 'cleanup':
            $maintenance = new MaintenanceManager();
            echo "Running maintenance cleanup...\n";
            
            $sessions = $maintenance->cleanExpiredSessions();
            echo "Cleaned $sessions expired sessions\n";
            
            $logs = $maintenance->cleanOldActivityLogs();
            echo "Cleaned $logs old activity logs\n";
            
            $notifications = $maintenance->cleanOldNotifications();
            echo "Cleaned $notifications old notifications\n";
            
            $files = $maintenance->cleanOrphanedFiles();
            echo "Cleaned $files orphaned files\n";
            
            echo "Optimizing database tables...\n";
            $optimized = $maintenance->optimizeTables();
            foreach ($optimized as $table => $result) {
                echo "$table: $result\n";
            }
            
            echo "Maintenance cleanup completed!\n";
            break;
            
        case 'report':
            $maintenance = new MaintenanceManager();
            echo "Generating system report...\n";
            $report = $maintenance->generateSystemReport();
            echo json_encode($report, JSON_PRETTY_PRINT);
            break;
            
        default:
            echo "Campus Hub Maintenance Tool\n";
            echo "Usage: php maintenance.php [action]\n";
            echo "Actions:\n";
            echo "  backup  - Create complete backup\n";
            echo "  cleanup - Run maintenance cleanup\n";
            echo "  report  - Generate system report\n";
            break;
    }
}

?>
