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

class AteliersControllerCore extends FrontController
{
    public $php_self = 'ateliers';

    public function init()
    {
        parent::init();

        $breadcrumbLabel = $this->trans('Studios & ateliers', array(), 'Shop.Theme.Kunstort');
        $headline = $this->trans('Atelier storytelling', array(), 'Shop.Theme.Kunstort');
        $description = $this->trans('Explore the production studios, rehearsal rooms and ateliers ready for Kunstort Lehnin residencies, including equipment details, availability cues and inquiry guidance.', array(), 'Shop.Theme.Kunstort');

        $shopName = Configuration::get('PS_SHOP_NAME', (int) $this->context->language->id);
        $metaTitle = $headline;
        if (!empty($shopName)) {
            $metaTitle = sprintf('%s – %s', $headline, $shopName);
        }

        $canonicalLink = $this->context->link ? $this->context->link->getPageLink($this->php_self, true) : null;

        $assignments = array(
            'meta_title' => $metaTitle,
            'meta_description' => $description,
            'path' => $breadcrumbLabel,
        );

        if ($canonicalLink) {
            $assignments['canonical_link'] = $canonicalLink;
        }

        $this->context->smarty->assign($assignments);
        $this->show_breadcrump = true;
    }

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

        $payload = $presenter->presentAteliersLanding();

        $this->context->smarty->assign(array(
            'storytelling' => $payload,
        ));

        $this->setTemplate(_PS_THEME_DIR_.'storytelling/ateliers.tpl');
    }
}
