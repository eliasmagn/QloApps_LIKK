<?php
require_once __DIR__.'/WebserviceDisabledBase.php';

class WebserviceRequestCore extends WebserviceDisabledBase
{
    public static $ws_current_classname = 'WebserviceRequest';

    public const HTTP_GET = 'GET';
    public const HTTP_POST = 'POST';
    public const HTTP_HEAD = 'HEAD';
    public const HTTP_PUT = 'PUT';
    public const HTTP_DELETE = 'DELETE';

    public static function getInstance()
    {
        static::throwDisabled();
    }

    public static function getResources(): array
    {
        return array();
    }

    public static function isActive(): bool
    {
        return false;
    }

    public function setError($statusCode, $message, $errorCode = null)
    {
        static::throwDisabled();
    }
}

class WebserviceRequest extends WebserviceRequestCore
{
}
