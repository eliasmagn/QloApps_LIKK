<?php
require_once __DIR__.'/WebserviceDisabledBase.php';
require_once __DIR__.'/WebserviceOutputInterface.php';

class WebserviceOutputJSONCore extends WebserviceDisabledBase implements WebserviceOutputInterface
{
}

class WebserviceOutputJSON extends WebserviceOutputJSONCore
{
}
