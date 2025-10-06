<?php
$query = array();
parse_str((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $query);
$module = isset($query['module']) ? $query['module'] : '';

if ($module === 'kloperations') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/panel-enabled.html');
    return;
}

http_response_code(404);
echo 'Not Found';
