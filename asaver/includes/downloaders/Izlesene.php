<?php

class Izlesene extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        if (preg_match_all('/videoObj\s*=\s*({.+?})\s*;\s*\n/', $http->response, $match)) {
            $playerJson = $match[1][0];
            $data = json_decode($playerJson, true);
            $this->title = $data['videoTitle'];
            $this->source = 'izlesene';
            $this->thumbnail = $data['posterURL'];
            $this->duration = $data['duration'] / 1000;
            if (!empty($data['media']['level'])) {
                foreach ($data["media"]["level"] as $video) {
                    array_push($this->medias, new Media($video['source'], $video['value'] . 'p', 'mp4', true, true));
                }
            }
        }
    }
}