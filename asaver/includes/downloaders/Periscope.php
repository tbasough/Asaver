<?php

class Periscope extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoUrl = strtok($videoUrl, '?');
        $broadcastId = '';
        if (isset(explode("/", $videoUrl)[4]) != '') {
            $broadcastId = explode("/", $videoUrl)[4];
        }
        if ($broadcastId != '') {
            $http = new Http("https://proxsee.pscp.tv/api/v2/accessVideoPublic?broadcast_id=$broadcastId&replay_redirect=false");
            $http->run();
            $data = json_decode($http->response, true);
            if (!isset($data['replay_url'])) {
                $data['replay_url'] = $data['hls_url'];
            }
            $this->title = $data['broadcast']['status'];
            $this->source = 'periscope';
            $http = new Http($data['broadcast']['image_url']);
            $http->run();
            $cache = new Cache('periscope-' . $broadcastId, 'jpg', $http->response);
            $this->thumbnail = $cache->url;
            $http = new Http($data['replay_url']);
            $http->run();
            $playlist = $http->response;
            $parsedUrl = parse_url($data['replay_url']);
            $playlistHost = $parsedUrl['host'];
            $playlistPath = $parsedUrl['path'];
            preg_match_all('/(\d{2,5}),CODECS.*?\s(.*?)\s/', $playlist, $matches);
            if (isset($matches[2][0]) == '') {
                preg_match_all('/(.*?).ts/', $playlist, $matches);
                if (isset($matches[0][0]) != "") {
                    $length = count($matches[0]);
                    for ($i = 0; $i < $length; $i++) {
                        $matches[0][$i] = preg_replace('/(\w{3,50}).m3u8/', $matches[0][$i], "https://" . $playlistHost . $playlistPath);
                    }
                    $cache = new Cache('periscope-' . $broadcastId, 'json', json_encode($matches[0]));
                    $chunkSize = $this->chunkSize($matches[0][0]);
                    $media = new Media($cache->url, '720p', 'mp4', true, true);
                    $media->size = $chunkSize * $length;
                    $media->chunked = true;
                }
            } else {
                $playlists = array();
                $length = count($matches[2]);
                for ($i = 0; $i < $length; $i++) {
                    if ($i > 0) {
                        break;
                    }
                    $playlists[$i]['quality'] = $matches[1][$i];
                    $playlists[$i]['url'] = 'https://' . $playlistHost . $matches[2][$i];
                    $http = new Http($playlists[$i]['url']);
                    $http->run();
                    preg_match_all('/(.*).ts/', $http->response, $matches2);
                    $cache = null;
                    if (isset($matches2[0][0])) {
                        for ($j = 0; $j < $length; $j++) {
                            $playlists[$i]['chunks'][$j] = preg_replace('/(\w{3,50}).m3u8/', $matches2[0][$j], $playlists[$i]["url"]);
                        }
                        array_push($playlists[$i]['chunks'], $playlists[$i]['url']);
                        $cache = new Cache('periscope-' . $broadcastId . '-' . $playlists[$i]["quality"], 'json', json_encode($playlists[$i]['chunks']));
                    }
                    $chunkSize = $this->chunkSize($playlists[$i]['chunks'][0]);
                    $media = new Media($cache->url, $playlists[$i]['quality'] . 'p', 'mp4', true, true);
                    $media->size = $chunkSize * count($playlists[$i]["chunks"]);
                    $media->chunked = true;
                }
            }
        }
    }

    private function chunkSize($url)
    {
        $http = new Http($url);
        return $http->getFileSize();
    }

}