<?php
set_time_limit(0);
ini_set("zlib.output_compression", "Off");
require_once __DIR__ . '/../../../wp-load.php';
$siteUrl = get_site_url();
$countdown = (int)get_option('asr_download_timer') - 1;
$bandwidthSaving = get_option('asr_bandwidth_saving_mode') == 'on';
$suffix = get_option('asr_filename_suffix');
if ((!empty($_GET['start']) || $countdown === -1) && !empty($_GET['media']) && !empty($_SESSION['result'])) {
    $id = (int)base64_decode($_GET['media']);
    if (is_numeric($id) && !empty($_SESSION['result']['medias'][$id])) {
        $media = $_SESSION['result']['medias'][$id];
        $name = substr($_SESSION['result']['title'], 0, 48);
        if ($bandwidthSaving) {
            Helpers::redirect($media['url']);
        }
        if ($suffix != '') {
            $name = $_SESSION['result']['title'] . ' ' . $suffix;
        }
        $parsedRemoteUrl = parse_url($media['url']);
        $remoteDomain = str_ireplace('www.', '', $parsedRemoteUrl['host'] ?? '');
        $localDomain = str_ireplace('www.', '', parse_url($siteUrl, PHP_URL_HOST));
        require_once __DIR__ . '/includes/Helpers.php';
        require_once __DIR__ . '/includes/Http.php';
        session_write_close();
        if ($media['chunked']) {
            $paths = explode('/', $parsedRemoteUrl['path']);
            $fileName = end($paths);
            $chunks = json_decode(file_get_contents(__DIR__ . '/cache/' . $fileName), true);
            Http::forceDownloadChunks($chunks, $name, $media['extension']);
        } else if ($media['cached']) {
            Http::forceDownloadLegacy(__DIR__ . $parsedRemoteUrl['path'], $name, $media['extension'], $media['size']);
        } else if ($remoteDomain == 'dailymotion.clipsav.com') {
            Helpers::redirect($media['url']);
        } else if ($_SESSION['result']['source'] == 'bilibili') {
            Http::forceDownloadLegacy($media['url'], $name, $media['extension'], $media['size'], false);
        } else if ($_SESSION['result']['source'] == 'youtube') {
            require_once __DIR__ . '/includes/Stream.php';
            $stream = new Stream();
            $stream->forceDownload($media['url'], $name, $media['extension'], $media['size'], $media['url']);
        } else {
            $referer = '';
            if ($_SESSION['result']['source'] == 'mxtakatak') {
                $referer = 'https://www.mxtakatak.com/';
                $media['size'] = 0;
            }
            Http::forceDownload($media['url'], $name, $media['extension'], $media['size'], $referer);
        }
    }
}

if ($countdown >= 0) {
    get_header();
    ?>
    <script>
        const urlSearchParams = new URLSearchParams(window.location.search);
        const params = Object.fromEntries(urlSearchParams.entries());
        let redirectUrl = window.location.href;
        let countdown = <?php echo $countdown; ?>;
        let timeLeft = countdown;

        function redirect() {
            if (!params.start) {
                window.location.href = redirectUrl + "&start=1";
            }
        }

        var downloadTimer = setInterval(function () {
            if (timeLeft <= 0) {
                clearInterval(downloadTimer);
                redirect();
                document.getElementById("text").innerHTML = "Download has started.";
                document.getElementById("loader").src = "<?php echo $siteUrl; ?>/wp-content/themes/asr-default/assets/icons/check-mark.svg";
            } else {
                document.getElementById("countdown").innerHTML = timeLeft + "";
            }
            timeLeft -= 1;
        }, 1000);
    </script>
    <?php echo get_option('asr_ad_area_3'); ?>
    <main class="container mt-8 mb-12">
        <div class="row align-items-center">
            <div class="col-12 mb-5 mb-lg-0" id="main">
                <div class="mt-8 mb-8 mb-lg-12 text-center"><img
                            src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/loader.gif"
                            class="img-fluid w-25 mx-auto" id="loader"></div>
                <div class="text-center"><strong id="text">Your download will start within <span
                                id="countdown"><?php echo $countdown + 1; ?></span> seconds.</strong></div>
            </div>
        </div>
    </main>
    <?php echo get_option('asr_ad_area_4'); ?>
    <?php
    get_footer();
}