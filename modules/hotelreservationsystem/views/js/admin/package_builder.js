(function () {
    'use strict';

    function parseJSONAttribute(element, attribute) {
        var raw = element.getAttribute(attribute);
        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (e) {
            console.error('Failed to parse JSON attribute', attribute, e);
            return null;
        }
    }

    function buildLookup(list, keyField, valueField) {
        var map = {};
        if (!Array.isArray(list)) {
            return map;
        }
        for (var i = 0; i < list.length; i += 1) {
            var entry = list[i];
            if (entry && entry.hasOwnProperty(keyField)) {
                map[String(entry[keyField])] = valueField ? entry[valueField] : entry;
            }
        }
        return map;
    }

    function formatNumber(value) {
        if (value === null || value === undefined) {
            return '';
        }
        if (typeof value === 'number' && !isNaN(value)) {
            return value % 1 === 0 ? value.toString() : value.toFixed(2);
        }
        var numeric = parseFloat(value);
        if (!isNaN(numeric)) {
            return numeric % 1 === 0 ? numeric.toString() : numeric.toFixed(2);
        }
        return '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('kl-package-components-app');
        if (!container) {
            return;
        }

        var componentTypes = parseJSONAttribute(container, 'data-component-types') || [];
        var componentTypeLookup = buildLookup(componentTypes, 'value', 'label');
        var ratePlans = parseJSONAttribute(container, 'data-rate-plans') || [];
        var ratePlanLookup = buildLookup(ratePlans, 'value', 'label');
        var i18n = parseJSONAttribute(container, 'data-i18n') || {};
        var components = parseJSONAttribute(container, 'data-components');
        if (!Array.isArray(components)) {
            components = [];
        }

        var hiddenInput = document.getElementById('kl_package_components_payload');
        var tbody = container.querySelector('.kl-package-components__tbody');
        var emptyState = container.querySelector('.kl-package-components__empty');
        var editorForm = container.querySelector('.kl-package-components__editor');
        var editorTitle = container.querySelector('.kl-package-components__editor-title');
        var addButton = container.querySelector('[data-action="add-component"]');
        var cancelButton = container.querySelector('[data-action="cancel-editor"]');

        if (!tbody || !emptyState || !editorForm || !editorTitle || !addButton || !cancelButton || !hiddenInput) {
            return;
        }

        var state = {
            mode: 'add',
            index: null
        };

        function syncHiddenInput() {
            hiddenInput.value = JSON.stringify(components);
        }

        function hideEditor() {
            editorForm.style.display = 'none';
            editorForm.reset();
            editorForm.setAttribute('data-mode', 'add');
            state.mode = 'add';
            state.index = null;
        }

        function setEditorValues(component) {
            var inputs = editorForm.elements;
            inputs.component_type.value = component.component_type || '';
            inputs.reference_code.value = component.reference_code || '';
            inputs.quantity.value = component.quantity !== undefined ? component.quantity : '';
            inputs.unit.value = component.unit || '';
            inputs.price_minor.value = component.price_minor !== undefined ? component.price_minor : '';
            inputs.id_kl_rate_plan.value = component.id_kl_rate_plan !== undefined ? String(component.id_kl_rate_plan) : '0';
            inputs.is_optional.checked = !!component.is_optional;
        }

        function getEditorValues() {
            var inputs = editorForm.elements;
            var type = inputs.component_type.value;
            if (!type) {
                return null;
            }

            var quantityValue = inputs.quantity.value;
            var quantity = quantityValue === '' ? 0 : parseFloat(quantityValue);
            if (isNaN(quantity)) {
                quantity = 0;
            }

            var priceValue = inputs.price_minor.value;
            var price = priceValue === '' ? 0 : parseInt(priceValue, 10);
            if (isNaN(price)) {
                price = 0;
            }

            var planValue = inputs.id_kl_rate_plan.value;
            var planId = planValue === '' ? 0 : parseInt(planValue, 10);
            if (isNaN(planId)) {
                planId = 0;
            }

            return {
                component_type: type,
                reference_code: inputs.reference_code.value || '',
                quantity: quantity,
                unit: inputs.unit.value || '',
                price_minor: price,
                id_kl_rate_plan: planId,
                is_optional: inputs.is_optional.checked ? 1 : 0
            };
        }

        function updateEditorTitle() {
            if (state.mode === 'edit') {
                editorTitle.textContent = i18n.edit_component || 'Edit component';
            } else {
                editorTitle.textContent = i18n.add_component || 'Add component';
            }
        }

        function showEditor(mode, index) {
            state.mode = mode;
            state.index = typeof index === 'number' ? index : null;
            editorForm.setAttribute('data-mode', mode);
            updateEditorTitle();

            if (mode === 'edit' && state.index !== null && components[state.index]) {
                setEditorValues(components[state.index]);
            } else {
                editorForm.reset();
                setEditorValues({
                    component_type: '',
                    reference_code: '',
                    quantity: '',
                    unit: '',
                    price_minor: '',
                    id_kl_rate_plan: 0,
                    is_optional: 0
                });
            }

            editorForm.style.display = 'block';
        }

        function createActionButton(action, label, icon) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-default btn-xs';
            button.setAttribute('data-action', action);
            if (icon) {
                var iconElement = document.createElement('i');
                iconElement.className = icon;
                button.appendChild(iconElement);
                button.appendChild(document.createTextNode(' '));
            }
            button.appendChild(document.createTextNode(label));
            return button;
        }

        function renderTable() {
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }

            if (!components.length) {
                emptyState.style.display = 'block';
            } else {
                emptyState.style.display = 'none';
            }

            components.forEach(function (component, index) {
                var row = document.createElement('tr');

                function appendCell(text, className) {
                    var cell = document.createElement('td');
                    if (className) {
                        cell.className = className;
                    }
                    cell.textContent = text;
                    row.appendChild(cell);
                }

                var typeLabel = componentTypeLookup[component.component_type] || component.component_type || '';
                appendCell(typeLabel);
                appendCell(component.reference_code || '—');
                appendCell(formatNumber(component.quantity));
                appendCell(component.unit || '');
                appendCell(component.price_minor !== undefined && component.price_minor !== null ? String(component.price_minor) : '');

                var planLabel;
                if (component.id_kl_rate_plan && ratePlanLookup.hasOwnProperty(String(component.id_kl_rate_plan))) {
                    planLabel = ratePlanLookup[String(component.id_kl_rate_plan)];
                } else if (component.id_kl_rate_plan) {
                    planLabel = String(component.id_kl_rate_plan);
                } else {
                    planLabel = i18n.use_package_plan || 'Use package plan';
                }
                appendCell(planLabel);

                var optionalLabel = component.is_optional ? (i18n.optional_label || 'Optional') : (i18n.included || 'Included');
                appendCell(optionalLabel);

                var actionsCell = document.createElement('td');
                actionsCell.className = 'text-right';

                var moveUpButton = createActionButton('move-up', '', 'icon-arrow-up');
                var moveDownButton = createActionButton('move-down', '', 'icon-arrow-down');
                var editButton = createActionButton('edit-component', i18n.edit || 'Edit', 'icon-pencil');
                var deleteButton = createActionButton('delete-component', i18n.delete || 'Delete', 'icon-trash');

                moveUpButton.setAttribute('data-index', index);
                moveDownButton.setAttribute('data-index', index);
                editButton.setAttribute('data-index', index);
                deleteButton.setAttribute('data-index', index);

                if (index === 0) {
                    moveUpButton.disabled = true;
                }
                if (index === components.length - 1) {
                    moveDownButton.disabled = true;
                }

                actionsCell.appendChild(moveUpButton);
                actionsCell.appendChild(document.createTextNode(' '));
                actionsCell.appendChild(moveDownButton);
                actionsCell.appendChild(document.createTextNode(' '));
                actionsCell.appendChild(editButton);
                actionsCell.appendChild(document.createTextNode(' '));
                actionsCell.appendChild(deleteButton);

                row.appendChild(actionsCell);
                tbody.appendChild(row);
            });

            syncHiddenInput();
        }

        addButton.addEventListener('click', function () {
            showEditor('add');
        });

        cancelButton.addEventListener('click', function () {
            hideEditor();
        });

        editorForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var payload = getEditorValues();
            if (!payload) {
                editorForm.reportValidity();
                return;
            }

            if (state.mode === 'edit' && state.index !== null && components[state.index]) {
                components[state.index] = payload;
            } else {
                components.push(payload);
            }

            hideEditor();
            renderTable();
        });

        tbody.addEventListener('click', function (event) {
            var target = event.target;
            while (target && target !== tbody && !target.getAttribute('data-action')) {
                target = target.parentElement;
            }

            if (!target || !target.getAttribute) {
                return;
            }

            var action = target.getAttribute('data-action');
            var index = parseInt(target.getAttribute('data-index'), 10);
            if (isNaN(index) || index < 0 || index >= components.length) {
                return;
            }

            if (action === 'move-up' && index > 0) {
                var previous = components[index - 1];
                components[index - 1] = components[index];
                components[index] = previous;
                renderTable();
            } else if (action === 'move-down' && index < components.length - 1) {
                var next = components[index + 1];
                components[index + 1] = components[index];
                components[index] = next;
                renderTable();
            } else if (action === 'edit-component') {
                showEditor('edit', index);
            } else if (action === 'delete-component') {
                var confirmationMessage = i18n.confirm_delete || 'Remove this component?';
                if (window.confirm(confirmationMessage)) {
                    components.splice(index, 1);
                    renderTable();
                }
            }
        });

        hideEditor();
        renderTable();
    });
})();
