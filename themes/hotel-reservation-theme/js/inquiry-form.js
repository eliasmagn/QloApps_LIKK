(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var form = document.getElementById('kl-inquiry-form');
        if (!form) {
            return;
        }

        var stepPanels = [].slice.call(document.querySelectorAll('.inquiry-step-panel'));
        var stepTabs = [].slice.call(document.querySelectorAll('#kl-inquiry-steps li'));
        var currentIndex = 0;

        function showStep(index) {
            currentIndex = index;
            stepPanels.forEach(function (panel, idx) {
                panel.classList.toggle('active', idx === index);
            });
            stepTabs.forEach(function (tab, idx) {
                tab.classList.toggle('active', idx === index);
            });
        }

        function nextStep() {
            if (currentIndex < stepPanels.length - 1) {
                showStep(currentIndex + 1);
            }
        }

        function previousStep() {
            if (currentIndex > 0) {
                showStep(currentIndex - 1);
            }
        }

        stepPanels.forEach(function (panel) {
            var nextButton = panel.querySelector('[data-action="next-step"]');
            var previousButton = panel.querySelector('[data-action="previous-step"]');
            if (nextButton) {
                nextButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    nextStep();
                });
            }
            if (previousButton) {
                previousButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    previousStep();
                });
            }
        });

        showStep(0);

        var endpoint = form.dataset.lookupEndpoint;
        if (!endpoint) {
            return;
        }

        fetch(endpoint + '?action=resources', { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                populateResourceSummary(payload.resources || []);
            })
            .catch(function () {});

        fetch(endpoint + '?action=packages', { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                populatePackageOptions(payload.packages || []);
            })
            .catch(function () {});

        function populateResourceSummary(resources) {
            var list = document.getElementById('resource-suggestions');
            if (!list) {
                return;
            }
            list.innerHTML = '';
            resources.forEach(function (resource) {
                var option = document.createElement('option');
                option.value = resource.code;
                option.label = (resource.name ? resource.name + ' – ' : '') + resource.code;
                list.appendChild(option);
            });
        }

        function populatePackageOptions(packages) {
            var select = document.getElementById('package_preferences');
            if (!select) {
                return;
            }
            packages.forEach(function (pkg) {
                var option = document.createElement('option');
                option.value = pkg.code;
                option.textContent = pkg.name + (pkg.tagline ? ' – ' + pkg.tagline : '');
                option.dataset.featured = pkg.is_featured ? '1' : '0';
                select.appendChild(option);
            });
        }

        var summaryBox = document.querySelector('.inquiry-summary');
        if (!summaryBox) {
            return;
        }

        var summaryFields = ['guest_name', 'guest_email', 'guest_phone', 'arrival_date', 'departure_date', 'party_size_adults', 'party_size_children'];
        summaryFields.forEach(function (field) {
            var input = form.querySelector('[name="' + field + '"]');
            if (!input) {
                return;
            }
            input.addEventListener('input', updateSummary);
        });

        var resourceCheckboxes = [].slice.call(form.querySelectorAll('input[name="resource_interests[]"]'));
        resourceCheckboxes.forEach(function (input) {
            input.addEventListener('change', updateSummary);
        });

        updateSummary();

        function updateSummary() {
            summaryBox.querySelector('[data-summary="guest_name"]').textContent = form.querySelector('[name="guest_name"]').value || '—';
            summaryBox.querySelector('[data-summary="guest_email"]').textContent = form.querySelector('[name="guest_email"]').value || '—';
            summaryBox.querySelector('[data-summary="guest_phone"]').textContent = form.querySelector('[name="guest_phone"]').value || '—';
            summaryBox.querySelector('[data-summary="stay"]').textContent = (form.querySelector('[name="arrival_date"]').value || '—') + ' → ' + (form.querySelector('[name="departure_date"]').value || '—');
            var adults = form.querySelector('[name="party_size_adults"]').value || '0';
            var children = form.querySelector('[name="party_size_children"]').value || '0';
            summaryBox.querySelector('[data-summary="party"]').textContent = adults + ' adults / ' + children + ' children';

            var interests = resourceCheckboxes.filter(function (input) { return input.checked; }).map(function (input) { return input.getAttribute('data-label'); });
            summaryBox.querySelector('[data-summary="interests"]').textContent = interests.length ? interests.join(', ') : '—';
        }
    });
})();
