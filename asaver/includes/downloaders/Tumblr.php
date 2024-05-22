<?php

class Tumblr extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->referer = 'https://tumblr.com';
        $http->run();
        $this->title = strip_tags(Helpers::getStringBetween($http->response, '<title>', '</title>'));
        $this->source = 'tumblr';
        $this->thumbnail = Helpers::getStringBetween($http->response, '<meta property="og:image" content="', '" />');
        if ($this->thumbnail == '') {
            $this->thumbnail = Helpers::getStringBetween($http->response, 'poster="', '"');
        }
        if ($this->thumbnail == '') {
            $videoData = json_decode(Helpers::getStringBetween($http->response, 'data-npf=', '">'), true);
            $this->thumbnail = $videoData['poster'][0]['url'];
        }
        $this->thumbnail = strip_tags($this->thumbnail);
        $url = strip_tags(Helpers::getStringBetween($http->response, '<meta property="og:video" content="', '" />'));
        if ($url != '') {
            array_push($this->medias, new Media($url, 'hd', 'mp4', true, true));
        } else {
            preg_match('/src="(.*?).mp4"/', $http->response, $matches);
            if (count($matches) > 1) {
                array_push($this->medias, new Media($matches[1] . '.mp4', 'hd', 'mp4', true, true));
            } else {
                preg_match_all('/src="(.*?.gifv)"/', $http->response, $matches);
                if (!empty($matches[1][1])) {
                    array_push($this->medias, new Media($matches[1][1], 'hd', 'gif', true, true));
                } else {
                    preg_match('/<iframe src=\'(https:\/\/www.tumblr.com\/video\/.*?)\'/', $http->response, $matches);
                    if (!empty($matches[1])) {
                        $http = new Http($matches[1]);
                        $http->referer = 'https://tumblr.com';
                        $http->run();
                        $url = Helpers::getStringBetween($http->response, '<source src="', '"');
                        if (!empty($url)) {
                            array_push($this->medias, new Media($url, 'sd', 'mp4', true, true));
                        }
                        $url = Helpers::getStringBetween($http->response, '"hdUrl":"', '"');
                        if (!empty($url)) {
                            array_push($this->medias, new Media($url, 'hd', 'mp4', true, true));
                        }
                    }
                }
            }
        }
    }
}