<div class="col-xs-6 col-sm-5 col-md-4 pull-right padding-top-5">
    {assign var='storytellingLaunch' value=(isset($smarty.const._KUNSTORT_STORYTELLING_LAUNCH_) && $smarty.const._KUNSTORT_STORYTELLING_LAUNCH_)}
    <div class="residency-top-actions">
        <ul class="list-unstyled residency-top-links">
            <li>
                {if $storytellingLaunch}
                    <a class="residency-top-link" href="{$link->getPageLink('residencies', true)|escape:'html':'UTF-8'}">{l s='Residences' mod='hotelreservationsystem'}</a>
                {else}
                    <a class="residency-top-link" href="{$base_dir|escape:'html':'UTF-8'}#residences-overview">{l s='Residences' mod='hotelreservationsystem'}</a>
                {/if}
            </li>
            <li>
                {if $storytellingLaunch}
                    <a class="residency-top-link" href="{$link->getPageLink('ateliers', true)|escape:'html':'UTF-8'}">{l s='Studios & ateliers' mod='hotelreservationsystem'}</a>
                {else}
                    <a class="residency-top-link" href="{$base_dir|escape:'html':'UTF-8'}#resident-ateliers">{l s='Studios & ateliers' mod='hotelreservationsystem'}</a>
                {/if}
            </li>
            <li>
                {if $storytellingLaunch}
                    <a class="residency-top-link" href="{$link->getPageLink('gastronomy', true)|escape:'html':'UTF-8'}">{l s='Dining & gastronomy' mod='hotelreservationsystem'}</a>
                {else}
                    <a class="residency-top-link" href="{$base_dir|escape:'html':'UTF-8'}#dining">{l s='Dining & gastronomy' mod='hotelreservationsystem'}</a>
                {/if}
            </li>
            <li>
                {if $storytellingLaunch}
                    <a class="residency-top-link" href="{$link->getPageLink('programme', true)|escape:'html':'UTF-8'}">{l s='Programme spaces' mod='hotelreservationsystem'}</a>
                {else}
                    <a class="residency-top-link" href="{$base_dir|escape:'html':'UTF-8'}#programme-spaces">{l s='Programme spaces' mod='hotelreservationsystem'}</a>
                {/if}
            </li>
        </ul>
    </div>
</div>
