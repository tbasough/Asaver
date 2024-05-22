<?php

class Espn extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoId = $this->extractVideoId($videoUrl);
        if ($videoId !== null) {
            $apiUrl = 'http://cdn.espn.com/core/video/clip/_/id/' . $videoId . '?xhr=1&device=desktop&country=us&lang=en&region=us&site=espn&edition-host=espn.com&one-site=true&site-type=full';
            $http = new Http($apiUrl);
            $http->run();
            $data = json_decode($http->response, true);
            if (isset($data['meta']) != '' && isset($data['content']['links']['source']) != '') {
                $this->title = $data['meta']['title'];
                $this->source = 'espn';
                $this->thumbnail = $data['meta']['image'];
                $this->duration = $data['content']['duration'];
                foreach ($data['content']['links']['source'] as $key => $link) {
                    switch ($key) {
                        case 'href':
                            array_push($this->medias, new Media($link, '360p', 'mp4', true, true));
                            break;
                        case 'HD':
                            array_push($this->medias, new Media($link['href'], '720p', 'mp4', true, true));
                            break;
                    }
                }
            }
        }
    }

    private function extractVideoId($videoUrl)
    {
        if (preg_match("/(id=\d{5,20}|id\/\d{5,20}|video\/\d{5,20})/", $videoUrl, $match)) {
            return (int)filter_var($match[0], FILTER_SANITIZE_NUMBER_INT);
        }
        return null;
    }
}