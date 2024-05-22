<?php

class Ifunny extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '"seo":{"title":"', ',"');
        $this->source = 'ifunny';
        preg_match_all('/window.__INITIAL_STATE__=(.*?);/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $data = json_decode($matches[1][0], true);
            if (!empty($data['seo']['video'])) {
                $this->thumbnail = $data['seo']['image'];
                $videoUrl = html_entity_decode($data['seo']['video']);
                array_push($this->medias, new Media($videoUrl, $data['seo']['videoWidth'] . 'p', 'mp4', true, true));
            }
        }
    }

    private function cleanUrl($url)
    {
        return str_replace('////', 'https://', $url);
    }
}