<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class IndexControllerCore extends FrontController
{
    public $php_self = 'index';

    /**
     * @var bool
     */
    protected $useStorytellingLanding = false;

    /**
     * @var array<string, mixed>
     */
    protected $storytellingPayload = array();

    public function init()
    {
        parent::init();

        $this->useStorytellingLanding = false;
        $this->storytellingPayload = array();

        if (!Module::isInstalled('hotelreservationsystem') || !Module::isEnabled('hotelreservationsystem')) {
            return;
        }

        include_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';

        $presenter = new HotelReservationSystemStorytellingPresenter($this->context);
        if (!$presenter->isEnabled()) {
            return;
        }

        $payload = $presenter->presentHomeLanding();
        if (!is_array($payload) || !$payload) {
            return;
        }

        $this->storytellingPayload = $payload;
        $this->useStorytellingLanding = true;
    }

    public function setMedia()
    {
        parent::setMedia();

        if ($this->useStorytellingLanding) {
            $this->addCSS(_THEME_CSS_DIR_.'storytelling.css');
            $this->addJS(_THEME_JS_DIR_.'storytelling-defer.js');
            $this->addJS(_THEME_JS_DIR_.'storytelling-content.js');
            $this->addJS(_THEME_JS_DIR_.'index-storytelling.js');
        } else {
            $this->addJS(_THEME_JS_DIR_.'index.js');
        }
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        if ($this->useStorytellingLanding) {
            $this->display_column_left = false;
            $this->display_column_right = false;
        }

        parent::initContent();

        if ($this->useStorytellingLanding) {
            $this->context->smarty->assign(array(
                'storytelling' => $this->storytellingPayload,
                'use_storytelling_landing' => true,
            ));
        } else {
            $this->context->smarty->assign(array(
                'HOOK_HOME' => Hook::exec('displayHome'),
                'HOOK_HOME_TAB' => Hook::exec('displayHomeTab'),
                'HOOK_HOME_TAB_CONTENT' => Hook::exec('displayHomeTabContent'),
                'use_storytelling_landing' => false,
            ));
        }

        $this->setTemplate(_PS_THEME_DIR_.'index.tpl');
    }
}
