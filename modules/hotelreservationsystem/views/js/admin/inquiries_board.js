(function ($) {
    'use strict';

    function notifySuccess(message) {
        if (!message) {
            return;
        }
        if (typeof window.showSuccessMessage === 'function') {
            window.showSuccessMessage(message);
        } else {
            $.growl ? $.growl.notice({ title: '', message: message }) : alert(message);
        }
    }

    function notifyError(message) {
        if (!message) {
            return;
        }
        if (typeof window.showErrorMessage === 'function') {
            window.showErrorMessage(message);
        } else {
            $.growl ? $.growl.error({ title: '', message: message }) : alert(message);
        }
    }

    $(function () {
        var config = window.hotelInquiryBoardConfig || {};
        var ajaxUrl = config.ajaxUrl || '';
        var stageStatuses = config.stageStatuses || {};
        var $board = $('#hotel-inquiry-board');
        var $createModal = $('#inquiry-create-modal');
        var $noteModal = $('#inquiry-note-modal');
        var $noteForm = $('#inquiry-note-form');
        var $noteHistory = $noteModal.find('.inquiry-note-history');

        function updateCounts() {
            $board.find('.inquiry-column-body').each(function () {
                var stage = $(this).data('stage-list');
                var count = $(this).find('.inquiry-card').length;
                $board.find('[data-stage-count="' + stage + '"]').text(count);
            });
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
                        replaceCard($card, response.card_html);
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
                openNotesModal($card.data('inquiry-id'));
            });

            $card.find('[data-role="reminder"]').off('click').on('click', function (event) {
                event.preventDefault();
                promptReminder($card);
            });
        }

        function replaceCard($oldCard, html) {
            var $newCard = $(html);
            $oldCard.replaceWith($newCard);
            bindCard($newCard);
            updateCounts();
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
                    replaceCard($card, response.card_html);
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
                    refreshCard(id, function () {
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
                if (response && response.success && response.card_html) {
                    var $card = $board.find('.inquiry-card[data-inquiry-id="' + id + '"]');
                    if ($card.length) {
                        replaceCard($card, response.card_html);
                    }
                    if (callback) {
                        callback(response);
                    }
                }
            });
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
            over: function (event, ui) {
                $(this).addClass('highlight-drop');
            },
            out: function (event, ui) {
                $(this).removeClass('highlight-drop');
            },
            receive: function (event, ui) {
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
                    refreshCard(id);
                    notifySuccess(config.messages && config.messages.noteSaved);
                    if (response.mail_sent) {
                        notifySuccess(config.messages && config.messages.mailNoteSent);
                    } else if (response.mail_error) {
                        notifyError(response.mail_error || (config.messages && config.messages.mailNoteFailed));
                    }
                    $noteForm[0].reset();
                } else {
                    notifyError(response && response.message ? response.message : (config.messages && config.messages.updateError));
                }
            }).fail(function () {
                notifyError(config.messages && config.messages.updateError);
            });
        });
    });
})(jQuery);
