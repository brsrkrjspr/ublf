<?php
/**
 * Configuration Management Class
 * 
 * Handles loading configuration from environment variables, .env file, or fallback values.
 * Provides centralized configuration management for the application.
 */
class Config {
    private static $config = [];
    private static $loaded = false;
    
    /**
     * Load configuration from .env file and environment variables
     */
    private static function load() {
        // #region agent log
        $logFile = __DIR__ . '/../debug.log';
        $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Config.php:15', 'message' => 'Config load started', 'data' => ['already_loaded' => self::$loaded], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B'];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        if (self::$loaded) {
            return;
        }
        
        // Load .env file if it exists (in htdocs root or parent directory)
        $envFiles = [
            __DIR__ . '/../.env',
            __DIR__ . '/../../.env',
            __DIR__ . '/.env'
        ];
        
        $envFileFound = false;
        foreach ($envFiles as $envFile) {
            if (file_exists($envFile)) {
                // #region agent log
                $logFile = __DIR__ . '/../debug.log';
                $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Config.php:29', 'message' => 'Config .env file found', 'data' => ['env_file' => $envFile], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B'];
                @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
                // #endregion
                self::loadEnvFile($envFile);
                $envFileFound = true;
                break;
            }
        }
        // #region agent log
        if (!$envFileFound) {
            $logFile = __DIR__ . '/../debug.log';
            $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Config.php:35', 'message' => 'Config no .env file found', 'data' => ['using_defaults' => true], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B'];
            @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        }
        // #endregion
        
        // Set default configuration values
        $n8nWebhookUrl = getenv('N8N_WEBHOOK_URL') ?: 'https://besmar.app.n8n.cloud/webhook/chatbot';
        $apiKey = getenv('API_KEY') ?: 'ublf-x10mx-2024-secure-api-key-7a9b3c2d1e4f6g8h';
        self::$config = [
            'N8N_WEBHOOK_URL' => $n8nWebhookUrl,
            'N8N_APPROVAL_WEBHOOK_URL' => getenv('N8N_APPROVAL_WEBHOOK_URL') ?: 'https://besmar.app.n8n.cloud/webhook/approval-action',
            'N8N_CREATE_LOST_REPORT_WEBHOOK_URL' => getenv('N8N_CREATE_LOST_REPORT_WEBHOOK_URL') ?: 'https://besmar.app.n8n.cloud/webhook/create-lost-report',
            'N8N_MATCH_DETECTION_FOUND_WEBHOOK_URL' => getenv('N8N_MATCH_DETECTION_FOUND_WEBHOOK_URL') ?: 'https://besmar.app.n8n.cloud/webhook/found-item-approved',
            'N8N_MATCH_DETECTION_LOST_WEBHOOK_URL' => getenv('N8N_MATCH_DETECTION_LOST_WEBHOOK_URL') ?: 'https://besmar.app.n8n.cloud/webhook/lost-item-approved',
            'N8N_API_KEY' => getenv('N8N_API_KEY') ?: '',
            'API_KEY' => $apiKey,
            'ENVIRONMENT' => getenv('ENVIRONMENT') ?: 'development',
            'DEBUG' => getenv('DEBUG') ?: 'false',
        ];
        // #region agent log
        $logFile = __DIR__ . '/../debug.log';
        $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Config.php:47', 'message' => 'Config values loaded', 'data' => ['n8n_webhook_url' => $n8nWebhookUrl, 'api_key_set' => !empty($apiKey), 'config_keys' => array_keys(self::$config)], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B'];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        
        self::$loaded = true;
    }
    
    /**
     * Load environment variables from .env file
     */
    private static function loadEnvFile($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Only set if not already in environment
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null) {
        self::load();
        
        // First check environment variable (highest priority)
        $envValue = getenv($key);
        if ($envValue !== false) {
            return $envValue;
        }
        
        // Then check loaded config
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        
        // Return default if provided
        return $default;
    }
    
    /**
     * Set configuration value (for testing or runtime changes)
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public static function set($key, $value) {
        self::load();
        self::$config[$key] = $value;
    }
    
    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if key exists
     */
    public static function has($key) {
        self::load();
        return isset(self::$config[$key]) || getenv($key) !== false;
    }
    
    /**
     * Get all configuration values
     * 
     * @return array All configuration values
     */
    public static function all() {
        self::load();
        return self::$config;
    }
    
    /**
     * Get environment mode
     * 
     * @return string 'development' or 'production'
     */
    public static function environment() {
        return self::get('ENVIRONMENT', 'development');
    }
    
    /**
     * Check if in development mode
     * 
     * @return bool True if development mode
     */
    public static function isDevelopment() {
        return self::environment() === 'development';
    }
    
    /**
     * Check if in production mode
     * 
     * @return bool True if production mode
     */
    public static function isProduction() {
        return self::environment() === 'production';
    }
    
    /**
     * Check if debug mode is enabled
     * 
     * @return bool True if debug enabled
     */
    public static function isDebug() {
        return self::get('DEBUG', 'false') === 'true' || self::isDevelopment();
    }
}

