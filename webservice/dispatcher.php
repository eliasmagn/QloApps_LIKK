<?php
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
header('X-Legacy-Webservice', 'disabled');
echo "The legacy PrestaShop webservice is disabled in this QloApps distribution.";
exit;
