<?php

class Reddit extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
        $this->source = 'reddit';
        $this->thumbnail = Helpers::getStringBetween($http->response, '<meta property="og:image" content="', '"/>');
        if ($this->thumbnail == '') {
            $this->thumbnail = Helpers::getStringBetween($http->response, '"thumbnailUrl":"', '"');
            $this->thumbnail = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            }, $this->thumbnail);
        }
        $playlistUrl = Helpers::getStringBetween($http->response, '"dashUrl":"', '"');
        if ($playlistUrl == '') {
            preg_match_all('/<a href="https:\/\/(youtu.be|www.youtube.com|youtube.com)\/(.*?)"/', $http->response, $output);
            if (count($output) >= 3) {
                $ytUrl = "https://www.youtube.com/" . html_entity_decode($output[2][0]);
                require_once __DIR__ . "/YouTube.php";
                $yt = new YouTube();
                $yt->fetch($ytUrl);
                $this->title = $yt->title;
                $this->source = $yt->source;
                $this->thumbnail = $yt->thumbnail;
                $this->medias = $yt->medias;
                $this->duration = $yt->duration;
            }
        }
        $http = new Http($playlistUrl);
        $http->run();
        $xmlPlaylist = $http->response;
        preg_match_all('/<BaseURL>(.*)<\/BaseURL>/', $xmlPlaylist, $medias);
        $videoId = Helpers::getStringBetween(parse_url($playlistUrl, PHP_URL_PATH), '/', '/DASHPlaylist.mpd');
        if ($medias[1] != '') {
            $medias = $medias[1];
            foreach ($medias as $media) {
                $dashType = Helpers::getStringBetween($media, 'DASH_', '.');
                $mediaUrl = 'https://v.redd.it/' . $videoId . '/' . $media;
                $quality = $dashType == 'audio' ? '128 kbps' : $dashType . 'p';
                $extension = $dashType == 'audio' ? 'm4a' : 'mp4';
                array_push($this->medias, new Media($mediaUrl, $quality, $extension, true, false));
            }
        }
    }
}