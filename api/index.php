<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize root request
if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$target = __DIR__ . '/..' . $uri;

// Check if exact file exists
if (file_exists($target) && is_file($target)) {
    require $target;
    exit;
}

// Check if file with .php exists
if (file_exists($target . '.php') && is_file($target . '.php')) {
    require $target . '.php';
    exit;
}

// Fallback to index
require __DIR__ . '/../index.php';
