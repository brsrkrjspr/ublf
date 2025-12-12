<?php
/**
 * Custom Logger Class
 * 
 * Writes logs to a file that can be accessed via web browser
 * Log file location: htdocs/debug.log
 */
class Logger {
    private static $logFile = null;
    
    /**
     * Initialize logger with custom log file path
     */
    public static function init($logFile = null) {
        if ($logFile === null) {
            // Default log file location (in htdocs root)
            self::$logFile = __DIR__ . '/../debug.log';
        } else {
            self::$logFile = $logFile;
        }
    }
    
    /**
     * Write a log message
     * 
     * @param string $message The log message
     * @param array $context Additional context data
     */
    public static function log($message, $context = []) {
        if (self::$logFile === null) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[$timestamp] $message$contextStr\n";
        
        // Append to log file
        @file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Also write to PHP error log (if available)
        @error_log($message);
    }
    
    /**
     * Get the log file path
     */
    public static function getLogFile() {
        if (self::$logFile === null) {
            self::init();
        }
        return self::$logFile;
    }
    
    /**
     * Clear the log file
     */
    public static function clear() {
        if (self::$logFile === null) {
            self::init();
        }
        if (file_exists(self::$logFile)) {
            @file_put_contents(self::$logFile, '');
        }
    }
    
    /**
     * Get last N lines from log file
     */
    public static function getLastLines($lines = 100) {
        if (self::$logFile === null) {
            self::init();
        }
        
        if (!file_exists(self::$logFile)) {
            return "Log file does not exist yet.";
        }
        
        $content = file_get_contents(self::$logFile);
        $logLines = explode("\n", $content);
        $logLines = array_filter($logLines); // Remove empty lines
        $logLines = array_slice($logLines, -$lines); // Get last N lines
        return implode("\n", $logLines);
    }
}
