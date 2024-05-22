<?php

class MxTakatak extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $videoUrl = $http->getLongUrl();
        preg_match('/video\/(\w+)/', $videoUrl, $matches);
        if (isset($matches[1]) != '') {
            $videoId = $matches[1];
            $http = new Http($videoUrl);
            $http->run();
            $data = Helpers::getStringBetween($http->response, 'window._state =', 'window.clientTime');
            $data = json_decode($data, true);
            if (isset($data['entities'][$videoId]) != '') {
                $this->title = $data['entities'][$videoId]['desc'];
                $this->source = 'mxtakatak';
                $this->thumbnail = $data['entities'][$videoId]['thumbnail'];
                if (isset($data['entities'][$videoId]["download_url"]) != "") {
                    array_push($this->medias, new Media($data['entities'][$videoId]['download_url'], $data['entities'][$videoId]['origin_height'] . 'p', 'mp4', true, true));
                }
                if (isset($data['entities'][$videoId]['audio']['url']) != '') {
                    array_push($this->medias, new Media($data['entities'][$videoId]['audio']['url'], '128kbps', 'm4a', false, true));
                }
            }
        }
    }
}