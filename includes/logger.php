<?php
/**
 * Simple logging functionality for the plugin
 * 
 * @package ListingsAPI
 * @since 0.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple logging function
 * 
 * @param string $message The message to log
 * @param string $level Log level (info, warning, error, debug)
 * @param string $context Optional context for the log entry
 * @return bool True if log was written successfully, false otherwise
 */
function api_listings_log($message, $level = 'info', $context = '') {
    // Create logs directory if it doesn't exist
    $log_dir = BRM_API_LISTINGS_PLUGIN_PLUGIN_DIR . 'logs';
    if (!file_exists($log_dir)) {
        if (!wp_mkdir_p($log_dir)) {
            return false;
        }
        
        // Create .htaccess to protect logs directory
        $htaccess_content = "Order deny,allow\nDeny from all";
        file_put_contents($log_dir . '/.htaccess', $htaccess_content);
        
        // Create index.php to prevent directory listing
        $index_content = "<?php\n// Silence is golden.";
        file_put_contents($log_dir . '/index.php', $index_content);
    }
    
    // Create log filename with date
    $log_file = $log_dir . '/plugin-' . date('Y-m-d') . '.log';
    
    // Format the log entry
    $timestamp = current_time('Y-m-d H:i:s');
    $formatted_message = sprintf(
        "[%s] [%s] %s%s\n",
        $timestamp,
        strtoupper($level),
        $context ? "[{$context}] " : '',
        $message
    );
    
    // Write to log file
    $result = file_put_contents($log_file, $formatted_message, FILE_APPEND | LOCK_EX);
    
    return $result !== false;
}

/**
 * Convenience function for info level logging
 */
function api_listings_log_info($message, $context = '') {
    return api_listings_log($message, 'info', $context);
}

/**
 * Convenience function for warning level logging
 */
function api_listings_log_warning($message, $context = '') {
    return api_listings_log($message, 'warning', $context);
}

/**
 * Convenience function for error level logging
 */
function api_listings_log_error($message, $context = '') {
    return api_listings_log($message, 'error', $context);
}

/**
 * Convenience function for debug level logging
 */
function api_listings_log_debug($message, $context = '') {
    return api_listings_log($message, 'debug', $context);
} 