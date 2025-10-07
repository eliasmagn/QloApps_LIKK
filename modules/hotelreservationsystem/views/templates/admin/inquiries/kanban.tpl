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
        <div class="inquiry-board-layout">
            <div class="inquiry-board-columns">
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
            <aside id="inquiry-detail-sidebar" class="inquiry-sidebar">
                <div class="inquiry-sidebar-placeholder" data-role="empty">
                    <p class="text-muted">{l s='Select an inquiry to review details.' mod='hotelreservationsystem'}</p>
                </div>
                <div class="inquiry-sidebar-content hidden" data-role="content">
                    <header class="inquiry-sidebar-header">
                        <div>
                            <span class="label label-default" data-field="reference"></span>
                            <span class="label label-info" data-field="status"></span>
                        </div>
                        <h4 data-field="subject"></h4>
                        <p class="text-muted" data-field="stage"></p>
                    </header>
                    <section class="inquiry-sidebar-section">
                        <ul class="list-unstyled inquiry-sidebar-details">
                            <li><strong>{l s='Requester' mod='hotelreservationsystem'}:</strong> <span data-field="contact"></span></li>
                            <li><strong>{l s='Stay window' mod='hotelreservationsystem'}:</strong> <span data-field="dates"></span></li>
                            <li><strong>{l s='Resources' mod='hotelreservationsystem'}:</strong> <span data-field="resources"></span></li>
                            <li><strong>{l s='Internal notes' mod='hotelreservationsystem'}:</strong> <span data-field="internal"></span></li>
                            <li><strong>{l s='Reminder' mod='hotelreservationsystem'}:</strong> <span data-field="reminder"></span></li>
                        </ul>
                    </section>
                    <section class="inquiry-sidebar-section inquiry-sidebar-actions">
                        <button type="button" class="btn btn-default btn-sm" data-role="open-notes">
                            <i class="icon-comments"></i> {l s='Log note' mod='hotelreservationsystem'}
                        </button>
                        <button type="button" class="btn btn-default btn-sm" data-role="open-reminder">
                            <i class="icon-bell"></i> {l s='Set reminder' mod='hotelreservationsystem'}
                        </button>
                    </section>
                    <section class="inquiry-sidebar-section inquiry-sidebar-quotes" data-role="quotes-section">
                        <h5>{l s='Quotes' mod='hotelreservationsystem'}</h5>
                        <p class="text-muted" data-role="quotes-empty">{l s='No quotes have been generated yet.' mod='hotelreservationsystem'}</p>
                        <div class="inquiry-quote-list" data-role="quotes-list"></div>
                        <script type="text/template" id="inquiry-quote-item-template">
                            {include file="modules/hotelreservationsystem/views/templates/admin/inquiries/_partials/quote_card.tpl"}
                        </script>
                    </section>
                    <section class="inquiry-sidebar-section inquiry-sidebar-operations" data-role="operations-section">
                        <h5>{l s='Operations follow-ups' mod='hotelreservationsystem'}</h5>
                        <div class="inquiry-operations-list" data-role="operations-list"></div>
                        <div class="btn-group btn-group-justified">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm" data-action="open-operation-modal" data-task-type="housekeeping_followup">
                                    <i class="icon-home"></i> {l s='Raise housekeeping task' mod='hotelreservationsystem'}
                                </button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm" data-action="open-operation-modal" data-task-type="maintenance_followup">
                                    <i class="icon-wrench"></i> {l s='Raise maintenance task' mod='hotelreservationsystem'}
                                </button>
                            </div>
                        </div>
                        <a href="{$link->getAdminLink('AdminKlOperationTasks')|escape:'htmlall':'UTF-8'}" class="btn btn-link btn-sm" target="_blank" rel="noopener" data-role="operations-console-link">
                            <i class="icon-external-link"></i> {l s='Open in operations console' mod='hotelreservationsystem'}
                        </a>
                    </section>
                </div>
            </aside>
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
                    {if $operations_enabled}
                        <hr>
                        <div class="inquiry-note-operations" data-role="note-operations">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="operation_follow_up" value="1" data-role="note-operation-toggle"> {l s='Also create an operations follow-up from this note' mod='hotelreservationsystem'}
                                </label>
                            </div>
                            <div class="note-operation-fields hidden" data-role="note-operation-fields">
                                <div class="form-group">
                                    <label for="inquiry-note-operation-type" class="control-label">{l s='Follow-up type' mod='hotelreservationsystem'}</label>
                                    <select class="form-control" id="inquiry-note-operation-type" name="operation_task_type" disabled>
                                        <option value="housekeeping_followup">{l s='Housekeeping follow-up' mod='hotelreservationsystem'}</option>
                                        <option value="maintenance_followup">{l s='Maintenance follow-up' mod='hotelreservationsystem'}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="inquiry-note-operation-priority" class="control-label">{l s='Priority' mod='hotelreservationsystem'}</label>
                                    <select class="form-control" id="inquiry-note-operation-priority" name="operation_priority" disabled>
                                        <option value="1">1 – {l s='Critical' mod='hotelreservationsystem'}</option>
                                        <option value="2">2 – {l s='High' mod='hotelreservationsystem'}</option>
                                        <option value="3" selected>3 – {l s='Standard' mod='hotelreservationsystem'}</option>
                                        <option value="4">4 – {l s='Low' mod='hotelreservationsystem'}</option>
                                        <option value="5">5 – {l s='Informational' mod='hotelreservationsystem'}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="inquiry-note-operation-scheduled" class="control-label">{l s='Scheduled for' mod='hotelreservationsystem'}</label>
                                    <input type="datetime-local" class="form-control" id="inquiry-note-operation-scheduled" name="operation_scheduled_for" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="inquiry-note-operation-due" class="control-label">{l s='Due end' mod='hotelreservationsystem'} ({l s='optional' mod='hotelreservationsystem'})</label>
                                    <input type="datetime-local" class="form-control" id="inquiry-note-operation-due" name="operation_due_end" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="inquiry-note-operation-reference" class="control-label">{l s='Reference' mod='hotelreservationsystem'}</label>
                                    <input type="text" class="form-control" id="inquiry-note-operation-reference" name="operation_reference" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="inquiry-note-operation-note" class="control-label">{l s='Operations note' mod='hotelreservationsystem'}</label>
                                    <textarea class="form-control" id="inquiry-note-operation-note" name="operation_note" rows="3" disabled></textarea>
                                </div>
                                <p class="help-block">{l s='Adjust the follow-up before saving. The inquiry note stays visible to the team and the operations task receives the same context.' mod='hotelreservationsystem'}</p>
                            </div>
                        </div>
                    {/if}
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

<div class="modal fade" id="inquiry-operation-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="inquiry-operation-form">
                <input type="hidden" name="id_inquiry" value="">
                <input type="hidden" name="task_type" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='hotelreservationsystem'}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="icon-tasks"></i> {l s='Raise operations follow-up' mod='hotelreservationsystem'}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="inquiry-operation-reference" class="control-label">{l s='Reference' mod='hotelreservationsystem'}</label>
                        <input type="text" class="form-control" id="inquiry-operation-reference" name="reference" required>
                    </div>
                    <div class="form-group">
                        <label for="inquiry-operation-priority" class="control-label">{l s='Priority' mod='hotelreservationsystem'}</label>
                        <select class="form-control" id="inquiry-operation-priority" name="priority">
                            <option value="1">1 – {l s='Critical' mod='hotelreservationsystem'}</option>
                            <option value="2">2 – {l s='High' mod='hotelreservationsystem'}</option>
                            <option value="3" selected>3 – {l s='Standard' mod='hotelreservationsystem'}</option>
                            <option value="4">4 – {l s='Low' mod='hotelreservationsystem'}</option>
                            <option value="5">5 – {l s='Informational' mod='hotelreservationsystem'}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inquiry-operation-scheduled" class="control-label">{l s='Scheduled for' mod='hotelreservationsystem'}</label>
                        <input type="datetime-local" class="form-control" id="inquiry-operation-scheduled" name="scheduled_for" required>
                    </div>
                    <div class="form-group">
                        <label for="inquiry-operation-due" class="control-label">{l s='Due end' mod='hotelreservationsystem'} ({l s='optional' mod='hotelreservationsystem'})</label>
                        <input type="datetime-local" class="form-control" id="inquiry-operation-due" name="due_end">
                    </div>
                    <div class="form-group">
                        <label for="inquiry-operation-resource-type" class="control-label">{l s='Resource type' mod='hotelreservationsystem'}</label>
                        <input type="text" class="form-control" id="inquiry-operation-resource-type" name="resource_type" placeholder="{l s='e.g. room, atelier' mod='hotelreservationsystem'}">
                    </div>
                    <div class="form-group">
                        <label for="inquiry-operation-resource-id" class="control-label">{l s='Resource ID' mod='hotelreservationsystem'} ({l s='optional' mod='hotelreservationsystem'})</label>
                        <input type="number" class="form-control" id="inquiry-operation-resource-id" name="id_resource">
                    </div>
                    <div class="form-group">
                        <label for="inquiry-operation-note" class="control-label">{l s='Context note' mod='hotelreservationsystem'}</label>
                        <textarea class="form-control" id="inquiry-operation-note" name="note" rows="4"></textarea>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="log_note" value="1" checked> {l s='Log this follow-up on the inquiry timeline' mod='hotelreservationsystem'}
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='hotelreservationsystem'}</button>
                    <button type="submit" class="btn btn-primary">{l s='Create follow-up' mod='hotelreservationsystem'}</button>
                </div>
            </form>
        </div>
    </div>
</div>
{/block}
