{assign var=data value=$kl_package_components}
<div id="kl-package-components-app"
    class="kl-package-components"
    data-component-types="{$data.component_types|json_encode|escape:'htmlall':'UTF-8'}"
    data-rate-plans="{$data.rate_plans|json_encode|escape:'htmlall':'UTF-8'}"
    data-i18n="{$data.i18n|json_encode|escape:'htmlall':'UTF-8'}"
    data-components="{$data.components|json_encode|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="components_payload" id="kl_package_components_payload" value="{$data.components|json_encode|escape:'htmlall':'UTF-8'}" />
    <div class="kl-package-components__toolbar">
        <button type="button" class="btn btn-default" data-action="add-component">
            <i class="icon-plus"></i>
            {$data.i18n.add_component}
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-striped kl-package-components__table">
            <thead>
                <tr>
                    <th>{$data.i18n.component_type}</th>
                    <th>{$data.i18n.reference_code}</th>
                    <th>{$data.i18n.quantity}</th>
                    <th>{$data.i18n.unit}</th>
                    <th>{$data.i18n.price_minor}</th>
                    <th>{$data.i18n.linked_plan}</th>
                    <th>{$data.i18n.optional}</th>
                    <th class="text-right">{$data.i18n.actions}</th>
                </tr>
            </thead>
            <tbody class="kl-package-components__tbody"></tbody>
        </table>
    </div>
    <p class="kl-package-components__empty text-muted">{$data.i18n.no_components}</p>

    <form class="kl-package-components__editor panel" data-mode="add">
        <div class="panel-heading">
            <span class="kl-package-components__editor-title"></span>
        </div>
        <div class="panel-body">
            <input type="hidden" name="component_index" value="" />
            <div class="form-group">
                <label class="control-label">{$data.i18n.component_type}</label>
                <select name="component_type" class="form-control" required>
                    <option value="">--</option>
                    {foreach from=$data.component_types item=type}
                        <option value="{$type.value|escape:'htmlall':'UTF-8'}">{$type.label|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="form-group">
                <label class="control-label">{$data.i18n.reference_code}</label>
                <input type="text" name="reference_code" class="form-control" maxlength="64" />
            </div>
            <div class="form-group">
                <label class="control-label">{$data.i18n.quantity}</label>
                <input type="number" name="quantity" class="form-control" step="0.01" min="0" />
            </div>
            <div class="form-group">
                <label class="control-label">{$data.i18n.unit}</label>
                <input type="text" name="unit" class="form-control" maxlength="16" />
            </div>
            <div class="form-group">
                <label class="control-label">{$data.i18n.price_minor}</label>
                <input type="number" name="price_minor" class="form-control" step="1" />
            </div>
            <div class="form-group">
                <label class="control-label">{$data.i18n.linked_plan}</label>
                <select name="id_kl_rate_plan" class="form-control">
                    {foreach from=$data.rate_plans item=plan}
                        <option value="{$plan.value|escape:'htmlall':'UTF-8'}">{$plan.label|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="is_optional" value="1" />
                    {$data.i18n.optional_label}
                </label>
            </div>
        </div>
        <div class="panel-footer text-right">
            <button type="button" class="btn btn-default" data-action="cancel-editor">{$data.i18n.cancel}</button>
            <button type="submit" class="btn btn-primary" data-action="save-component">{$data.i18n.save_component}</button>
        </div>
    </form>
</div>
