{if isset($kloperations_export_filters)}
<div class="panel" id="kloperations-export-filters">
  <h3>
    <i class="icon-filter"></i>
    {l s='Export filters' mod='kloperations'}
  </h3>
  <form method="get" action="{$kloperations_export_filters.action|escape:'htmlall':'UTF-8'}">
    <div class="row">
      <div class="col-lg-3">
        <div class="form-group">
          <label for="kloperations-export-from">{l s='From date' mod='kloperations'}</label>
          <input type="date" class="form-control" id="kloperations-export-from" name="export_from" value="{$kloperations_export_filters.from|escape:'htmlall':'UTF-8'}" />
        </div>
      </div>
      <div class="col-lg-3">
        <div class="form-group">
          <label for="kloperations-export-to">{l s='To date' mod='kloperations'}</label>
          <input type="date" class="form-control" id="kloperations-export-to" name="export_to" value="{$kloperations_export_filters.to|escape:'htmlall':'UTF-8'}" />
        </div>
      </div>
      <div class="col-lg-3">
        <div class="form-group">
          <label for="kloperations-export-statuses">{l s='Statuses' mod='kloperations'}</label>
          <select id="kloperations-export-statuses" class="form-control" name="export_statuses[]" multiple="multiple" size="4">
            {foreach from=$kloperations_export_filters.status_options item=option}
              <option value="{$option.id|escape:'htmlall':'UTF-8'}"{if in_array($option.id, $kloperations_export_filters.selected_statuses)} selected="selected"{/if}>{$option.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
          </select>
        </div>
      </div>
      <div class="col-lg-3">
        <div class="form-group">
          <label for="kloperations-export-resources">{l s='Resource kinds' mod='kloperations'}</label>
          <select id="kloperations-export-resources" class="form-control" name="export_resource_types[]" multiple="multiple" size="4">
            {foreach from=$kloperations_export_filters.resource_type_options item=resource}
              <option value="{$resource.id|escape:'htmlall':'UTF-8'}"{if in_array($resource.id, $kloperations_export_filters.selected_resource_types)} selected="selected"{/if}>{$resource.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
          </select>
        </div>
      </div>
    </div>
    {if $kloperations_export_filters.has_team_options}
    <div class="row">
      <div class="col-lg-6">
        <div class="form-group">
          <label for="kloperations-export-teams">{l s='Teams' mod='kloperations'}</label>
          <select id="kloperations-export-teams" class="form-control" name="export_teams[]" multiple="multiple" size="4">
            {foreach from=$kloperations_export_filters.team_options item=team}
              <option value="{$team.id|escape:'htmlall':'UTF-8'}"{if in_array($team.id, $kloperations_export_filters.selected_teams)} selected="selected"{/if}>{$team.label|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
          </select>
        </div>
      </div>
    </div>
    {/if}
    <p class="help-block">
      {l s='Use Ctrl (or ⌘ on macOS) to select multiple values. Leave a list empty to include every option.' mod='kloperations'}
    </p>
    {if $kloperations_export_filters.summary}
      <p class="text-muted">
        <strong>{l s='Current scope:' mod='kloperations'}</strong>
        {$kloperations_export_filters.summary|escape:'htmlall':'UTF-8'}
      </p>
    {/if}
    <div class="btn-toolbar">
      <button type="submit" name="export_tasks_csv" value="1" class="btn btn-default">
        <i class="icon-table"></i> {l s='Export CSV' mod='kloperations'}
      </button>
      <button type="submit" name="export_tasks_ics" value="1" class="btn btn-default">
        <i class="icon-calendar"></i> {l s='Export ICS' mod='kloperations'}
      </button>
    </div>
  </form>
</div>
{/if}
