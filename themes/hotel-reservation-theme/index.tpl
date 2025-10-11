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

{extends file='page.tpl'}

{block name='page_content'}
  {if isset($use_storytelling_landing) && $use_storytelling_landing}
    {include file="$tpl_dir./_partials/storytelling-critical.tpl"}
    {capture assign=storytellingSlimLayout}
      <div class="kl-storytelling kl-storytelling--home"
        {if isset($storytelling.resource_key) && $storytelling.resource_key}data-kl-storytelling-resource="{$storytelling.resource_key|escape:'html':'UTF-8'}"{/if}>
      <section class="kl-storytelling__hero">
        <div class="kl-storytelling__hero-copy">
          {if isset($storytelling.hero.cms) && $storytelling.hero.cms}
            {$storytelling.hero.cms.content nofilter}
          {else}
            <h1 class="h1">{$storytelling.hero.headline|default:{l s='Artist campus in Lehnin' d='Shop.Theme.Kunstort'}|escape:'html':'UTF-8'}</h1>
            <p class="lead text-muted">{$storytelling.hero.lead|default:{l s='Residencies, studios, communal dining and programme spaces come together on a historic monastery campus. Explore the spaces and open an inquiry to plan your stay.' d='Shop.Theme.Kunstort'}|escape:'html':'UTF-8'}</p>
          {/if}
        </div>
        {if isset($storytelling.hero.cta_url) && $storytelling.hero.cta_url}
          <div class="kl-storytelling__hero-cta">
            <a class="btn btn-primary" href="{$storytelling.hero.cta_url|escape:'html':'UTF-8'}">
              {$storytelling.hero.cta_label|default:{l s='Start an inquiry' d='Shop.Theme.Kunstort'}|escape:'html':'UTF-8'}
            </a>
          </div>
        {/if}
      </section>

      {if isset($storytelling.sections) && $storytelling.sections}
        <nav class="kl-storytelling__home-nav" data-kl-storytelling-home-nav>
          <span class="kl-storytelling__home-nav-label">{l s='Jump to' d='Shop.Theme.Kunstort'}</span>
          <ul class="list-unstyled">
            {foreach from=$storytelling.sections item=section}
              <li class="kl-storytelling__home-nav-item" data-kl-storytelling-target="{$section.anchor|escape:'html':'UTF-8'}">
                <a href="#{$section.anchor|escape:'html':'UTF-8'}">{$section.nav_label|escape:'html':'UTF-8'}</a>
              </li>
            {/foreach}
          </ul>
        </nav>

        {foreach from=$storytelling.sections item=section}
          <section id="{$section.anchor|escape:'html':'UTF-8'}" class="kl-storytelling__section kl-storytelling__section--home">
            <header class="kl-storytelling__section-header">
              <h2 class="h2">{$section.title|escape:'html':'UTF-8'}</h2>
            </header>
            <div class="kl-storytelling__section-body">
              {if isset($section.summary_html) && $section.summary_html}
                <div class="kl-storytelling__section-summary">
                  {$section.summary_html nofilter}
                </div>
              {elseif isset($section.intro) && $section.intro}
                <p class="text-muted">{$section.intro|escape:'html':'UTF-8'}</p>
              {/if}

              {if (isset($section.availability.slot) && $section.availability.slot) || (isset($section.availability.message) && $section.availability.message)}
                <div class="kl-storytelling__home-availability">
                  <div class="kl-storytelling__home-availability-header">
                    <h3 class="h4">{l s='Next availability' d='Shop.Theme.Kunstort'}</h3>
                    {if isset($section.availability.slot.label) && $section.availability.slot.label}
                      <span class="kl-storytelling__home-availability-label">{$section.availability.slot.label|escape:'html':'UTF-8'}</span>
                    {/if}
                  </div>
                  {if isset($section.availability.slot.window) && $section.availability.slot.window}
                    <p class="kl-storytelling__home-availability-window text-muted">{$section.availability.slot.window|escape:'html':'UTF-8'}</p>
                  {/if}
                  {if isset($section.availability.slot.inquiry_url) && $section.availability.slot.inquiry_url}
                    <a class="btn btn-primary kl-storytelling__home-availability-cta" href="{$section.availability.slot.inquiry_url|escape:'html':'UTF-8'}">
                      {l s='Request this slot' d='Shop.Theme.Kunstort'}
                    </a>
                  {/if}
                  {if isset($section.availability.message) && $section.availability.message}
                    <p class="text-muted kl-storytelling__home-availability-message">{$section.availability.message|escape:'html':'UTF-8'}</p>
                  {/if}
                </div>
              {/if}

              {if isset($section.profiles) && $section.profiles}
                <div class="kl-storytelling__section-profiles kl-storytelling__section-profiles--compact">
                  {foreach from=$section.profiles item=profile}
                    <article class="kl-storytelling__profile kl-storytelling__profile--compact">
                      {include file="$tpl_dir./_partials/storytelling-profile-media.tpl" profile=$profile}
                      <div class="kl-storytelling__profile-body">
                        <h3 class="h4">{$profile.display_name|escape:'html':'UTF-8'}</h3>
                        {if isset($profile.excerpt) && $profile.excerpt}
                          <p class="kl-storytelling__profile-excerpt text-muted">{$profile.excerpt|escape:'html':'UTF-8'}</p>
                        {/if}
                        {if isset($profile.capacity_summary) && $profile.capacity_summary}
                          <ul class="list-unstyled kl-storytelling__profile-capacity">
                            {foreach from=$profile.capacity_summary item=capacity}
                              <li>{$capacity|escape:'html':'UTF-8'}</li>
                            {/foreach}
                          </ul>
                        {/if}
                      </div>
                    </article>
                  {/foreach}
                </div>
              {/if}

              {if isset($section.package) && $section.package}
                <div class="kl-storytelling__home-package">
                  <div class="kl-storytelling__home-package-heading">
                    <h3 class="h4">{l s='Featured package' d='Shop.Theme.Kunstort'}</h3>
                  </div>
                  <p class="kl-storytelling__home-package-name">{$section.package.name|escape:'html':'UTF-8'}</p>
                  {if isset($section.package.tagline) && $section.package.tagline}
                    <p class="text-muted">{$section.package.tagline|escape:'html':'UTF-8'}</p>
                  {/if}
                  {if isset($section.package.headline) && $section.package.headline}
                    <p class="kl-storytelling__home-package-highlight">{$section.package.headline|escape:'html':'UTF-8'}</p>
                  {/if}
                  {if isset($section.package.message) && $section.package.message}
                    <p class="text-muted">{$section.package.message|escape:'html':'UTF-8'}</p>
                  {/if}
                  {if isset($section.package.inquiry_url) && $section.package.inquiry_url}
                    <a class="btn btn--ghost" href="{$section.package.inquiry_url|escape:'html':'UTF-8'}">
                      {if isset($section.package.cta_label) && $section.package.cta_label}
                        {$section.package.cta_label|escape:'html':'UTF-8'}
                      {else}
                        {l s='Ask about this package' d='Shop.Theme.Kunstort'}
                      {/if}
                    </a>
                  {/if}
                </div>
              {/if}

              <div class="kl-storytelling__cta-group">
                {if isset($section.inquiry_url) && $section.inquiry_url}
                  <a class="btn btn-primary" href="{$section.inquiry_url|escape:'html':'UTF-8'}">{$section.inquiry_label|escape:'html':'UTF-8'}</a>
                {/if}
                {if isset($section.landing_url) && $section.landing_url}
                  <a class="btn btn--ghost" href="{$section.landing_url|escape:'html':'UTF-8'}">{$section.landing_label|escape:'html':'UTF-8'}</a>
                {/if}
              </div>
            </div>
          </section>
        {/foreach}
      {else}
        <p class="text-muted">{l s='Storytelling sections will appear soon.' d='Shop.Theme.Kunstort'}</p>
      {/if}
      </div>
    {/capture}
    {include file="$tpl_dir./_partials/storytelling-layout-slim.tpl" content=$storytellingSlimLayout}
  {else}
    {block name='displayHomeTabContent'}
      {if isset($HOOK_HOME_TAB_CONTENT) && $HOOK_HOME_TAB_CONTENT|trim}
        {block name='displayHomeTab'}
          {if isset($HOOK_HOME_TAB) && $HOOK_HOME_TAB|trim}
            <ul id="home-page-tabs" class="nav nav-tabs clearfix">
              {$HOOK_HOME_TAB}
            </ul>
          {/if}
        {/block}
        <div class="tab-content">{$HOOK_HOME_TAB_CONTENT}</div>
      {/if}
    {/block}
    {block name='displayHome'}
      {if isset($HOOK_HOME) && $HOOK_HOME|trim}
        <div class="clearfix">{$HOOK_HOME}</div>
      {/if}
    {/block}
  {/if}
{/block}
