<?php
require_once __DIR__.'/WebserviceDisabledBase.php';
require_once __DIR__.'/WebserviceOutputInterface.php';

class WebserviceOutputXMLCore extends WebserviceDisabledBase implements WebserviceOutputInterface
{
}

class WebserviceOutputXML extends WebserviceOutputXMLCore
{
}
