<?php

$uri = $_SERVER['REQUEST_URI'];

if ($uri === '/') {
    echo '<h1>Hello FrankenPHP 🚀</h1>';
    echo '<p>PHP version: ' . PHP_VERSION . '</p>';
} elseif ($uri === '/hello') {
    echo '<h1>Hello depuis FrankenPHP</h1>';
} elseif ($uri === '/api') {
    header('Content-Type: application/json');

    echo json_encode([
        'server' => 'FrankenPHP',
        'php' => PHP_VERSION,
        'status' => 'OK',
    ]);
} else {
    http_response_code(404);
    echo '<h1>404</h1>';
}
