<?php

class Odnoklassniki extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoId = str_replace('video/', '', substr(parse_url($videoUrl, PHP_URL_PATH), 1));
        $data = $this->getVideoData($videoId);
        if (!empty($data)) {
            $this->title = $data['movie']['title'];
            $this->source = 'odnoklassniki';
            $this->thumbnail = $data['movie']['poster'];
            $this->duration = $data['movie']['duration'];
            foreach ($data['videos'] as $video) {
                array_push($this->medias, new Media($video['url'], $video['name'], 'mp4', true, true));
            }
        }
    }

    private function getVideoData($videoId)
    {
        $http = new Http("https://ok.ru/dk?cmd=videoPlayerMetadata&mid=$videoId");
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->run();
        return json_decode($http->response, true);
    }
}