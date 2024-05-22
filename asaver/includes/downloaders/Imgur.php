<?php

class Imgur extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
        $this->source = 'imgur';
        $this->thumbnail = Helpers::getStringBetween($http->response, '<meta name="twitter:image" data-react-helmet="true" content="', '">');
        $videoUrl = Helpers::getStringBetween($http->response, '<meta property="og:video:secure_url" data-react-helmet="true" content="', '">');
        array_push($this->medias, new Media($videoUrl, 'hd', 'mp4', true, true));
    }
}