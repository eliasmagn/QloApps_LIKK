{extends file='page.tpl'}

{block name='page_title'}{l s='Send an inquiry'}{/block}

{block name='page_content'}
<div class="inquiry-flow">
    <div class="inquiry-card">
        <h2>{l s='Residency request'}</h2>

        {if isset($submission_success) && $submission_success && isset($submitted_inquiry)}
            <div class="alert alert-success">
                <p>{l s='Thank you for your message. We logged inquiry %s and will respond shortly.' sprintf=[$submitted_inquiry->reference]}</p>
            </div>
        {/if}

        {if isset($errors) && $errors}
            <div class="alert alert-danger">
                <ul>
                    {foreach from=$errors item=error}
                        <li>{$error|escape:'html':'UTF-8'}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        {if isset($confirmations) && $confirmations}
            <div class="alert alert-success">
                {foreach from=$confirmations item=confirmation}
                    <p>{$confirmation|escape:'html':'UTF-8'}</p>
                {/foreach}
            </div>
        {/if}

        <form id="kl-inquiry-form" method="post" data-lookup-endpoint="{$inquiry_lookup_endpoint|escape:'html':'UTF-8'}">
            <input type="hidden" name="token" value="{$inquiry_form_token|escape:'html':'UTF-8'}" />
            <input type="hidden" name="submitInquiryForm" value="1" />

            <ul id="kl-inquiry-steps">
                <li class="active">{l s='Guest details'}</li>
                <li>{l s='Stay preferences'}</li>
                <li>{l s='Programme & send'}</li>
            </ul>

            <div class="inquiry-step-panel active">
                <div class="form-group">
                    <label for="guest_name">{l s='Your name'} *</label>
                    <input class="form-control" type="text" name="guest_name" id="guest_name" required="required" value="{$form_values.guest_name|default:''|escape:'html':'UTF-8'}" />
                </div>
                <div class="form-group">
                    <label for="guest_email">{l s='Email'} *</label>
                    <input class="form-control" type="email" name="guest_email" id="guest_email" required="required" value="{$form_values.guest_email|default:''|escape:'html':'UTF-8'}" />
                </div>
                <div class="form-group">
                    <label for="guest_phone">{l s='Phone'} <span class="text-muted">{l s='(optional)'}</span></label>
                    <input class="form-control" type="tel" name="guest_phone" id="guest_phone" value="{$form_values.guest_phone|default:''|escape:'html':'UTF-8'}" />
                </div>
                <div class="inquiry-step-navigation">
                    <span></span>
                    <button class="btn btn-primary" data-action="next-step">{l s='Next'}</button>
                </div>
            </div>

            <div class="inquiry-step-panel">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="arrival_date">{l s='Arrival'} *</label>
                            <input class="form-control" type="date" name="arrival_date" id="arrival_date" required="required" value="{$form_values.arrival_date|default:''|escape:'html':'UTF-8'}" />
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="departure_date">{l s='Departure'} *</label>
                            <input class="form-control" type="date" name="departure_date" id="departure_date" required="required" value="{$form_values.departure_date|default:''|escape:'html':'UTF-8'}" />
                        </div>
                    </div>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="date_flexibility" value="1" {if $form_values.date_flexibility|default:false}checked="checked"{/if} />
                        {l s='Our dates are flexible'}
                    </label>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="party_size_adults">{l s='Adults'}</label>
                            <input class="form-control" type="number" name="party_size_adults" id="party_size_adults" min="0" value="{$form_values.party_size_adults|default:1|intval}" />
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="party_size_children">{l s='Children'}</label>
                            <input class="form-control" type="number" name="party_size_children" id="party_size_children" min="0" value="{$form_values.party_size_children|default:0|intval}" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>{l s='What spaces are you interested in?'}</label>
                    {foreach from=$resource_kind_options key=kind item=label}
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="resource_interests[]" data-label="{$label|escape:'html':'UTF-8'}" value="{$kind|escape:'html':'UTF-8'}" {if isset($form_values.resource_interests) && in_array($kind, $form_values.resource_interests)}checked="checked"{/if} />
                                {$label|escape:'html':'UTF-8'}
                            </label>
                        </div>
                    {/foreach}
                </div>
                <div class="form-group">
                    <label for="programme_focus">{l s='Programme focus'}</label>
                    <textarea class="form-control" name="programme_focus" id="programme_focus" rows="3">{$form_values.programme_focus|default:''|escape:'html':'UTF-8'}</textarea>
                </div>
                <div class="form-group">
                    <label for="resource_notes">{l s='Specific spaces or codes'}</label>
                    <input class="form-control" type="text" name="resource_notes" id="resource_notes" list="resource-suggestions" value="{$form_values.resource_notes|default:''|escape:'html':'UTF-8'}" />
                    <datalist id="resource-suggestions"></datalist>
                </div>
                <div class="inquiry-step-navigation">
                    <button class="btn btn-default" data-action="previous-step">{l s='Back'}</button>
                    <button class="btn btn-primary" data-action="next-step">{l s='Next'}</button>
                </div>
            </div>

            <div class="inquiry-step-panel">
                <div class="form-group">
                    <label for="package_preferences">{l s='Interested packages'}</label>
                    <select class="form-control" name="package_preferences[]" id="package_preferences" multiple="multiple">
                        {if isset($form_values.package_preferences)}
                            {foreach from=$form_values.package_preferences item=packageCode}
                                <option value="{$packageCode|escape:'html':'UTF-8'}" selected="selected">{$packageCode|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        {/if}
                    </select>
                    <span class="help-block">{l s='We will include curated bundles or rate plans in the follow-up quote.'}</span>
                </div>
                <div class="form-group">
                    <label for="additional_notes">{l s='Anything else we should know?'}</label>
                    <textarea class="form-control" name="additional_notes" id="additional_notes" rows="4">{$form_values.additional_notes|default:''|escape:'html':'UTF-8'}</textarea>
                </div>
                <div class="inquiry-consents">
                    <label>
                        <input type="checkbox" name="consent_data_usage" value="1" required="required" {if $form_values.consent_data_usage|default:false}checked="checked"{/if} />
                        {$data_usage_statement|escape:'html':'UTF-8'}
                    </label>
                    <label>
                        <input type="checkbox" name="consent_newsletter" value="1" {if $form_values.consent_newsletter|default:false}checked="checked"{/if} />
                        {$newsletter_opt_in_label|escape:'html':'UTF-8'}
                    </label>
                </div>
                <div class="inquiry-step-navigation">
                    <button class="btn btn-default" data-action="previous-step">{l s='Back'}</button>
                    <button class="btn btn-primary" type="submit">{l s='Send inquiry'}</button>
                </div>
            </div>
        </form>
    </div>
    <div class="inquiry-card inquiry-summary">
        <h2>{l s='Summary'}</h2>
        <dl>
            <dt>{l s='Guest'}</dt>
            <dd data-summary="guest_name">{$form_values.guest_name|default:'—'|escape:'html':'UTF-8'}</dd>
            <dd data-summary="guest_email">{$form_values.guest_email|default:'—'|escape:'html':'UTF-8'}</dd>
            <dd data-summary="guest_phone">{$form_values.guest_phone|default:'—'|escape:'html':'UTF-8'}</dd>

            <dt>{l s='Stay window'}</dt>
            <dd data-summary="stay">
                {if $form_values.arrival_date|default:false}
                    {$form_values.arrival_date|escape:'html':'UTF-8'} → {$form_values.departure_date|escape:'html':'UTF-8'}
                {else}
                    —
                {/if}
            </dd>

            <dt>{l s='Party size'}</dt>
            <dd data-summary="party">{$form_values.party_size_adults|default:1|intval} {l s='adults'} / {$form_values.party_size_children|default:0|intval} {l s='children'}</dd>

            <dt>{l s='Interests'}</dt>
            <dd data-summary="interests">
                {if isset($form_values.resource_interests) && $form_values.resource_interests}
                    {foreach from=$form_values.resource_interests item=interest name=interestLoop}
                        {assign var=interestLabel value=$resource_kind_options[$interest]|default:$interest}
                        {$interestLabel|escape:'html':'UTF-8'}{if not $smarty.foreach.interestLoop.last}, {/if}
                    {/foreach}
                {else}
                    —
                {/if}
            </dd>
        </dl>
        <p class="text-muted">
            {l s='We will email a confirmation immediately and follow up with curated availability and package suggestions.'}
        </p>
    </div>
</div>
{/block}
