<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
*/

require dirname(__FILE__).'/vendor/autoload.php';

require_once dirname(__DIR__).'/config/defines.inc.php';
if (file_exists(dirname(__DIR__).'/config/defines_custom.inc.php')) {
    require_once dirname(__DIR__).'/config/defines_custom.inc.php';
}
require_once dirname(__DIR__).'/config/autoload.php';

if (!function_exists('each')) {
    /**
     * Polyfill for the deprecated PHP <7.2 each() helper used by PHPUnit 4.
     *
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>|false
     */
    function each(array &$array)
    {
        $key = key($array);
        if ($key === null) {
            return false;
        }

        $value = current($array);
        next($array);

        return array(
            1 => $value,
            'value' => $value,
            0 => $key,
            'key' => $key,
        );
    }
}
