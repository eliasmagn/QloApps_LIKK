<div class="inquiry-card" data-inquiry-id="{$inquiry.id_inquiry|intval}" data-stage="{$inquiry.stage|escape:'htmlall':'UTF-8'}">
    <div class="inquiry-card-header">
        <span class="label label-default">{$inquiry.reference|escape:'htmlall':'UTF-8'}</span>
        <span class="inquiry-card-status">{if isset($status_definitions[$inquiry.status])}{$status_definitions[$inquiry.status]|escape:'htmlall':'UTF-8'}{else}{$inquiry.status|escape:'htmlall':'UTF-8'}{/if}</span>
    </div>
    <div class="inquiry-card-body">
        <strong class="inquiry-card-subject">{$inquiry.subject|escape:'htmlall':'UTF-8'}</strong>
        {if $inquiry.requester_name || $inquiry.requester_email}
            <div class="inquiry-card-contact">
                <i class="icon-user"></i>
                {if $inquiry.requester_name}<span>{$inquiry.requester_name|escape:'htmlall':'UTF-8'}</span>{/if}
                {if $inquiry.requester_email}
                    <span>&lt;{$inquiry.requester_email|escape:'htmlall':'UTF-8'}&gt;</span>
                {/if}
            </div>
        {/if}
        {if $inquiry.check_in || $inquiry.check_out}
            <div class="inquiry-card-dates">
                <i class="icon-calendar"></i>
                <span>
                    {if $inquiry.check_in}{$inquiry.check_in|escape:'htmlall':'UTF-8'}{else}?{/if}
                    –
                    {if $inquiry.check_out}{$inquiry.check_out|escape:'htmlall':'UTF-8'}{else}?{/if}
                </span>
            </div>
        {/if}
        {if $inquiry.resource_request}
            <div class="inquiry-card-resources">{$inquiry.resource_request|escape:'htmlall':'UTF-8'}</div>
        {/if}
        {if $inquiry.reminder_at}
            <div class="inquiry-card-reminder text-warning">
                <i class="icon-bell"></i> {l s='Reminder' mod='hotelreservationsystem'}: {$inquiry.reminder_at|escape:'htmlall':'UTF-8'}
            </div>
        {/if}
        {if $inquiry.last_note_at}
            <div class="inquiry-card-noteinfo">
                <i class="icon-sticky-note"></i> {l s='Last note' mod='hotelreservationsystem'}: {$inquiry.last_note_at|escape:'htmlall':'UTF-8'}
            </div>
        {/if}
    </div>
    <div class="inquiry-card-footer">
        <div class="inquiry-card-assignee">
            <label>{l s='Assigned' mod='hotelreservationsystem'}</label>
            <select class="form-control input-sm inquiry-assignee" data-role="assignee">
                <option value="">{l s='Unassigned' mod='hotelreservationsystem'}</option>
                {foreach from=$board_employees item=employee}
                    <option value="{$employee.id_employee|intval}"{if isset($inquiry.assigned_to) && $inquiry.assigned_to == $employee.id_employee} selected{/if}>{$employee.name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
        <div class="inquiry-card-actions">
            <button type="button" class="btn btn-link btn-xs inquiry-set-reminder" data-role="reminder">
                <i class="icon-bell"></i>
            </button>
            <button type="button" class="btn btn-link btn-xs inquiry-open-notes" data-role="notes">
                <i class="icon-comments"></i>
            </button>
            <button type="button" class="btn btn-link btn-xs inquiry-open-details" data-role="inspect">
                <i class="icon-info-circle"></i>
            </button>
        </div>
    </div>
</div>
