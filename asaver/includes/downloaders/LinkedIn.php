<?php

class LinkedIn extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $data = Helpers::getStringBetween($http->response, 'data-sources="', '"');
        if ($data != '') {
            $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
            $this->source = 'linkedin';
            $this->thumbnail = html_entity_decode(Helpers::getStringBetween($http->response, 'data-poster-url="', '"'));
            $videoUrl = json_decode(html_entity_decode($data), true)[0]['src'];
            if ($videoUrl != '') {
                array_push($this->medias, new Media($videoUrl, 'hd', 'mp4', true, true));
            }
        }
    }
}