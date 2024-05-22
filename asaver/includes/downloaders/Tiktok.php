<?php

class Tiktok extends Downloader
{
    private $cookieFile = __DIR__ . '/../../cookies/tiktok-cookie.txt';
    private static $useApi = false;

    public function fetch($videoUrl)
    {
        $http = new Http('https://www.tikwm.com/api/?url=' . $videoUrl);
        $http->run();
        $data = json_decode($http->response, true)['data'] ?? null;
        if (!empty($data['wmplay'])) {
            $this->title = $data['title'] ?? 'Tiktok Video';
            $this->source = 'tiktok';
            $this->thumbnail = $data['cover'];
            $this->medias[] = new Media($data['wmplay'], 'watermark', 'mp4', true, true);
            if (!empty($data['play'])) {
                $media = new Media($data['play'], 'hd', 'mp4', true, true);
                $media->size = $this->getSize($media->url);
                $this->medias[] = $media;
            }
            if (!empty($data['music'])) {
                $this->medias[] = new Media($data['music'], '128kbps', 'mp3', false, true);
            }
        }
    }

    public function fetch1($videoUrl)
    {
        $videoId = $this->getVideoId($videoUrl);
        if ($videoId != '') {
            $apiUrl = 'https://tt-dl.herokuapp.com/?url=' . $videoUrl;
            $http = new Http($apiUrl);
            $http->run();
            $data = json_decode($http->response, true);
            if (!empty($data['caption']) && !empty($data['download'])) {
                $this->title = $data['caption'];
                $this->source = 'tiktok';
                $this->thumbnail = $data['images'];
                array_push($this->medias, new Media($data['download'], 'hd', 'mp4', true, true));
            }
        }
    }

    public function fetch2($videoUrl)
    {
        $videoId = $this->getVideoId($videoUrl);
        if ($videoId != '') {
            $apiUrl = 'http://toolav.herokuapp.com/id/?video_id=' . $videoId;
            $http = new Http($apiUrl);
            $http->run();
            $data = json_decode($http->response, true);
            $i = 0;
            while (empty($data['item']['video']['downloadAddr']) && $i < 20) {
                $apiUrl = 'http://toolav.herokuapp.com/id/?video_id=' . $videoId;
                $http = new Http($apiUrl);
                $http->run();
                $data = json_decode($http->response, true);
                $i++;
            }
            if (!empty($data['item']['desc']) && !empty($data['item']['video']['downloadAddr'])) {
                $this->title = $data['item']['desc'];
                $this->source = 'tiktok';
                $this->thumbnail = $data['item']['video']['cover'];
                $this->duration = $data['item']['video']['duration'] / 1000;
                $video = $data['item']['video'];
                $media = new Media($video['downloadAddr'][0], $video['ratio'], 'mp4', true, true);
                array_push($this->medias, $media);
            }
        }
    }

    public function fetch3($videoUrl)
    {
        if (self::$useApi) {
            $this->fetchFromApi($videoUrl);
        } else {
            preg_match('/#\/@\w{2,32}\/video\/\d{2,32}/', $videoUrl, $matches);
            if (count($matches) === 1) {
                $videoUrl = 'https://www.tiktok.com' . ltrim($matches[0], '#');
            }
            if (!$this->checkHost($videoUrl)) {
                $videoUrl = $this->getRedirectUrl($videoUrl);
                if ($this->checkHost($videoUrl)) {
                    $videoUrl = $this->getRedirectUrl($videoUrl);
                }
            }
            preg_match('/\/video\/([0-9]+)/', $videoUrl, $matches);
            if (count($matches) >= 2) {
                $shareUrl = 'https://www.tiktok.com/node/share/video/@' . $matches[1] . '/' . $matches[1];
                $shareData = $this->get($shareUrl);
                $shareData = json_decode($shareData, true);
                if (!empty($shareData['itemInfo']['itemStruct']['video']) && !empty($shareData['seoProps'])) {
                    $this->title = $shareData['seoProps']['metaParams']['title'];
                    $this->source = 'tiktok';
                    $this->thumbnail = $shareData['itemInfo']['itemStruct']['video']['cover'];
                    if (isset($shareData['itemInfo']['itemStruct']['video']['duration']) != '') {
                        $this->duration = $shareData['itemInfo']['itemStruct']['video']['duration'];
                    }
                    if (isset($shareData['itemInfo']['itemStruct']['video']['downloadAddr']) != '') {
                        $trackId = rand(0, 4);
                        $cacheFile = 'tiktok-' . $trackId . '.mp4';
                        $cachePath = Cache::$cachePath . $cacheFile;
                        $this->downloadVideo($shareData['itemInfo']['itemStruct']['video']['downloadAddr'], $cachePath);
                        $videoKey = $this->getVideoKey(file_get_contents($cachePath));
                        $url = Cache::getCacheUrl() . $cacheFile;
                        $media = new Media($url, $shareData['itemInfo']['itemStruct']['video']['ratio'], 'mp4', true, true);
                        $media->size = filesize($cachePath);
                        array_push($this->medias, $media);
                        if ($videoKey != '') {
                            $nwmVideo = 'https://api2-16-h2.musical.ly/aweme/v1/play/?video_id=$videoKey&vr_type=0&is_play_url=1&source=PackSourceEnum_PUBLISH&media_type=4';
                            $nwmVideo = $this->getRedirectUrl($nwmVideo);
                            if (filter_var($nwmVideo, FILTER_VALIDATE_URL)) {
                                array_push($this->medias, new Media($nwmVideo, $shareData['itemInfo']['itemStruct']['video']['ratio'], 'mp4', true, true));
                            }
                        }
                    }
                    if (isset($shareData['itemInfo']['itemStruct']['music']['playUrl']) != '') {
                        array_push($this->medias, new Media($shareData['itemInfo']['itemStruct']['music']['playUrl'], '128kbps', 'mp3', false, true));
                    }
                }
            }
        }
    }

    private function getVideoId($url)
    {
        if (preg_match('/https?:\/\/(www.tiktok.com|tiktok.com)\/@[^\s]+\/video\/[0-9]+/', $url, $matches)) {
            $explode = explode('/', $matches[0]);
            return end($explode);
        } else if (preg_match('/https?:\/\/[^\s]+tiktok.com\/[^\s@]+/', $url, $matches)) {
            $url = $matches[0];
            $url = $this->getRedirectUrl($url);
            return $this->getVideoId($url);
        } else {
            return '';
        }
    }

    private function checkHost($url)
    {
        $host = str_replace('www.', '', parse_url($url, PHP_URL_HOST));
        return $host == 'tiktok.com';
    }

    private function getSize($url)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => Http::$userAgent,
            CURLOPT_ENCODING => 'utf-8',
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_NOBODY => true,
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        curl_exec($ch);
        //$this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $size = -1;
        if (curl_errno($ch) == 0) {
            $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        }
        curl_close($ch);
        return $size;
    }

    private function get($url)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => Http::$userAgent,
            CURLOPT_ENCODING => 'utf-8',
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $data;
    }

    private function downloadVideo($url, $file_path)
    {
        $fp = fopen($file_path, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.tiktok.com/');
        curl_setopt($ch, CURLOPT_USERAGENT, Http::$userAgent);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    private function getVideoKey($file_data)
    {
        $key = '';
        preg_match("/vid:([a-zA-Z0-9]+)/", $file_data, $matches);
        if (isset($matches[1])) {
            $key = $matches[1];
        }
        return $key;
    }

    public function getRedirectUrl($url)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => Http::$userAgent,
            CURLOPT_ENCODING => "utf-8",
            CURLOPT_AUTOREFERER => false,
            CURLOPT_REFERER => 'https://www.tiktok.com/',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
        );
        curl_setopt_array($ch, $options);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return $url;
    }

    private function fetchFromApi($url)
    {
        $fingerprint = get_option('asr_license_fingerprint');
        $http = new Http('https://dailymotion.clipsav.com/system/action.php?fp=' . $fingerprint);
        $http->addCurlOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->addCurlOption(CURLOPT_POSTFIELDS, 'url=' . urlencode($url) . '&purchase_code=' . get_option('asr_license_code'));
        $http->addHeader('x-requested-with', 'PHP-cURL');
        $http->addHeader('user-agent', $fingerprint);
        $http->addHeader('content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
        $http->run();
        $response = json_decode($http->response, true);
        $this->title = $response['title'];
        $this->source = 'tiktok';
        $this->thumbnail = $response['thumbnail'];
        $this->duration = $response['duration'];
        foreach ($response['links'] as $link) {
            $data = ['url' => $link['url'], 'title' => $response["title"], 'type' => $link['type'], 'source' => 'dailymotion'];
            $mediaUrl = 'https://dailymotion.clipsav.com/dl.php?' . http_build_query($data);
            $media = new Media($mediaUrl, $link['quality'], $link['type'], true, true);
            $media->size = $link['bytes'];
            array_push($this->medias, $media);
        }
    }
}