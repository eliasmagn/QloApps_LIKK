(function ($) {
    'use strict';

    function notifySuccess(message) {
        if (!message) {
            return;
        }
        if (typeof window.showSuccessMessage === 'function') {
            window.showSuccessMessage(message);
        } else if ($.growl && $.growl.notice) {
            $.growl.notice({ title: '', message: message });
        } else {
            window.alert(message);
        }
    }

    function notifyError(message) {
        if (!message) {
            return;
        }
        if (typeof window.showErrorMessage === 'function') {
            window.showErrorMessage(message);
        } else if ($.growl && $.growl.error) {
            $.growl.error({ title: '', message: message });
        } else {
            window.alert(message);
        }
    }

    function cleanRichText(value) {
        if (!value) {
            return '';
        }

        return $('<div/>').html(value).text().trim();
    }

    function formatDateTimeLocal(date) {
        if (!(date instanceof Date)) {
            return '';
        }

        var year = date.getFullYear();
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
        var hours = ('0' + date.getHours()).slice(-2);
        var minutes = ('0' + date.getMinutes()).slice(-2);

        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }

    function normaliseDateTimeField(value) {
        if (!value) {
            return '';
        }

        return value.replace('T', ' ');
    }

    function defaultScheduledDate() {
        var date = new Date();
        date.setMinutes(0, 0, 0);
        date.setHours(date.getHours() + 1);

        return date;
    }

    $(function () {
        var config = window.hotelInquiryBoardConfig || {};
        var ajaxUrl = config.ajaxUrl || '';
        var stageStatuses = config.stageStatuses || {};
        var stageLabels = config.stageLabels || {};
        var statusLabels = config.statusLabels || {};
        var operationsEnabled = !!config.operationsEnabled;
        var operationsConsoleUrl = config.operationsConsoleUrl || '';
        var focusInquiryId = parseInt(config.focusInquiryId, 10) || null;

        var $board = $('#hotel-inquiry-board');
        var $createModal = $('#inquiry-create-modal');
        var $noteModal = $('#inquiry-note-modal');
        var $noteForm = $('#inquiry-note-form');
        var $noteHistory = $noteModal.find('.inquiry-note-history');
        var $sidebar = $('#inquiry-detail-sidebar');
        var $sidebarContent = $sidebar.find('[data-role="content"]');
        var $sidebarEmpty = $sidebar.find('[data-role="empty"]');
        var $operationsSection = $sidebar.find('[data-role="operations-section"]');
        var $operationsList = $sidebar.find('[data-role="operations-list"]');
        var $operationsConsoleLink = $sidebar.find('[data-role="operations-console-link"]');
        var $operationModal = $('#inquiry-operation-modal');
        var $operationForm = $('#inquiry-operation-form');
        var $noteOperationsToggle = $noteModal.find('[data-role="note-operation-toggle"]');
        var $noteOperationFields = $noteModal.find('[data-role="note-operation-fields"]');
        var $noteOperationType = $('#inquiry-note-operation-type');
        var $noteOperationPriority = $('#inquiry-note-operation-priority');
        var $noteOperationScheduled = $('#inquiry-note-operation-scheduled');
        var $noteOperationDue = $('#inquiry-note-operation-due');
        var $noteOperationReference = $('#inquiry-note-operation-reference');
        var $noteOperationNote = $('#inquiry-note-operation-note');

        var selectedInquiryId = null;
        var selectedInquiryData = null;
        var selectedInquiryQuotes = [];
        var quotePermissions = { can_download: false, can_email: false };

        var $quotesSection = $sidebar.find('[data-role="quotes-section"]');
        var $quotesList = $sidebar.find('[data-role="quotes-list"]');
        var $quotesEmpty = $sidebar.find('[data-role="quotes-empty"]');

        if (!operationsEnabled && $operationsSection.length) {
            $operationsSection.hide();
        }

        function highlightSelectedCard(id) {
            $board.find('.inquiry-card').removeClass('is-selected');
            if (!id) {
                return;
            }
            $board.find('.inquiry-card[data-inquiry-id="' + id + '"]').addClass('is-selected');
        }

        function renderOperationsList(operations) {
            if (!operationsEnabled || !$operationsSection.length) {
                return;
            }

            $operationsList.empty();
            var tasks = operations && $.isArray(operations.tasks) ? operations.tasks : [];
            if (!tasks.length) {
                $operationsList.append('<p class="text-muted">' + (config.messages && config.messages.operationsNoTasks || 'No operations follow-ups yet.') + '</p>');
            } else {
                $.each(tasks, function (_, task) {
                    var $item = $('<div class="inquiry-operation-item"/>');
                    $item.append($('<div/>').text(task.reference || ''));

                    var metaParts = [];
                    if (task.status) {
                        metaParts.push(statusLabels[task.status] || task.status);
                    }
                    if (task.task_type) {
                        metaParts.push(task.task_type);
                    }
                    if (task.scheduled_for) {
                        metaParts.push(task.scheduled_for);
                    }
                    if (typeof task.priority !== 'undefined' && task.priority !== null) {
                        metaParts.push('P' + task.priority);
                    }
                    if (metaParts.length) {
                        $item.append($('<div class="operation-meta"/>').text(metaParts.join(' • ')));
                    }

                    if (task.view_link) {
                        var viewLabel = (config.messages && config.messages.operationsViewTask) || 'View task';
                        var $link = $('<a class="operation-link" target="_blank" rel="noopener"/>');
                        $link.attr('href', task.view_link);
                        $link.text(viewLabel);
                        $item.append($link);
                    }

                    $operationsList.append($item);
                });
            }

            var consoleLink = operations && operations.list_link ? operations.list_link : operationsConsoleUrl;
            if ($operationsConsoleLink.length) {
                if (consoleLink) {
                    $operationsConsoleLink.attr('href', consoleLink).show();
                } else {
                    $operationsConsoleLink.hide();
                }
            }
        }

        function renderSidebarDetails(inquiry, operations, quotes, permissions) {
            if (!$sidebar.length) {
                return;
            }

            if (!inquiry) {
                selectedInquiryId = null;
                selectedInquiryData = null;
                selectedInquiryQuotes = [];
                quotePermissions = { can_download: false, can_email: false };
                highlightSelectedCard(null);
                $sidebarContent.addClass('hidden');
                $sidebarEmpty.removeClass('hidden');
                renderQuotesList();

                return;
            }

            selectedInquiryId = parseInt(inquiry.id_inquiry, 10) || null;
            selectedInquiryData = inquiry;
            if (Array.isArray(quotes)) {
                selectedInquiryQuotes = quotes;
            }
            if (permissions) {
                quotePermissions = {
                    can_download: !!permissions.can_download,
                    can_email: !!permissions.can_email
                };
            }

            highlightSelectedCard(selectedInquiryId);
            $sidebarEmpty.addClass('hidden');
            $sidebarContent.removeClass('hidden');

            $sidebarContent.find('[data-field="reference"]').text(inquiry.reference || '');
            var statusLabel = statusLabels[inquiry.status] || inquiry.status || '';
            if (statusLabel) {
                $sidebarContent.find('[data-field="status"]').text(statusLabel).show();
            } else {
                $sidebarContent.find('[data-field="status"]').text('').hide();
            }
            $sidebarContent.find('[data-field="subject"]').text(inquiry.subject || '');
            var stageLabel = stageLabels[inquiry.stage] || inquiry.stage || '';
            $sidebarContent.find('[data-field="stage"]').text(stageLabel || '');

            var contactParts = [];
            if (inquiry.requester_name) {
                contactParts.push(inquiry.requester_name);
            }
            if (inquiry.requester_email) {
                contactParts.push('<' + inquiry.requester_email + '>');
            }
            if (inquiry.requester_phone) {
                contactParts.push(inquiry.requester_phone);
            }
            $sidebarContent.find('[data-field="contact"]').text(contactParts.join(' ').trim() || '—');

            var dateText = '';
            if (inquiry.check_in || inquiry.check_out) {
                dateText = (inquiry.check_in || '?') + ' → ' + (inquiry.check_out || '?');
            }
            $sidebarContent.find('[data-field="dates"]').text(dateText || '—');

            var resourcesText = cleanRichText(inquiry.resource_request);
            $sidebarContent.find('[data-field="resources"]').text(resourcesText || '—');

            var internalText = cleanRichText(inquiry.internal_notes);
            $sidebarContent.find('[data-field="internal"]').text(internalText || '—');

            $sidebarContent.find('[data-field="reminder"]').text(inquiry.reminder_at || '—');

            if (operationsEnabled) {
                renderOperationsList(operations);
            }

            renderQuotesList();
        }

        function renderQuotesList() {
            if (!$quotesSection.length) {
                return;
            }

            $quotesList.empty();

            if (!selectedInquiryQuotes || !selectedInquiryQuotes.length) {
                var emptyMessage = (config.messages && config.messages.quotesEmpty) || 'No quotes yet.';
                if ($quotesEmpty.length) {
                    $quotesEmpty.text(emptyMessage).removeClass('hidden');
                }
                return;
            }

            if ($quotesEmpty.length) {
                $quotesEmpty.addClass('hidden');
            }

            selectedInquiryQuotes.forEach(function (quote) {
                var $item = $('<div class="inquiry-quote-item"/>');
                var $header = $('<div class="inquiry-quote-item-header"/>');

                var statusLabel = quote.status_label || quote.status || '';
                if (statusLabel) {
                    $header.append($('<span class="label label-default"/>').text(statusLabel));
                }
                if (quote.total_display) {
                    $header.append($('<strong class="inquiry-quote-total"/>').text(quote.total_display));
                }

                var metaParts = [];
                if (quote.date_add_display) {
                    metaParts.push(quote.date_add_display);
                }
                if (quote.valid_until_display) {
                    var validText = (config.messages && config.messages.quoteValidUntil)
                        ? config.messages.quoteValidUntil.replace('%s', quote.valid_until_display)
                        : quote.valid_until_display;
                    metaParts.push(validText);
                }
                if (quote.author_name) {
                    metaParts.push(quote.author_name);
                }

                var $body = $('<div class="inquiry-quote-item-body"/>');
                if (metaParts.length) {
                    $body.append($('<div class="inquiry-quote-meta"/>').text(metaParts.join(' · ')));
                }

                var $actions = $('<div class="inquiry-quote-actions"/>');
                if (quotePermissions.can_download) {
                    var downloadLabel = (config.messages && config.messages.quoteDownload) || 'Download PDF';
                    var $downloadBtn = $('<button type="button" class="btn btn-default btn-xs" data-action="download-quote"/>')
                        .attr('data-quote-id', quote.id_kl_quote)
                        .text(downloadLabel);
                    $downloadBtn.prepend('<i class="icon-download"></i> ');
                    $actions.append($downloadBtn);
                }
                if (quotePermissions.can_email) {
                    var emailLabel = (config.messages && config.messages.quoteEmail) || 'Email to guest';
                    var $emailBtn = $('<button type="button" class="btn btn-default btn-xs" data-action="email-quote"/>')
                        .attr('data-quote-id', quote.id_kl_quote)
                        .text(emailLabel);
                    $emailBtn.prepend('<i class="icon-envelope"></i> ');
                    $actions.append($emailBtn);
                }

                if ($actions.children().length) {
                    $body.append($actions);
                }

                $item.append($header).append($body);
                $quotesList.append($item);
            });

            bindQuoteActions();
        }

        function bindQuoteActions() {
            if (!$quotesSection.length) {
                return;
            }

            $quotesSection.find('[data-action="download-quote"]').off('click').on('click', function (event) {
                event.preventDefault();
                var quoteId = parseInt($(this).attr('data-quote-id'), 10);
                if (!quoteId) {
                    return;
                }
                requestQuoteDownload(quoteId);
            });

            $quotesSection.find('[data-action="email-quote"]').off('click').on('click', function (event) {
                event.preventDefault();
                var quoteId = parseInt($(this).attr('data-quote-id'), 10);
                if (!quoteId) {
                    return;
                }
                requestQuoteEmail(quoteId);
            });
        }

        function requestQuoteDownload(quoteId) {
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'downloadQuotePdf',
                    id_quote: quoteId
                }
            }).done(function (response) {
                if (response && response.success && response.content_base64) {
                    handleQuoteDownload(response);
                    if (response.quotes) {
                        selectedInquiryQuotes = response.quotes;
                        if (response.quote_permissions) {
                            quotePermissions = {
                                can_download: !!response.quote_permissions.can_download,
                                can_email: !!response.quote_permissions.can_email
                            };
                        }
                        renderQuotesList();
                    }
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.quoteDownloadFailed));
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.quoteDownloadFailed);
            });
        }

        function handleQuoteDownload(response) {
            try {
                var base64 = response.content_base64;
                var binary = window.atob(base64);
                var len = binary.length;
                var bytes = new Uint8Array(len);
                for (var i = 0; i < len; i += 1) {
                    bytes[i] = binary.charCodeAt(i);
                }

                var blob = new Blob([bytes], { type: response.mime || 'application/pdf' });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = response.filename || 'quote.pdf';
                document.body.appendChild(link);
                link.click();
                window.setTimeout(function () {
                    window.URL.revokeObjectURL(link.href);
                    document.body.removeChild(link);
                }, 0);
            } catch (error) {
                notifyError(config.messages && config.messages.quoteDownloadFailed);
            }
        }

        function requestQuoteEmail(quoteId) {
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'emailQuotePdf',
                    id_quote: quoteId
                }
            }).done(function (response) {
                if (response && response.success) {
                    notifySuccess(response.message || (config.messages && config.messages.quoteEmailSuccess));
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.quoteEmailFailed));
                }

                if (response && response.quotes) {
                    selectedInquiryQuotes = response.quotes;
                    if (response.quote_permissions) {
                        quotePermissions = {
                            can_download: !!response.quote_permissions.can_download,
                            can_email: !!response.quote_permissions.can_email
                        };
                    }
                    renderQuotesList();
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.quoteEmailFailed);
            });
        }

        function updateCounts() {
            $board.find('.inquiry-column-body').each(function () {
                var stage = $(this).data('stage-list');
                var count = $(this).find('.inquiry-card').length;
                $board.find('[data-stage-count="' + stage + '"]').text(count);
            });
        }

        function replaceCard($oldCard, html, response) {
            var $newCard = $(html);
            $oldCard.replaceWith($newCard);
            bindCard($newCard);
            updateCounts();
            if (response && response.inquiry && selectedInquiryId === parseInt(response.inquiry.id_inquiry, 10)) {
                renderSidebarDetails(response.inquiry, response.operations, response.quotes, response.quote_permissions);
            }
        }

        function bindCard($card) {
            $card.find('.inquiry-assignee').off('change').on('change', function () {
                var assignedTo = $(this).val();
                var id = $card.data('inquiry-id');
                var stage = $card.closest('.inquiry-column').data('stage');
                $.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        ajax: true,
                        action: 'updateInquiryAssignment',
                        id_inquiry: id,
                        assigned_to: assignedTo,
                        stage: stage
                    }
                }).done(function (response) {
                    if (response && response.success && response.card_html) {
                        replaceCard($card, response.card_html, response);
                        notifySuccess(config.messages && config.messages.assignmentSaved);
                    } else {
                        notifyError(response && response.message ? response.message : (config.messages && config.messages.updateError));
                    }
                }).fail(function () {
                    notifyError(config.messages && config.messages.updateError);
                });
            });

            $card.find('[data-role="notes"]').off('click').on('click', function (event) {
                event.preventDefault();
                selectedInquiryId = $card.data('inquiry-id');
                highlightSelectedCard(selectedInquiryId);
                openNotesModal(selectedInquiryId);
            });

            $card.find('[data-role="reminder"]').off('click').on('click', function (event) {
                event.preventDefault();
                promptReminder($card);
            });

            $card.find('[data-role="inspect"]').off('click').on('click', function (event) {
                event.preventDefault();
                inspectInquiry($card.data('inquiry-id'));
            });
        }

        function handleStageChange($card, targetStage, originalStage) {
            if (targetStage === originalStage) {
                updateCounts();
                return;
            }
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'updateInquiryStage',
                    id_inquiry: $card.data('inquiry-id'),
                    stage: targetStage,
                    status: stageStatuses[targetStage] || ''
                }
            }).done(function (response) {
                if (response && response.success && response.card_html) {
                    replaceCard($card, response.card_html, response);
                } else {
                    revertCard($card, originalStage);
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.updateError));
                }
            }).fail(function () {
                revertCard($card, originalStage);
                notifyError(config.messages && config.messages.updateError);
            });
        }

        function revertCard($card, stage) {
            var $list = $board.find('[data-stage-list="' + stage + '"]');
            $list.append($card);
            updateCounts();
        }

        function promptReminder($card) {
            var id = $card.data('inquiry-id');
            var existing = $card.find('.inquiry-card-reminder').text();
            var promptMessage = config.messages && config.messages.reminderPrompt ? config.messages.reminderPrompt : 'Enter reminder datetime (YYYY-MM-DD HH:MM) or leave blank to clear.';
            var value = window.prompt(promptMessage, existing ? existing.replace(/^.*:/, '').trim() : '');
            if (value === null) {
                return;
            }
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'scheduleInquiryReminder',
                    id_inquiry: id,
                    reminder_at: value
                }
            }).done(function (response) {
                if (response && response.success) {
                    refreshCard(id, function (resp) {
                        if (resp && resp.inquiry && selectedInquiryId === parseInt(id, 10)) {
                            renderSidebarDetails(resp.inquiry, resp.operations, resp.quotes || selectedInquiryQuotes, resp.quote_permissions || quotePermissions);
                        }
                        if (!value) {
                            notifySuccess(config.messages && config.messages.reminderCleared);
                        } else {
                            notifySuccess(config.messages && config.messages.reminderSaved);
                        }
                    });
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.updateError));
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.updateError);
            });
        }

        function openNotesModal(id) {
            $noteForm[0].reset();
            $noteForm.find('input[name="id_inquiry"]').val(id);
            $noteHistory.empty();
            if ($noteOperationsToggle.length) {
                $noteOperationsToggle.prop('checked', false);
                setNoteOperationFieldsActive(false);
            }
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'getInquiryDetails',
                    id_inquiry: id
                }
            }).done(function (response) {
                if (response && response.success) {
                    renderNoteHistory(response.notes || []);
                    if (response.inquiry) {
                        renderSidebarDetails(response.inquiry, response.operations, response.quotes || selectedInquiryQuotes, response.quote_permissions || quotePermissions);
                    }
                    $noteModal.modal('show');
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.updateError));
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.updateError);
            });
        }

        function renderNoteHistory(notes) {
            $noteHistory.empty();
            if (!notes || !notes.length) {
                $noteHistory.append('<p class="text-muted">' + (config.messages && config.messages.noNotes || 'No notes yet.') + '</p>');
                return;
            }
            $.each(notes, function (_, note) {
                var mailBadge = parseInt(note.is_mail, 10) ? '<span class="label label-info">Mail</span> ' : '';
                var employee = note.id_employee ? (' #' + note.id_employee) : '';
                var meta = $('<div class="note-meta"/>').html(mailBadge + (note.date_add || '') + employee);
                var body = $('<div class="note-body"/>').text(note.note || '');
                var container = $('<div class="note-item"/>').append(meta).append(body);
                $noteHistory.append(container);
            });
        }

        function refreshCard(id, callback) {
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'getInquiryDetails',
                    id_inquiry: id
                }
            }).done(function (response) {
                if (response && response.success) {
                    if (response.card_html) {
                        var $card = $board.find('.inquiry-card[data-inquiry-id="' + id + '"]');
                        if ($card.length) {
                            replaceCard($card, response.card_html, response);
                        }
                    } else if (response.inquiry && selectedInquiryId === parseInt(id, 10)) {
                        renderSidebarDetails(response.inquiry, response.operations, response.quotes || selectedInquiryQuotes, response.quote_permissions || quotePermissions);
                    }
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                }
            });
        }

        function inspectInquiry(id) {
            if (!id) {
                return;
            }
            refreshCard(id, function (response) {
                if (response && response.inquiry) {
                    renderSidebarDetails(response.inquiry, response.operations, response.quotes || selectedInquiryQuotes, response.quote_permissions || quotePermissions);
                }
            });
        }

        function buildOperationReference(taskType, inquiry) {
            var base = inquiry && inquiry.reference ? inquiry.reference : 'INQ';
            if (taskType === 'housekeeping_followup' && config.messages && config.messages.operationsHousekeepingReference) {
                return config.messages.operationsHousekeepingReference.replace('%s', base);
            }
            if (taskType === 'maintenance_followup' && config.messages && config.messages.operationsMaintenanceReference) {
                return config.messages.operationsMaintenanceReference.replace('%s', base);
            }
            return base + ' ' + taskType;
        }

        function buildOperationNote(taskType, inquiry) {
            if (!inquiry) {
                return '';
            }
            var lines = [];
            if (config.messages && config.messages.operationsNoteHeader) {
                lines.push(config.messages.operationsNoteHeader.replace('%s', inquiry.reference || '').replace('%s', inquiry.subject || ''));
            } else {
                lines.push((inquiry.reference || '') + ' — ' + (inquiry.subject || ''));
            }
            var requesterParts = [];
            if (inquiry.requester_name) {
                requesterParts.push(inquiry.requester_name);
            }
            if (inquiry.requester_email) {
                requesterParts.push('<' + inquiry.requester_email + '>');
            }
            if (inquiry.requester_phone) {
                requesterParts.push(inquiry.requester_phone);
            }
            if (requesterParts.length) {
                if (config.messages && config.messages.operationsNoteRequester) {
                    lines.push(config.messages.operationsNoteRequester.replace('%s', requesterParts.join(' ')));
                } else {
                    lines.push('Requester: ' + requesterParts.join(' '));
                }
            }
            if (inquiry.check_in || inquiry.check_out) {
                if (config.messages && config.messages.operationsNoteDates) {
                    lines.push(config.messages.operationsNoteDates.replace('%s', inquiry.check_in || '?').replace('%s', inquiry.check_out || '?'));
                } else {
                    lines.push('Stay window: ' + (inquiry.check_in || '?') + ' → ' + (inquiry.check_out || '?'));
                }
            }
            var resources = cleanRichText(inquiry.resource_request);
            if (resources) {
                if (config.messages && config.messages.operationsNoteResources) {
                    lines.push(config.messages.operationsNoteResources.replace('%s', resources));
                } else {
                    lines.push('Requested: ' + resources);
                }
            }

            return lines.join('\n');
        }

        function setNoteOperationFieldsActive(active) {
            if (!$noteOperationFields.length) {
                return;
            }

            if (active) {
                $noteOperationFields.removeClass('hidden');
                $noteOperationFields.find(':input').prop('disabled', false);
                hydrateNoteOperationDefaults();
            } else {
                $noteOperationFields.addClass('hidden');
                $noteOperationFields.find(':input').prop('disabled', true);
                $noteOperationScheduled.val('');
                $noteOperationDue.val('');
                $noteOperationReference.val('').removeData('default-value');
                $noteOperationNote.val('').removeData('default-value');
            }
        }

        function hydrateNoteOperationDefaults() {
            if (!$noteOperationFields.length || !selectedInquiryData) {
                return;
            }

            var taskType = $noteOperationType.val() || 'housekeeping_followup';
            var referenceDefault = buildOperationReference(taskType, selectedInquiryData);
            if (!$noteOperationReference.val()) {
                $noteOperationReference.val(referenceDefault);
            }
            $noteOperationReference.data('default-value', referenceDefault);

            if (!$noteOperationScheduled.val()) {
                $noteOperationScheduled.val(formatDateTimeLocal(defaultScheduledDate()));
            }

            var noteDefault = buildOperationNote(taskType, selectedInquiryData);
            if (!$noteOperationNote.val()) {
                $noteOperationNote.val(noteDefault);
            }
            $noteOperationNote.data('default-value', noteDefault);

            if (!$noteOperationPriority.val()) {
                $noteOperationPriority.val('3');
            }
        }

        function openOperationModal(taskType) {
            if (!operationsEnabled) {
                notifyError(config.messages && config.messages.operationsUnavailable);
                return;
            }
            if (!selectedInquiryId || !selectedInquiryData) {
                notifyError(config.messages && config.messages.updateError);
                return;
            }

            $operationForm[0].reset();
            $operationForm.find('input[name="id_inquiry"]').val(selectedInquiryId);
            $operationForm.find('input[name="task_type"]').val(taskType);
            $operationForm.find('input[name="reference"]').val(buildOperationReference(taskType, selectedInquiryData));
            $operationForm.find('select[name="priority"]').val('3');
            $operationForm.find('input[name="scheduled_for"]').val(formatDateTimeLocal(defaultScheduledDate()));
            $operationForm.find('input[name="due_end"]').val('');
            $operationForm.find('textarea[name="note"]').val(buildOperationNote(taskType, selectedInquiryData));
            $operationForm.find('input[name="log_note"]').prop('checked', true);

            $operationModal.modal('show');
        }

        $board.find('.inquiry-column-body').sortable({
            connectWith: '.inquiry-column-body',
            placeholder: 'inquiry-card-placeholder',
            forcePlaceholderSize: true,
            start: function (event, ui) {
                ui.item.addClass('dragging');
                ui.item.data('original-stage', ui.item.closest('.inquiry-column').data('stage'));
            },
            stop: function (event, ui) {
                ui.item.removeClass('dragging');
                var $card = ui.item;
                var targetStage = $card.closest('.inquiry-column').data('stage');
                var originalStage = $card.data('original-stage');
                handleStageChange($card, targetStage, originalStage);
                $card.removeData('original-stage');
                updateCounts();
            },
            over: function () {
                $(this).addClass('highlight-drop');
            },
            out: function () {
                $(this).removeClass('highlight-drop');
            },
            receive: function () {
                $(this).addClass('highlight-drop');
            },
            deactivate: function () {
                $board.find('.highlight-drop').removeClass('highlight-drop');
            }
        }).disableSelection();

        $board.find('.inquiry-card').each(function () {
            bindCard($(this));
        });

        $('#inquiry-create-trigger').on('click', function (event) {
            event.preventDefault();
            $createModal.find('form')[0].reset();
            $createModal.modal('show');
        });

        $('#inquiry-refresh-board').on('click', function (event) {
            event.preventDefault();
            window.location.reload();
        });

        $('#inquiry-create-form').on('submit', function (event) {
            event.preventDefault();
            var formData = $(this).serializeArray();
            formData.push({ name: 'ajax', value: true });
            formData.push({ name: 'action', value: 'createInquiry' });
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: $.param(formData)
            }).done(function (response) {
                if (response && response.success && response.card_html) {
                    var stage = response.inquiry && response.inquiry.stage ? response.inquiry.stage : 'inbox';
                    var $list = $board.find('[data-stage-list="' + stage + '"]');
                    var $card = $(response.card_html);
                    $list.prepend($card);
                    bindCard($card);
                    updateCounts();
                    notifySuccess(config.messages && config.messages.createSuccess);
                    $createModal.modal('hide');
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.updateError));
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.updateError);
            });
        });

        $noteForm.on('submit', function (event) {
            event.preventDefault();
            var formData = $(this).serializeArray();
            formData.push({ name: 'ajax', value: true });
            formData.push({ name: 'action', value: 'addInquiryNote' });
            formData = $.map(formData, function (field) {
                var value = field.value;
                if ($noteOperationsToggle.length && $noteOperationsToggle.is(':checked') && (field.name === 'operation_scheduled_for' || field.name === 'operation_due_end')) {
                    value = normaliseDateTimeField(value);
                }
                return { name: field.name, value: value };
            });
            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: $.param(formData)
            }).done(function (response) {
                if (response && response.success) {
                    var id = $noteForm.find('input[name="id_inquiry"]').val();
                    if (response.notes) {
                        renderNoteHistory(response.notes);
                    }
                    refreshCard(id, function (resp) {
                        if (resp && resp.inquiry && selectedInquiryId === parseInt(id, 10)) {
                            renderSidebarDetails(resp.inquiry, resp.operations, resp.quotes || selectedInquiryQuotes, resp.quote_permissions || quotePermissions);
                        }
                    });
                    notifySuccess(config.messages && config.messages.noteSaved);
                    if (response.mail_sent) {
                        notifySuccess(config.messages && config.messages.mailNoteSent);
                    } else if (response.mail_error) {
                        notifyError(response.mail_error || (config.messages && config.messages.mailNoteFailed));
                    }
                    if (response.operations_follow_up) {
                        notifySuccess(config.messages && config.messages.operationsTaskCreated);
                    } else if (response.operations_error) {
                        notifyError(response.operations_error || (config.messages && config.messages.operationsTaskFailed));
                    }
                    $noteForm[0].reset();
                    if ($noteOperationsToggle.length) {
                        $noteOperationsToggle.prop('checked', false);
                        setNoteOperationFieldsActive(false);
                    }
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.updateError));
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.updateError);
            });
        });

        $sidebar.on('click', '[data-role="open-notes"]', function (event) {
            event.preventDefault();
            if (selectedInquiryId) {
                openNotesModal(selectedInquiryId);
            }
        });

        $sidebar.on('click', '[data-role="open-reminder"]', function (event) {
            event.preventDefault();
            if (!selectedInquiryId) {
                return;
            }
            var $card = $board.find('.inquiry-card[data-inquiry-id="' + selectedInquiryId + '"]');
            if ($card.length) {
                promptReminder($card);
            }
        });

        $sidebar.on('click', '[data-action="open-operation-modal"]', function (event) {
            event.preventDefault();
            var taskType = $(this).data('taskType');
            openOperationModal(taskType);
        });

        if ($noteOperationsToggle.length) {
            $noteOperationsToggle.on('change', function () {
                var active = $(this).is(':checked');
                setNoteOperationFieldsActive(active);
                if (active) {
                    hydrateNoteOperationDefaults();
                }
            });

            $noteOperationType.on('change', function () {
                if (!$noteOperationsToggle.is(':checked')) {
                    return;
                }
                if (!selectedInquiryData) {
                    return;
                }
                var taskType = $(this).val();
                var referenceDefault = buildOperationReference(taskType, selectedInquiryData);
                if (!$noteOperationReference.val() || $noteOperationReference.val() === $noteOperationReference.data('default-value')) {
                    $noteOperationReference.val(referenceDefault);
                }
                $noteOperationReference.data('default-value', referenceDefault);

                var noteDefault = buildOperationNote(taskType, selectedInquiryData);
                if (!$noteOperationNote.val() || $noteOperationNote.val() === $noteOperationNote.data('default-value')) {
                    $noteOperationNote.val(noteDefault);
                }
                $noteOperationNote.data('default-value', noteDefault);
            });
        }

        $operationForm.on('submit', function (event) {
            event.preventDefault();
            if (!selectedInquiryId) {
                notifyError(config.messages && config.messages.operationsTaskFailed);
                return;
            }
            var formData = $operationForm.serializeArray();
            formData.push({ name: 'ajax', value: true });
            formData.push({ name: 'action', value: 'createInquiryOperationTask' });
            formData = $.map(formData, function (field) {
                var value = field.value;
                if ((field.name === 'scheduled_for' || field.name === 'due_end') && value) {
                    value = normaliseDateTimeField(value);
                }
                return { name: field.name, value: value };
            });

            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: $.param(formData)
            }).done(function (response) {
                if (response && response.success) {
                    notifySuccess(config.messages && config.messages.operationsTaskCreated);
                    $operationModal.modal('hide');
                    refreshCard(selectedInquiryId, function (resp) {
                        var operations = resp && resp.operations ? resp.operations : response.operations;
                        if (resp && resp.inquiry) {
                            renderSidebarDetails(
                                resp.inquiry,
                                operations,
                                resp.quotes || selectedInquiryQuotes,
                                resp.quote_permissions || quotePermissions
                            );
                        } else if (operations) {
                            renderSidebarDetails(selectedInquiryData, operations, selectedInquiryQuotes, quotePermissions);
                        }
                    });
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.operationsTaskFailed));
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.operationsTaskFailed);
            });
        });

        if (focusInquiryId) {
            inspectInquiry(focusInquiryId);
        }
    });
})(jQuery);
