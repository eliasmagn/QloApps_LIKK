{*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if $marketplace_disabled}
        <div class="alert alert-info">
                <h4>{l s='Marketplace disabled'}</h4>
                <p>{l s='Remote marketplace content has been disabled for this distribution. Manage modules from source directly within your installation.'}</p>
        </div>
{elseif $display_addons_content}
        {$addons_content}
{else}
        <div class="alert alert-warning">
                <h4>{l s='Marketplace unavailable'}</h4>
                <p>{l s='The PrestaShop Addons service could not be reached. Try again later or install modules manually from source.'}</p>
        </div>
{/if}
