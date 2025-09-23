/**
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
*/

$(document).ready(function() {

    var calendar = null;
    var calendarInitialized = false;
    var timelineState = {
        loaded: false,
        loading: false,
    };
    var timelineContainer = $('#booking-timeline');
    var timelineLoading = $('#timeline-loading');
    var DAY_MS = 24 * 60 * 60 * 1000;
    var timelineLocale = $('html').attr('lang') || navigator.language || 'en-US';
    var timelineLabels = window.timeline_labels || {
        noData: 'No occupancy data for the selected period.',
        error: 'Unable to load occupancy data.',
        headerRoom: 'Room',
        roomLabel: 'Room %id%',
        summaryTemplate: 'Booked: %booked% | Available: %available% | Partial: %partial% | Unavailable: %unavailable%',
        statusLabels: {
            booked: 'Booked',
            cart: 'In cart',
            unavailable: 'Unavailable',
            partial: 'Partially available',
            available: 'Available',
        },
    };
    var inquiryMode = ($('.panel-booking-timeline').data('kunstort-core-mode') || '').toLowerCase() === 'inquiry';

    if (inquiryMode && timelineLabels.statusLabels) {
        delete timelineLabels.statusLabels.cart;
    }

    function initFullCalendar() {
        if (!$('#fullcalendar').length || calendarInitialized) {
            return;
        }

        calendar = new FullCalendar.Calendar($('#fullcalendar').get(0), {
            initialView: 'dayGridMonth',
            initialDate: initialDate,
            events: {
                url: rooms_booking_url,
                method: 'POST',
                extraParams: function() {
                    removeInitializedTooltips();

                    return $.extend(
                        {
                            ajax: true,
                            action: 'getCalenderData',
                        },
                        getSearchData()
                    );
                },


            },
            eventContent: function(info) {
                if (info.event.extendedProps.is_notification) {
                    return false;
                }
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.is_notification) {
                    if (info.event.extendedProps.data.stats.num_avail > 0) {
                        $(info.el).closest('td').find('.day-info svg circle').attr('fill', '#7EC77B');
                    } else if (info.event.extendedProps.data.stats.num_part_avai > 0) {
                        $(info.el).closest('td').find('.day-info svg circle').attr('fill', '#FFC224');
                    } else if ((info.event.extendedProps.data.stats.num_booked == info.event.extendedProps.data.stats.total_rooms) && info.event.extendedProps.data.stats.total_rooms != 0) {
                        $(info.el).closest('td').find('.day-info svg circle').attr('fill', '#00AFF0');
                    } else {
                        $(info.el).closest('td').find('.day-info svg circle').attr('fill', '#FF3838');
                    }
                    $(info.el).closest('td').find('.day-info').tooltip({
                        content: function()
                        {
                            $('#date-stats-tooltop .tip_date').text(info.event.extendedProps.data.date_format);
                            $.each(info.event.extendedProps.data.stats, function(elem, val) {
                                if (elem == 'num_part_avai') {
                                    $('#date-stats-tooltop').find('.'+elem).hide().find('.tip_element_value').text('');
                                } else {
                                    $('#date-stats-tooltop').find('.'+elem).show().find('.tip_element_value').text(val);
                                }
                            });
                            return $('#date-stats-tooltop').html();
                        },
                        items: "div",
                        trigger : 'hover',
                        show: {
                            delay: 100,
                        },
                        hide: {
                            delay: 300,
                        },
                        open: function(event, ui)
                        {
                            if(event.buttons == 1 || event.buttons == 3){
                                ui.tooltip.remove();
                            }

                            if (typeof(event.originalEvent) === 'undefined') {
                                return false;
                            }

                            var $id = $(ui.tooltip).attr('id');

                            // close any lingering tooltips
                            if ($('div.ui-tooltip').not('#' + $id).length) {
                                return false;
                            }

                        // ajax function to pull in data and add it to the tooltip goes here
                    },
                    close: function(event, ui)
                    {
                        ui.tooltip.hover(function() {
                            $(this).stop(true).fadeTo(300, 1);
                        },
                        function() {
                            $(this).fadeOut('300', function()
                            {
                                $(this).remove();
                            });
                        });
                    }
                });
                info.event.remove();
            } else {
                $(info.el).tooltip({
                    content: function()
                    {
                        $('#date-stats-tooltop .tip_date').text(info.event.extendedProps.data.date_from_format + ' - ' +info.event.extendedProps.data.date_to_format);
                        $.each(info.event.extendedProps.data.stats, function(elem, val) {
                            if (elem == 'num_part_avai') {
                                if (val > 0) {
                                    $('#date-stats-tooltop').find('.'+elem).show().find('.tip_element_value').text(val);
                                } else {
                                    $('#date-stats-tooltop').find('.'+elem).hide().find('.tip_element_value').text('');
                                }
                            } else {
                                $('#date-stats-tooltop').find('.'+elem).find('.tip_element_value').text(val);
                            }
                        });
                        return $('#date-stats-tooltop').html();
                    },
                    items: "div",
                    trigger : 'hover',
                    show: {
                        delay: 100,
                    },
                    hide: {
                        delay: 300,
                    },
                    open: function(event, ui)
                    {
                        if(event.buttons == 1 || event.buttons == 3){
                            ui.tooltip.remove();
                        }

                            if (typeof(event.originalEvent) === 'undefined') {
                                return false;
                            }

                            var $id = $(ui.tooltip).attr('id');

                            // close any lingering tooltips
                            if ($('div.ui-tooltip').not('#' + $id).length) {
                                return false;
                            }

                            // ajax function to pull in data and add it to the tooltip goes here
                        },
                        close: function(event, ui)
                        {
                            ui.tooltip.hover(function() {
                                $(this).stop(true).fadeTo(300, 1);
                            },
                            function() {
                                $(this).fadeOut('300', function()
                                {
                                    $(this).remove();
                                });
                            });
                        }
                    });
                }
            },
            dayCellDidMount: (arg)  => {

                let svg = $('#svg-icon').html();
                $(arg.el).find('.fc-daygrid-day-top').append('<a class="day-info">'+svg+'</a>');
            },
            datesSet: function(arg) {
                if($('.fc-event').tooltip()) {
                    $('.fc-event').tooltip('destroy');
                }
            }
        });
        calendar.render();
        calendarInitialized = true;
    }

    function ensureTimeline(forceReload) {
        if (!timelineContainer.length) {
            return;
        }
        if (forceReload) {
            timelineState.loaded = false;
        }
        if (timelineState.loading || timelineState.loaded) {
            return;
        }
        timelineState.loading = true;
        if (timelineLoading.length) {
            timelineLoading.removeClass('hidden');
        }
        timelineContainer.addClass('hidden').empty();

        $.ajax({
            url: rooms_booking_url,
            method: 'POST',
            dataType: 'json',
            data: $.extend({
                ajax: true,
                action: 'getCalenderData',
                start: $('#search_date_from').val(),
                end: $('#search_date_to').val(),
            }, getSearchData()),
        }).done(function(response) {
            renderTimeline(response);
            timelineState.loaded = true;
        }).fail(function() {
            var message = $('<div class="timeline-empty alert alert-warning"/>').text(timelineLabels.error || 'Unable to load occupancy data.');
            timelineContainer.empty().append(message);
            timelineState.loaded = false;
        }).always(function() {
            timelineState.loading = false;
            timelineContainer.removeClass('hidden');
            if (timelineLoading.length) {
                timelineLoading.addClass('hidden');
            }
        });
    }

    function queueTimelineRefresh() {
        if (!timelineContainer.length) {
            return;
        }
        timelineState.loaded = false;
        if ($('#timeline-tab').hasClass('active')) {
            ensureTimeline(true);
        }
    }

    function computeTimelineRange() {
        var startTs = normalizeDateInput($('#search_date_from').val());
        var endTs = normalizeDateInput($('#search_date_to').val());
        if (startTs === null) {
            startTs = normalizeDateInput(initialDate) || normalizeDateInput((new Date()).toISOString().slice(0, 10));
        }
        if (endTs === null || endTs <= startTs) {
            endTs = startTs + DAY_MS;
        }
        var days = [];
        for (var ts = startTs; ts < endTs; ts += DAY_MS) {
            days.push(ts);
        }
        if (!days.length) {
            days.push(startTs);
        }
        return {
            start: startTs,
            end: endTs,
            days: days,
        };
    }

    function normalizeDateInput(value) {
        if (!value) {
            return null;
        }
        var normalized = value.toString().replace(' ', 'T');
        var date = new Date(normalized);
        if (isNaN(date.getTime())) {
            return null;
        }
        return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
    }

    function formatDateDisplay(ts) {
        try {
            return new Date(ts).toLocaleDateString(timelineLocale, { day: '2-digit', month: 'short' });
        } catch (error) {
            var date = new Date(ts);
            return ('0' + date.getDate()).slice(-2) + '/' + ('0' + (date.getMonth() + 1)).slice(-2);
        }
    }

    function formatDateFromString(value) {
        var ts = normalizeDateInput(value);
        if (ts === null) {
            return value || '';
        }
        return formatDateDisplay(ts);
    }

    function renderTimeline(response) {
        timelineContainer.empty();

        if (!timelineContainer.length) {
            return;
        }

        var events = $.isArray(response) ? response : [];
        var dataset = null;
        $.each(events, function(index, event) {
            if (!event.is_notification && event.data) {
                dataset = event.data;
                return false;
            }
        });

        if (!dataset || !dataset.rm_data || $.isEmptyObject(dataset.rm_data)) {
            var emptyMessage = $('<div class="timeline-empty alert alert-info"/>').text(timelineLabels.noData || 'No occupancy data for the selected period.');
            timelineContainer.append(emptyMessage);
            return;
        }

        var range = computeTimelineRange();
        var table = $('<div class="timeline-table"/>');
        table.append(buildTimelineHeader(range.days));

        var roomTypeKeys = Object.keys(dataset.rm_data).sort(function(a, b) {
            return a.localeCompare(b);
        });

        $.each(roomTypeKeys, function(_, roomTypeKey) {
            var roomType = dataset.rm_data[roomTypeKey];
            table.append(buildRoomTypeHeader(roomType));

            var rooms = buildRoomsForRoomType(roomType, range);
            if (!rooms.length) {
                var emptyRow = $('<div class="timeline-row room-row empty-row"/>');
                emptyRow.append($('<div class="timeline-room-column room-label"/>').text(timelineLabels.noData || 'No occupancy data for the selected period.'));
                var emptyGrid = $('<div class="timeline-grid"/>');
                $.each(range.days, function() {
                    emptyGrid.append($('<div class="timeline-cell status-empty"/>'));
                });
                emptyRow.append(emptyGrid);
                table.append(emptyRow);
            } else {
                $.each(rooms, function(_, room) {
                    table.append(buildRoomRow(room, range));
                });
            }
        });

        timelineContainer.append(table);
    }

    function buildTimelineHeader(days) {
        var header = $('<div class="timeline-row timeline-header-row"/>');
        header.append($('<div class="timeline-room-column header-label"/>').text(timelineLabels.headerRoom || 'Room'));
        var grid = $('<div class="timeline-grid timeline-header-grid"/>');
        $.each(days, function(_, ts) {
            grid.append($('<div class="timeline-cell timeline-day-label"/>').text(formatDateDisplay(ts)));
        });
        header.append(grid);
        return header;
    }

    function buildRoomTypeHeader(roomType) {
        var header = $('<div class="timeline-row room-type-header"/>');
        header.append($('<div class="timeline-room-column room-type-title"/>').text(roomType.name || timelineLabels.headerRoom || 'Room'));
        var stats = roomType.stats || {};
        var summary = (timelineLabels.summaryTemplate || '')
            .replace('%booked%', stats.num_booked || 0)
            .replace('%available%', stats.num_avail || 0)
            .replace('%partial%', stats.num_part_avai || 0)
            .replace('%unavailable%', stats.num_unavail || 0);
        if (!summary.trim()) {
            summary = [
                (timelineLabels.statusLabels.booked || 'Booked') + ': ' + (stats.num_booked || 0),
                (timelineLabels.statusLabels.available || 'Available') + ': ' + (stats.num_avail || 0),
                (timelineLabels.statusLabels.partial || 'Partially available') + ': ' + (stats.num_part_avai || 0),
                (timelineLabels.statusLabels.unavailable || 'Unavailable') + ': ' + (stats.num_unavail || 0)
            ].join(' | ');
        }
        header.append($('<div class="timeline-room-stats"/>').text(summary));
        return header;
    }

    function buildRoomsForRoomType(roomType, range) {
        var roomsMap = {};
        var data = roomType.data || {};

        function ensureRoom(info) {
            if (!info) {
                return null;
            }
            var id = parseInt(info.id_room || info.id, 10);
            if (!id) {
                return null;
            }
            if (!roomsMap[id]) {
                var label = info.room_num || (timelineLabels.roomLabel || 'Room %id%').replace('%id%', id);
                roomsMap[id] = {
                    id: id,
                    label: label,
                    sortValue: label.toString().toLowerCase(),
                    periods: [],
                };
            }
            return roomsMap[id];
        }

        function addPeriod(room, from, to, status, meta) {
            if (!room) {
                return;
            }
            var startTs = normalizeDateInput(from);
            var endTs = normalizeDateInput(to);
            if (startTs === null) {
                startTs = range.start;
            }
            if (endTs === null) {
                endTs = range.end;
            }
            if (endTs <= startTs) {
                endTs = startTs + DAY_MS;
            }
            room.periods.push({
                start: startTs,
                end: endTs,
                status: status,
                meta: meta || {},
            });
        }

        if (data.available) {
            $.each(data.available, function(_, info) {
                ensureRoom(info);
            });
        }

        if (data.booked) {
            $.each(data.booked, function(_, info) {
                var room = ensureRoom(info);
                if (room && $.isArray(info.detail)) {
                    $.each(info.detail, function(__, detail) {
                        addPeriod(room, detail.date_from, detail.date_to, 'booked', detail);
                    });
                }
            });
        }

        if (data.cart_rooms) {
            $.each(data.cart_rooms, function(_, detail) {
                addPeriod(ensureRoom(detail), detail.date_from, detail.date_to, 'cart', detail);
            });
        }

        if (data.unavailable) {
            $.each(data.unavailable, function(_, info) {
                var room = ensureRoom(info);
                if (room && $.isArray(info.detail) && info.detail.length) {
                    $.each(info.detail, function(__, detail) {
                        addPeriod(room, detail.date_from, detail.date_to, 'unavailable', detail);
                    });
                } else {
                    addPeriod(room, null, null, 'unavailable', info);
                }
            });
        }

        if (data.partially_available) {
            $.each(data.partially_available, function(_, partial) {
                var rooms = partial.rooms || partial;
                $.each(rooms, function(__, info) {
                    addPeriod(ensureRoom(info), partial.date_from, partial.date_to, 'partial', partial);
                });
            });
        }

        var rooms = $.map(roomsMap, function(room) {
            return room;
        });

        rooms.sort(function(a, b) {
            return a.sortValue.localeCompare(b.sortValue, undefined, { numeric: true, sensitivity: 'base' });
        });

        return rooms;
    }

    function resolveStatusForDay(room, dayTs, range) {
        var statusPriority = {
            booked: 5,
            cart: 4,
            unavailable: 3,
            partial: 2,
            available: 1,
        };

        var selected = {
            status: 'available',
            meta: {},
            priority: 1,
        };

        $.each(room.periods, function(_, period) {
            var start = period.start != null ? period.start : range.start;
            var end = period.end != null ? period.end : range.end;
            if (dayTs >= start && dayTs < end) {
                var priority = statusPriority[period.status] || 1;
                if (priority >= selected.priority) {
                    selected = {
                        status: period.status,
                        meta: period.meta || {},
                        priority: priority,
                    };
                }
            }
        });

        return selected;
    }

    function buildTooltip(info, dayTs) {
        var labels = timelineLabels.statusLabels || {};
        var label = labels[info.status] || labels.available || 'Available';
        var details = '';
        if (info.meta) {
            if (info.meta.date_from && info.meta.date_to) {
                details = ' (' + formatDateFromString(info.meta.date_from) + ' - ' + formatDateFromString(info.meta.date_to) + ')';
            } else if (info.meta.date_from) {
                details = ' (' + formatDateFromString(info.meta.date_from) + ')';
            } else if (info.meta.room_comment) {
                details = ' – ' + info.meta.room_comment;
            } else if (info.meta.comment) {
                details = ' – ' + info.meta.comment;
            }
        }
        return label + details;
    }

    function buildRoomRow(room, range) {
        var row = $('<div class="timeline-row room-row"/>');
        row.append($('<div class="timeline-room-column room-label"/>').text(room.label));
        var grid = $('<div class="timeline-grid"/>');
        $.each(range.days, function(_, ts) {
            var info = resolveStatusForDay(room, ts, range);
            var cell = $('<div class="timeline-cell"/>').addClass('status-' + info.status);
            cell.attr('title', buildTooltip(info, ts));
            grid.append(cell);
        });
        row.append(grid);
        return row;
    }
    function ensureTimeline(forceReload) {
        if (!timelineContainer.length) {
            return;
        }
        if (forceReload) {
            timelineState.loaded = false;
        }
        if (timelineState.loading || timelineState.loaded) {
            return;
        }
        timelineState.loading = true;
        if (timelineLoading.length) {
            timelineLoading.removeClass('hidden');
        }
        timelineContainer.addClass('hidden').empty();

        $.ajax({
            url: rooms_booking_url,
            method: 'POST',
            dataType: 'json',
            data: $.extend({
                ajax: true,
                action: 'getCalenderData',
                start: $('#search_date_from').val(),
                end: $('#search_date_to').val(),
            }, getSearchData()),
        }).done(function(response) {
            renderTimeline(response);
            timelineState.loaded = true;
        }).fail(function() {
            var message = $('<div class="timeline-empty alert alert-warning"/>').text(timelineLabels.error || 'Unable to load occupancy data.');
            timelineContainer.empty().append(message);
            timelineState.loaded = false;
        }).always(function() {
            timelineState.loading = false;
            timelineContainer.removeClass('hidden');
            if (timelineLoading.length) {
                timelineLoading.addClass('hidden');
            }
        });
    }

    function queueTimelineRefresh() {
        if (!timelineContainer.length) {
            return;
        }
        timelineState.loaded = false;
        if ($('#timeline-tab').hasClass('active')) {
            ensureTimeline(true);
        }
    }

    function computeTimelineRange() {
        var startTs = normalizeDateInput($('#search_date_from').val());
        var endTs = normalizeDateInput($('#search_date_to').val());
        if (startTs === null) {
            startTs = normalizeDateInput(initialDate) || normalizeDateInput((new Date()).toISOString().slice(0, 10));
        }
        if (endTs === null || endTs <= startTs) {
            endTs = startTs + DAY_MS;
        }
        var days = [];
        for (var ts = startTs; ts < endTs; ts += DAY_MS) {
            days.push(ts);
        }
        if (!days.length) {
            days.push(startTs);
        }
        return {
            start: startTs,
            end: endTs,
            days: days,
        };
    }

    function removeInitializedTooltips() {
        $('#fullcalendar a.day-info, #fullcalendar .fc-daygrid-event').each(function () {
            if ($(this).data('ui-tooltip')) {
                $(this).tooltip('destroy');
            }
        });
    }

    function getSearchData()
    {
        return {
            search_id_room_type: $("#search_id_room_type").val(),
            search_id_hotel: $("#search_id_hotel").val(),
            search_date_from: $("#search_date_from").val(),
            search_date_to: $("#search_date_to").val(),
        }
    }

    // toggleSearchFields();
    // $('#booking_product').on('change', function() {
    //     toggleSearchFields();
    // });

    // search form changes
    $('#search_hotel_list').on('click', function(e) {
        if ($('#date_from').val() == '') {
            alert(from_date_cond);
            return false;
        } else if ($('#date_to').val() == '') {
            alert(to_date_cond);
            return false;
        } else if ($('#hotel-id').val() == '') {
            alert(hotel_name_cond);
            return false;
        } else if ($('#num-rooms').val() == '') {
            alert(num_rooms_cond);
            return false;
        }
    });

    $("#from_date").datepicker({
        showOtherMonths: true,
        dateFormat: 'dd-mm-yy',
        altFormat: 'yy-mm-dd',
        altField: '#date_from',
        minDate: PS_BACKDATE_ORDER_ALLOW == 1 ? null : 0,
        beforeShowDay: function (date) {
            return highlightDateBorder($("#from_date").val(), date);
        },
        onSelect: function(selectedDate) {
            let objDateToMin = $.datepicker.parseDate('dd-mm-yy', selectedDate);
            objDateToMin.setDate(objDateToMin.getDate() + 1);

            $('#to_date').datepicker('option', 'minDate', objDateToMin);
        },
    });

    $("#to_date").datepicker({
        showOtherMonths: true,
        dateFormat: 'dd-mm-yy',
        altFormat: 'yy-mm-dd',
        altField: '#date_to',
        beforeShow: function () {
            from_date = $.datepicker.parseDate('dd-mm-yy', $("#from_date").val());
            from_date.setDate(from_date.getDate() + 1);
            $("#to_date").datepicker("option", "minDate", from_date);
        },
        beforeShowDay: function (date) {
            return highlightDateBorder($("#to_date").val(), date);
        },
    });

    $("#id_hotel").on('change', function() {
        var hotel_id = $(this).val();
        if (!isNaN(hotel_id)) {
            if (hotel_id > 0) {
                $.ajax({
                    url: rooms_booking_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajax: true,
                        action: 'getRoomType',
                        hotel_id: hotel_id,
                    },
                    success: function(result) {
                        $("#id_hotel option[value='0']").remove(); // to remove Select hotel option
                        $('#id_room_type').empty();
                        html = "<option value='0'>" + opt_select_all + "</option>";
                        if (result.length) {
                            $.each(result, function(key, value) {
                                html += "<option value='" + value.id_product + "'>" + value.room_type + "</option>";
                            });
                            $('#id_room_type').append(html);
                        } else {
                            if ($('#booking_product').val() == 1) {
                                showErrorMessage(noRoomTypeAvlTxt);
                            }
                            $('#id_room_type').append(html);
                        }
                    }
                });
            }
        }
    });

    function getBookingOccupancyDetails(bookingform)
    {
        let occupancy;
        if (occupancy_required_for_booking) {
            $('.booking_occupancy_wrapper').parent().removeClass('open');
            let selected_occupancy = $(bookingform).find(".occupancy_info_block.selected")
            if (selected_occupancy.length) {
                occupancy = [];
                $(selected_occupancy).each(function(ind, element) {
                    if (parseInt($(element).find('.num_adults').val())) {
                        let child_ages = [];
                        $(element).find('.guest_child_age').each(function(index) {
                            if ($(this).val() > -1) {
                                child_ages.push($(this).val());
                            }
                        });
                        if ($(element).find('.num_children').val()) {
                            if (child_ages.length != $(element).find('.num_children').val()) {
                                $(bookingform).find('.booking_occupancy_wrapper').parent().addClass('open');
                                occupancy = false;
                                return false;
                            }
                        }
                        occupancy.push({
                            'adults': $(element).find('.num_adults').val(),
                            'children': $(element).find('.num_children').val(),
                            'child_ages': child_ages
                        });
                    } else {
                        $(bookingform).find('.booking_occupancy_wrapper').parent().addClass('open');
                        occupancy = false;
                    }
                });
            } else {
                $(bookingform).find('.booking_occupancy_wrapper').parent().addClass('open');
                occupancy = false;
            }
        } else {
            return 1;
        }

        return occupancy;
    }

    // booking form
    $('body').on('click', '.avai_add_cart', function(e) {
        e.preventDefault();
        $current_btn = $(this);
        $current_btn.attr('disabled', 'disabled');
        var search_id_room_type = $("#search_id_room_type").val();
        var search_id_hotel = $("#search_id_hotel").val();
        var search_date_from = $("#search_date_from").val();
        var search_date_to = $("#search_date_to").val();

        var id_prod = $(this).attr('data-id-product');
        var id_room = $(this).attr('data-id-room');
        var id_hotel = $(this).attr('data-id-hotel');
        var date_from = $(this).attr('data-date-from');
        var date_to = $(this).attr('data-date-to');
        var booking_type = $("input[name='bk_type_" + id_room + "']:checked").val();
        var comment = $("#comment_" + id_room).val();
        var btn = $(this);
        $(this).closest('tr').find('.booking_occupancy_wrapper').parent().removeClass('open');
        var occupancy = getBookingOccupancyDetails($(this).closest('tr').find('.booking_occupancy'));

        if (occupancy) {
            $.ajax({
                url: rooms_booking_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'addDataToCart',
                    id_prod: id_prod,
                    id_room: id_room,
                    id_hotel: id_hotel,
                    date_from: date_from,
                    date_to: date_to,
                    occupancy: occupancy,
                    booking_type: booking_type,
                    comment: comment,
                    search_id_hotel: search_id_hotel,
                    search_id_room_type: search_id_room_type,
                    search_date_from: search_date_from,
                    search_date_to: search_date_to,
                    opt: 1,
                },
                success: function(result) {
                    if (result) {
                        if (result.success) {
                            $(".cart_booking_btn").removeAttr('disabled');
                            $current_btn.removeAttr('disabled');
                        }

                        btn.removeClass('btn-primary').removeClass('avai_add_cart').addClass('btn-danger').addClass('avai_delete_cart_data').html(remove);

                        btn.attr('data-id-cart', result.data.id_cart);
                        btn.attr('data-id-cart-book-data', result.data.id_cart_book_data);
                        refreshCartData();
                        refreshStatsData();
                        if (calendar) {
                            calendar.refetchEvents();
                        }
                        queueTimelineRefresh();
                    }
                }
            });
        } else {
            $current_btn.attr('disabled', false);
            setRoomTypeGuestOccupancy($(this).closest('tr').find('.booking_occupancy_wrapper'));
        }
    });

    $('body').on('click', '.par_add_cart', function(e) {
        e.preventDefault();
        $current_btn = $(this);
        $current_btn.attr('disabled', 'disabled');
        var search_id_room_type = $("#search_id_room_type").val();
        var search_id_hotel = $("#search_id_hotel").val();
        var search_date_from = $("#search_date_from").val();
        var search_date_to = $("#search_date_to").val();

        var id_prod = $(this).attr('data-id-product');
        var id_room = $(this).attr('data-id-room');
        var id_hotel = $(this).attr('data-id-hotel');
        var date_from = $(this).attr('data-date-from');
        var date_to = $(this).attr('data-date-to');

        var sub_key = $(this).attr('data-sub-key');
        var booking_type = $(this).closest('tr').find("input.par_bk_type:checked").val();
        var comment = $("#comment_" + id_room + "_" + sub_key).val();
        var btn = $(this);
        $(this).closest('tr').find('.booking_occupancy_wrapper').parent().removeClass('open');
        var occupancy = getBookingOccupancyDetails($(this).closest('tr').find('.booking_occupancy'));

        if (occupancy) {
            $.ajax({
                url: rooms_booking_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'addDataToCart',
                    id_prod: id_prod,
                    id_room: id_room,
                    id_hotel: id_hotel,
                    date_from: date_from,
                    date_to: date_to,
                    occupancy: occupancy,
                    booking_type: booking_type,
                    comment: comment,
                    search_id_hotel: search_id_hotel,
                    search_id_room_type: search_id_room_type,
                    search_date_from: search_date_from,
                    search_date_to: search_date_to,
                    opt: 1,
                },
                success: function(result) {
                    if (result) {
                        if (result.success) {
                            $(".cart_booking_btn").removeAttr('disabled');
                            $current_btn.removeAttr('disabled');
                        }

                        btn.removeClass('btn-primary').removeClass('par_add_cart').addClass('btn-danger').addClass('part_delete_cart_data').html(remove);

                        btn.attr('data-id-cart', result.data.id_cart);
                        btn.attr('data-id-cart-book-data', result.data.id_cart_book_data);
                        refreshCartData();
                        refreshStatsData();
                        if (calendar) {
                            calendar.refetchEvents();
                        }
                        queueTimelineRefresh();
                    }
                }
            });
        } else {
            $current_btn.attr('disabled', false);
            setRoomTypeGuestOccupancy($(this).closest('tr').find('.booking_occupancy_wrapper'));
        }
    });

    $('body').on('click', '.ajax_cart_delete_data', function() {
        //for booking_data
        var search_id_room_type = $("#search_id_room_type").val();
        var search_id_hotel = $("#search_id_hotel").val();
        var search_date_from = $("#search_date_from").val();
        var search_date_to = $("#search_date_to").val();

        var ajax_delete = 1;
        var id_product = $(this).attr('data-id-product');
        var id_cart = $(this).attr('data-id-cart');
        var id_cart_book_data = $(this).attr('data-id-cart-book-data');
        var date_from = $(this).attr('data-date-from');
        var date_to = $(this).attr('data-date-to');
        var id_hotel = $(this).attr('data-id-hotel');
        var btn = $(this);

        $.ajax({
            url: rooms_booking_url,
            type: 'POST',
            dataType: 'json',
            async: false,
            data: {
                ajax: true,
                action: 'addDataToCart',
                id_prod: id_product,
                id_cart: id_cart,
                id_cart_book_data: id_cart_book_data,
                date_from: date_from,
                date_to: date_to,
                id_hotel: id_hotel,
                search_id_hotel: search_id_hotel,
                search_id_room_type: search_id_room_type,
                search_date_from: search_date_from,
                search_date_to: search_date_to,
                ajax_delete: ajax_delete,
                opt: 0,
            },
            success: function(result) {
                if (!result.success) {
                    $(".cart_booking_btn").attr('disabled', 'true');
                } else {
                    $("#htl_rooms_list").empty().append(result.data.room_tpl);
                    refreshCartData();
                    refreshStatsData();
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                    queueTimelineRefresh();
                    initBookingList();
                    var panel_btn = $(".tab-pane tr td button[data-id-cart-book-data='" + id_cart_book_data + "']");

                    panel_btn.attr('data-id-cart', '');
                    panel_btn.attr('data-id-cart-book-data', '');

                    if (panel_btn.hasClass('avai_delete_cart_data'))
                        panel_btn.removeClass('avai_delete_cart_data').addClass('avai_add_cart');
                    else if (panel_btn.hasClass('part_delete_cart_data'))
                        panel_btn.removeClass('part_delete_cart_data').addClass('par_add_cart');

                    panel_btn.removeClass('btn-danger').addClass('btn-primary').html(add_to_cart);
                }
            }
        });
    });

    $('body').on('click', '.avai_delete_cart_data, .part_delete_cart_data', function() {
        var search_id_room_type = $("#search_id_room_type").val();
        var search_id_hotel = $("#search_id_hotel").val();
        var search_date_from = $("#search_date_from").val();
        var search_date_to = $("#search_date_to").val();

        var id_product = $(this).attr('data-id-product');
        var id_cart = $(this).attr('data-id-cart');
        var id_cart_book_data = $(this).attr('data-id-cart-book-data');
        var date_from = $(this).attr('data-date-from');
        var date_to = $(this).attr('data-date-to');
        var id_hotel = $(this).attr('data-id-hotel');
        var btn = $(this);

        $.ajax({
            url: rooms_booking_url,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: true,
                action: 'addDataToCart',
                id_prod: id_product,
                id_cart: id_cart,
                id_cart_book_data: id_cart_book_data,
                date_from: date_from,
                date_to: date_to,
                search_id_hotel: search_id_hotel,
                search_id_room_type: search_id_room_type,
                search_date_from: search_date_from,
                search_date_to: search_date_to,
                id_hotel: id_hotel,
                opt: 0,
            },
            success: function(result) {
                if (result) {
                    if (!(result.success)) {
                        $(".cart_booking_btn").attr('disabled', 'true');
                    }

                    $(".cart_tbody tr td button[data-id-cart-book-data='" + id_cart_book_data + "']").parent().parent().remove();
                    refreshCartData();
                    refreshStatsData();
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                    queueTimelineRefresh();

                    btn.attr('data-id-cart', '');
                    btn.attr('data-id-cart-book-data', '');

                    if (btn.hasClass('avai_delete_cart_data'))
                        btn.removeClass('avai_delete_cart_data').addClass('avai_add_cart');
                    else if (btn.hasClass('part_delete_cart_data'))
                        btn.removeClass('part_delete_cart_data').addClass('par_add_cart');

                    btn.removeClass('btn-danger').addClass('btn-primary').html(add_to_cart);
                }
            }
        });
    });

    $(document).on('click', '.booking_occupancy_wrapper .remove-room-link', function(e) {
        e.preventDefault();
        e.stopPropagation();

		booking_occupancy_inner = $(this).closest('.booking_occupancy_inner');
        $(this).closest('.occupancy_info_block').remove();
		$(booking_occupancy_inner).find('.room_num_wrapper').each(function(key, val) {
            $(this).text(room_txt + ' - '+ (key+1) );
        });
        setRoomTypeGuestOccupancy($(booking_occupancy_inner).closest('.booking_occupancy_wrapper'));
    });

    $(document).on('change', '.num_occupancy', function(e) {
        let elementVal = parseInt($(this).val());
        let current_room_occupancy = 0;
		$(this).closest('.occupancy_info_block').find('.num_occupancy').each(function(){
            current_room_occupancy += parseInt($(this).val());
		});
        let max_guests_in_room = $(this).closest(".booking_occupancy_wrapper").find('.max_guests').val();
		let max_allowed_for_current = (max_guests_in_room - current_room_occupancy) + parseInt($(this).val());
        let haserror = false
        if ($(this).hasClass('num_children')) {
            max_child_in_room = $(this).closest(".booking_occupancy_wrapper").find('.max_children').val();
            if (elementVal > max_child_in_room) {
                $(this).val(max_child_in_room);
                if (elementVal == 1) {
                    showOccupancyError(no_children_allowed_txt, $(this).closest(".occupancy_info_block"));
                    haserror = true;
                } else {
                    showOccupancyError(max_children_txt, $(this).closest(".occupancy_info_block"));
                    haserror = true;
                }
            } else if (elementVal > max_allowed_for_current)  {
                $(this).val(max_allowed_for_current);
                showOccupancyError(max_occupancy_reached_txt, $(this).closest(".occupancy_info_block"));
                haserror = true;
            }
        } else {
            max_adults_in_room = $(this).closest(".booking_occupancy_wrapper").find('.max_adults').val();
            if (elementVal >= max_adults_in_room) {
                $(this).val(max_adults_in_room);
                showOccupancyError(max_adults_txt, $(this).closest(".occupancy_info_block"));
                haserror = true;
            } else if (elementVal > max_allowed_for_current)  {
                $(this).val(max_allowed_for_current);
                showOccupancyError(max_occupancy_reached_txt, $(this).closest(".occupancy_info_block"));
                haserror = true;
            }
        }

        if (!haserror) {
            if ($(this).hasClass('num_children')) {
                let totalChilds = $(this).closest('.occupancy_info_block').find('.guest_child_age').length;
                if (totalChilds < $(this).val()) {
                    $(this).closest('.occupancy_info_block').find('.children_age_info_block').show();
                    while ($(this).closest('.occupancy_info_block').find('.guest_child_age').length < $(this).val()) {
                        var roomBlockIndex = parseInt($(this).closest('.occupancy_info_block').attr('occ_block_index'));
                        var childAgeSelect = '<div class="form-group col-xs-12 col-sm-12 col-md-6 col-lg-6">';
                            childAgeSelect += '<select class="guest_child_age room_occupancies" name="occupancy[' +roomBlockIndex+ '][child_ages][]">';
                                childAgeSelect += '<option value="-1">' + select_age_txt + '</option>';
                                childAgeSelect += '<option value="0">' + under_1_age + '</option>';
                                for (let age = 1; age < max_child_age; age++) {
                                    childAgeSelect += '<option value="'+age+'">'+age+'</option>';
                                }
                            childAgeSelect += '</select>';
                        childAgeSelect += '</div>';
                        $(this).closest('.occupancy_info_block').find('.children_ages').append(childAgeSelect);
                    }
                } else {
                    let child = $(this).val();
                    $(this).closest('.occupancy_info_block').find('.guest_child_age').each(function(ind, element) {
                        if (child <= ind) {
                            $(element).parent().remove();
                        }
                    });
                    if (child == 0) {
                        $(this).closest('.occupancy_info_block').find('.children_age_info_block').hide();
                    }

                }
            }
        }
        setRoomTypeGuestOccupancy($(this).closest('.booking_occupancy_wrapper'));
    });

    var errorMsgTime;
    $('.occupancy-input-errors').parent().hide();
    function showOccupancyError(msg, occupancy_info_block)
    {
        var errorMsgBlock = $(occupancy_info_block).find('.occupancy-input-errors')
        $(errorMsgBlock).html(msg).parent().show('fast');
        clearTimeout(errorMsgTime);
        errorMsgTime = setTimeout(function() {
            $(errorMsgBlock).parent().hide('fast');
        }, 1000);
    }


	$(document).on('click', '.booking_guest_occupancy', function(e) {
		$(this).parent().toggleClass('open');
    });

    $(document).on('click', function(e) {
        if ($('.booking_occupancy_wrapper:visible').length) {
			var occupancy_wrapper = $('.booking_occupancy_wrapper:visible');
			$(occupancy_wrapper).find(".occupancy_info_block").addClass('selected');
            setRoomTypeGuestOccupancy(occupancy_wrapper);

            if (!($(e.target).closest(".booking_occupancy_wrapper").length || $(e.target).closest(".booking_guest_occupancy").length || $(e.target).closest(".avai_add_cart").length || $(e.target).closest(".par_add_cart").length)) {
                let hasErrors = 0;

                let adults = $(occupancy_wrapper).find(".num_adults").map(function(){return $(this).val();}).get();
                let children = $(occupancy_wrapper).find(".num_children").map(function(){return $(this).val();}).get();
                let child_ages = $(occupancy_wrapper).find(".guest_child_age").map(function(){return $(this).val();}).get();

                // start validating above values
                if (!adults.length || (adults.length != children.length)) {
                    hasErrors = 1;
                    showErrorMessage(invalid_occupancy_txt);
                } else {
                    $(occupancy_wrapper).find('.occupancy_count').removeClass('error_border');

                    // validate values of adults and children
                    adults.forEach(function (item, index) {
                        if (isNaN(item) || parseInt(item) < 1) {
                            hasErrors = 1;
                            $(occupancy_wrapper).find(".num_adults").eq(index).closest('.occupancy_count_block').find('.occupancy_count').addClass('error_border');
                        }
                        if (isNaN(children[index])) {
                            hasErrors = 1;
                            $(occupancy_wrapper).find(".num_children").eq(index).closest('.occupancy_count_block').find('.occupancy_count').addClass('error_border');
                        }
                    });

                    // validate values of selected child ages
                    $(occupancy_wrapper).find('.guest_child_age').parent().removeClass('has-error');
                    child_ages.forEach(function (age, index) {
                        age = parseInt(age);
                        if (isNaN(age) || (age < 0) || (age >= parseInt(max_child_age))) {
                            hasErrors = 1;
                            $(occupancy_wrapper).find(".guest_child_age").eq(index).parent().addClass('has-error');
                        }
                    });
                }
                if (hasErrors == 0) {
					$(occupancy_wrapper).parent().removeClass('open');
					// $(occupancy_wrapper).siblings(".booking_guest_occupancy").parent().removeClass('has-error');

                    $(document).trigger( "QloApps:updateRoomOccupancy", [occupancy_wrapper]);
                } else {
                    // $(occupancy_wrapper).siblings(".booking_guest_occupancy").parent().addClass('has-error');
                }
			}
        }
    });

    $('.booking_occupancy_wrapper .add_new_occupancy_btn').on('click', function(e) {
        e.preventDefault();

        var booking_occupancy_wrapper = $(this).closest('.booking_occupancy_wrapper');
        var occupancy_block = '';
        var roomBlockIndex = parseInt($(booking_occupancy_wrapper).find(".occupancy_info_block").last().attr('occ_block_index'));
        roomBlockIndex += 1;


        var countRooms = parseInt($(booking_occupancy_wrapper).find('.occupancy_info_block').length);
        countRooms += 1
        occupancy_block += '<div class="occupancy_info_block col-sm-12" occ_block_index="'+roomBlockIndex+'">';
            occupancy_block += '<div class="occupancy_info_head col-sm-12"><label class="room_num_wrapper">'+ room_txt + ' - ' + countRooms + '</label><a class="remove-room-link pull-right" href="#">' + remove_txt + '</a></div>';
            occupancy_block += '<div class="col-sm-12">';
                occupancy_block += '<div class="row">';
                    occupancy_block += '<div class="form-group col-xs-6 occupancy_count_block">';
                        occupancy_block += '<label>' + adults_txt + '</label>';
                        occupancy_block += '<input type="number" class="form-control num_occupancy num_adults" name="occupancy['+roomBlockIndex+'][adults]" value="1" min="1">';
                    occupancy_block += '</div>';
                    occupancy_block += '<div class="form-group col-xs-6 occupancy_count_block">';
                        occupancy_block += '<label>' + children_txt + '<span class="label-desc-txt"></span></label>';
                        occupancy_block += '<input type="number" class="form-control num_occupancy num_children" name="occupancy['+roomBlockIndex+'][children]" value="0" min="0" max="'+max_child_in_room+'">(' + below_txt + ' ' + max_child_age + ' ' + years_txt + ')';
                    occupancy_block += '</div>';
                occupancy_block += '</div>';
                occupancy_block += '<div class="row children_age_info_block"  style="display:none">';
                    occupancy_block += '<div class="form-group col-sm-12">';
                        occupancy_block += '<label class="">' + all_children_txt + '</label>';
                        occupancy_block += '<div class="">';
                            occupancy_block += '<div class="row children_ages">';
                            occupancy_block += '</div>';
                        occupancy_block += '</div>';
                    occupancy_block += '</div>';
                occupancy_block += '</div>';
            occupancy_block += '</div>';
            occupancy_block += '<hr class="occupancy-info-separator col-sm-12">';
        occupancy_block += '</div>';

        $(booking_occupancy_wrapper).find('.booking_occupancy_inner').append(occupancy_block);

        setRoomTypeGuestOccupancy(booking_occupancy_wrapper);
    });

    function setRoomTypeGuestOccupancy(booking_occupancy_wrapper)
    {
        var adults = 0;
        var children = 0;
        var rooms = $(booking_occupancy_wrapper).find('.occupancy_info_block').length;

        $(booking_occupancy_wrapper).find(".num_adults" ).each(function(key, val) {
            adults += parseInt($(this).val());
        });
        $(booking_occupancy_wrapper).find(".num_children" ).each(function(key, val) {
            children += parseInt($(this).val());
        });

        var guestButtonVal = parseInt(adults) + ' ';
        if (parseInt(adults) > 1) {
            guestButtonVal += adults_txt;
        } else {
            guestButtonVal += adult_txt;
        }
        if (parseInt(children) > 0) {
            if (parseInt(children) > 1) {
                guestButtonVal += ', ' + parseInt(children) + ' ' + children_txt;
            } else {
                guestButtonVal += ', ' + parseInt(children) + ' ' + child_txt;
            }
        }
        if (parseInt(rooms) > 1) {
            guestButtonVal += ', ' + parseInt(rooms) + ' ' + rooms_txt;
        } else {
            guestButtonVal += ', ' + parseInt(rooms) + ' ' + room_txt;
        }
        $(booking_occupancy_wrapper).siblings('.booking_guest_occupancy').find('span').text(guestButtonVal);
    }

    // normal products
    $('body').on('click', '.service_product_add_to_cart', function() {
        var current_btn = $(this);
        current_btn.attr('disabled', 'disabled');
        var search_id_prod = $("#search_id_prod").val();
        var id_product = $(this).data('id-product');
        var id_hotel = $(this).data('id-hotel');
        var qty = current_btn.closest('.product-container').find('.product_quantity').val();
        if (typeof(qty) == 'undefined') {
            qty = 1;
        }

        $.ajax({
            url: rooms_booking_url,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: true,
                action: 'updateProductInCart',
                id_product: id_product,
                id_hotel: id_hotel,
                qty: qty,
                search_id_prod: search_id_prod,
                opt: 'up',
            },
            success: function(result) {
                if (result.status) {
                    $(current_btn).closest('.product-info-container').find('.product_quantity').val('1');
                    refreshCartData();
                    showSuccessMessage(product_added_cart_txt)
                } else if (result.errors) {
                    showErrorMessage(result.errors);
                }
            },
            complete: function() {
                current_btn.attr('disabled', false);
            }
        });
    });

    $('body').on('click', '.service_product_delete', function() {
        var current_btn = $(this);
        current_btn.attr('disabled', 'disabled');
        var id_product = $(this).attr('data-id-product');
        var id_cart = $(this).attr('data-id-cart');
        var id_hotel = $(this).attr('data-id-hotel');


        $.ajax({
            url: rooms_booking_url,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: true,
                action: 'updateProductInCart',
                id_product: id_product,
                id_hotel: id_hotel,
                id_cart: id_cart,
                opt: 0,
            },
            success: function(result) {
                if (result) {
                    refreshCartData();
                }
            }
        });
    });

    function toggleSearchFields()
    {
        if ($('#booking_product').val() == 1) {
            $('#from_date').closest('.form-group').show('fast');
            $('#to_date').closest('.form-group').show('fast');
            $('#id_room_type').closest('.form-group').show('fast');
            $('#search_occupancy').closest('.form-group').show('fast');
        } else {
            $('#from_date').closest('.form-group').hide('fast');
            $('#to_date').closest('.form-group').hide('fast');
            $('#id_room_type').closest('.form-group').hide('fast');
            $('#search_occupancy').closest('.form-group').hide('fast');
        }
    }

    function refreshCartData()
    {
        $.ajax({
            url: rooms_booking_url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                ajax: true,
                action: 'updateCartData',
            },
            success: function(result) {
                if (result) {
                    if (result.cart_content) {
                        $("#cartModal").html(result.cart_content);
                    }
                    $("#cart_record").html(result.total_products_in_cart);

                    if (parseInt(result.total_products_in_cart) > 0) {
                        $('#cart_record').closest('button').removeClass('disabled');
                    } else {
                        $('#cart_record').closest('button').addClass('disabled');
                    }
                }
            }
        });
    }

    function refreshStatsData()
    {
        var formData = new FormData($('form#room-search-form').get(0));
        formData.append('ajax', true);
        formData.append('action', 'getBookingStats');

        $.ajax({
            url: rooms_booking_url,
            type: 'POST',
            dataType: 'JSON',
            data: formData,
            processData: false,
            contentType: false,
            success: function(result) {
                if (result.success) {
                    $(".htl_room_data_cont").html(result.data.stats_panel);
                    initstatstooltip();
                }
            }
        });
    }
    initstatstooltip();

    function initstatstooltip()
    {
        $(".htl_room_data_cont").find('.status-info-tooltip').tooltip({
            trigger : 'hover',
            show: {
                delay: 100,
            },
            hide: {
                delay: 300,
            },
            open: function(event, ui)
            {
                if(event.buttons == 1 || event.buttons == 3){
                    ui.tooltip.remove();
                }

                if (typeof(event.originalEvent) === 'undefined') {
                    return false;
                }

                var $id = $(ui.tooltip).attr('id');

                // close any lingering tooltips
                if ($('div.ui-tooltip').not('#' + $id).length) {
                    return false;
                }

                // ajax function to pull in data and add it to the tooltip goes here
            },
            close: function(event, ui)
            {
                ui.tooltip.hover(function() {
                    $(this).stop(true).fadeTo(300, 1);
                },
                function() {
                    $(this).fadeOut('300', function()
                    {
                        $(this).remove();
                    });
                });
            }
        });
    }

    $('a[href="#calendar-tab"]').on('shown.bs.tab', function () {
        initFullCalendar();
    });

    $('a[href="#timeline-tab"]').on('shown.bs.tab', function () {
        ensureTimeline(false);
    });

    if ($('#calendar-tab').hasClass('active')) {
        initFullCalendar();
    }

    ensureTimeline(false);

    var allotmentTypes = {
		auto: ALLOTMENT_AUTO,
		manual: ALLOTMENT_MANUAL,
	};
    initBookingList();
    function initBookingList() {
        $('.booking_type_comment').hide();
        $('.avai_bk_type').on('change', function() {
            var booking_type = $(this).val();

            if (booking_type == allotmentTypes.auto) {
                $(this).closest('td').find('.booking_type_comment').hide().val('');
            } else if (booking_type == allotmentTypes.manual) {
                $(this).closest('td').find('.booking_type_comment').show();
            }
        });

        $('.par_bk_type').on('change', function() {
            var booking_type = $(this).val();

            if (booking_type == allotmentTypes.auto) {
                $(this).closest('td').find('.booking_type_comment').hide().val('');
            } else if (booking_type == allotmentTypes.manual) {
                $(this).closest('td').find('.booking_type_comment').show();
            }
        });
    }

    function highlightDateBorder(elementVal, date)
    {
        if (elementVal) {
            let selectedDate = $.datepicker.formatDate('dd-mm-yy', date);
            if (selectedDate == elementVal) {
                return [true, "selectedCheckedDate", "Check-In date"];
            } else {
                return [true, ""];
            }
        } else {
            return [true, ""];
        }
    }
});
