{assign var=inquiry_href value=$link->getPageLink('inquiry', true)}
<div class="checkout-inquiry-message">
    <p>{l s='Online checkout has been retired in favour of a guided inquiry process.'}</p>
    <p>{l s='Share the requested stay details via our inquiry page and the team will confirm availability with you directly.'}</p>
    <p>
        <a class="btn btn-primary" href="{$inquiry_href|escape:'html':'UTF-8'}">
            {l s='Start an inquiry'}
        </a>
    </p>
</div>
