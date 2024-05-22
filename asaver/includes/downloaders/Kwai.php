<?php

class Kwai extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $mp4Url = Helpers::getStringBetween($http->response, '<video src="', '"');
        if (empty($mp4Url)) {
            preg_match_all('/<video .* src="(.*?)"/', $http->response, $matches);
            if (!empty($matches[1][0])) {
                $mp4Url = $matches[1][0];
            }
        }
        if (!empty($mp4Url)) {
            $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
            $this->source = 'kwai';
            $this->thumbnail = Helpers::getStringBetween($http->response, 'poster="', '"');
            array_push($this->medias, new Media($mp4Url, 'hd', 'mp4', true, true));
        }
    }
}