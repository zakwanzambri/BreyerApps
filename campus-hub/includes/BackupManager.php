<?php
/**
 * Campus Hub Backup Manager
 * Automated backup system for database and files
 */

require_once __DIR__ . '/Logger.php';

class BackupManager {
    private $config;
    private $logger;
    private $backupDir;
    
    public function __construct($config = null) {
        $this->config = $config ?: $this->loadConfig();
        $this->logger = getLogger();
        $this->backupDir = $this->config['backup_dir'] ?? dirname(__DIR__) . '/backups';
        
        $this->ensureBackupDirectory();
    }
    
    /**
     * Perform full backup (database + files)
     */
    public function performFullBackup() {
        $this->logger->info('Starting full backup process');
        
        $results = [
            'database' => $this->backupDatabase(),
            'files' => $this->backupFiles(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $success = $results['database']['success'] && $results['files']['success'];
        
        if ($success) {
            $this->logger->info('Full backup completed successfully', $results);
            $this->cleanOldBackups();
        } else {
            $this->logger->error('Full backup failed', $results);
        }
        
        return $results;
    }
    
    /**
     * Backup database
     */
    public function backupDatabase() {
        $startTime = microtime(true);
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "db_backup_{$timestamp}.sql";
        $filepath = $this->backupDir . '/' . $filename;
        $gzFilepath = $filepath . '.gz';
        
        try {
            $this->logger->info('Starting database backup');
            
            // Build mysqldump command
            $command = $this->buildMysqldumpCommand($filepath);
            
            // Execute backup
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('Mysqldump failed: ' . implode("\n", $output));
            }
            
            // Check if backup file was created and has content
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new Exception('Backup file was not created or is empty');
            }
            
            // Compress the backup
            $this->compressFile($filepath, $gzFilepath);
            
            // Remove uncompressed file
            unlink($filepath);
            
            $executionTime = round(microtime(true) - $startTime, 2);
            $fileSize = $this->formatFileSize(filesize($gzFilepath));
            
            $result = [
                'success' => true,
                'filename' => basename($gzFilepath),
                'filepath' => $gzFilepath,
                'size' => $fileSize,
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->info('Database backup completed', $result);
            return $result;
            
        } catch (Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Clean up failed backup files
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            if (file_exists($gzFilepath)) {
                unlink($gzFilepath);
            }
            
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->error('Database backup failed', $result);
            return $result;
        }
    }
    
    /**
     * Backup application files
     */
    public function backupFiles() {
        $startTime = microtime(true);
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "files_backup_{$timestamp}.tar.gz";
        $filepath = $this->backupDir . '/' . $filename;
        $appDir = dirname(__DIR__);
        
        try {
            $this->logger->info('Starting files backup');
            
            // Files and directories to include in backup
            $includeItems = [
                'uploads/',
                'config/',
                '.env',
                'index.php',
                'css/',
                'js/',
                'images/',
                'includes/',
                'php/',
                'admin/',
                'api/'
            ];
            
            // Files and directories to exclude
            $excludeItems = [
                'logs/',
                'backups/',
                'tests/',
                '.git/',
                'node_modules/',
                'vendor/',
                '*.log',
                '.DS_Store',
                'Thumbs.db'
            ];
            
            // Build tar command
            $command = $this->buildTarCommand($appDir, $filepath, $includeItems, $excludeItems);
            
            // Execute backup
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('Tar command failed: ' . implode("\n", $output));
            }
            
            // Check if backup file was created
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new Exception('Backup file was not created or is empty');
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            $fileSize = $this->formatFileSize(filesize($filepath));
            
            $result = [
                'success' => true,
                'filename' => basename($filepath),
                'filepath' => $filepath,
                'size' => $fileSize,
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->info('Files backup completed', $result);
            return $result;
            
        } catch (Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Clean up failed backup
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->error('Files backup failed', $result);
            return $result;
        }
    }
    
    /**
     * List available backups
     */
    public function listBackups() {
        $backups = [
            'database' => [],
            'files' => [],
            'full' => []
        ];
        
        if (!is_dir($this->backupDir)) {
            return $backups;
        }
        
        $files = scandir($this->backupDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filepath = $this->backupDir . '/' . $file;
            if (!is_file($filepath)) {
                continue;
            }
            
            $fileInfo = [
                'filename' => $file,
                'size' => $this->formatFileSize(filesize($filepath)),
                'date' => date('Y-m-d H:i:s', filemtime($filepath)),
                'age_days' => floor((time() - filemtime($filepath)) / 86400)
            ];
            
            if (strpos($file, 'db_backup_') === 0) {
                $backups['database'][] = $fileInfo;
            } elseif (strpos($file, 'files_backup_') === 0) {
                $backups['files'][] = $fileInfo;
            }
        }
        
        // Sort by date (newest first)
        foreach ($backups as &$category) {
            usort($category, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }
        
        return $backups;
    }
    
    /**
     * Restore database from backup
     */
    public function restoreDatabase($backupFile) {
        $filepath = $this->backupDir . '/' . $backupFile;
        
        if (!file_exists($filepath)) {
            throw new Exception("Backup file not found: $backupFile");
        }
        
        $startTime = microtime(true);
        
        try {
            $this->logger->info("Starting database restore from $backupFile");
            
            // Decompress if needed
            $sqlFile = $filepath;
            if (substr($filepath, -3) === '.gz') {
                $sqlFile = $this->decompressFile($filepath);
            }
            
            // Build mysql restore command
            $command = $this->buildMysqlRestoreCommand($sqlFile);
            
            // Execute restore
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            // Clean up temporary SQL file if it was decompressed
            if ($sqlFile !== $filepath && file_exists($sqlFile)) {
                unlink($sqlFile);
            }
            
            if ($returnCode !== 0) {
                throw new Exception('MySQL restore failed: ' . implode("\n", $output));
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $result = [
                'success' => true,
                'backup_file' => $backupFile,
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->info('Database restore completed', $result);
            return $result;
            
        } catch (Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_file' => $backupFile,
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logger->error('Database restore failed', $result);
            return $result;
        }
    }
    
    /**
     * Clean old backups based on retention policy
     */
    public function cleanOldBackups() {
        $retentionDays = $this->config['backup_retention_days'] ?? 30;
        $cutoffTime = time() - ($retentionDays * 24 * 3600);
        
        $deleted = 0;
        $files = glob($this->backupDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
                $this->logger->info("Deleted old backup: " . basename($file));
            }
        }
        
        if ($deleted > 0) {
            $this->logger->info("Cleaned $deleted old backup files older than $retentionDays days");
        }
        
        return $deleted;
    }
    
    /**
     * Get backup statistics
     */
    public function getBackupStats() {
        $backups = $this->listBackups();
        
        $stats = [
            'total_backups' => count($backups['database']) + count($backups['files']),
            'database_backups' => count($backups['database']),
            'files_backups' => count($backups['files']),
            'total_size' => 0,
            'oldest_backup' => null,
            'newest_backup' => null
        ];
        
        $allBackups = array_merge($backups['database'], $backups['files']);
        
        if (!empty($allBackups)) {
            // Calculate total size
            foreach ($allBackups as $backup) {
                $filepath = $this->backupDir . '/' . $backup['filename'];
                if (file_exists($filepath)) {
                    $stats['total_size'] += filesize($filepath);
                }
            }
            
            // Find oldest and newest
            usort($allBackups, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
            
            $stats['oldest_backup'] = $allBackups[0]['date'];
            $stats['newest_backup'] = end($allBackups)['date'];
        }
        
        $stats['total_size_formatted'] = $this->formatFileSize($stats['total_size']);
        
        return $stats;
    }
    
    private function loadConfig() {
        $configFile = dirname(__DIR__) . '/config/config.php';
        if (file_exists($configFile)) {
            return include $configFile;
        }
        
        // Fallback configuration
        return [
            'db_host' => 'localhost',
            'db_name' => 'campus_hub',
            'db_user' => 'root',
            'db_password' => '',
            'backup_dir' => dirname(__DIR__) . '/backups',
            'backup_retention_days' => 30
        ];
    }
    
    private function buildMysqldumpCommand($outputFile) {
        $host = escapeshellarg($this->config['db_host']);
        $user = escapeshellarg($this->config['db_user']);
        $password = $this->config['db_password'] ? '--password=' . escapeshellarg($this->config['db_password']) : '';
        $database = escapeshellarg($this->config['db_name']);
        $output = escapeshellarg($outputFile);
        
        return "mysqldump --host=$host --user=$user $password --single-transaction --routines --triggers $database > $output";
    }
    
    private function buildMysqlRestoreCommand($inputFile) {
        $host = escapeshellarg($this->config['db_host']);
        $user = escapeshellarg($this->config['db_user']);
        $password = $this->config['db_password'] ? '--password=' . escapeshellarg($this->config['db_password']) : '';
        $database = escapeshellarg($this->config['db_name']);
        $input = escapeshellarg($inputFile);
        
        return "mysql --host=$host --user=$user $password $database < $input";
    }
    
    private function buildTarCommand($sourceDir, $outputFile, $includeItems, $excludeItems) {
        $output = escapeshellarg($outputFile);
        $source = escapeshellarg($sourceDir);
        
        // Build exclude options
        $excludeOptions = '';
        foreach ($excludeItems as $exclude) {
            $excludeOptions .= ' --exclude=' . escapeshellarg($exclude);
        }
        
        // Build include list
        $includeList = '';
        foreach ($includeItems as $include) {
            $includeList .= ' ' . escapeshellarg($include);
        }
        
        return "cd $source && tar -czf $output $excludeOptions $includeList";
    }
    
    private function compressFile($inputFile, $outputFile) {
        $input = fopen($inputFile, 'rb');
        $output = gzopen($outputFile, 'wb9');
        
        if (!$input || !$output) {
            throw new Exception('Failed to open files for compression');
        }
        
        while (!feof($input)) {
            gzwrite($output, fread($input, 8192));
        }
        
        fclose($input);
        gzclose($output);
    }
    
    private function decompressFile($gzFile) {
        $outputFile = $this->backupDir . '/temp_' . uniqid() . '.sql';
        
        $input = gzopen($gzFile, 'rb');
        $output = fopen($outputFile, 'wb');
        
        if (!$input || !$output) {
            throw new Exception('Failed to open files for decompression');
        }
        
        while (!gzeof($input)) {
            fwrite($output, gzread($input, 8192));
        }
        
        gzclose($input);
        fclose($output);
        
        return $outputFile;
    }
    
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    private function ensureBackupDirectory() {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // Create .htaccess to protect backup files
        $htaccessFile = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Order Deny,Allow\nDeny from all\n");
        }
    }
}

// Command line interface for running backups
if (php_sapi_name() === 'cli' && isset($argv)) {
    $action = $argv[1] ?? 'full';
    
    $backupManager = new BackupManager();
    
    switch ($action) {
        case 'database':
        case 'db':
            echo "Starting database backup...\n";
            $result = $backupManager->backupDatabase();
            break;
            
        case 'files':
            echo "Starting files backup...\n";
            $result = $backupManager->backupFiles();
            break;
            
        case 'full':
        default:
            echo "Starting full backup...\n";
            $result = $backupManager->performFullBackup();
            break;
            
        case 'clean':
            echo "Cleaning old backups...\n";
            $deleted = $backupManager->cleanOldBackups();
            echo "Deleted $deleted old backup files.\n";
            exit(0);
            
        case 'list':
            echo "Available backups:\n";
            $backups = $backupManager->listBackups();
            foreach ($backups as $type => $files) {
                echo "\n$type backups:\n";
                foreach ($files as $file) {
                    echo "  {$file['filename']} - {$file['size']} - {$file['date']}\n";
                }
            }
            exit(0);
    }
    
    if (isset($result)) {
        if (is_array($result) && isset($result['database']) && isset($result['files'])) {
            // Full backup result
            echo "Database backup: " . ($result['database']['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "Files backup: " . ($result['files']['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        } else {
            // Single backup result
            echo "Backup " . ($result['success'] ? 'completed successfully' : 'failed') . "\n";
            if (!$result['success']) {
                echo "Error: " . $result['error'] . "\n";
            }
        }
    }
}
?>
