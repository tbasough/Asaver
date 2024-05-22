const urlSearchParams = new URLSearchParams(window.location.search);
const params = Object.fromEntries(urlSearchParams.entries());
let redirectUrl = 'http://localhost/wordpress/wp-content/plugins/asaver/download.php?media=';
let countdown = 10;
let timeLeft = countdown;
setTimeout(redirect, countdown);

function redirect() {
    window.location.href = redirectUrl + params.media;
}


var downloadTimer = setInterval(function () {
    if (timeLeft <= 0) {
        clearInterval(downloadTimer);
        document.getElementById("countdown").innerHTML = "";
    } else {
        document.getElementById("countdown").innerHTML = "" + timeLeft + " seconds.";
    }
    timeLeft -= 1;
}, 1000);