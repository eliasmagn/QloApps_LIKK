<?php
$projectRoot = realpath(__DIR__ . '/..');
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$filePath = realpath($projectRoot . $parsedPath);

if ($filePath !== false && str_starts_with($filePath, $projectRoot) && is_file($filePath)) {
    return false; // Serve the requested resource as-is.
}

// Route admin traffic if the directory hasn't been renamed yet.
if (preg_match('#^/admin(/.*)?$#', $parsedPath) && is_file($projectRoot . '/admin/index.php')) {
    require $projectRoot . '/admin/index.php';
    return true;
}

require $projectRoot . '/index.php';
return true;
