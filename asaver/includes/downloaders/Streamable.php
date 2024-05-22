<?php

class Streamable extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $data = Helpers::getStringBetween($http->response, 'var videoObject =', ';');
        $data = json_decode($data, true);
        if (isset($data) != '') {
            $this->title = $data['title'];
            if ($this->title == '') {
                $this->title = 'Streamable Video';
            }
            $this->source = 'streamable';
            $this->thumbnail = $data['thumbnail_url'];
            $this->duration = (int)ceil($data['duration']);
            foreach ($data['files'] as $key => $data) {
                $url = 'https:' . $data['url'];
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                array_push($this->medias, new Media($url, $data['height'] . 'p', $extension, true, true));
            }
            usort($this->medias, array('Helpers', 'sortByQuality'));
        }
    }
}