<?php
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if (is_file($file)) {
        return false; // CSS/JS/Bilder direkt ausliefern
    }
}
require __DIR__ . '/index.php';
