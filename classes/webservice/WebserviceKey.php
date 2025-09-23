<?php
require_once __DIR__.'/WebserviceDisabledBase.php';

class WebserviceKeyCore extends WebserviceDisabledBase
{
    public static function getPermissionForAccount($key)
    {
        return array();
    }

    public static function setPermissionForAccount($idAccount, $resources)
    {
        static::throwDisabled();
    }

    public static function getClassFromKey($key)
    {
        return 'WebserviceRequest';
    }

    public static function isKeyActive($key)
    {
        return false;
    }

    public static function keyExists($key)
    {
        return false;
    }
}

class WebserviceKey extends WebserviceKeyCore
{
}
