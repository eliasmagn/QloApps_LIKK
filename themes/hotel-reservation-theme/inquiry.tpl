{extends file='page.tpl'}

{block name='page_title'}{l s='Send an inquiry'}{/block}

{block name='page_content'}
<div class="inquiry-landing">
    <p>{l s='Let us know your desired stay dates, room preferences and programme needs. We will respond with availability and next steps.'}</p>
    <p>
        <a class="btn btn-primary" href="{$contact_link|escape:'html':'UTF-8'}">{l s='Use the contact form'}</a>
    </p>
    <p class="text-muted">{l s='A dedicated inquiry form will arrive soon; for now the contact page routes your request to the residency team.'}</p>
</div>
{/block}
