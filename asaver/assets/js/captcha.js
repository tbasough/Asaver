showLoader();
grecaptcha.ready(function () {
    recaptcha_execute();
});
window.recaptcha_execute = recaptcha_execute;
function recaptcha_execute() {
    grecaptcha.execute('%s', {action: 'homepage'}).then(function (token) {
        document.getElementById('token').value = token;
        hideLoader();
    });
}