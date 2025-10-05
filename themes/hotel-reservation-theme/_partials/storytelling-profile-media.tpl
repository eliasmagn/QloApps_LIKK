{if isset($profile.media) && $profile.media}
  <figure class="kl-storytelling__profile-media">
    <picture>
      {if isset($profile.media.sources) && $profile.media.sources}
        {foreach from=$profile.media.sources item=source}
          {if isset($source.srcset) && $source.srcset}
            <source type="{$source.type|escape:'html':'UTF-8'}" srcset="{$source.srcset|escape:'html':'UTF-8'}"{if isset($source.sizes) && $source.sizes} sizes="{$source.sizes|escape:'html':'UTF-8'}"{/if}>
          {/if}
        {/foreach}
      {/if}
      <img
        class="kl-storytelling__profile-image"
        src="{$profile.media.fallback.src|escape:'html':'UTF-8'}"
        alt="{$profile.media.alt|escape:'html':'UTF-8'}"
        loading="lazy"
        decoding="async"
        {if isset($profile.media.fallback.width)}width="{$profile.media.fallback.width|intval}"{/if}
      />
    </picture>
    {if isset($profile.media.caption) && $profile.media.caption}
      <figcaption class="kl-storytelling__profile-caption text-muted">{$profile.media.caption|escape:'html':'UTF-8'}</figcaption>
    {/if}
  </figure>
{/if}
