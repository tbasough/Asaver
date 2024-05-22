<?php

class Vimeo extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        if (preg_match_all('/window.vimeo.clip_page_config.player\s*=\s*({.+?})\s*;\s*\n/', $http->response, $match)) {
            $configUrl = json_decode($match[1][0], true)["config_url"];
            $data = new Http($configUrl);
            $data->run();
            $data = json_decode($data->response, true);
        } else {
            $data = json_decode(Helpers::getStringBetween($http->response, "var config = ", "; if (!config.request)"), true);
        }
        if ($data['video']['title'] != '') {
            $this->title = $data['video']['title'];
            $this->source = 'vimeo';
            $this->thumbnail = reset($data['video']['thumbs']);
            $this->duration = $data['video']['duration'];
            foreach ($data['request']['files']['progressive'] as $video) {
                array_push($this->medias, new Media($video['url'], $video['quality'], 'mp4', true, true));
            }
            usort($this->medias, array('Helpers', 'sortByQuality'));
        }
    }
}