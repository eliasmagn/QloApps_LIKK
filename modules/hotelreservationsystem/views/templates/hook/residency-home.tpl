{*
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
 *}

{if isset($residency_showcase.sections)}
<section id="residency-showcase" class="residency-showcase">
    <div class="container">
        {foreach from=$residency_showcase.sections item=section}
            <section id="{$section.anchor|escape:'html':'UTF-8'}" class="residency-section">
                <header class="residency-section-header">
                    <h2 class="residency-section-title">{$section.title|escape:'html':'UTF-8'}</h2>
                    {if $section.intro}
                        <p class="residency-section-intro">{$section.intro|escape:'html':'UTF-8'}</p>
                    {/if}
                </header>
                {if isset($section.profiles) && $section.profiles}
                    <div class="residency-card-grid">
                        {foreach from=$section.profiles item=profile}
                            <article class="residency-card" data-resource-code="{$profile.resource_code|escape:'html':'UTF-8'}">
                                <header class="residency-card-header">
                                    <h3 class="residency-card-title">{$profile.display_name|escape:'html':'UTF-8'}</h3>
                                    {if $profile.room_type_name}
                                        <p class="residency-card-subtitle">{$profile.room_type_name|escape:'html':'UTF-8'}</p>
                                    {/if}
                                </header>
                                {if $profile.excerpt}
                                    <p class="residency-card-excerpt">{$profile.excerpt|escape:'html':'UTF-8'}</p>
                                {/if}
                                {if $profile.capacity_summary}
                                    <ul class="residency-card-meta">
                                        {foreach from=$profile.capacity_summary item=item}
                                            <li>{$item|escape:'html':'UTF-8'}</li>
                                        {/foreach}
                                    </ul>
                                {/if}
                                <footer class="residency-card-footer">
                                    <span class="residency-card-code">
                                        <strong>{l s='Resource code:' mod='hotelreservationsystem'}</strong>
                                        {$profile.resource_code|escape:'html':'UTF-8'}
                                    </span>
                                    {if $profile.timezone}
                                        <span class="residency-card-timezone">
                                            <strong>{l s='Timezone:' mod='hotelreservationsystem'}</strong>
                                            {$profile.timezone|escape:'html':'UTF-8'}
                                        </span>
                                    {/if}
                                </footer>
                            </article>
                        {/foreach}
                    </div>
                {else}
                    <p class="text-muted residency-section-empty">{l s='No resources published yet.' mod='hotelreservationsystem'}</p>
                {/if}
            </section>
        {/foreach}
    </div>
</section>
{/if}
