<?php

class Buzzfeed extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('@__NEXT_DATA__ =(.*?);@si', $http->response, $matches);
        $data = json_decode($matches[1][0], true)['props']['pageProps']['video'];
        if (!empty($data)) {
            $this->title = $data['title'];
            $this->source = 'buzzfeed';
            $this->thumbnail = $data['thumbnail_url'];
            array_push($this->medias, new Media($data['url'], '1080p', 'mp4', true, true));
        }
        preg_match_all('/"contentUrl": "https:\/\/www\.youtube\.com\/watch\?v=(.*?)"/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            require_once __DIR__ . '/YouTube.php';
            $yt = new YouTube();
            $yt->fetch($videoUrl);
            $this->title = $yt->title;
            $this->source = $yt->source;
            $this->thumbnail = $yt->thumbnail;
            $this->medias = $yt->medias;
            $this->duration = $yt->duration;
        }
    }
}