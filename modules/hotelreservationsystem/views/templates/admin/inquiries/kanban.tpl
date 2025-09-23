{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
<div class="panel inquiry-board-panel">
    <div class="panel-heading">
        <i class="icon-comments"></i> {l s='Inquiry board' mod='hotelreservationsystem'}
        <div class="pull-right">
            <button type="button" class="btn btn-default" id="inquiry-refresh-board">
                <i class="icon-refresh"></i> {l s='Refresh' mod='hotelreservationsystem'}
            </button>
            <button type="button" class="btn btn-primary" id="inquiry-create-trigger">
                <i class="icon-plus"></i> {l s='New inquiry' mod='hotelreservationsystem'}
            </button>
        </div>
    </div>
    <div class="panel-body">
        <div class="row" id="hotel-inquiry-board">
            {foreach from=$stage_definitions key=stageKey item=stageDefinition}
                <div class="col-md-3 col-sm-6 inquiry-column" data-stage="{$stageKey|escape:'htmlall':'UTF-8'}">
                    <div class="inquiry-column-header">
                        <span class="inquiry-column-title">{$stageDefinition.label|escape:'htmlall':'UTF-8'}</span>
                        <span class="badge" data-stage-count="{$stageKey|escape:'htmlall':'UTF-8'}">{if isset($inquiry_dataset[$stageKey])}{count($inquiry_dataset[$stageKey])}{else}0{/if}</span>
                    </div>
                    <div class="inquiry-column-body" data-stage-list="{$stageKey|escape:'htmlall':'UTF-8'}">
                        {if isset($inquiry_dataset[$stageKey]) && $inquiry_dataset[$stageKey]}
                            {foreach from=$inquiry_dataset[$stageKey] item=inquiry}
                                {include file="modules/hotelreservationsystem/views/templates/admin/inquiries/_partials/card.tpl" inquiry=$inquiry stage_definitions=$stage_definitions status_definitions=$status_definitions board_employees=$board_employees}
                            {/foreach}
                        {/if}
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>

<div class="modal fade" id="inquiry-create-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="inquiry-create-form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='hotelreservationsystem'}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-plus"></i> {l s='Create inquiry' mod='hotelreservationsystem'}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="inquiry-subject" class="control-label">{l s='Subject' mod='hotelreservationsystem'}</label>
                        <input type="text" class="form-control" id="inquiry-subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="inquiry-requester-name" class="control-label">{l s='Requester name' mod='hotelreservationsystem'}</label>
                        <input type="text" class="form-control" id="inquiry-requester-name" name="requester_name">
                    </div>
                    <div class="form-group">
                        <label for="inquiry-requester-email" class="control-label">{l s='Requester email' mod='hotelreservationsystem'}</label>
                        <input type="email" class="form-control" id="inquiry-requester-email" name="requester_email">
                    </div>
                    <div class="form-group">
                        <label for="inquiry-requester-phone" class="control-label">{l s='Requester phone' mod='hotelreservationsystem'}</label>
                        <input type="text" class="form-control" id="inquiry-requester-phone" name="requester_phone">
                    </div>
                    <div class="form-group">
                        <label class="control-label">{l s='Requested stay' mod='hotelreservationsystem'}</label>
                        <div class="row">
                            <div class="col-sm-6">
                                <input type="date" class="form-control" name="check_in" placeholder="{l s='Check-in' mod='hotelreservationsystem'}">
                            </div>
                            <div class="col-sm-6">
                                <input type="date" class="form-control" name="check_out" placeholder="{l s='Check-out' mod='hotelreservationsystem'}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inquiry-resource-request" class="control-label">{l s='Requested rooms or spaces' mod='hotelreservationsystem'}</label>
                        <textarea class="form-control" id="inquiry-resource-request" name="resource_request" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="inquiry-internal-notes" class="control-label">{l s='Internal notes' mod='hotelreservationsystem'}</label>
                        <textarea class="form-control" id="inquiry-internal-notes" name="internal_notes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="inquiry-assigned" class="control-label">{l s='Assign to' mod='hotelreservationsystem'}</label>
                        <select class="form-control" id="inquiry-assigned" name="assigned_to">
                            <option value="">{l s='Unassigned' mod='hotelreservationsystem'}</option>
                            {foreach from=$board_employees item=employee}
                                <option value="{$employee.id_employee|intval}">{$employee.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='hotelreservationsystem'}</button>
                    <button type="submit" class="btn btn-primary">{l s='Save inquiry' mod='hotelreservationsystem'}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="inquiry-note-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="inquiry-note-form">
                <input type="hidden" name="id_inquiry" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='hotelreservationsystem'}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-envelope"></i> {l s='Add note' mod='hotelreservationsystem'}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="inquiry-note-body" class="control-label">{l s='Note' mod='hotelreservationsystem'}</label>
                        <textarea class="form-control" id="inquiry-note-body" name="note" rows="4" required></textarea>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="is_mail" value="1"> {l s='Send as mail note to requester' mod='hotelreservationsystem'}
                        </label>
                    </div>
                    <div class="inquiry-note-history"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='hotelreservationsystem'}</button>
                    <button type="submit" class="btn btn-primary">{l s='Save note' mod='hotelreservationsystem'}</button>
                </div>
            </form>
        </div>
    </div>
</div>
{/block}
