<?php

class Soundcloud extends Downloader
{
    public $apiKey = '';
    public $apiKeyFile = __DIR__ . '/../../cookies/soundcloud-api-key.json';
    public $jsFiles = ['https://a-v2.sndcdn.com/assets/2-de6d2802-3.js', 'https://a-v2.sndcdn.com/assets/2-5e4e4418-3.js', 'https://a-v2.sndcdn.com/assets/2-6b083daa-3.js'];
    public $tries = 0;

    public function __construct()
    {
        if (file_exists($this->apiKeyFile)) {
            $array = json_decode(file_get_contents($this->apiKeyFile), true);
            if (isset($array['expires_at']) && time() < $array['expires_at'] && isset($array['key']) != '') {
                $this->apiKey = $array['key'];
            } else {
                $this->apiKey = $this->getApiKey();
            }
        } else {
            $this->apiKey = $this->getApiKey();
        }
    }

    public function fetch($videoUrl)
    {
        if (parse_url($videoUrl, PHP_URL_HOST) == 'm.soundcloud.com') {
            $videoUrl = str_replace('m.soundcloud.com', 'soundcloud.com', $videoUrl);
        }
        $this->tries++;
        $apiKey = $this->apiKey;
        $http = new Http($videoUrl);
        $http->run();
        $trackId = Helpers::getStringBetween($http->response, 'content="soundcloud://sounds:', '">');
        $this->title = Helpers::getStringBetween($http->response, 'property="og:title" content="', '"');
        $this->source = 'soundcloud';
        $this->thumbnail = Helpers::getStringBetween($http->response, 'property="og:image" content="', '"');
        $this->duration = (int)Helpers::getStringBetween($http->response, '"full_duration":', ',') / 1000;
        $data = $this->getTrackData($trackId);
        if (isset($data['media']['transcodings']) != '') {
            $mp3Found = false;
            foreach ($data['media']['transcodings'] as $stream) {
                if ($stream['format']['protocol'] == 'progressive') {
                    $mp3Url = new Http($stream['url'] . '?client_id=' . $apiKey);
                    $mp3Url->run();
                    $mp3Url = json_decode($mp3Url->response, true)['url'] ?? null;
                    $mp3Size = new Http($mp3Url);
                    $mp3Size = $mp3Size->getFileSize();
                    if ($mp3Size > 0) {
                        $media = new Media($mp3Url, '128kbps', 'mp3', false, true);
                        $media->size = $mp3Size;
                        array_push($this->medias, $media);
                        $mp3Found = true;
                    }
                    break;
                }
            }
            foreach ($data['media']['transcodings'] as $stream) {
                if ($stream['format']['protocol'] == 'hls') {
                    $fileExt = $stream['format']['mime_type'] == 'audio/mpeg' ? 'mp3' : 'ogg';
                    if ($fileExt == 'ogg' || (!$mp3Found && $fileExt == 'mp3')) {
                        $chunks = $this->getChunks($stream['url']);
                        $cache = new Cache('soundcloud-' . $trackId, 'json', json_encode($chunks));
                        $chunkSize = new Http($chunks[0]);
                        $chunkSize = $chunkSize->getFileSize();
                        $media = new Media($cache->url, '128kbps', $fileExt, false, true);
                        $media->size = $chunkSize * count($chunks) * 4;
                        $media->chunked = true;
                        array_push($this->medias, $media);
                    }
                }
            }
            if (!empty($this->medias[0]->url) && !filter_var($this->medias[0]->url, FILTER_VALIDATE_URL) && $this->tries < 2) {
                $this->fetch($videoUrl);
            }
        }
    }

    private function getApiKey()
    {
        $http = new Http('https://soundcloud.com');
        $http->run();
        preg_match_all('/src="(.*?sndcdn\.com.*?js)/', $http->response, $matches);
        $apiKey = '';
        if (isset($matches[1]) != '') {
            $this->jsFiles = $matches[1];
            foreach ($this->jsFiles as $jsFile) {
                if (!empty($apiKey)) {
                    break;
                }
                $jsContent = new Http($jsFile);
                $jsContent->run();
                $apiKey = Helpers::getStringBetween($jsContent->response, '"web-auth?client_id=', '&device_id=');
                if (empty($apiKey)) {
                    $apiKey = Helpers::getStringBetween($jsContent->response, 'client_id:"', '",env:"');
                }
                if (!empty($apiKey)) {
                    break;
                }
            }
        }
        file_put_contents($this->apiKeyFile, json_encode(array("key" => $apiKey, "expires_at" => time() + 10800, "js_files" => $matches[1]), JSON_PRETTY_PRINT));
        return $apiKey;
    }

    private function getTrackData($trackId)
    {
        $http = new Http("https://api-v2.soundcloud.com/tracks?ids=$trackId&client_id=$this->apiKey&app_version=1605107988&app_locale=en");
        $http->addCurlOption(CURLOPT_HTTPHEADER, [
            'Connection: keep-alive',
            'Accept: application/json, text/javascript, */*; q=0.1',
            'Content-Type: application/json',
            'Origin: https://soundcloud.com',
            'Sec-Fetch-Site: same-site',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Dest: empty',
            'Referer: https://soundcloud.com/',
            'Accept-Language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6'
        ]);
        $http->run();
        $data = json_decode($http->response, true);
        if (isset($data[0]) != '') {
            return $data[0];
        } else {
           return '';
        }
    }

    private function getChunks($stream_url)
    {
        $http = new Http($stream_url . '?client_id=' . $this->apiKey);
        $http->run();
        $m3u8Url = json_decode($http->response, true)['url'];
        $m3u8Data = new Http($m3u8Url);
        $m3u8Data->run();
        preg_match_all('/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)/', $m3u8Data->response, $streamsRaw);
        return $streamsRaw[0];
    }
}