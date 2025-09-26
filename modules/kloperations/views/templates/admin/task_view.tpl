{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
<div class="panel">
  <h3><i class="icon-tasks"></i> {l s='Operation task' mod='kloperations'}: {$task->reference|escape:'htmlall':'UTF-8'}</h3>
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
      <dd>{$task->context_type|escape:'htmlall':'UTF-8'} #{$task->context_id|escape:'htmlall':'UTF-8'}</dd>
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
{/block}
