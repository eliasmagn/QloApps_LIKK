<?php
require_once __DIR__.'/WebserviceDisabledBase.php';
require_once __DIR__.'/WebserviceOutputInterface.php';

class WebserviceOutputBuilderCore extends WebserviceDisabledBase
{
    protected $objOutput;

    public function setObjectRender($obj)
    {
        static::throwDisabled();
    }

    public function getObjectRender()
    {
        return null;
    }

    public function setWsUrl()
    {
        static::throwDisabled();
    }

    public function execute()
    {
        static::throwDisabled();
    }
}

class WebserviceOutputBuilder extends WebserviceOutputBuilderCore
{
}
