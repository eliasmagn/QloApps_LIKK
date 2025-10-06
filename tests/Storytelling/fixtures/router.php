<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/' || $path === '/story/residencies') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__.'/residencies.html');
    return;
}
http_response_code(404);
echo 'Not Found';
