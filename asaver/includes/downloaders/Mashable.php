<?php

class Mashable extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('@<script class="playerMetadata" type="application/json">(.*?)</script>@si', $http->response, $match);
        preg_match_all('/<script type="application\/ld\+json">{"@context": "https:\/\/schema.org", "@type": "VideoObject",(.*?)<\/script>/', $http->response, $output);
        preg_match_all('/data: ({.*?}),/', $http->response, $output2);
        $this->source = 'mashable';
        if (!empty($match[1][0])) {
            $data = json_decode($match[1][0], true);
            $this->title = $data['player']['title'];
            $this->thumbnail = $data['player']['image'];
            foreach ($data['player']['sources'] as $url) {
                if (preg_match_all("@/(.*?).mp4@si", $url['file'], $match)) {
                    array_push($this->medias, new Media($url['file'], $match[1][1] . 'p', 'mp4', true, true));
                }
            }
            $this->medias = array_reverse($this->medias);
        } else if (!empty($output[0][0])) {
            $data = Helpers::getStringBetween($output[0][0], '<script type="application/ld+json">', '</script>');
            $data = json_decode($data, true);
            $this->title = $data['name'];
            $this->thumbnail = $data['thumbnailUrl'];
            array_push($this->medias, new Media($data['contentUrl'], 'hd', 'mp4', true, true));
        } else if (!empty($output2[1][0])) {
            $data = json_decode($output2[1][0], true);
            $this->title = $data['title'];
            $this->thumbnail = $data['thumbnail_url'];
            $this->duration = $data['duration'];
            foreach ($data['transcoded_urls'] as $url) {
                preg_match('/(\d{3,5}).mp4/', $url, $matches);
                if (!empty($matches[1])) {
                    array_push($this->medias, new Media($url, $matches[1] . 'p', 'mp4', true, true));
                }
            }
            usort($this->medias, array('Helpers', 'sortByQuality'));
        }
    }
}