<?php
$projectRoot = rtrim(realpath(__DIR__ . '/..'), DIRECTORY_SEPARATOR);
$rootPrefix = $projectRoot . DIRECTORY_SEPARATOR;
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$filePath = realpath($projectRoot . $parsedPath);

if ($filePath !== false && strncmp($filePath, $rootPrefix, strlen($rootPrefix)) === 0 && is_file($filePath)) {
    return false; // Serve the requested resource as-is.
}

// Route admin traffic if the directory hasn't been renamed yet.
if (preg_match('#^/admin(/.*)?$#', $parsedPath) && is_file($projectRoot . '/admin/index.php')) {
    require $projectRoot . '/admin/index.php';
    return true;
}

require $projectRoot . '/index.php';
return true;
