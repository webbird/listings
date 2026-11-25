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
