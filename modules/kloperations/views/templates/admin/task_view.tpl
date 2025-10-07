{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
<div class="panel">
  <h3>
    <i class="icon-tasks"></i> {l s='Operation task' mod='kloperations'}: {$task->reference|escape:'htmlall':'UTF-8'}
    <a class="btn btn-default btn-sm pull-right" href="{$mobile_view_link|escape:'htmlall':'UTF-8'}">
      <i class="icon-mobile-phone"></i> {l s='Open mobile view' mod='kloperations'}
    </a>
  </h3>
  <div class="well">
    <dl class="dl-horizontal">
      <dt>{l s='Task type' mod='kloperations'}</dt>
      <dd>{$task->task_type|escape:'htmlall':'UTF-8'}</dd>
      <dt>{l s='Status' mod='kloperations'}</dt>
      <dd>{$task->status|escape:'htmlall':'UTF-8'}</dd>
      <dt>{l s='Scheduled for' mod='kloperations'}</dt>
      <dd>{$task->scheduled_for|escape:'htmlall':'UTF-8'}</dd>
      <dt>{l s='Due end' mod='kloperations'}</dt>
      <dd>{$task->due_end|escape:'htmlall':'UTF-8'}</dd>
      <dt>{l s='Last reminder' mod='kloperations'}</dt>
      <dd>{$task->last_reminded_at|escape:'htmlall':'UTF-8'}</dd>
      <dt>{l s='Resource' mod='kloperations'}</dt>
      <dd>{$task->resource_type|escape:'htmlall':'UTF-8'} #{$task->id_resource|escape:'htmlall':'UTF-8'}</dd>
      <dt>{l s='Context' mod='kloperations'}</dt>
      <dd>
        {if isset($inquiry_context) && $inquiry_context}
          {if isset($inquiry_context.link)}
            <a href="{$inquiry_context.link|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">
              {if isset($inquiry_context.reference)}{$inquiry_context.reference|escape:'htmlall':'UTF-8'}{/if}
              {if isset($inquiry_context.subject) && $inquiry_context.subject}&nbsp;&ndash;&nbsp;{$inquiry_context.subject|escape:'htmlall':'UTF-8'}{/if}
            </a>
          {elseif isset($inquiry_context.id)}
            {l s='Inquiry' mod='kloperations'} #{$inquiry_context.id|intval}
          {else}
            {$task->context_type|escape:'htmlall':'UTF-8'} #{$task->context_id|escape:'htmlall':'UTF-8'}
          {/if}
        {else}
          {$task->context_type|escape:'htmlall':'UTF-8'} #{$task->context_id|escape:'htmlall':'UTF-8'}
        {/if}
      </dd>
      <dt>{l s='Priority' mod='kloperations'}</dt>
      <dd>{$task->priority|escape:'htmlall':'UTF-8'}</dd>
    </dl>
  </div>

  <h4>{l s='Payload' mod='kloperations'}</h4>
  {if $payload_pretty}
  <pre>{$payload_pretty|escape:'htmlall':'UTF-8'}</pre>
  {else}
  <p class="text-muted">{l s='No additional payload stored.' mod='kloperations'}</p>
  {/if}

  <h4>{l s='Assignments' mod='kloperations'}</h4>
  {if $assignments}
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>{l s='Assignee' mod='kloperations'}</th>
            <th>{l s='Type' mod='kloperations'}</th>
            <th>{l s='Status' mod='kloperations'}</th>
            <th>{l s='Acknowledged' mod='kloperations'}</th>
            <th>{l s='Completed' mod='kloperations'}</th>
            <th>{l s='Actions' mod='kloperations'}</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$assignments item=assignment}
            <tr>
              <td>{$assignment.display_name|escape:'htmlall':'UTF-8'}</td>
              <td>{if $assignment.assignee_type == 'employee'}{l s='Employee' mod='kloperations'}{else}{l s='Team' mod='kloperations'}{/if}</td>
              <td>{$assignment_status_labels[$assignment.status]|default:$assignment.status|escape:'htmlall':'UTF-8'}</td>
              <td>{if $assignment.acknowledged_at}{$assignment.acknowledged_at|escape:'htmlall':'UTF-8'}{else}<span class="text-muted">{l s='Not yet' mod='kloperations'}</span>{/if}</td>
              <td>{if $assignment.completed_at}{$assignment.completed_at|escape:'htmlall':'UTF-8'}{else}<span class="text-muted">{l s='Not yet' mod='kloperations'}</span>{/if}</td>
              <td>
                <form class="form-inline" method="post" action="{$form_action|escape:'htmlall':'UTF-8'}">
                  <input type="hidden" name="id_kl_operation_task" value="{$task->id|intval}" />
                  <input type="hidden" name="id_assignment" value="{$assignment.id_assignment|intval}" />
                  <div class="form-group">
                    <label class="sr-only" for="assignment-status-{$assignment.id_assignment|intval}">{l s='Status' mod='kloperations'}</label>
                    <select id="assignment-status-{$assignment.id_assignment|intval}" name="assignment_status" class="form-control input-sm">
                      {foreach from=$assignment_status_options item=statusOption}
                        <option value="{$statusOption.id|escape:'htmlall':'UTF-8'}" {if $assignment.status == $statusOption.id}selected="selected"{/if}>{$statusOption.name|escape:'htmlall':'UTF-8'}</option>
                      {/foreach}
                    </select>
                  </div>
                  <button type="submit" name="submitUpdateAssignmentStatus" class="btn btn-default btn-sm">
                    <i class="icon-refresh"></i> {l s='Update' mod='kloperations'}
                  </button>
                  <button type="submit" name="submitDeleteAssignment" class="btn btn-link btn-sm text-danger" onclick="return confirm('{l s='Remove this assignment?' mod='kloperations'}');">
                    <i class="icon-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  {else}
    <p class="text-muted">{l s='No assignments yet.' mod='kloperations'}</p>
  {/if}

  <div class="row">
    <div class="col-lg-6">
      <h4>{l s='Assign to employee' mod='kloperations'}</h4>
      <form method="post" action="{$form_action|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="id_kl_operation_task" value="{$task->id|intval}" />
        <div class="form-group">
          <label for="assignment_id_employee">{l s='Employee' mod='kloperations'}</label>
          <select name="assignment_id_employee" id="assignment_id_employee" class="form-control">
            <option value="">{l s='Select an employee' mod='kloperations'}</option>
            {foreach from=$employees item=employee}
              <option value="{$employee.id|intval}">{$employee.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
          </select>
        </div>
        <button type="submit" name="submitAssignEmployee" class="btn btn-primary">
          <i class="icon-user"></i> {l s='Assign employee' mod='kloperations'}
        </button>
      </form>
    </div>
    <div class="col-lg-6">
      <h4>{l s='Assign to team' mod='kloperations'}</h4>
      <form method="post" action="{$form_action|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="id_kl_operation_task" value="{$task->id|intval}" />
        <div class="form-group">
          <label for="assignment_team_reference">{l s='Team reference' mod='kloperations'}</label>
          <select name="assignment_team_reference" id="assignment_team_reference" class="form-control">
            <option value="">{l s='Select a team' mod='kloperations'}</option>
            {foreach from=$team_options item=team}
              <option value="{$team.id|escape:'htmlall':'UTF-8'}" data-label="{$team.label|escape:'htmlall':'UTF-8'}">{$team.label|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
          </select>
        </div>
        <div class="form-group">
          <label for="assignment_team_label">{l s='Team label' mod='kloperations'}</label>
          <input type="text" class="form-control" id="assignment_team_label" name="assignment_team_label" value="" />
          {if $team_options}
            <p class="help-block">{l s='Selecting a team will populate the label automatically; you can also enter a custom reference and label.' mod='kloperations'}</p>
          {/if}
        </div>
        <button type="submit" name="submitAssignTeam" class="btn btn-primary">
          <i class="icon-group"></i> {l s='Assign team' mod='kloperations'}
        </button>
      </form>
    </div>
  </div>

  <h4>{l s='Notes' mod='kloperations'}</h4>
  {if $notes}
    <ul class="list-unstyled">
      {foreach from=$notes item=note}
        <li>
          <strong>{$note.date_add|escape:'htmlall':'UTF-8'}:</strong>
          {$note.content|escape:'htmlall':'UTF-8'}
        </li>
      {/foreach}
    </ul>
  {else}
    <p class="text-muted">{l s='No notes yet.' mod='kloperations'}</p>
  {/if}
</div>

{literal}
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
  var select = document.getElementById('assignment_team_reference');
  var labelInput = document.getElementById('assignment_team_label');
  if (select && labelInput) {
    select.addEventListener('change', function () {
      var option = select.options[select.selectedIndex];
      if (option && option.getAttribute('data-label')) {
        if (!labelInput.value || labelInput.value === '' || labelInput.value === option.getAttribute('data-label')) {
          labelInput.value = option.getAttribute('data-label');
        }
      }
    });
  }
});
</script>
{/literal}
{/block}
