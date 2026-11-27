/* Listings – backend: confirm before deleting, show only the options of the chosen field type */
(function () {
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.matches && form.matches('.lst-backend form[data-lst-confirm]') && !window.confirm(form.getAttribute('data-lst-confirm'))) {
            e.preventDefault();
        }
    });

    document.querySelectorAll('.lst-field-form').forEach(function (form) {
        var select = form.querySelector('[data-lst-type]');
        if (!select) {
            return;
        }
        var update = function () {
            form.querySelectorAll('[data-lst-for]').forEach(function (row) {
                row.hidden = row.getAttribute('data-lst-for').split(' ').indexOf(select.value) === -1;
            });
        };
        select.addEventListener('change', update);
        update();
    });
})();

/* Listings – files: refuse too large files before sending, PHP would drop the whole form */
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('form[data-lst-max-post]') || e.defaultPrevented) {
        return;
    }
    var total = 0;
    var problems = [];
    form.querySelectorAll('input[type=file][data-lst-max-bytes]').forEach(function (input) {
        var max = parseInt(input.getAttribute('data-lst-max-bytes'), 10) || 0;
        Array.prototype.forEach.call(input.files || [], function (file) {
            total += file.size;
            if (max > 0 && file.size > max) {
                problems.push(input.getAttribute('data-lst-too-big').replace('%name%', file.name));
            }
        });
    });
    var maxPost = parseInt(form.getAttribute('data-lst-max-post'), 10) || 0;
    if (problems.length === 0 && maxPost > 0 && total > maxPost * 0.95) {
        problems.push(form.getAttribute('data-lst-post-too-big'));
    }
    if (problems.length > 0) {
        e.preventDefault();
        window.alert(problems.join('\n'));
    }
});

/* Listings – sorting by drag and drop or the arrow buttons: [data-lst-sortable] holds the items ([data-lst-key]).
   With a value the container names a hidden input that gets the keys in order (gallery in a form);
   with data-lst-sort-form the order goes to that form by fetch at once (backend lists). */
(function () {
    var changed = function (box) {
        var keys = Array.prototype.map.call(box.querySelectorAll(':scope > [data-lst-key]'), function (el) {
            return el.getAttribute('data-lst-key');
        });
        var input = box.getAttribute('data-lst-sortable') ? document.getElementById(box.getAttribute('data-lst-sortable')) : null;
        if (input) {
            input.value = keys.join(',');
        }
        var form = box.getAttribute('data-lst-sort-form') ? document.getElementById(box.getAttribute('data-lst-sort-form')) : null;
        if (!form) {
            return;
        }
        form.elements.lst_order.value = keys.join(',');
        var live = document.querySelector('[data-lst-live]');
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' } })
            .then(function (res) {
                return res.json().catch(function () { return null; }).then(function (data) {
                    if (window.WbceSession && WbceSession.isDenied(data || res)) {
                        return;
                    }
                    if (live) {
                        live.textContent = data && data.message ? data.message : '';
                    }
                    if (!res.ok || !data || !data.ok) {
                        window.alert(data && data.message ? data.message : 'Error');
                        window.location.reload();
                    }
                });
            });
    };
    var dragged = null;
    document.querySelectorAll('[data-lst-sortable]').forEach(function (box) {
        box.addEventListener('dragstart', function (e) {
            var item = e.target.closest ? e.target.closest('[data-lst-key]') : null;
            if (!item || item.parentNode !== box) {
                return;
            }
            dragged = item;
            item.classList.add('lst-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', item.getAttribute('data-lst-key'));
        });
        box.addEventListener('dragover', function (e) {
            if (!dragged || dragged.parentNode !== box) {
                return;
            }
            e.preventDefault();
            var over = e.target.closest ? e.target.closest('[data-lst-key]') : null;
            if (!over || over === dragged || over.parentNode !== box) {
                return;
            }
            var rect = over.getBoundingClientRect();
            var horizontal = box.tagName !== 'TBODY' && window.getComputedStyle(box).flexDirection === 'row';
            var after = horizontal ? e.clientX > rect.left + rect.width / 2 : e.clientY > rect.top + rect.height / 2;
            box.insertBefore(dragged, after ? over.nextSibling : over);
        });
        box.addEventListener('dragend', function () {
            if (!dragged) {
                return;
            }
            dragged.classList.remove('lst-dragging');
            dragged = null;
            changed(box);
        });
        box.addEventListener('click', function (e) {
            var button = e.target.closest ? e.target.closest('[data-lst-move]') : null;
            var item = button ? button.closest('[data-lst-key]') : null;
            if (!item || item.parentNode !== box) {
                return;
            }
            e.preventDefault();
            var up = button.getAttribute('data-lst-move') === '-1';
            var other = up ? item.previousElementSibling : item.nextElementSibling;
            if (other) {
                box.insertBefore(item, up ? other : other.nextSibling);
                button.focus();
                changed(box);
            }
        });
    });
})();

/* Listings – entries: pick all, bulk actions, approve or reject a row by fetch */
(function () {
    var all = document.querySelector('[data-lst-pick-all]');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('input[name="lst_pick[]"]').forEach(function (box) {
                box.checked = all.checked;
            });
        });
    }
    var bulk = document.querySelector('form[data-lst-bulk]');
    if (bulk) {
        bulk.addEventListener('submit', function (e) {
            var picked = document.querySelectorAll('input[name="lst_pick[]"]:checked').length;
            if (picked === 0) {
                e.preventDefault();
                window.alert(bulk.getAttribute('data-lst-none'));
            } else if (bulk.elements.lst_bulk.value === 'delete' && !window.confirm(bulk.getAttribute('data-lst-confirm-delete').replace('{n}', picked))) {
                e.preventDefault();
            }
        });
    }
    var live = document.querySelector('[data-lst-live]');
    var text = function (html) {
        // the server escapes the title in the message; decode the entities for textContent
        var tmp = document.createElement('textarea');
        tmp.innerHTML = html;
        return tmp.value;
    };
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches || !form.matches('form[data-lst-ajax]') || e.defaultPrevented || !window.fetch) {
            return;
        }
        e.preventDefault();
        var row = form.closest('tr');
        Array.prototype.forEach.call(row.querySelectorAll('form[data-lst-ajax] button'), function (b) { b.disabled = true; });
        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' } })
            .then(function (res) {
                return res.json().catch(function () { return null; }).then(function (data) {
                    if (window.WbceSession && WbceSession.isDenied(data || res)) {
                        return;
                    }
                    if (!res.ok || !data || !data.ok) {
                        // the normal request shows the error
                        form.removeAttribute('data-lst-ajax');
                        form.submit();
                        return;
                    }
                    var status = row.querySelector('.lst-status');
                    if (status) {
                        status.textContent = data.label;
                        status.className = 'lst-status lst-status-' + data.status;
                    }
                    row.classList.remove('lst-row-waiting');
                    Array.prototype.forEach.call(row.querySelectorAll('form[data-lst-ajax]'), function (f) { f.remove(); });
                    if (live) {
                        live.textContent = text(data.message);
                    }
                    var count = document.querySelector('.lst-tabs .lst-count');
                    if (count) {
                        if (data.waiting > 0) {
                            count.textContent = data.waiting;
                        } else {
                            count.remove();
                        }
                    }
                });
            });
    });
})();

/* Listings – preview of a mail template with sample values, in the page (nothing is sent or saved) */
document.addEventListener('click', function (e) {
    var button = e.target.closest ? e.target.closest('[data-lst-mail-preview]') : null;
    var box = button ? button.closest('[data-lst-mail]') : null;
    if (!box) {
        return;
    }
    var sample = {};
    try {
        sample = JSON.parse(box.getAttribute('data-sample')) || {};
    } catch (err) { /* no samples */ }
    var fill = function (text) {
        // one pass, like the server: a value is never searched for placeholders again
        return text.replace(/\{[A-Z_]+\}/g, function (key) {
            return Object.prototype.hasOwnProperty.call(sample, key) ? sample[key] : key;
        });
    };
    var subject = box.querySelector('input[name$="_subject"]').value;
    var text = box.querySelector('textarea[name$="_text"]').value;
    var out = box.querySelector('.lst-mail-preview');
    out.textContent = fill(subject) + '\n' + '─'.repeat(40) + '\n' + fill(text);
    out.hidden = false;
});

/* Listings – preview of list, detail and form in a dialog: the form goes by fetch, the answer into a
   sandboxed frame without scripts. Without JavaScript the button opens the preview in a new window. */
document.addEventListener('click', function (e) {
    var button = e.target.closest ? e.target.closest('button[name="lst_preview"]') : null;
    if (!button || !window.fetch || !window.HTMLDialogElement) {
        return;
    }
    e.preventDefault();
    var form = button.form;
    var data = new FormData(form);
    data.set('lst_preview', button.value);
    var dialog = document.getElementById('lst_preview_dialog');
    if (!dialog) {
        dialog = document.createElement('dialog');
        dialog.id = 'lst_preview_dialog';
        dialog.className = 'lst-preview-dialog';
        dialog.innerHTML = '<form method="dialog" class="lst-preview-bar"><strong></strong><button type="submit" class="lst-button">×</button></form><iframe sandbox title=""></iframe>';
        document.body.appendChild(dialog);
    }
    dialog.querySelector('strong').textContent = button.textContent;
    dialog.querySelector('iframe').title = button.textContent;
    fetch(form.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'fetch-preview' } })
        .then(function (res) { return res.text(); })
        .then(function (html) {
            dialog.querySelector('iframe').srcdoc = html;
            if (!dialog.open) {
                dialog.showModal();
            }
        });
});
