<?php
/**
 * WordWise Environment Variable Loader
 * Safely parses .env files without external dependencies.
 */

if (!function_exists('load_env')) {
    function load_env(string $filePath): void {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Strip wrapping quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("{$name}={$value}");
                }
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        return $val;
    }
}

// Auto-load .env from project root if present
load_env(dirname(__DIR__) . '/.env');
