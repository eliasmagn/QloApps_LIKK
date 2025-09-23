<?php
/**
 * Legacy PrestaShop webservice support has been removed from this distribution.
 * These stub classes remain to avoid autoload failures when obsolete hooks or
 * modules still reference the legacy API entry points.
 */
abstract class WebserviceDisabledBase
{
    /**
     * @throws LogicException
     */
    protected static function throwDisabled(): void
    {
        throw new LogicException('The legacy webservice has been removed from this QloApps distribution.');
    }

    public function __call(string $name, array $arguments)
    {
        static::throwDisabled();
    }

    public static function __callStatic(string $name, array $arguments)
    {
        static::throwDisabled();
    }
}
