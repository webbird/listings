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
