<?php

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }

            // Set environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/../.env');

// Helper function to get environment variables
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// Application configuration
define('APP_NAME', env('APP_NAME', 'Regnum Online Shop'));
define('APP_URL', env('APP_URL', 'http://localhost:8080'));

// Database configuration
define('DB_PATH', env('DB_PATH', __DIR__ . '/../database/shop.db'));

// COR Forum API configuration
define('COR_API_URL', env('COR_API_URL', 'https://cor-forum.de/api.php/login'));
define('COR_API_KEY', env('COR_API_KEY', ''));

// Session configuration
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200));

// Payment configuration
define('PAYPAL_EMAIL', env('PAYPAL_EMAIL', ''));
define('BANK_NAME', env('BANK_NAME', ''));
define('BANK_ACCOUNT_HOLDER', env('BANK_ACCOUNT_HOLDER', ''));
define('BANK_IBAN', env('BANK_IBAN', ''));
define('BANK_BIC', env('BANK_BIC', ''));

// Admin configuration
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
define('ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'admin123'));

// SMTP Email configuration
define('SMTP_HOST', env('SMTP_HOST', 'mail.treudler.net'));
define('SMTP_PORT', (int)env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'system@treudler.net'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'system@treudler.net'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Regnum Online Shop'));

// Timezone
date_default_timezone_set('Europe/Berlin');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
