<?php

class Bilibili extends Downloader
{
    public function fetch($videoUrl)
    {

        $parsedUrl = parse_url($videoUrl);
        if ($parsedUrl['host'] === 'bilibili.tv' || $parsedUrl['host'] === 'www.bilibili.tv') {
            preg_match('/video\/(\d{3,10})/', $parsedUrl['path'], $matches);
            if (!empty($matches[1])) {
                $apiUrl = 'https://api.bilibili.tv/intl/gateway/web/playurl?aid=' . $matches[1] . '&device=wap&platform=web&qn=64&s_locale=en_US&tf=0&type=0';
                $http = new Http($videoUrl);
                $http->run();
                $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
                $this->thumbnail = Helpers::getStringBetween($http->response, '<meta data-vue-meta="ssr" name="og:image" content="', '">');
                $this->thumbnailHotlinkProtection = true;
                $this->saveThumbnail();
                $http = new Http($apiUrl);
                $http->run();
                if (!empty($http->response)) {
                    $data = json_decode($http->response, true);
                    if (!empty($data['data']['playurl'])) {
                        $this->duration = $data['data']['playurl']['duration'] / 1000;
                        $this->source = 'bilibili';
                        if (!empty($data['data']['playurl']['audio_resource'])) {
                            $lastId = count($data['data']['playurl']['audio_resource']) - 1;
                            $media = new Media($data['data']['playurl']['audio_resource'][$lastId]['backup_url'][0], '320kbps', 'm4a', false, true);
                            $this->medias[] = $media;
                            $cdnHost = parse_url($this->medias[0]->url, PHP_URL_HOST);
                            if (!empty($cdnHost)) {
                                foreach ($data['data']['playurl']['video'] as $video) {
                                    if (!empty($video['video_resource']['backup_url'])) {
                                        $media = new Media($video['video_resource']['backup_url'][0], $video['stream_info']['desc_words'], 'mp4', true, false);
                                        $this->medias[] = $media;
                                    }
                                }
                            }
                            usort($this->medias, array('Helpers', 'sortByQuality'));
                        }
                    }
                }
            }
        } else {
            $http = new Http($videoUrl);
            $http->response = $this->urlGetContents($videoUrl);
            preg_match_all('/window\.__playinfo__=(.*)<\/script><script>/', $http->response, $matches);
            if (!empty($matches[1][0])) {
                $data = json_decode($matches[1][0], true);
                $this->title = Helpers::getStringBetween($http->response, 'itemprop="name" name="title" content="', '"');
                $this->thumbnail = Helpers::getStringBetween($http->response, 'data-vue-meta="true" itemprop="image" content="', '"');
                $this->thumbnailHotlinkProtection = true;
                $this->saveThumbnail();
                $this->duration = $data['data']['dash']['duration'];
                foreach ($data['data']['dash']['video'] as $video) {
                    $media = new Media($video['base_url'], $video['height'] . 'p', 'mp4', true, false);
                    $media->size = $this->estimateVideoSize($video['bandwidth'], $data['data']['dash']['duration']);
                    array_push($this->medias, $media);
                }
                foreach ($data['data']['dash']['audio'] as $audio) {
                    $quality = (int)Helpers::formatSize($audio['bandwidth']) . ' kbps';
                    $media = new Media($audio['base_url'], $quality, 'm4a', false, true);
                    $media->size = $this->estimateVideoSize($audio['bandwidth'], $data['data']['dash']['duration']);
                    array_push($this->medias, $media);
                }
                usort($this->medias, array('Helpers', 'sortByQuality'));
            }
        }
    }

    private function saveThumbnail()
    {
        $id = sha1($this->thumbnail);
        $cache = new Cache('bilibili-' . $id, 'jpg', $this->urlGetContents($this->thumbnail));
        $this->thumbnail = $cache->url;
    }

    private function estimateVideoSize($bandwidth, $duration)
    {
        return ($duration / 60.0) * ($bandwidth * 10.0);
    }

    private function urlGetContents($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: buvid3=5EEF555A-8B5A-F1C8-6C1D-23A786E0627988125infoc'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}