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
        if (self::$loaded) {
            return;
        }
        
        // Load .env file if it exists (in htdocs root or parent directory)
        $envFiles = [
            __DIR__ . '/../.env',
            __DIR__ . '/../../.env',
            __DIR__ . '/.env'
        ];
        
        foreach ($envFiles as $envFile) {
            if (file_exists($envFile)) {
                self::loadEnvFile($envFile);
                break;
            }
        }
        
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

