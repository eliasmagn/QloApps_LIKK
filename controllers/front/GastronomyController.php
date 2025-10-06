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
 */

class GastronomyControllerCore extends FrontController
{
    public $php_self = 'gastronomy';

    public function setMedia()
    {
        parent::setMedia();

        $this->addCSS(_THEME_CSS_DIR_.'storytelling.css');
        $this->addJS(_THEME_JS_DIR_.'storytelling-defer.js');
        $this->addJS(_THEME_JS_DIR_.'storytelling-content.js');
    }

    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;

        parent::initContent();

        if (!Module::isInstalled('hotelreservationsystem') || !Module::isEnabled('hotelreservationsystem')) {
            Tools::redirect($this->context->link->getPageLink('index'));
        }

        include_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';

        $presenter = new HotelReservationSystemStorytellingPresenter($this->context);
        if (!$presenter->isEnabled()) {
            Tools::redirect($this->context->link->getPageLink('our-properties'));
        }

        $payload = $presenter->presentGastronomyLanding();

        $this->context->smarty->assign(array(
            'storytelling' => $payload,
        ));

        $this->setTemplate(_PS_THEME_DIR_.'storytelling/gastronomy.tpl');
    }
}
