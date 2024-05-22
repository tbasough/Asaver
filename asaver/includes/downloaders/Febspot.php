<?php

class Febspot extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
        $this->source = 'febspot';
        $this->thumbnail = Helpers::getStringBetween($http->response, 'property="og:image" content="', '"');
        $videoUrl = Helpers::getStringBetween($http->response, 'property="og:video" content="', '"');
        if (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            array_push($this->medias, new Media($videoUrl, '480p', 'mp4', true, true));
        }
    }
}