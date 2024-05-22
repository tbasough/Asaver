document.addEventListener("DOMContentLoaded", function() {
    if ("IntersectionObserver" in window) {
        var a = document.querySelectorAll(".lazy");
        var b = new IntersectionObserver(function(c, e) {
            c.forEach(function(f) {
                f.isIntersecting && (f = f.target, f.src = f.dataset.src, f.classList.remove("lazy"), b.unobserve(f))
            })
        });
        a.forEach(function(c) {
            b.observe(c)
        })
    } else {
        var d = function() {
                g && clearTimeout(g);
                g = setTimeout(function() {
                    var c = window.pageYOffset;
                    a.forEach(function(e) {
                        e.offsetTop < window.innerHeight + c && (e.src = e.dataset.src,
                            e.classList.remove("lazy"))
                    });
                    0 === a.length && (document.removeEventListener("scroll", d), window.removeEventListener("resize", d), window.removeEventListener("orientationChange", d))
                }, 20)
            },
            g;
        a = document.querySelectorAll(".lazy");
        document.addEventListener("scroll", d);
        window.addEventListener("resize", d);
        window.addEventListener("orientationChange", d)
    }
});
var siteUrl = WPURLS.siteurl,
    autoFetch = !1,
    executed = !1,
    elm;
document.getElementById("downloadBtn").addEventListener("click", clickDownload);
window.addEventListener("hashchange", function() {
    url()
}, !1);
url();

function clickDownload(a) {
    var b = document.getElementById("url").value,
        d = document.getElementById("token").value;
    if (!isEmpty(b) && isValidURL(b)) {
        executed = !0;
        hideAlert();
        var g = new Headers;
        g.append("Content-Type", "application/x-www-form-urlencoded");
        var c = new URLSearchParams;
        c.append("url", b);
        c.append("token", d);
        d = {
            method: "POST",
            headers: g,
            body: c,
            redirect: "follow"
        };
        showLoader();
        removeHash();
        window.location.replace("#url=" + b);
        fetch(siteUrl + "/wp-json/a-saver/video-data/", d).then(function(e) {
            return e.text()
        }).then(function(e) {
            return showResult(e)
        })["catch"](function(e) {
            return showAlert(e)
        })
    } else showAlert("Please enter a valid URL.");
    a.preventDefault()
}
var input = document.getElementById("url");
input.addEventListener("keyup", function(a) {
    13 === a.keyCode && (clickDownload(), a.preventDefault())
});

function url() {
    if (-1 < window.location.href.indexOf("#url=") && !1 === executed) {
        var a = window.location.href.match(RegExp("#url=(.+)", ""))[1],
            b = document.getElementById("token").value;
        document.getElementById("url").value = a;
        //document.getElementById("header").scrollIntoView();
        autoFetch && "" !== b && "" !== a && !executed && (clickDownload(), executed = !0)
    }
}

function isValidURL(a) {
    elm || (elm = document.createElement("input"), elm.setAttribute("type", "url"));
    elm.value = a;
    return elm.validity.valid
}

function isEmpty(a) {
    return !a || 0 === a.length
}

function showLoader() {
    document.getElementById("url").style.display = "none";
    document.getElementById("downloadBtn").style.display = "none";
    document.getElementById("pasteBtn").style.display = "none";
    var a = document.createElement("img");
    a.src = WPURLS.pluginurl + "assets/images/loader.gif";
    a.className = "img-fluid w-25 mx-auto";
    a.id = "loader";
    document.getElementById("download-form").appendChild(a)
}

function hideLoader() {
    document.getElementById("url").style.display = "";
    document.getElementById("downloadBtn").style.display = "";
    document.getElementById("pasteBtn").style.display = "";
    null !== document.getElementById("loader") && document.getElementById("loader").remove()
}

function showAlert(a) {
    var b = document.getElementById("alert");
    b.innerHTML = a;
    b.style.display = "";
    setTimeout(hideAlert, 5E3)
}

function hideAlert() {
    var a = document.getElementById("alert");
    a.innerHTML = "";
    a.style.display = "none"
}

function removeHash() {
    history.pushState("", document.title, window.location.pathname + window.location.search)
}

function showResult(a) {
    hideLoader();
    a = JSON.parse(a);
    var b = `<div class="container"> 
                <div class="row mb-0"> 
                    <div class="col-12 mb-10 mb-lg-0 xf-video-intro"> 
                        <img class="position-relative img-fluid rounded w-100" style="border-radius:10px; object-fit: cover;z-index:-1" src="{{thumbnail}}" alt="{{title}}" title="{{title}}">
                        <h2 class="mt-8 mb-0 text-center" id="xfvideotitle">{{title}} </h2> 
                        <small class="text-muted" id="videoDuration">{{duration}}</small>
                    </div>
                </div>
                <div class="row text-center"> 
                    <div class="xf-download-links">{{links}}</div> 
                </div>
            </div>`,
                    /*<div class="row mb-n6 py-6 d-flex align-items-start text-center"> 
                        <div id="share-buttons"> 
                            <p class="lead share-text">Share</p> 
                            <a title="Facebook" href="{{facebook_share_link}}" class="btn btn-social btn-fill btn-facebook mx-1" target="_blank">Facebook</a>
                            <a title="Twitter" href="{{twitter_share_link}}" class="btn btn-social btn-fill btn-twitter mx-1" target="_blank">Twitter</a>
                            <a title="Whatsapp" href="{{whatsapp_share_link}}" class="btn btn-social btn-fill btn-whatsapp mx-1" target="_blank">Whatsapp</a>
                            <a title="Pinterest" href="{{pinterest_share_link}}" class="btn btn-social btn-fill btn-pinterest mx-1" target="_blank">Pinterest</a>
                            <a title="Tumblr" href="{{tumblr_share_link}}" class="btn btn-social btn-fill btn-tumblr mx-1" target="_blank">Tumblr</a>
                            <a title="Reddit" href="{{reddit_share_link}}" class="btn btn-social btn-fill btn-reddit mx-1" target="_blank">Reddit</a>
                            <a title="QR Code" href="{{qr_share_link}}" class="btn btn-social btn-fill btn-qr mx-1" target="_blank">QR Code</a>
                        </div>
                    </div>*/
        d = "",
        g = 0;

    var video_icon = WPURLS.pluginurl + "assets/images/vuesax-linear-video-square.svg";
    var audio_icon = WPURLS.pluginurl + "assets/images/vuesax-linear-musicnote.svg";

    var video_links = '';
    var audio_links = '';
    var other_links = '';

    var col_class = 'xf-two-col';

    if ("undefined" === typeof a.medias) showAlert("No media found.");
    else {
        a.medias.forEach(function(e) {
            if (null !== e.url) {

                if( "jpg" === e.extension || !0 === e.videoAvailable && !0 === e.audioAvailable ){
                    var f = '<a href="{{url}}" class="xfdl xfvl" target="_blank"><span class="xficon">{{icon}}</span> <span class="xfquality">{{quality}}</span> | <span class="xftype">{{type}}</span> | <span class="xfsize">{{size}}</span></a>';
                }else if( e.quality.includes("kbps") ){
                    var f = '<a href="{{url}}" target="_blank" class="xfdl xfal"><span class="xficon">{{icon}}</span><span class="xfquality">{{quality}}</span> | <span class="xftype">{{type}}</span> | <span class="xfsize">{{size}}</span></a>';
                }else{
                    var f = '<a href="{{url}}" class="xfdl xfol" target="_blank"><span class="xficon">{{icon}}</span> <span class="xfquality">{{quality}}</span> | <span class="xftype">{{type}}</span> | <span class="xfsize">{{size}}</span></a>';
                }

                /*var f = "jpg" === e.extension || !0 === e.videoAvailable && !0 === e.audioAvailable ? '<a href="{{url}}" class="xfdl xfvl" target="_blank"><span class="xficon">{{icon}}</span> <span class="xfquality">{{quality}}</span> | <span class="xftype">{{type}}</span> | <span class="xfsize">{{size}}</span></a>' : 
                e.quality.includes("kbps") ? '<a href="{{url}}" target="_blank" class="xfdl xfal"><span class="xficon">{{icon}}</span><span class="xfquality">{{quality}}</span> | <span class="xftype">{{type}}</span> | <span class="xfsize">{{size}}</span></a>' :
                    '<a href="{{url}}" class="xfdl xfol" target="_blank"><span class="xficon">{{icon}}</span> <span class="xfquality">{{quality}}</span> | <span class="xftype">{{type}}</span> | <span class="xfsize">{{size}}</span></a>';
                */
                var h = "jpg" === e.extension ? '<svg xmlns="http://www.w3.org/2000/svg" height="1rem" fill="white" viewBox="0 0 550.801 550.8"><path d="M515.828 61.201H34.972C15.659 61.201 0 76.859 0 96.172V454.63c0 19.312 15.659 34.97 34.972 34.97h480.856c19.314 0 34.973-15.658 34.973-34.971V96.172c0-19.313-15.658-34.971-34.973-34.971zm0 34.971V350.51l-68.92-62.66c-10.359-9.416-26.289-9.04-36.186.866l-69.752 69.741-137.532-164.278c-10.396-12.415-29.438-12.537-39.99-.271L34.972 343.219V96.172h480.856zm-148.627 91.8c0-26.561 21.523-48.086 48.084-48.086 26.562 0 48.086 21.525 48.086 48.086s-21.523 48.085-48.086 48.085c-26.56.001-48.084-21.524-48.084-48.085z"/></svg> ' :
                    !0 === e.videoAvailable && !1 === e.audioAvailable ? 'Other<svg xmlns="http://www.w3.org/2000/svg" height="1rem" fill="white" viewBox="0 0 448.075 448.075"><path d="M352.021 16.075c0-6.08-3.52-11.84-8.96-14.4-5.76-2.88-12.16-1.92-16.96 1.92l-141.76 112.96 167.68 167.68V16.075zM443.349 420.747l-416-416c-6.24-6.24-16.384-6.24-22.624 0s-6.24 16.384 0 22.624l100.672 100.704h-9.376c-9.92 0-18.56 4.48-24.32 11.52-4.8 5.44-7.68 12.8-7.68 20.48v128c0 17.6 14.4 32 32 32h74.24l155.84 124.48c2.88 2.24 6.4 3.52 9.92 3.52 2.24 0 4.8-.64 7.04-1.6 5.44-2.56 8.96-8.32 8.96-14.4v-57.376l68.672 68.672c3.136 3.136 7.232 4.704 11.328 4.704s8.192-1.568 11.328-4.672c6.24-6.272 6.24-16.384 0-22.656z"/></svg> ' :
                    e.quality.includes("kbps") ? ' <object data="'+audio_icon+'"></object> ' :
                    ' <object data="'+video_icon+'"></object> ';
                
                f = f.replace(RegExp("{{quality}}","g"), e.quality);
                f = f.replace(RegExp("{{type}}", "g"), e.extension);
                f = f.replace(RegExp("{{icon}}", "g"), h);
                f = f.replace(RegExp("{{size}}", "g"), e.formattedSize);
                f = f.replace(RegExp("{{url}}", "g"), siteUrl + "/wp-content/plugins/asaver/download.php?source=" + a.source + "&media=" + btoa(g));
                

                if( "jpg" === e.extension || !0 === e.videoAvailable && !0 === e.audioAvailable ){
                    video_links = video_links.concat(f);
                }else if( e.quality.includes("kbps") ){
                    audio_links = audio_links.concat(f);
                }else{
                    other_links = other_links.concat(f);
                    col_class = 'xf-three-col';
                }

                
                g++
            }
        });

        if( video_links != '' ){
            d = d.concat('<div class="xf-video-links '+col_class+'"><h2 class="xf-format-heading">Video Formats</h2>'+video_links+'</div>');
        }

        if( audio_links != '' ){
            d = d.concat('<div class="xf-audio-links '+col_class+'"><h2 class="xf-format-heading">Audio Formats</h2>'+audio_links+'</div>');
        }

        if( other_links != '' ){
            d = d.concat('<div class="xf-other-links '+col_class+'"><h2 class="xf-format-heading">Other Formats</h2>'+other_links+'</div>');
        }

        d = d.concat('<div class="xfclear"></div>');

        b = b.replace(RegExp("{{title}}", "g"), a.title.substr(0,40)+'...  ');
        b = b.replace(RegExp("{{thumbnail}}", "g"), a.thumbnail);
        b = b.replace(RegExp("{{duration}}", "g"), a.duration);
        b = b.replace(RegExp("{{links}}", "g"), d);

        //var c = window.location.href.replace(RegExp("#url=", "g"), "?u=");
        
        
        //b = b.replace(RegExp("{{facebook_share_link}}", "g"), encodeURI("https://www.facebook.com/sharer.php?u=" + c));
        //b = b.replace(RegExp("{{twitter_share_link}}", "g"), encodeURI("https://twitter.com/intent/tweet?url=" + c + "&text=Download " + a.title));
        //b = b.replace(RegExp("{{whatsapp_share_link}}", "g"), encodeURI("whatsapp://send?text=Download " + a.title + " " + c));
        //b = b.replace(RegExp("{{pinterest_share_link}}", "g"), encodeURI("http://pinterest.com/pin/create/link/?url=" + c));
        //b = b.replace(RegExp("{{tumblr_share_link}}", "g"), encodeURI("https://www.tumblr.com/widgets/share/tool?canonicalUrl=" + c + "&title=" + a.title));
        //b = b.replace(RegExp("{{reddit_share_link}}", "g"), encodeURI("https://reddit.com/submit?url=" + c + "&title=" + a.title));
        //b = b.replace(RegExp("{{qr_share_link}}", "g"), encodeURI("https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" + c));
        document.getElementById("result").innerHTML = b;
        null === a.duration && null !== document.getElementById("videoDuration") && document.getElementById("videoDuration").remove();
        document.getElementById("result").style.display = "";
        //document.getElementById("ad-area-2").scrollIntoView()
        
        document.getElementById("xfvideotitle").scrollIntoView();
    }
}
document.getElementById("pasteBtn").addEventListener("click", function(a) {
    a = document.getElementById("xfpaste-text");
    var b = document.getElementById("url");
    "Erase" === a.innerHTML ? (b.value = "", a.innerHTML = "Paste from clipboard") : navigator.clipboard.readText().then(function(d) {
        return b.value = d
    }, a.innerHTML = "Erase")
    
    if( b.value == '' && a.innerHTML == 'Paste from clipboard' ){
        
        removeHash();
        
    }
});

var $ = jQuery;
var ctrlDown = false,ctrlKey = 17,cmdKey = 91,vKey = 86;
$(document).keydown(function(e) {
        if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = true;
    }).keyup(function(e) {
        if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = false;
    });

// Document Ctrl + C/V 
$(document).keydown(function(e) {
    if (ctrlDown && (e.keyCode == vKey)){
        a = document.getElementById("xfpaste-text");
        a.innerHTML = 'Erase';
    }
    
});

document.addEventListener("DOMContentLoaded", function() {
    var a = document.querySelectorAll(".navbar-burger"),
        b = document.querySelectorAll(".navbar-menu");
    if (a.length && b.length)
        for (var d = 0; d < a.length; d++) a[d].addEventListener("click", function() {
            for (var c = 0; c < b.length; c++) b[c].classList.toggle("d-none")
        });
    a = document.querySelectorAll(".navbar-close");
    var g = document.querySelectorAll(".navbar-backdrop");
    if (a.length)
        for (d = 0; d < a.length; d++) a[d].addEventListener("click", function() {
            for (var c = 0; c < b.length; c++) b[c].classList.toggle("d-none")
        });
    if (g.length)
        for (d = 0; d < g.length; d++) g[d].addEventListener("click", function() {
            for (var c = 0; c < b.length; c++) b[c].classList.toggle("d-none")
        })
});

