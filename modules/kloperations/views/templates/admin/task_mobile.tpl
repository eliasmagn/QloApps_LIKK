<div class="klops-mobile">
  <div class="panel">
    <div class="panel-heading">
      <h3 class="panel-title"><i class="icon-mobile-phone"></i> {l s='Operations checklist' mod='kloperations'}</h3>
    </div>
    <div class="panel-body">
      <form method="get" action="{$mobile_refresh_link|escape:'htmlall':'UTF-8'}" class="form-inline">
        <input type="hidden" name="mobile_view" value="1" />
        <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />
        <div class="form-group">
          <label class="control-label" for="mobile_range_days">{l s='Show window' mod='kloperations'}</label>
          <select name="mobile_range_days" id="mobile_range_days" class="form-control input-sm">
            {for $day=1 to 7}
              <option value="{$day}" {if $day == $mobile_range_days}selected="selected"{/if}>{$day} {if $day == 1}{l s='day' mod='kloperations'}{else}{l s='days' mod='kloperations'}{/if}</option>
            {/for}
          </select>
        </div>
        <button type="submit" class="btn btn-default btn-sm">
          <i class="icon-refresh"></i> {l s='Refresh' mod='kloperations'}
        </button>
      </form>
    </div>
  </div>

  <h4>{l s='My tasks' mod='kloperations'}</h4>
  {if $my_tasks}
    {foreach from=$my_tasks item=task}
      <div class="panel panel-default klops-task">
        <div class="panel-heading">
          <strong>{$task.reference|escape:'htmlall':'UTF-8'}</strong>
          <span class="label label-info">{$task.status_label|escape:'htmlall':'UTF-8'}</span>
        </div>
        <div class="panel-body">
          <p class="text-muted">{l s='Scheduled' mod='kloperations'}: {$task.scheduled_for|escape:'htmlall':'UTF-8'}{if $task.due_end} · {l s='Due' mod='kloperations'}: {$task.due_end|escape:'htmlall':'UTF-8'}{/if}</p>
          <p>{l s='Assignment status' mod='kloperations'}: <strong>{$task.current_assignment.status_label|escape:'htmlall':'UTF-8'}</strong></p>
          <div class="klops-mobile-actions">
            <form method="post" action="{$mobile_form_action|escape:'htmlall':'UTF-8'}">
              <input type="hidden" name="id_kl_operation_task" value="{$task.id_kl_operation_task|intval}" />
              <input type="hidden" name="id_assignment" value="{$task.current_assignment.id_assignment|intval}" />
              <input type="hidden" name="assignment_status" value="pending" />
              <input type="hidden" name="mobile_range_days" value="{$mobile_range_days|intval}" />
              <input type="hidden" name="return" value="mobile" />
              <button type="submit" name="submitUpdateAssignmentStatus" class="btn btn-default btn-block" {if $task.current_assignment.status == 'pending'}disabled="disabled"{/if}>
                {l s='Reset' mod='kloperations'}
              </button>
            </form>
            <form method="post" action="{$mobile_form_action|escape:'htmlall':'UTF-8'}">
              <input type="hidden" name="id_kl_operation_task" value="{$task.id_kl_operation_task|intval}" />
              <input type="hidden" name="id_assignment" value="{$task.current_assignment.id_assignment|intval}" />
              <input type="hidden" name="assignment_status" value="acknowledged" />
              <input type="hidden" name="mobile_range_days" value="{$mobile_range_days|intval}" />
              <input type="hidden" name="return" value="mobile" />
              <button type="submit" name="submitUpdateAssignmentStatus" class="btn btn-info btn-block" {if $task.current_assignment.status == 'acknowledged'}disabled="disabled"{/if}>
                {l s='Acknowledge' mod='kloperations'}
              </button>
            </form>
            <form method="post" action="{$mobile_form_action|escape:'htmlall':'UTF-8'}">
              <input type="hidden" name="id_kl_operation_task" value="{$task.id_kl_operation_task|intval}" />
              <input type="hidden" name="id_assignment" value="{$task.current_assignment.id_assignment|intval}" />
              <input type="hidden" name="assignment_status" value="in_progress" />
              <input type="hidden" name="mobile_range_days" value="{$mobile_range_days|intval}" />
              <input type="hidden" name="return" value="mobile" />
              <button type="submit" name="submitUpdateAssignmentStatus" class="btn btn-primary btn-block" {if $task.current_assignment.status == 'in_progress'}disabled="disabled"{/if}>
                {l s='Start' mod='kloperations'}
              </button>
            </form>
            <form method="post" action="{$mobile_form_action|escape:'htmlall':'UTF-8'}">
              <input type="hidden" name="id_kl_operation_task" value="{$task.id_kl_operation_task|intval}" />
              <input type="hidden" name="id_assignment" value="{$task.current_assignment.id_assignment|intval}" />
              <input type="hidden" name="assignment_status" value="completed" />
              <input type="hidden" name="mobile_range_days" value="{$mobile_range_days|intval}" />
              <input type="hidden" name="return" value="mobile" />
              <button type="submit" name="submitUpdateAssignmentStatus" class="btn btn-success btn-block" {if $task.current_assignment.status == 'completed'}disabled="disabled"{/if}>
                {l s='Complete' mod='kloperations'}
              </button>
            </form>
            <form method="post" action="{$mobile_form_action|escape:'htmlall':'UTF-8'}" onsubmit="return confirm('{l s='Release this task?' mod='kloperations'}');">
              <input type="hidden" name="id_kl_operation_task" value="{$task.id_kl_operation_task|intval}" />
              <input type="hidden" name="id_assignment" value="{$task.current_assignment.id_assignment|intval}" />
              <input type="hidden" name="mobile_range_days" value="{$mobile_range_days|intval}" />
              <input type="hidden" name="return" value="mobile" />
              <button type="submit" name="submitDeleteAssignment" class="btn btn-link btn-block text-danger">
                {l s='Release' mod='kloperations'}
              </button>
            </form>
          </div>
        </div>
      </div>
    {/foreach}
  {else}
    <p class="text-muted">{l s='No tasks assigned to you in this window.' mod='kloperations'}</p>
  {/if}

  <h4>{l s='Unassigned tasks' mod='kloperations'}</h4>
  {if $unassigned_tasks}
    {foreach from=$unassigned_tasks item=task}
      <div class="panel panel-default klops-task">
        <div class="panel-heading">
          <strong>{$task.reference|escape:'htmlall':'UTF-8'}</strong>
          <span class="label label-default">{$status_labels[$task.status]|default:$task.status|escape:'htmlall':'UTF-8'}</span>
        </div>
        <div class="panel-body">
          <p class="text-muted">{l s='Scheduled' mod='kloperations'}: {$task.scheduled_for|escape:'htmlall':'UTF-8'}{if $task.due_end} · {l s='Due' mod='kloperations'}: {$task.due_end|escape:'htmlall':'UTF-8'}{/if}</p>
          <p>{l s='Summary' mod='kloperations'}: {$task.assignment_summary|escape:'htmlall':'UTF-8'}</p>
          <form method="post" action="{$mobile_form_action|escape:'htmlall':'UTF-8'}">
            <input type="hidden" name="id_kl_operation_task" value="{$task.id_kl_operation_task|intval}" />
            <input type="hidden" name="mobile_range_days" value="{$mobile_range_days|intval}" />
            <input type="hidden" name="return" value="mobile" />
            <button type="submit" name="submitClaimTask" class="btn btn-primary btn-block">
              <i class="icon-hand-right"></i> {l s='Claim task' mod='kloperations'}
            </button>
          </form>
        </div>
      </div>
    {/foreach}
  {else}
    <p class="text-muted">{l s='No unassigned tasks in this window.' mod='kloperations'}</p>
  {/if}
</div>

{literal}
<style>
.klops-mobile .panel { margin-bottom: 15px; }
.klops-mobile .klops-task .panel-heading { display: flex; align-items: center; justify-content: space-between; }
.klops-mobile-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.klops-mobile-actions form { flex: 1 1 45%; }
.klops-mobile-actions button { width: 100%; }
@media (min-width: 768px) {
  .klops-mobile-actions form { flex: 1 1 18%; }
}
</style>
{/literal}
