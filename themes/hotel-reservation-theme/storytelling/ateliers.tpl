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

{block name='storytelling_ateliers'}
  <div class="kl-storytelling kl-storytelling--ateliers">
    <section class="kl-storytelling__hero">
      <div class="kl-storytelling__hero-copy">
        {if isset($storytelling.cms.hero) && $storytelling.cms.hero}
          {$storytelling.cms.hero.content nofilter}
        {else}
          <h1 class="h1">{l s='Studios & ateliers at Kunstort Lehnin' d='Shop.Theme.Kunstort'}</h1>
          <p class="lead text-muted">
            {l s='Explore production spaces, rehearsal studios and workshops prepared for multidisciplinary residencies. The sections below surface taxonomy-driven profiles while full storytelling copy is staged.' d='Shop.Theme.Kunstort'}
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
              <li>
                <strong>{$slot.label|escape:'html':'UTF-8'}</strong>
                <span class="text-muted">{$slot.window|escape:'html':'UTF-8'}</span>
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
              <p class="text-muted">{l s='Atelier profiles will appear here once storytelling entries are published.' d='Shop.Theme.Kunstort'}</p>
            {/if}
          </article>
        {/foreach}
      {else}
        <p class="text-muted">{l s='Atelier storytelling is being prepared and will launch once profiles are published.' d='Shop.Theme.Kunstort'}</p>
      {/if}
    </section>

    {if isset($storytelling.packages) && $storytelling.packages}
      <section class="kl-storytelling__packages">
        <header class="kl-storytelling__section-header">
          <h2 class="h2">{l s='Featured studio programmes' d='Shop.Theme.Kunstort'}</h2>
          <p class="text-muted">{l s='These packages spotlight atelier-focused residencies once editors flag them for promotion.' d='Shop.Theme.Kunstort'}</p>
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
          <h2 class="h2">{l s='Artist voices' d='Shop.Theme.Kunstort'}</h2>
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
        <div class="panel-group" id="kl-storytelling-ateliers-faq" role="tablist">
          {$storytelling.cms.faq.content nofilter}
        </div>
      </section>
    {/if}
  </div>
{/block}
