<?php

class Kickstarter extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('/window.current_project = "(.*)";/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $data = json_decode(html_entity_decode($matches[1][0]), true);
            if (!empty($data['video']['base'])) {
                $this->title = $data['name'];
                $this->source = 'kickstarter';
                $this->thumbnail = $data['photo']['full'];
                foreach ($data['video'] as $quality => $video) {
                    if ($quality == 'hls') {
                        $this->parseHls($video);
                    } else if ($quality == 'high') {
                        $quality = $data['video']['height'] . 'p';
                        $this->medias[] = new Media($video, $quality, 'mp4', true, true);
                    }
                }
                usort($this->medias, array('Helpers', 'sortByQuality'));
            }
        }
    }

    private function parseHls($hlsUrl)
    {
        $http = new Http($hlsUrl);
        $http->run();
        preg_match_all('/(.*)_(\d{3,4}).m3u8/', $http->response, $matches);
        if (count($matches) === 3) {
            $playlistNames = $matches[0];
            $qualities = $matches[2];
            $baseUrl = dirname($hlsUrl);
            for ($i = 0; $i < count($playlistNames); $i++) {
                if ($this->qualityExists($qualities[$i])) {
                    continue;
                }
                $playlistUrl = $baseUrl . '/' . $playlistNames[$i];
                $chunks = $this->parsePlaylist($baseUrl, $playlistUrl);
                if ($chunks != null) {
                    $cacheFileName = 'kickstarter-' . sha1($playlistUrl) . '-' . $qualities[$i];
                    $media = new Media(Helpers::createChunkCache($chunks, $cacheFileName)->url, $qualities[$i] . 'p', 'mp4', true, true);
                    $media->chunked = true;
                    $media->size = Helpers::getChunkedSize($chunks[0], count($chunks));
                    $this->medias[] = $media;
                }

            }
        }
    }

    private function parsePlaylist($baseUrl, $playlistUrl)
    {
        $http = new Http($playlistUrl);
        $http->run();
        preg_match_all('/(.*).ts/', $http->response, $matches);
        if (!empty($matches[0])) {
            $chunks = [];
            foreach ($matches[0] as $chunk) {
                $chunks[] = $baseUrl . '/' . $chunk;
            }
            return $chunks;
        } else {
            return null;
        }
    }
}