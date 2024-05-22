<?php

class Imdb extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoId = $this->extractVideoId($videoUrl);
        if ($videoId !== null) {
            $embedUrl = "https://www.imdb.com/video/imdb/$videoId/imdb/embed";
            $embedPage = new Http($embedUrl);
            $embedPage->run();
            $embedPage = $embedPage->response;
            $data = Helpers::getStringBetween($embedPage, '<script class="imdb-player-data" type="text/imdb-video-player-json">', '</script>');
            $data = json_decode($data, true);
            if (!empty($videoData['videoPlayerObject']['video']['videoInfoList'] ?? '')) {
                $this->title = Helpers::getStringBetween($embedPage, '<meta property="og:title" content="', '"/>');
                $this->source = 'imdb';
                $this->thumbnail = Helpers::getStringBetween($embedPage, '<meta property="og:image" content="', '">');
                foreach ($data['videoPlayerObject']['video']['videoInfoList'] as $stream) {
                    if ($stream['videoMimeType'] == 'video/mp4') {
                        array_push($this->medias, new Media($stream['videoUrl'], 'hd', 'mp4', true, true));
                    }
                }
            } else {
                $this->fetchLegacy($videoUrl);
            }
        }
    }

    private function extractVideoId($videoUrl)
    {
        preg_match('/vi\d{4,20}/', $videoUrl, $match);
        return $match[0] ?? null;
    }

    private function fetchLegacy($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match('/"playbackData":\[(.*)\],"videoInfoKey"/', $http->response, $matches);
        if (count($matches) >= 2) {
            $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
            $this->source = 'imdb';
            $this->thumbnail = Helpers::getStringBetween($http->response, 'property="og:image" content="', '">');
            $json = '[' . $matches[1] . ']';
            $streams = json_decode(json_decode($json, true)[0], true)[0]['videoLegacyEncodings'];
            if (count($streams) >= 2) {
                foreach ($streams as $stream) {
                    if ($stream['mimeType'] == 'video/mp4') {
                        array_push($this->medias, new Media($stream["url"], $stream['definition'], 'mp4', true, true));
                    }
                }
            }
        }
    }
}