/* Listings – frontend: confirm before an owner deletes an entry */
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.matches && form.matches('.mod_listings form[data-lst-confirm]') && !window.confirm(form.getAttribute('data-lst-confirm'))) {
        e.preventDefault();
    }
});
