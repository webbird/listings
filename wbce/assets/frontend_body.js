/* Listings – frontend: confirm before an owner deletes an entry */
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.matches && form.matches('.mod_listings form[data-lst-confirm]') && !window.confirm(form.getAttribute('data-lst-confirm'))) {
        e.preventDefault();
    }
});

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

/* Listings – the small editor of HTML fields in the owner's form. Without JavaScript the textarea stays;
   the server reduces whatever arrives to the allowed tags. */
(function () {
    document.querySelectorAll('[data-lst-html]').forEach(function (box) {
        var textarea = box.querySelector('textarea');
        var area = box.querySelector('.lst-html-area');
        var toolbar = box.querySelector('.lst-html-toolbar');
        if (!textarea || !area || !toolbar || !document.execCommand) {
            return;
        }
        // the textarea holds HTML the server cleaned already
        area.innerHTML = textarea.value;
        var label = document.querySelector('label[for="' + textarea.id + '"]');
        if (label) {
            label.id = textarea.id + '_label';
            label.addEventListener('click', function () { area.focus(); });
        }
        var sync = function () {
            textarea.value = area.textContent.trim() === '' ? '' : area.innerHTML;
        };
        textarea.hidden = true;
        textarea.required = false;
        area.hidden = false;
        toolbar.hidden = false;
        try {
            document.execCommand('defaultParagraphSeparator', false, 'p');
        } catch (e) { /* older browsers */ }
        area.addEventListener('input', sync);
        // pasted text arrives without the formatting of the source
        area.addEventListener('paste', function (e) {
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            e.preventDefault();
            document.execCommand('insertText', false, text);
        });
        toolbar.addEventListener('mousedown', function (e) {
            // keep the selection in the text while clicking a button
            if (e.target.closest('button')) {
                e.preventDefault();
            }
        });
        toolbar.addEventListener('click', function (e) {
            var button = e.target.closest('button');
            if (!button) {
                return;
            }
            var cmd = button.getAttribute('data-cmd');
            var value = null;
            if (cmd === 'createLink') {
                value = window.prompt(button.getAttribute('data-prompt'), 'https://');
                if (!value || !/^(https?:\/\/|mailto:)/i.test(value)) {
                    return;
                }
            }
            area.focus();
            document.execCommand(cmd, false, value);
            sync();
        });
        var form = textarea.form;
        if (form) {
            form.addEventListener('submit', sync);
        }
    });
})();
