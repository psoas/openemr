/**
 * Flow Board (interface/patient_tracker/patient_tracker.php):
 * - the Status and Room badges open a menu of the values they can change to, and a choice
 *   is saved through patient_tracker.php (flb_change), then the board is redrawn;
 * - clicking an Appt Date, Appt Time or Order cell (or a repeating appointment's badge)
 *   opens the appointment dialog;
 * - a date or time the row above already shows is hidden, so each appears once;
 * - the To date is kept on or after the From date, and a change of dates, filters or the
 *   date range menu reloads the board in place;
 * - a filter menu is redrawn after each change, so a long choice's "…" doesn't stay;
 * - rows whose status or room changed since the last load flash once, and an empty board
 *   says so, with Clear filters when filters hide every row;
 * - messages show as toasts.
 *
 * window.flowBoardConfig (templates/patient_tracker/flow_board_script.html.twig):
 * { url, csrfToken, dateFormat, today (Y-m-d), firstDayOfWeek (0-6, 0 Sunday),
 *   menus: { status: [{id, title}], room: [...] },
 *   text: { menuLabel: { status, room }, failed, redrawFailed, close, noneMatch, noneForDates,
 *   clearFilters } }
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    David Eschelbacher <psoas@tampabay.rr.com>
 * @copyright Copyright (c) 2026 David Eschelbacher <psoas@tampabay.rr.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

/* exported flowBoardMarkRepeats, flowBoardDateKey, flowBoardKeepRange, flowBoardRedrawChoice,
   flowBoardFormatDate, flowBoardDateRange, flowBoardMatchRange, flowBoardMarkChanges */

/**
 * Hides each Appt Date or Appt Time that is the same as in the shown row above, so a date
 * shows once per day and a time once per time. Rows hidden by the board's filters are skipped.
 */
function flowBoardMarkRepeats(root) {
    if (!root) {
        return;
    }
    var previous = [];
    root.querySelectorAll('tbody tr').forEach(function (row) {
        if (row.style.display === 'none') {
            return;
        }
        var keys = [];
        row.querySelectorAll('[data-flb-repeat]').forEach(function (cell, index) {
            var key = cell.getAttribute('data-flb-repeat');
            cell.classList.toggle('flb-repeat', key === previous[index]);
            keys.push(key);
        });
        previous = keys;
    });
}

/**
 * A date as typed in a date picker, as YYYYMMDD for comparing, or null when unreadable.
 * format is date_display_format: 0 Y-m-d, 1 m/d/Y, 2 d/m/Y.
 */
function flowBoardDateKey(value, format) {
    var parts = String(value || '').trim().split(/[-/.]/);
    if (parts.length !== 3) {
        return null;
    }
    var year, month, day;
    if (Number(format) === 1) {
        month = parts[0]; day = parts[1]; year = parts[2];
    } else if (Number(format) === 2) {
        day = parts[0]; month = parts[1]; year = parts[2];
    } else {
        year = parts[0]; month = parts[1]; day = parts[2];
    }
    if (!/^\d{4}$/.test(year) || !/^\d{1,2}$/.test(month) || !/^\d{1,2}$/.test(day)) {
        return null;
    }
    return year + month.padStart(2, '0') + day.padStart(2, '0');
}

/**
 * When the To date is before the From date, sets it to the From date. Returns whether it changed.
 */
function flowBoardKeepRange(fromInput, toInput, format) {
    var from = flowBoardDateKey(fromInput.value, format);
    var to = flowBoardDateKey(toInput.value, format);
    if (from === null || to === null || to >= from) {
        return false;
    }
    toInput.value = fromInput.value;
    return true;
}

/**
 * A YYYY-MM-DD or YYYYMMDD date as a UTC Date, so day arithmetic ignores daylight saving.
 */
function flowBoardUtcDate(value) {
    var digits = String(value).replace(/-/g, '');
    return new Date(Date.UTC(Number(digits.slice(0, 4)), Number(digits.slice(4, 6)) - 1, Number(digits.slice(6, 8))));
}

function flowBoardIsoDate(date) {
    return date.toISOString().slice(0, 10);
}

function flowBoardAddDays(date, days) {
    return new Date(date.getTime() + days * 86400000);
}

/**
 * A YYYY-MM-DD date as the date pickers show it (format as in flowBoardDateKey).
 */
function flowBoardFormatDate(iso, format) {
    var parts = iso.split('-');
    if (Number(format) === 1) {
        return parts[1] + '/' + parts[2] + '/' + parts[0];
    }
    if (Number(format) === 2) {
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    return iso;
}

/**
 * The From and To dates (YYYY-MM-DD) of a choice in the date range menu, or null.
 * today is YYYY-MM-DD; from and to are the current dates as YYYYMMDD, for Day before and
 * Day after; firstDay is the first day of the week (0 Sunday ... 6 Saturday).
 */
function flowBoardDateRange(choice, today, from, to, firstDay) {
    var day = flowBoardUtcDate(today);
    var weekStart = flowBoardAddDays(day, -((day.getUTCDay() - Number(firstDay) + 7) % 7));
    var year = day.getUTCFullYear();
    var month = day.getUTCMonth();
    function range(start, end) {
        return { from: flowBoardIsoDate(start), to: flowBoardIsoDate(end) };
    }
    function monthRange(offset) {
        return range(new Date(Date.UTC(year, month + offset, 1)), new Date(Date.UTC(year, month + offset + 1, 0)));
    }
    switch (choice) {
        case 'today':
            return range(day, day);
        case 'yesterday':
            return range(flowBoardAddDays(day, -1), flowBoardAddDays(day, -1));
        case 'tomorrow':
            return range(flowBoardAddDays(day, 1), flowBoardAddDays(day, 1));
        case 'dayBefore':
            return from ? range(flowBoardAddDays(flowBoardUtcDate(from), -1), flowBoardAddDays(flowBoardUtcDate(from), -1)) : null;
        case 'dayAfter':
            return to ? range(flowBoardAddDays(flowBoardUtcDate(to), 1), flowBoardAddDays(flowBoardUtcDate(to), 1)) : null;
        case 'thisWeek':
            return range(weekStart, flowBoardAddDays(weekStart, 6));
        case 'lastWeek':
            return range(flowBoardAddDays(weekStart, -7), flowBoardAddDays(weekStart, -1));
        case 'nextWeek':
            return range(flowBoardAddDays(weekStart, 7), flowBoardAddDays(weekStart, 13));
        case 'last7':
            return range(flowBoardAddDays(day, -6), day);
        case 'next7':
            return range(day, flowBoardAddDays(day, 6));
        case 'thisMonth':
            return monthRange(0);
        case 'lastMonth':
            return monthRange(-1);
        case 'nextMonth':
            return monthRange(1);
        default:
            return null;
    }
}

/**
 * The date range menu choice that gives these dates (YYYYMMDD), or '' for custom dates.
 * Day before and Day after depend on the dates themselves, so they never match.
 */
function flowBoardMatchRange(today, from, to, firstDay) {
    var choices = ['today', 'yesterday', 'tomorrow', 'thisWeek', 'lastWeek', 'nextWeek', 'last7', 'next7', 'thisMonth', 'lastMonth', 'nextMonth'];
    for (var i = 0; i < choices.length; i++) {
        var range = flowBoardDateRange(choices[i], today, null, null, firstDay);
        if (range.from.replace(/-/g, '') === from && range.to.replace(/-/g, '') === to) {
            return choices[i];
        }
    }
    return '';
}

/**
 * Marks with flb-changed each row whose status or room differs from previous (a map from
 * row key to what it showed, from the last call), and each new row. Returns the map for the
 * next call. With no previous map, nothing is marked.
 */
function flowBoardMarkChanges(root, previous) {
    var shown = {};
    root.querySelectorAll('tbody tr').forEach(function (row) {
        var cell = row.querySelector('[data-flb-eid]');
        if (!cell) {
            return;
        }
        var key = cell.getAttribute('data-flb-eid') + ' ' + cell.getAttribute('data-flb-date');
        var badges = Array.prototype.map.call(row.querySelectorAll('.flb-badge'), function (badge) {
            return badge.textContent.trim();
        });
        shown[key] = (row.getAttribute('data-apptstatus') || '') + '|' + badges.join('|');
        if (previous && previous[key] !== shown[key]) {
            row.classList.add('flb-changed');
        }
    });
    return shown;
}

/**
 * Redraws a filter menu's shown choice. Some browsers keep the "…" of a long choice after a
 * shorter one is picked; showing the choice clipped for one frame makes them draw it again.
 */
function flowBoardRedrawChoice(select) {
    select.classList.add('flb-redraw');
    select.getBoundingClientRect();
    window.requestAnimationFrame(function () {
        select.classList.remove('flb-redraw');
    });
}

(function () {
    var openMenu = null;

    function config() {
        return window.flowBoardConfig || { menus: {}, text: { menuLabel: {} } };
    }

    // A message at the bottom right that closes itself; in kiosk mode, inside the fullscreen table.
    function toast(message) {
        var host = document.fullscreenElement || document.body;
        var box = host.querySelector('.flb-toasts');
        if (!box) {
            box = document.createElement('div');
            box.className = 'flb-toasts';
            box.setAttribute('aria-live', 'assertive');
            host.appendChild(box);
        }
        var item = document.createElement('div');
        item.className = 'toast show';
        item.setAttribute('role', 'alert');
        var body = document.createElement('div');
        body.className = 'toast-body d-flex align-items-start';
        var text = document.createElement('span');
        text.className = 'mr-2';
        text.textContent = message;
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'close ml-auto';
        close.setAttribute('aria-label', config().text.close || '');
        close.textContent = '\u00d7';
        close.addEventListener('click', function () {
            item.remove();
        });
        body.appendChild(text);
        body.appendChild(close);
        item.appendChild(body);
        box.appendChild(item);
        window.setTimeout(function () {
            item.remove();
        }, 8000);
    }

    function closeMenu(focusBadge) {
        if (!openMenu) {
            return;
        }
        var badge = openMenu.badge;
        openMenu.element.remove();
        badge.setAttribute('aria-expanded', 'false');
        openMenu = null;
        if (focusBadge && badge.isConnected) {
            badge.focus();
        }
    }

    // The badge is disabled until its save is done, so a second choice can't race the first.
    function save(badge, kind, value, title) {
        var settings = config();
        var body = new URLSearchParams();
        body.set('flb_change', '1');
        body.set('csrf_token_form', settings.csrfToken);
        body.set('eid', badge.getAttribute('data-flb-eid'));
        body.set(kind, value);
        if (window.top && typeof window.top.restoreSession === 'function') {
            window.top.restoreSession();
        }
        badge.disabled = true;
        return fetch(settings.url, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Flow Board change failed: ' + response.status);
                }
                // The badge shows what was saved even if the redraw below fails.
                badge.setAttribute('data-flb-value', value);
                badge.textContent = value === '' ? '\u2013' : title;
                badge.disabled = false;
                if (typeof window.refreshMe !== 'function') {
                    return;
                }
                // As the board's timer does: the new table replaces the old in place,
                // with no blank table and loading spinner.
                var redraw = window.refreshMe(true);
                if (redraw && typeof redraw.fail === 'function') {
                    redraw.fail(function () {
                        toast(settings.text.redrawFailed);
                    });
                }
            })
            .catch(function () {
                badge.disabled = false;
                toast(settings.text.failed);
            });
    }

    function showMenu(badge) {
        var kind = badge.getAttribute('data-flb-choice');
        var current = badge.getAttribute('data-flb-value');
        var settings = config();
        var options = settings.menus[kind] || [];

        var menu = document.createElement('div');
        menu.className = 'dropdown-menu show flb-menu';
        menu.setAttribute('role', 'menu');
        menu.setAttribute('aria-label', settings.text.menuLabel[kind] || '');
        options.forEach(function (option) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item' + (String(option.id) === current ? ' active' : '');
            item.setAttribute('role', 'menuitem');
            item.textContent = option.title;
            item.addEventListener('click', function () {
                closeMenu(true);
                if (String(option.id) !== current) {
                    save(badge, kind, String(option.id), option.title);
                }
            });
            menu.appendChild(item);
        });

        // In kiosk mode the table is fullscreen, and only what is inside it shows.
        var host = document.fullscreenElement || document.body;
        var fixed = host !== document.body;
        host.appendChild(menu);
        var rect = badge.getBoundingClientRect();
        menu.style.position = fixed ? 'fixed' : 'absolute';
        menu.style.top = (rect.bottom + (fixed ? 0 : window.scrollY)) + 'px';
        menu.style.left = (rect.left + (fixed ? 0 : window.scrollX)) + 'px';
        badge.setAttribute('aria-expanded', 'true');
        openMenu = { element: menu, badge: badge };
        var first = menu.querySelector('.active') || menu.querySelector('.dropdown-item');
        if (first) {
            first.focus();
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target instanceof Element ? event.target : null;
        if (!target || (openMenu && openMenu.element.contains(target))) {
            return;
        }
        var badge = target.closest('[data-flb-choice]');
        var wasOpen = openMenu && openMenu.badge === badge;
        closeMenu(false);
        if (badge) {
            if (!wasOpen) {
                showMenu(badge);
            }
            return;
        }
        var cell = target.closest('.flb-appt');
        if (cell && typeof window.calendarpopup === 'function') {
            window.calendarpopup(cell.getAttribute('data-flb-eid'), cell.getAttribute('data-flb-date'));
        }
    });

    document.addEventListener('keydown', function (event) {
        if (!openMenu) {
            return;
        }
        var items = Array.prototype.slice.call(openMenu.element.querySelectorAll('.dropdown-item'));
        var index = items.indexOf(document.activeElement);
        if (event.key === 'Escape') {
            event.preventDefault();
            closeMenu(true);
        } else if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && index !== -1) {
            // Only while focus is in the menu; elsewhere the keys keep their own meaning.
            event.preventDefault();
            var step = event.key === 'ArrowDown' ? 1 : -1;
            var next = items[(index + step + items.length) % items.length];
            if (next) {
                next.focus();
            }
        }
    });

    function dateKeys() {
        var from = document.getElementById('form_from_date');
        var to = document.getElementById('form_to_date');
        if (!from || !to) {
            return null;
        }
        var format = config().dateFormat;
        return { from: flowBoardDateKey(from.value, format), to: flowBoardDateKey(to.value, format) };
    }

    // Shows in the date range menu the choice that gives the current dates, if any.
    function showRangeChoice() {
        var menu = document.getElementById('flb_date_range');
        var keys = dateKeys();
        if (!menu || !keys || !keys.from || !keys.to) {
            return;
        }
        var settings = config();
        menu.value = flowBoardMatchRange(settings.today, keys.from, keys.to, settings.firstDayOfWeek);
    }

    // The dates the board shows, so a change event that changed nothing doesn't reload it.
    var loadedDates = null;
    var reloadTimer = null;

    function reload(delay) {
        window.clearTimeout(reloadTimer);
        reloadTimer = window.setTimeout(function () {
            var keys = dateKeys();
            if (keys) {
                loadedDates = keys.from + keys.to;
            }
            if (typeof window.refreshMe === 'function') {
                window.refreshMe(false, true);
            }
        }, delay);
    }

    function onDateChange() {
        var from = document.getElementById('form_from_date');
        var to = document.getElementById('form_to_date');
        if (!from || !to) {
            return;
        }
        flowBoardKeepRange(from, to, config().dateFormat);
        showRangeChoice();
        var keys = dateKeys();
        if (keys.from && keys.to && keys.from + keys.to !== loadedDates) {
            reload(250);
        }
    }

    function setDates(range) {
        var format = config().dateFormat;
        document.getElementById('form_from_date').value = flowBoardFormatDate(range.from, format);
        document.getElementById('form_to_date').value = flowBoardFormatDate(range.to, format);
        onDateChange();
    }

    function filterFields() {
        return ['form_patient_id', 'form_patient_name', 'form_apptcat', 'form_apptstatus', 'form_facility', 'form_provider']
            .map(function (id) {
                return document.getElementById(id);
            })
            .filter(Boolean);
    }

    function filtersActive() {
        return filterFields().some(function (field) {
            return field.value !== '';
        });
    }

    function clearFilters() {
        filterFields().forEach(function (field) {
            field.value = '';
            if (field.tagName === 'SELECT') {
                flowBoardRedrawChoice(field);
            }
        });
        if (typeof window.refineMe === 'function') {
            window.refineMe();
        }
        reload(0);
    }

    // When no row is shown: says so below the table, with Clear filters when filters hide rows.
    function showEmpty(root) {
        var table = root.querySelector('table');
        var message = root.querySelector('.flb-empty');
        if (!table) {
            return;
        }
        var shown = Array.prototype.some.call(table.querySelectorAll('tbody tr'), function (row) {
            return row.style.display !== 'none';
        });
        if (shown) {
            if (message) {
                message.remove();
            }
            return;
        }
        var text = config().text;
        if (!message) {
            message = document.createElement('div');
            message.className = 'flb-empty text-center text-muted p-3';
            table.parentNode.insertAdjacentElement('afterend', message);
        }
        message.textContent = filtersActive() ? text.noneMatch + ' ' : text.noneForDates;
        if (filtersActive()) {
            var clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'btn btn-link btn-sm p-0 align-baseline flb-clear-filters';
            clear.textContent = text.clearFilters;
            message.appendChild(clear);
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target instanceof Element ? event.target : null;
        if (target && target.closest('#flb_clear_filters, .flb-clear-filters')) {
            clearFilters();
        }
    });

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (!target) {
            return;
        }
        if (target.id === 'flb_date_range') {
            var keys = dateKeys();
            var settings = config();
            var range = keys && flowBoardDateRange(target.value, settings.today, keys.from, keys.to, settings.firstDayOfWeek);
            if (range) {
                setDates(range);
            }
        } else if (/^form_(apptcat|apptstatus|facility|provider)$/.test(target.id)) {
            // The board loads only the rows these filters allow, so load again for the new ones.
            reload(0);
        }
    });

    // As the patient boxes are typed in, once typing pauses.
    document.addEventListener('input', function (event) {
        var target = event.target;
        if (target && (target.id === 'form_patient_id' || target.id === 'form_patient_name')) {
            reload(600);
        }
    });

    // A menu placed under its badge would stay put while the table scrolls.
    document.addEventListener('scroll', function (event) {
        if (openMenu && !(event.target instanceof Node && openMenu.element.contains(event.target))) {
            closeMenu(false);
        }
    }, true);

    // The date picker reports a pick through jQuery, which native listeners don't see.
    if (window.jQuery) {
        window.jQuery(document).on('change', '#form_from_date, #form_to_date', onDateChange);
    } else {
        document.addEventListener('change', function (event) {
            if (event.target && (event.target.id === 'form_from_date' || event.target.id === 'form_to_date')) {
                onDateChange();
            }
        });
    }

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (target && target.tagName === 'SELECT' && target.closest('.flb-filters')) {
            flowBoardRedrawChoice(target);
        }
    });

    var shownRows = null;
    var lastBody = null;

    // Called by the board's refineMe() after each redraw or filter change.
    window.flowBoardRedrawn = function (root) {
        // A redraw replaces the badge an open menu belongs to.
        if (openMenu && !openMenu.badge.isConnected) {
            closeMenu(false);
        }
        if (!root) {
            return;
        }
        flowBoardMarkRepeats(root);
        var body = root.querySelector('tbody');
        if (body && body !== lastBody) {
            // A new table: flash what changed since the last one.
            lastBody = body;
            shownRows = flowBoardMarkChanges(root, shownRows);
        }
        showEmpty(root);
    };

    var keys = dateKeys();
    if (keys) {
        loadedDates = keys.from + keys.to;
    }
    showRangeChoice();
})();
