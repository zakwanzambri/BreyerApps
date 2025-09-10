<?php
/**
 * Campus Hub Security Scanner
 * Automated security vulnerability detection and monitoring
 */

require_once __DIR__ . '/Logger.php';

class SecurityScanner {
    private $logger;
    private $config;
    private $appDir;
    private $vulnerabilities;
    
    public function __construct($config = null) {
        $this->logger = getLogger();
        $this->config = $config ?: $this->loadConfig();
        $this->appDir = dirname(__DIR__);
        $this->vulnerabilities = [];
    }
    
    /**
     * Perform comprehensive security scan
     */
    public function performSecurityScan() {
        $this->logger->info('Starting comprehensive security scan');
        
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'scans' => [
                'file_permissions' => $this->scanFilePermissions(),
                'sensitive_files' => $this->scanSensitiveFiles(),
                'sql_injection' => $this->scanSQLInjection(),
                'xss_vulnerabilities' => $this->scanXSSVulnerabilities(),
                'csrf_protection' => $this->scanCSRFProtection(),
                'input_validation' => $this->scanInputValidation(),
                'authentication' => $this->scanAuthentication(),
                'session_security' => $this->scanSessionSecurity(),
                'file_upload' => $this->scanFileUploadSecurity(),
                'configuration' => $this->scanConfiguration()
            ],
            'summary' => [
                'total_vulnerabilities' => 0,
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'info' => 0
            ]
        ];
        
        // Calculate summary
        foreach ($results['scans'] as $scan) {
            if (isset($scan['vulnerabilities'])) {
                foreach ($scan['vulnerabilities'] as $vuln) {
                    $results['summary']['total_vulnerabilities']++;
                    $results['summary'][$vuln['severity']]++;
                }
            }
        }
        
        $this->logger->info('Security scan completed', [
            'total_vulnerabilities' => $results['summary']['total_vulnerabilities'],
            'critical' => $results['summary']['critical'],
            'high' => $results['summary']['high']
        ]);
        
        return $results;
    }
    
    /**
     * Scan file permissions
     */
    private function scanFilePermissions() {
        $vulnerabilities = [];
        
        // Files that should not be world-writable
        $protectedFiles = [
            '.env',
            'config/config.php',
            'includes/',
            'admin/',
            'api/'
        ];
        
        foreach ($protectedFiles as $file) {
            $path = $this->appDir . '/' . $file;
            if (file_exists($path)) {
                $perms = fileperms($path);
                
                // Check if world-writable (dangerous)
                if ($perms & 0002) {
                    $vulnerabilities[] = [
                        'type' => 'file_permissions',
                        'severity' => 'high',
                        'file' => $file,
                        'description' => 'File is world-writable',
                        'recommendation' => 'Change file permissions to remove world-write access'
                    ];
                }
                
                // Check if world-readable for sensitive files
                if (in_array($file, ['.env', 'config/config.php']) && ($perms & 0004)) {
                    $vulnerabilities[] = [
                        'type' => 'file_permissions',
                        'severity' => 'medium',
                        'file' => $file,
                        'description' => 'Sensitive file is world-readable',
                        'recommendation' => 'Restrict read access to sensitive configuration files'
                    ];
                }
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'checked_files' => count($protectedFiles)
        ];
    }
    
    /**
     * Scan for exposed sensitive files
     */
    private function scanSensitiveFiles() {
        $vulnerabilities = [];
        
        // Files that should not be publicly accessible
        $sensitiveFiles = [
            '.env',
            '.git/config',
            'config/config.php',
            'composer.json',
            'composer.lock',
            'package.json',
            'README.md',
            'logs/',
            'backups/',
            'tests/'
        ];
        
        foreach ($sensitiveFiles as $file) {
            $path = $this->appDir . '/' . $file;
            if (file_exists($path)) {
                // Check if file is web-accessible (basic check)
                if ($this->isWebAccessible($file)) {
                    $severity = in_array($file, ['.env', 'config/config.php']) ? 'critical' : 'medium';
                    
                    $vulnerabilities[] = [
                        'type' => 'sensitive_files',
                        'severity' => $severity,
                        'file' => $file,
                        'description' => 'Sensitive file may be publicly accessible',
                        'recommendation' => 'Add .htaccess rules or move file outside web root'
                    ];
                }
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'checked_files' => count($sensitiveFiles)
        ];
    }
    
    /**
     * Scan for SQL injection vulnerabilities
     */
    private function scanSQLInjection() {
        $vulnerabilities = [];
        $phpFiles = $this->findPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            
            // Look for dangerous SQL patterns
            $patterns = [
                '/\$_[A-Z]+\[[\'"][^\'"]*[\'"]\]\s*\.\s*[\'"][^\'"]*(SELECT|INSERT|UPDATE|DELETE)/i' => 'Direct variable concatenation in SQL',
                '/mysql_query\s*\(\s*[\'"][^\'"]*(SELECT|INSERT|UPDATE|DELETE)[^\'"]*.?\$_[A-Z]+/i' => 'mysql_query with user input',
                '/query\s*\(\s*[\'"][^\'"]*(SELECT|INSERT|UPDATE|DELETE)[^\'"]*.?\$_[A-Z]+/i' => 'Query with direct user input'
            ];
            
            foreach ($patterns as $pattern => $description) {
                if (preg_match($pattern, $content, $matches)) {
                    $vulnerabilities[] = [
                        'type' => 'sql_injection',
                        'severity' => 'critical',
                        'file' => str_replace($this->appDir . '/', '', $file),
                        'description' => $description,
                        'code_snippet' => trim($matches[0]),
                        'recommendation' => 'Use prepared statements with parameter binding'
                    ];
                }
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'scanned_files' => count($phpFiles)
        ];
    }
    
    /**
     * Scan for XSS vulnerabilities
     */
    private function scanXSSVulnerabilities() {
        $vulnerabilities = [];
        $phpFiles = $this->findPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            
            // Look for unescaped output
            $patterns = [
                '/echo\s+\$_[A-Z]+\[[\'"][^\'"]*[\'"]\]/i' => 'Direct echo of user input',
                '/print\s+\$_[A-Z]+\[[\'"][^\'"]*[\'"]\]/i' => 'Direct print of user input',
                '/\<\?\=\s*\$_[A-Z]+\[[\'"][^\'"]*[\'"]\]/i' => 'Direct output of user input in short tag'
            ];
            
            foreach ($patterns as $pattern => $description) {
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $vulnerabilities[] = [
                            'type' => 'xss',
                            'severity' => 'high',
                            'file' => str_replace($this->appDir . '/', '', $file),
                            'description' => $description,
                            'code_snippet' => trim($match[0]),
                            'recommendation' => 'Use htmlspecialchars() or similar escaping functions'
                        ];
                    }
                }
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'scanned_files' => count($phpFiles)
        ];
    }
    
    /**
     * Scan CSRF protection
     */
    private function scanCSRFProtection() {
        $vulnerabilities = [];
        $phpFiles = $this->findPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            
            // Look for forms without CSRF protection
            if (preg_match('/<form[^>]*method\s*=\s*[\'"]post[\'"][^>]*>/i', $content)) {
                if (!preg_match('/csrf_token|_token|csrf_field/i', $content)) {
                    $vulnerabilities[] = [
                        'type' => 'csrf',
                        'severity' => 'medium',
                        'file' => str_replace($this->appDir . '/', '', $file),
                        'description' => 'POST form without CSRF protection detected',
                        'recommendation' => 'Implement CSRF token validation for all forms'
                    ];
                }
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'scanned_files' => count($phpFiles)
        ];
    }
    
    /**
     * Scan input validation
     */
    private function scanInputValidation() {
        $vulnerabilities = [];
        $phpFiles = $this->findPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            
            // Look for direct use of user input without validation
            $patterns = [
                '/\$_POST\[[\'"][^\'"]*[\'"]\]\s*(?!.*(?:filter_|is_|empty\(|isset\(|validate))/i' => 'Direct use of $_POST without validation',
                '/\$_GET\[[\'"][^\'"]*[\'"]\]\s*(?!.*(?:filter_|is_|empty\(|isset\(|validate))/i' => 'Direct use of $_GET without validation'
            ];
            
            foreach ($patterns as $pattern => $description) {
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $vulnerabilities[] = [
                            'type' => 'input_validation',
                            'severity' => 'medium',
                            'file' => str_replace($this->appDir . '/', '', $file),
                            'description' => $description,
                            'code_snippet' => trim($match[0]),
                            'recommendation' => 'Implement proper input validation and sanitization'
                        ];
                    }
                }
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'scanned_files' => count($phpFiles)
        ];
    }
    
    /**
     * Scan authentication mechanisms
     */
    private function scanAuthentication() {
        $vulnerabilities = [];
        
        // Check for weak password policies
        $authFiles = $this->findFilesContaining(['password', 'login', 'auth']);
        
        foreach ($authFiles as $file) {
            $content = file_get_contents($file);
            
            // Look for weak password validation
            if (preg_match('/password.*length.*[<]\s*[1-6]/i', $content)) {
                $vulnerabilities[] = [
                    'type' => 'authentication',
                    'severity' => 'medium',
                    'file' => str_replace($this->appDir . '/', '', $file),
                    'description' => 'Weak password length requirement detected',
                    'recommendation' => 'Enforce minimum password length of 8+ characters'
                ];
            }
            
            // Look for plain text password storage
            if (preg_match('/password.*=.*\$_POST.*(?!.*(?:hash|crypt|password_))/i', $content)) {
                $vulnerabilities[] = [
                    'type' => 'authentication',
                    'severity' => 'critical',
                    'file' => str_replace($this->appDir . '/', '', $file),
                    'description' => 'Plain text password storage detected',
                    'recommendation' => 'Use password_hash() for secure password storage'
                ];
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'scanned_files' => count($authFiles)
        ];
    }
    
    /**
     * Scan session security
     */
    private function scanSessionSecurity() {
        $vulnerabilities = [];
        
        // Check session configuration
        $sessionConfig = [
            'session.cookie_httponly' => '1',
            'session.cookie_secure' => '1',
            'session.use_strict_mode' => '1',
            'session.cookie_samesite' => 'Strict'
        ];
        
        foreach ($sessionConfig as $directive => $expectedValue) {
            $currentValue = ini_get($directive);
            if ($currentValue !== $expectedValue) {
                $severity = in_array($directive, ['session.cookie_httponly', 'session.cookie_secure']) ? 'high' : 'medium';
                
                $vulnerabilities[] = [
                    'type' => 'session_security',
                    'severity' => $severity,
                    'directive' => $directive,
                    'current_value' => $currentValue ?: 'not set',
                    'expected_value' => $expectedValue,
                    'description' => "Insecure session configuration: $directive",
                    'recommendation' => "Set $directive = $expectedValue in php.ini"
                ];
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'checked_directives' => count($sessionConfig)
        ];
    }
    
    /**
     * Scan file upload security
     */
    private function scanFileUploadSecurity() {
        $vulnerabilities = [];
        $uploadFiles = $this->findFilesContaining(['$_FILES', 'upload', 'move_uploaded_file']);
        
        foreach ($uploadFiles as $file) {
            $content = file_get_contents($file);
            
            // Check for missing file type validation
            if (preg_match('/move_uploaded_file/i', $content) && !preg_match('/mime.*type|getimagesize|pathinfo.*PATHINFO_EXTENSION/i', $content)) {
                $vulnerabilities[] = [
                    'type' => 'file_upload',
                    'severity' => 'high',
                    'file' => str_replace($this->appDir . '/', '', $file),
                    'description' => 'File upload without proper type validation',
                    'recommendation' => 'Implement file type and MIME type validation'
                ];
            }
            
            // Check for uploads to web-accessible directory
            if (preg_match('/move_uploaded_file.*[\'"](?!.*\.\.).*\//i', $content)) {
                $vulnerabilities[] = [
                    'type' => 'file_upload',
                    'severity' => 'medium',
                    'file' => str_replace($this->appDir . '/', '', $file),
                    'description' => 'Files may be uploaded to web-accessible directory',
                    'recommendation' => 'Store uploaded files outside web root or implement access controls'
                ];
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'scanned_files' => count($uploadFiles)
        ];
    }
    
    /**
     * Scan configuration security
     */
    private function scanConfiguration() {
        $vulnerabilities = [];
        
        // Check PHP configuration
        $phpConfig = [
            'display_errors' => ['expected' => 'Off', 'severity' => 'medium'],
            'display_startup_errors' => ['expected' => 'Off', 'severity' => 'low'],
            'expose_php' => ['expected' => 'Off', 'severity' => 'low'],
            'allow_url_fopen' => ['expected' => 'Off', 'severity' => 'medium'],
            'allow_url_include' => ['expected' => 'Off', 'severity' => 'high']
        ];
        
        foreach ($phpConfig as $directive => $config) {
            $currentValue = ini_get($directive);
            if ($currentValue !== $config['expected']) {
                $vulnerabilities[] = [
                    'type' => 'configuration',
                    'severity' => $config['severity'],
                    'directive' => $directive,
                    'current_value' => $currentValue ?: 'not set',
                    'expected_value' => $config['expected'],
                    'description' => "Insecure PHP configuration: $directive",
                    'recommendation' => "Set $directive = {$config['expected']} in php.ini"
                ];
            }
        }
        
        return [
            'status' => empty($vulnerabilities) ? 'passed' : 'failed',
            'vulnerabilities' => $vulnerabilities,
            'checked_directives' => count($phpConfig)
        ];
    }
    
    /**
     * Generate security report
     */
    public function generateSecurityReport($format = 'json') {
        $scanResults = $this->performSecurityScan();
        
        if ($format === 'html') {
            return $this->generateHTMLReport($scanResults);
        } elseif ($format === 'csv') {
            return $this->generateCSVReport($scanResults);
        }
        
        return json_encode($scanResults, JSON_PRETTY_PRINT);
    }
    
    private function generateHTMLReport($results) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Campus Hub Security Scan Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { background: #f4f4f4; padding: 20px; border-radius: 5px; }
        .summary { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; }
        .critical { border-left: 5px solid #dc3545; }
        .high { border-left: 5px solid #fd7e14; }
        .medium { border-left: 5px solid #ffc107; }
        .low { border-left: 5px solid #28a745; }
        .vulnerability { margin: 10px 0; padding: 15px; border-radius: 5px; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Security Scan Report</h1>
        <p>Generated: ' . $results['timestamp'] . '</p>
    </div>
    
    <div class="summary">
        <div class="stat-box critical">
            <h3>' . $results['summary']['critical'] . '</h3>
            <p>Critical</p>
        </div>
        <div class="stat-box high">
            <h3>' . $results['summary']['high'] . '</h3>
            <p>High</p>
        </div>
        <div class="stat-box medium">
            <h3>' . $results['summary']['medium'] . '</h3>
            <p>Medium</p>
        </div>
        <div class="stat-box low">
            <h3>' . $results['summary']['low'] . '</h3>
            <p>Low</p>
        </div>
    </div>';
        
        foreach ($results['scans'] as $scanType => $scan) {
            if (!empty($scan['vulnerabilities'])) {
                $html .= "<h2>" . ucwords(str_replace('_', ' ', $scanType)) . "</h2>";
                
                foreach ($scan['vulnerabilities'] as $vuln) {
                    $html .= '<div class="vulnerability ' . $vuln['severity'] . '">';
                    $html .= '<h4>' . strtoupper($vuln['severity']) . ': ' . $vuln['description'] . '</h4>';
                    if (isset($vuln['file'])) {
                        $html .= '<p><strong>File:</strong> ' . $vuln['file'] . '</p>';
                    }
                    if (isset($vuln['code_snippet'])) {
                        $html .= '<div class="code">' . htmlspecialchars($vuln['code_snippet']) . '</div>';
                    }
                    $html .= '<p><strong>Recommendation:</strong> ' . $vuln['recommendation'] . '</p>';
                    $html .= '</div>';
                }
            }
        }
        
        $html .= '</body></html>';
        return $html;
    }
    
    private function findPHPFiles() {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->appDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    private function findFilesContaining($keywords) {
        $files = [];
        $phpFiles = $this->findPHPFiles();
        
        foreach ($phpFiles as $file) {
            $content = strtolower(file_get_contents($file));
            foreach ($keywords as $keyword) {
                if (strpos($content, strtolower($keyword)) !== false) {
                    $files[] = $file;
                    break;
                }
            }
        }
        
        return $files;
    }
    
    private function isWebAccessible($file) {
        // Basic check - assumes files in root or public directories are web-accessible
        $webAccessiblePaths = ['', 'public/', 'www/', 'htdocs/'];
        
        foreach ($webAccessiblePaths as $path) {
            if (strpos($file, $path) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function loadConfig() {
        $configFile = dirname(__DIR__) . '/config/config.php';
        if (file_exists($configFile)) {
            return include $configFile;
        }
        return [];
    }
}

// Command line interface
if (php_sapi_name() === 'cli' && isset($argv)) {
    $format = $argv[1] ?? 'json';
    
    $scanner = new SecurityScanner();
    $report = $scanner->generateSecurityReport($format);
    
    if ($format === 'html') {
        $filename = 'security_report_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($filename, $report);
        echo "Security report saved to: $filename\n";
    } else {
        echo $report;
    }
}
?>
