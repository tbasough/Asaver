<?php

class Dailymotion extends Downloader
{
    private static $useApi = false;

    public function fetch($videoUrl)
    {
        if (!self::$useApi) {
            $videoId = $this->extractVideoId($videoUrl);
            $http = new Http('https://www.dailymotion.com/player/metadata/video/' . $videoId);
            $http->run();
            if (!empty($http->response)) {
                $data = json_decode($http->response, true);
                $this->title = $data['title'];
                $this->source = 'dailymotion';
                $this->thumbnail = end($data['posters']);
                $this->duration = $data['duration'];
                $streamsM3u8 = new Http($data['qualities']['auto'][0]['url']);
                $streamsM3u8->run();
                $streamsM3u8 = $streamsM3u8->response;
                preg_match_all('/#EXT-X-STREAM-INF:(.*)/', $streamsM3u8, $streamsRaw);
                $streamsRaw = $streamsRaw[1];
                $streams = array();
                foreach ($streamsRaw as $stream) {
                    $quality = Helpers::getStringBetween($stream, 'NAME="', '"');
                    if (!isset($streams[$quality])) {
                        $streams[$quality]['quality'] = $quality;
                        $streams[$quality]['url'] = Helpers::getStringBetween($stream, 'PROGRESSIVE-URI="', '"');
                    }
                }
                foreach ($streams as $stream) {
                    $this->medias[] = new Media($stream['url'], $stream['quality'] . 'p', 'mp4', true, true);
                }
                usort($this->medias, array('Helpers', 'sortByQuality'));
            }
        } else {
            $this->fetchFromApi($videoUrl);
        }
    }

    private function extractVideoId($url)
    {
        $domain = str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));
        switch (true) {
            case($domain == 'dai.ly'):
                $videoId = str_replace('https://dai.ly/', "", $url);
                $videoId = str_replace('/', "", $videoId);
                return $videoId;
                break;
            case($domain == 'dailymotion.com'):
                $urlParts = parse_url($url);
                $pathArr = explode('/', $urlParts['path']);
                $videoId = $pathArr[2];
                if ($videoId == 'video' && count($pathArr) === 4) {
                    $videoId = $pathArr[3];
                }
                return $videoId;
                break;
            default:
                return '';
                break;
        }
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
        $this->source = 'dailymotion';
        $this->thumbnail = $response['thumbnail'];
        $this->duration = $response['duration'];
        if (!empty($response['links'])) {
            foreach ($response['links'] as $link) {
                $data = ['url' => $link['url'], 'title' => $response["title"], 'type' => $link['type'], 'source' => 'dailymotion'];
                $mediaUrl = 'https://dailymotion.clipsav.com/dl.php?' . http_build_query($data);
                $media = new Media($mediaUrl, $link['quality'], $link['type'], true, true);
                $media->size = $link['bytes'];
                array_push($this->medias, $media);
            }
        }
    }
}