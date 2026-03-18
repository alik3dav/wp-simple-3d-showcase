<?php

namespace WP3DS;

defined('ABSPATH') || exit;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload(string $class): void
    {
        if (strpos($class, 'WP3DS\\') !== 0) {
            return;
        }

        $relative = str_replace('WP3DS\\', '', $class);
        $relative = str_replace('\\', '/', $relative);
        $file = WP3DS_PATH . 'includes/' . $relative . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}