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
 *}

{block name='storytelling_programme'}
  {include file="$tpl_dir./_partials/storytelling-critical.tpl"}
  <div class="kl-storytelling kl-storytelling--programme">
    <section class="kl-storytelling__hero">
      <div class="kl-storytelling__hero-copy">
        {if isset($storytelling.cms.hero) && $storytelling.cms.hero}
          {$storytelling.cms.hero.content nofilter}
        {else}
          <h1 class="h1">{l s='Programme & gathering spaces' d='Shop.Theme.Kunstort'}</h1>
          <p class="lead text-muted">
            {l s='Our halls, rehearsal rooms and gathering spaces are being staged with long-form storytelling. Until then, explore the highlights, availability cues and inquiry pathways curated below.' d='Shop.Theme.Kunstort'}
          </p>
        {/if}
      </div>
      <div class="kl-storytelling__hero-cta">
        <a class="btn btn-primary" href="{$storytelling.inquiry_url|escape:'html':'UTF-8'}">
          {l s='Plan a programme inquiry' d='Shop.Theme.Kunstort'}
        </a>
      </div>
    </section>

    <section class="kl-storytelling__highlights">
      <header class="kl-storytelling__section-header">
        <h2 class="h2">{l s='Programme highlights' d='Shop.Theme.Kunstort'}</h2>
      </header>
      <div class="kl-storytelling__highlights-body">
        {if isset($storytelling.cms.highlights) && $storytelling.cms.highlights}
          {$storytelling.cms.highlights.content nofilter}
        {else}
          <p class="text-muted">{l s='Editorial highlights for programme spaces will appear here once CMS copy is published.' d='Shop.Theme.Kunstort'}</p>
        {/if}
      </div>
    </section>

    <section class="kl-storytelling__availability">
      <header class="kl-storytelling__section-header">
        <h2 class="h2">{l s='Availability snapshot' d='Shop.Theme.Kunstort'}</h2>
      </header>
      <div class="kl-storytelling__availability-body">
        {if isset($storytelling.cms.availability) && $storytelling.cms.availability}
          <div class="kl-storytelling__availability-cms">
            {$storytelling.cms.availability.content nofilter}
          </div>
        {/if}
        {if isset($storytelling.availability.message)}
          <p class="text-muted">{$storytelling.availability.message|escape:'html':'UTF-8'}</p>
        {/if}
        {if isset($storytelling.availability.groups) && $storytelling.availability.groups}
          <div class="kl-storytelling__availability-groups">
            {foreach from=$storytelling.availability.groups item=group}
              <article class="kl-storytelling__availability-group">
                <header>
                  <h3 class="h3">{$group.label|escape:'html':'UTF-8'}</h3>
                  {if $group.intro}
                    <p class="text-muted">{$group.intro|escape:'html':'UTF-8'}</p>
                  {/if}
                </header>
                {if $group.slot}
                  <div class="kl-storytelling__availability-slot">
                    <div class="kl-storytelling__availability-slot-meta">
                      <strong>{l s='Next opening:' d='Shop.Theme.Kunstort'}</strong>
                      <span class="text-muted">{$group.slot.window|escape:'html':'UTF-8'}</span>
                    </div>
                    {if isset($group.slot.inquiry_url) && $group.slot.inquiry_url}
                      <a class="btn btn-primary btn-sm" href="{$group.slot.inquiry_url|escape:'html':'UTF-8'}">{l s='Plan this stay' d='Shop.Theme.Kunstort'}</a>
                    {/if}
                  </div>
                {else}
                  <p class="text-muted">{l s='We are still mapping live availability for this space type.' d='Shop.Theme.Kunstort'}</p>
                {/if}
              </article>
            {/foreach}
          </div>
        {elseif isset($storytelling.availability.slots) && $storytelling.availability.slots}
          <ul class="list-unstyled kl-storytelling__availability-slots">
            {foreach from=$storytelling.availability.slots item=slot}
              <li class="kl-storytelling__availability-slot">
                <div class="kl-storytelling__availability-slot-meta">
                  <strong>{$slot.label|escape:'html':'UTF-8'}</strong>
                  <span class="text-muted">{$slot.window|escape:'html':'UTF-8'}</span>
                </div>
                {if isset($slot.inquiry_url) && $slot.inquiry_url}
                  <a class="btn btn-primary btn-sm" href="{$slot.inquiry_url|escape:'html':'UTF-8'}">{l s='Plan this stay' d='Shop.Theme.Kunstort'}</a>
                {/if}
              </li>
            {/foreach}
          </ul>
        {/if}
      </div>
    </section>

    <section class="kl-storytelling__schedule">
      <header class="kl-storytelling__section-header">
        <h2 class="h2">{l s='Schedule cues' d='Shop.Theme.Kunstort'}</h2>
      </header>
      <div class="kl-storytelling__schedule-body">
        {if isset($storytelling.cms.schedule) && $storytelling.cms.schedule}
          {$storytelling.cms.schedule.content nofilter}
        {elseif isset($storytelling.availability.groups) && $storytelling.availability.groups}
          <p class="text-muted">{l s='Track the next booking windows for each programme cluster above and contact us to reserve dedicated time.' d='Shop.Theme.Kunstort'}</p>
        {else}
          <p class="text-muted">{l s='Detailed scheduling guidance will be published alongside programme announcements.' d='Shop.Theme.Kunstort'}</p>
        {/if}
      </div>
    </section>

    <section class="kl-storytelling__sections">
      {if $storytelling.sections}
        {foreach from=$storytelling.sections item=section}
          <article id="{$section.anchor|escape:'html':'UTF-8'}" class="kl-storytelling__section">
            <header>
              <h2 class="h2">{$section.title|escape:'html':'UTF-8'}</h2>
              {if $section.intro}
                <p class="text-muted">{$section.intro|escape:'html':'UTF-8'}</p>
              {/if}
            </header>
            {if $section.profiles}
              <div class="kl-storytelling__section-profiles">
                {foreach from=$section.profiles item=profile}
                  <div class="kl-storytelling__profile">
                    {include file="$tpl_dir./_partials/storytelling-profile-media.tpl" profile=$profile}
                    <h3 class="h3">{$profile.display_name|escape:'html':'UTF-8'}</h3>
                    {if $profile.excerpt}
                      <p class="kl-storytelling__profile-excerpt text-muted">{$profile.excerpt|escape:'html':'UTF-8'}</p>
                    {/if}
                    {if $profile.capacity_summary}
                      <ul class="list-unstyled kl-storytelling__profile-capacity">
                        {foreach from=$profile.capacity_summary item=item}
                          <li>{$item|escape:'html':'UTF-8'}</li>
                        {/foreach}
                      </ul>
                    {/if}
                    {if $profile.amenities}
                      <ul class="list-unstyled kl-storytelling__profile-amenities text-muted">
                        {foreach from=$profile.amenities item=amenity}
                          <li>{$amenity|escape:'html':'UTF-8'}</li>
                        {/foreach}
                      </ul>
                    {/if}
                  </div>
                {/foreach}
              </div>
            {else}
              <p class="text-muted">{l s='Profiles will appear here once programme storytelling entries are published.' d='Shop.Theme.Kunstort'}</p>
            {/if}
          </article>
        {/foreach}
      {else}
        <p class="text-muted">{l s='Programme profiles are still being prepared for the storytelling rollout.' d='Shop.Theme.Kunstort'}</p>
      {/if}
    </section>

    {if isset($storytelling.packages) && $storytelling.packages}
      <section class="kl-storytelling__packages">
        <header class="kl-storytelling__section-header">
          <h2 class="h2">{l s='Featured programme bundles' d='Shop.Theme.Kunstort'}</h2>
          <p class="text-muted">{l s='These packages highlight campus-wide programming once editors flag them for promotion.' d='Shop.Theme.Kunstort'}</p>
        </header>
        <div class="kl-storytelling__package-list">
          {foreach from=$storytelling.packages item=package}
            <article class="kl-storytelling__package">
              <h3 class="h3">{$package.name|escape:'html':'UTF-8'}</h3>
              {if $package.tagline}
                <p class="text-muted">{$package.tagline|escape:'html':'UTF-8'}</p>
              {/if}
              {if $package.description}
                <div class="kl-storytelling__package-description text-muted">
                  {$package.description nofilter}
                </div>
              {/if}
            </article>
          {/foreach}
        </div>
      </section>
    {/if}

    <section class="kl-storytelling__inquiry">
      <header class="kl-storytelling__section-header">
        <h2 class="h2">{l s='Inquiry pathways' d='Shop.Theme.Kunstort'}</h2>
      </header>
      <div class="kl-storytelling__inquiry-body">
        {if isset($storytelling.cms.inquiry) && $storytelling.cms.inquiry}
          {$storytelling.cms.inquiry.content nofilter}
        {else}
          <p class="text-muted">{l s='Share your programme needs and we will shape a schedule across spaces, hospitality and catering.' d='Shop.Theme.Kunstort'}</p>
          <a class="btn btn-primary" href="{$storytelling.inquiry_url|escape:'html':'UTF-8'}">{l s='Start an inquiry' d='Shop.Theme.Kunstort'}</a>
        {/if}
      </div>
    </section>

    {if isset($storytelling.cms.faq) && $storytelling.cms.faq}
      <section class="kl-storytelling__faq">
        <header class="kl-storytelling__section-header">
          <h2 class="h2">{l s='Frequently asked questions' d='Shop.Theme.Kunstort'}</h2>
        </header>
        <div class="panel-group" id="kl-storytelling-programme-faq" role="tablist">
          {$storytelling.cms.faq.content nofilter}
        </div>
      </section>
    {/if}

    {block name='storytelling_deferred_scripts'}
      {capture name='klStorytellingScriptsProgramme'}{hook h='displayStorytellingScripts'}{/capture}
      {if trim($smarty.capture.klStorytellingScriptsProgramme)}
        <div class="kl-storytelling__defer" aria-hidden="true">
          {$smarty.capture.klStorytellingScriptsProgramme nofilter}
        </div>
      {/if}
    {/block}
  </div>
{/block}
