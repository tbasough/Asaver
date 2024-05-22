<?php

class NineGag extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoId = $this->extractVideoId($videoUrl);
        if ($videoId != '') {
            $this->title = '9gag video';
            $this->source = 'ninegag';
            $this->thumbnail = 'http://images-cdn.9gag.com/photo/' . $videoId . '_460s.jpg';
            $videoUrl = "https://img-9gag-fun.9cache.com/photo/" . $videoId . "_460sv.mp4";
            $http = new Http($videoUrl);
            $videoSize = $http->getFileSize();
            if ($videoSize > 1000) {
                $media = new Media($videoUrl, 'hd', 'mp4', true, true);
                $media->size = $videoSize;
                array_push($this->medias, $media);
            }

        }
    }

    private function extractVideoId($videoUrl)
    {
        preg_match('/gag\/(\w+)/', $videoUrl, $output);
        return isset($output[1]) != '' ? $output[1] : '';
    }
}