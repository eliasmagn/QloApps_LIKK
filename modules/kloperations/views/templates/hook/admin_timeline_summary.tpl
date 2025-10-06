{if isset($kloperations_timeline_widget)}
  {assign var=widget value=$kloperations_timeline_widget}
  <div class="panel panel-default kloperations-timeline-widget" data-kloperations-widget="timeline">
    <div class="panel-heading">
      <i class="icon-tasks"></i> {l s='Operations summary' mod='kloperations'}
      <a class="btn btn-default btn-xs pull-right" href="{$widget.console_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">
        <i class="icon-external-link"></i> {l s='Open operations console' mod='kloperations'}
      </a>
    </div>
    <div class="panel-body">
      {if $widget.summary.totals.overdue == 0 && $widget.summary.totals.today == 0 && $widget.summary.totals.tomorrow == 0}
        <p class="text-muted">
          {l s='No pending housekeeping or maintenance tasks for today or tomorrow.' mod='kloperations'}
        </p>
      {else}
        <div class="table-responsive">
          <table class="table table-bordered table-striped kloperations-timeline-summary" data-kloperations-widget-table>
            <thead>
              <tr>
                <th>{l s='Resource' mod='kloperations'}</th>
                <th class="text-center">{l s='Overdue' mod='kloperations'}</th>
                <th class="text-center">{l s='Today' mod='kloperations'}</th>
                <th class="text-center">{l s='Tomorrow' mod='kloperations'}</th>
                <th class="text-center">{l s='Total' mod='kloperations'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$widget.summary.resources item=resource}
                <tr data-kloperations-resource="{$resource.key|escape:'htmlall':'UTF-8'}">
                  <td>
                    <strong>{$resource.label|escape:'htmlall':'UTF-8'}</strong>
                    <div>
                      <a class="btn btn-link btn-xs" href="{$resource.link_all|escape:'htmlall':'UTF-8'}">
                        {l s='View tasks' mod='kloperations'}
                      </a>
                    </div>
                  </td>
                  {foreach from=$widget.buckets item=bucket}
                    {assign var=count value=$resource.counts[$bucket]}
                    <td class="text-center">
                      {if $count > 0}
                        <a href="{$resource.links[$bucket]|escape:'htmlall':'UTF-8'}" class="label label-warning">{$count|intval}</a>
                      {else}
                        <span class="text-muted">0</span>
                      {/if}
                    </td>
                  {/foreach}
                  <td class="text-center">
                    {if $resource.total > 0}
                      <span class="label label-default">{$resource.total|intval}</span>
                    {else}
                      <span class="text-muted">0</span>
                    {/if}
                  </td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      {/if}
      <p class="help-block">
        {l s='Snapshot generated at %s (%s).' sprintf=array($widget.summary.generated_at, $widget.summary.timezone) mod='kloperations'}
      </p>
    </div>
  </div>
{/if}
