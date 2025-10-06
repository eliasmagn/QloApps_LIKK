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

{block name='storytelling_residencies'}
  {include file="$tpl_dir./_partials/storytelling-critical.tpl"}
  <div class="kl-storytelling kl-storytelling--residencies">
    <section class="kl-storytelling__hero">
      <div class="kl-storytelling__hero-copy">
        {if isset($storytelling.cms.hero) && $storytelling.cms.hero}
          {$storytelling.cms.hero.content nofilter}
        {else}
          <h1 class="h1">{l s='Residencies at Kunstort Lehnin' d='Shop.Theme.Kunstort'}</h1>
          <p class="lead text-muted">
            {l s='We are preparing a narrative overview of the residency houses, studios and shared spaces. The section below lists published profiles while we stage full storytelling copy.' d='Shop.Theme.Kunstort'}
          </p>
        {/if}
      </div>
      <div class="kl-storytelling__hero-cta">
        <a class="btn btn-primary" href="{$storytelling.inquiry_url|escape:'html':'UTF-8'}">
          {l s='Start an inquiry' d='Shop.Theme.Kunstort'}
        </a>
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
        {if isset($storytelling.availability.slots) && $storytelling.availability.slots}
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
                  </div>
                {/foreach}
              </div>
            {else}
              <p class="text-muted">{l s='Profiles will appear here once residency storytelling entries are published.' d='Shop.Theme.Kunstort'}</p>
            {/if}
          </article>
        {/foreach}
      {else}
        <p class="text-muted">{l s='Resource profiles are still being prepared for the storytelling rollout.' d='Shop.Theme.Kunstort'}</p>
      {/if}
    </section>

    {if isset($storytelling.packages) && $storytelling.packages}
      <section class="kl-storytelling__packages">
        <header class="kl-storytelling__section-header">
          <h2 class="h2">{l s='Featured residency programmes' d='Shop.Theme.Kunstort'}</h2>
          <p class="text-muted">{l s='These curated bundles surface once editors flag packages for front-office promotion.' d='Shop.Theme.Kunstort'}</p>
        </header>
        <div class="kl-storytelling__package-groups">
          {foreach from=$storytelling.packages item=packageGroup}
            <div class="kl-storytelling__package-group" id="packages-{$packageGroup.anchor|escape:'html':'UTF-8'}">
              <header class="kl-storytelling__package-group-header">
                <h3 class="h3">{$packageGroup.label|escape:'html':'UTF-8'}</h3>
                {if $packageGroup.intro}
                  <p class="text-muted">{$packageGroup.intro|escape:'html':'UTF-8'}</p>
                {/if}
              </header>
              <div class="kl-storytelling__package-list">
                {foreach from=$packageGroup.packages item=package}
                  <article class="kl-storytelling__package">
                    <h4 class="h4">{$package.name|escape:'html':'UTF-8'}</h4>
                    {if $package.tagline}
                      <p class="text-muted">{$package.tagline|escape:'html':'UTF-8'}</p>
                    {/if}
                    {if isset($package.highlight)}
                      {if $package.highlight.status == 'ready'}
                        <div class="kl-storytelling__package-highlight">
                          <p class="kl-storytelling__package-price h5">{$package.highlight.headline|escape:'html':'UTF-8'}</p>
                          {if $package.highlight.sample_label}
                            <p class="text-muted kl-storytelling__package-sample">{$package.highlight.sample_label|escape:'html':'UTF-8'}</p>
                          {/if}
                          {if $package.highlight.inclusions_label}
                            <p class="text-muted kl-storytelling__package-inclusions">{$package.highlight.inclusions_label|escape:'html':'UTF-8'}</p>
                          {/if}
                          {if $package.highlight.warning_label}
                            <p class="kl-storytelling__package-warning">{$package.highlight.warning_label|escape:'html':'UTF-8'}</p>
                          {/if}
                        </div>
                      {elseif $package.highlight.message}
                        <p class="text-muted kl-storytelling__package-message">{$package.highlight.message|escape:'html':'UTF-8'}</p>
                      {/if}
                    {/if}
                    {if $package.description}
                      <div class="kl-storytelling__package-description text-muted">
                        {$package.description nofilter}
                      </div>
                    {/if}
                    {if $package.inquiry_url}
                      <div class="kl-storytelling__package-actions">
                        <a class="btn btn-primary" href="{$package.inquiry_url|escape:'html':'UTF-8'}">
                          {$package.cta_label|escape:'html':'UTF-8'}
                        </a>
                      </div>
                    {/if}
                  </article>
                {/foreach}
              </div>
            </div>
          {/foreach}
        </div>
      </section>
    {/if}

    {if isset($storytelling.cms.practical) && $storytelling.cms.practical}
      <section class="kl-storytelling__practical">
        <header class="kl-storytelling__section-header">
          <h2 class="h2">{l s='Practical information' d='Shop.Theme.Kunstort'}</h2>
        </header>
        <div class="kl-storytelling__practical-body">
          {$storytelling.cms.practical.content nofilter}
        </div>
      </section>
    {/if}

    {if isset($storytelling.cms.testimonials) && $storytelling.cms.testimonials}
      <section class="kl-storytelling__testimonials">
        <header class="kl-storytelling__section-header">
          <h2 class="h2">{l s='Resident voices' d='Shop.Theme.Kunstort'}</h2>
        </header>
        <div class="kl-storytelling__testimonials-body">
          {$storytelling.cms.testimonials.content nofilter}
        </div>
      </section>
    {/if}

    {if isset($storytelling.cms.faq) && $storytelling.cms.faq}
      <section class="kl-storytelling__faq">
        <header class="kl-storytelling__section-header">
          <h2 class="h2">{l s='Frequently asked questions' d='Shop.Theme.Kunstort'}</h2>
        </header>
        <div class="panel-group" id="kl-storytelling-faq" role="tablist">
          {$storytelling.cms.faq.content nofilter}
        </div>
      </section>
    {/if}

    {block name='storytelling_deferred_scripts'}
      {capture name='klStorytellingScriptsResidencies'}{hook h='displayStorytellingScripts'}{/capture}
      {if trim($smarty.capture.klStorytellingScriptsResidencies)}
        <div class="kl-storytelling__defer" aria-hidden="true">
          {$smarty.capture.klStorytellingScriptsResidencies nofilter}
        </div>
      {/if}
    {/block}
  </div>
{/block}
