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
