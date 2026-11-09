<?php
/**
 * LaundryPro PSR-4 Autoloader
 * Works out-of-the-box on shared hosting without requiring Composer.
 */

spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\' => dirname(__DIR__) . '/app/',
        'Database\\Seeders\\' => dirname(__DIR__) . '/database/seeders/',
        'Database\\' => dirname(__DIR__) . '/database/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Load global helpers
require_once __DIR__ . '/Helpers/functions.php';
