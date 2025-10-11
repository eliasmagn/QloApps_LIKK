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
 *}

{block name='our_properties'}
  {capture name=path}{l s='Residencies & spaces' d='Shop.Theme.Kunstort'}{/capture}
  <div class="kl-our-properties">
    <header class="kl-our-properties__hero">
      <h1 class="h1">{l s='Residencies & spaces' d='Shop.Theme.Kunstort'}</h1>
      {if isset($our_properties.intro) && $our_properties.intro}
        <p class="lead text-muted">{$our_properties.intro|escape:'html':'UTF-8'}</p>
      {else}
        <p class="lead text-muted">
          {l s='Explore the rooms, ateliers and kitchens that anchor Kunstort Lehnin. Each profile below is curated from our taxonomy-driven storytelling system so you can compare spaces at a glance.' d='Shop.Theme.Kunstort'}
        </p>
      {/if}
    </header>

    {block name='displayPropertiesListBefore'}
      {hook h='displayPropertiesListBefore'}
    {/block}

    {if isset($our_properties.module_active) && !$our_properties.module_active}
      <div class="kl-our-properties__empty text-center">
        <p class="h3">{l s='Properties are unavailable.' d='Shop.Theme.Kunstort'}</p>
        <p class="text-muted">{l s='Activate the Hotel Reservation System module to surface residency, atelier and gastronomy profiles.' d='Shop.Theme.Kunstort'}</p>
      </div>
    {else}
      {if isset($our_properties.sections) && $our_properties.sections}
        <div class="kl-our-properties__sections">
          {foreach from=$our_properties.sections item=section}
            <section id="campus-{$section.key|escape:'html':'UTF-8'}" class="kl-our-properties__section">
              <header class="kl-our-properties__section-header">
                <h2 class="h2">{$section.title|escape:'html':'UTF-8'}</h2>
                {if isset($section.intro) && $section.intro}
                  <p class="text-muted">{$section.intro|escape:'html':'UTF-8'}</p>
                {/if}
              </header>

              {if (isset($section.availability.message) && $section.availability.message) || (isset($section.availability.slots) && $section.availability.slots)}
                <div class="kl-our-properties__availability">
                  {if isset($section.availability.message) && $section.availability.message}
                    <p class="text-muted kl-our-properties__availability-message">{$section.availability.message|escape:'html':'UTF-8'}</p>
                  {/if}
                  {if isset($section.availability.slots) && $section.availability.slots}
                    <ul class="list-unstyled kl-our-properties__availability-list">
                      {foreach from=$section.availability.slots item=slot}
                        <li class="kl-our-properties__availability-slot">
                          <div class="kl-our-properties__availability-slot-meta">
                            {if isset($slot.label) && $slot.label}
                              <strong>{$slot.label|escape:'html':'UTF-8'}</strong>
                            {/if}
                            {if isset($slot.window) && $slot.window}
                              <span class="text-muted">{$slot.window|escape:'html':'UTF-8'}</span>
                            {/if}
                          </div>
                          {if isset($slot.inquiry_url) && $slot.inquiry_url}
                            <a class="btn btn-link" href="{$slot.inquiry_url|escape:'html':'UTF-8'}">
                              {l s='Plan this stay' d='Shop.Theme.Kunstort'}
                            </a>
                          {/if}
                        </li>
                      {/foreach}
                    </ul>
                  {/if}
                </div>
              {/if}

              {if isset($section.profiles) && $section.profiles}
                <div class="kl-our-properties__profiles">
                  {foreach from=$section.profiles item=profile}
                    <article class="kl-our-properties__profile">
                      {include file="$tpl_dir./_partials/storytelling-profile-media.tpl" profile=$profile}
                      <h3 class="h3">{$profile.display_name|escape:'html':'UTF-8'}</h3>
                      {if isset($profile.excerpt) && $profile.excerpt}
                        <p class="text-muted kl-our-properties__profile-excerpt">{$profile.excerpt|escape:'html':'UTF-8'}</p>
                      {/if}
                      {if isset($profile.capacity_summary) && $profile.capacity_summary}
                        <ul class="list-unstyled kl-our-properties__profile-capacity">
                          {foreach from=$profile.capacity_summary item=item}
                            <li>{$item|escape:'html':'UTF-8'}</li>
                          {/foreach}
                        </ul>
                      {/if}
                      {if isset($profile.amenities) && $profile.amenities}
                        <ul class="list-unstyled kl-our-properties__profile-amenities">
                          {foreach from=$profile.amenities item=amenity name=amenityLoop}
                            {if $smarty.foreach.amenityLoop.index < 3}
                              <li>{$amenity|escape:'html':'UTF-8'}</li>
                            {/if}
                          {/foreach}
                        </ul>
                      {/if}
                    </article>
                  {/foreach}
                </div>
              {else}
                <p class="text-muted">{l s='Profiles will appear here once this category is published.' d='Shop.Theme.Kunstort'}</p>
              {/if}

              <div class="kl-our-properties__actions">
                {if isset($section.landing_url) && $section.landing_url}
                  <a class="btn btn-default" href="{$section.landing_url|escape:'html':'UTF-8'}">
                    {l s='Explore the full story' d='Shop.Theme.Kunstort'}
                  </a>
                {/if}
                {if isset($section.inquiry_url) && $section.inquiry_url}
                  <a class="btn btn-primary" href="{$section.inquiry_url|escape:'html':'UTF-8'}">
                    {l s='Start an inquiry' d='Shop.Theme.Kunstort'}
                  </a>
                {/if}
              </div>

              {if isset($section.additional_profiles) && $section.additional_profiles > 0}
                <p class="kl-our-properties__additional text-muted">
                  {l s='And %d more spaces are ready to explore.' sprintf=[$section.additional_profiles] d='Shop.Theme.Kunstort'}
                </p>
              {/if}
            </section>
          {/foreach}
        </div>
      {else}
        <div class="kl-our-properties__empty text-center">
          <p class="h3">{l s='Campus profiles are being prepared.' d='Shop.Theme.Kunstort'}</p>
          <p class="text-muted">{l s='Check back soon or contact us for tailored guidance.' d='Shop.Theme.Kunstort'}</p>
        </div>
      {/if}
    {/if}

    {block name='displayPropertiesListAfter'}
      {hook h='displayPropertiesListAfter'}
    {/block}
  </div>
{/block}
