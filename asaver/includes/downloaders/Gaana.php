<?php

class Gaana extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $artistId = Helpers::getStringBetween($http->response, '"artist_id":"', '","seokey"');
        $trackId = Helpers::getStringBetween($http->response, '"track_id":"', '","seokey"');
        if ($artistId != '' && $trackId != '') {
            $artistSongs = $this->getArtistSongs($artistId);
            if (!empty($artistSongs['entities'])) {
                $song = [];
                foreach ($artistSongs['entities'] as $artistSong) {
                    if ($artistSong['entity_id'] == $trackId) {
                        $song = $artistSong;
                        break;
                    }
                }
                if (!empty($song)) {
                    $songDetails = [];
                    foreach ($song['entity_info'] as $info) {
                        if (!empty($info['key']) && !empty($info['value'])) {
                            $songDetails[$info['key']] = $info['value'];
                        }
                    }
                    $this->title = $song['name'];
                    $this->source = 'gaana';
                    $this->thumbnail = $song['artwork_medium'];
                    $playlistUrl = '';
                    if (!empty($songDetails['stream_url']['high']['message'])) {
                        $playlistUrl = $this->decryptUrl($songDetails['stream_url']['high']['message']);
                    } else if (!empty($songDetails['stream_url']['medium']['message'])) {
                        $playlistUrl = $this->decryptUrl($songDetails['stream_url']['medium']['message']);
                    }
                    if (filter_var($playlistUrl, FILTER_VALIDATE_URL)) {
                        $playlistUrl = new Http($playlistUrl);
                        $playlistUrl->run();
                        $playlistUrl = explode("\n", $playlistUrl->response ?? '');
                        if (count($playlistUrl) >= 4) {
                            $playlistUrl = 'https://stream-cdn.gaana.com' . $playlistUrl[3];
                            $streamPlaylist = new Http($playlistUrl);
                            $streamPlaylist->run();
                            $streamPlaylist = $streamPlaylist->response;
                            preg_match_all('/\d{1,3}.ts(.*)/', $streamPlaylist, $matches);
                            $length = count($matches[0]);
                            if ($length >= 1) {
                                $playlistPath = parse_url($playlistUrl, PHP_URL_PATH);
                                for ($i = 0; $i < $length; $i++) {
                                    $matches[0][$i] = 'https://stream-cdn.gaana.com' . str_replace('index.m3u8', $matches[0][$i], $playlistPath);
                                }
                                $streamPlaylist = $matches[0];
                                $cache = new Cache('gaana-' . $trackId, 'json', json_encode($streamPlaylist));
                                $media = new Media($cache->url, '256kbps', 'mp3', false, true);
                                $media->chunked = true;
                                $chunkSize = new Http($streamPlaylist[0]);
                                $media->size = $chunkSize->getFileSize() * count($streamPlaylist);
                                array_push($this->medias, $media);
                            }
                        }
                    }
                    /*
                    if ($playlistUrl != '') {
                        $playlistHost = parse_url($playlistUrl, PHP_URL_HOST);
                        $playlist = new Http($playlistUrl);
                        $playlist->run();
                        echo $playlist->response;
                        preg_match_all('/(.*)\/songs\/(.*)/', $playlist->response, $matches);
                        if (!empty($matches[0][0]) && !filter_var($matches[0][0], FILTER_VALIDATE_URL)) {
                            $matches[0][0] = 'https://' . $playlistHost . $matches[0][0];
                        }
                        if (!empty($matches[0][0])) {
                            $chunkPlaylist = $matches[0][0];
                            $chunkPlaylist = new Http($chunkPlaylist);
                            $chunkPlaylist->run();
                            print_r($chunkPlaylist);
                            preg_match_all('/https(.*)/', $chunkPlaylist->response, $matches);
                            if (!empty($matches[0][0])) {
                                $chunks = $matches[0][0];
                                $cache = new Cache('gaana-' . $trackId, 'json', json_encode($chunks));
                                $media = new Media($cache->url, '256kbps', 'mp3', false, true);
                                $chunkSize = new Http($chunks[0]);
                                $media->size = $chunkSize->getFileSize() * count($chunks);
                                array_push($this->medias, $media);
                            }
                        }
                    }*/
                    $this->duration = $songDetails['duration'] ?? '';
                }
            }
        }
    }

    private function getArtistSongs($artistId)
    {
        $http = new Http('https://wrapapi.com/use/txxx/gaana/artistSongs/0.0.1?artistId=' . $artistId . '&wrapAPIKey=7YQu0z1Qy6xPA5Dg6cReTQprt96iGj3g');
        $http->run();
        return json_decode($http->response, true);
    }

    private function getArtistSongs2($artistId)
    {
        $http = new Http('https://gaana.com/apiv2?factor=11&id=' . $artistId . 'type=albumArtistSongs');
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->run();

        return json_decode($http->response, true);
    }

    public function fetch2($videoUrl)
    {
        $songName = '';
        $explodeUrl = explode('/', parse_url($videoUrl, PHP_URL_PATH));
        if (count($explodeUrl) >= 3) {
            if ($explodeUrl[1] == 'song') {
                $songName = $explodeUrl[2];
            }
        }
        if (!empty($songName)) {
            $data = $this->gaanaApi($songName);
            if (!empty($data['tracks'][0]['youtube_id'])) {
                require_once __DIR__ . '/YouTube.php';
                $yt = new YouTube();
                $yt->fetch('https://www.youtube.com/watch?v=' . $data['tracks'][0]['youtube_id']);
                $this->title = $yt->title;
                $this->source = $yt->source;
                $this->thumbnail = $yt->thumbnail;
                $this->medias = $yt->medias;
                $this->duration = $yt->duration;
            }
        }
    }

    public function fetchLegacy($videoUrl)
    {
        $songName = '';
        $explodeUrl = explode('/', parse_url($videoUrl, PHP_URL_PATH));
        if (count($explodeUrl) >= 3) {
            if ($explodeUrl[1] == 'song') {
                $songName = $explodeUrl[2];
            }
        }
        if (!empty($songName)) {
            $data = $this->gaanaApi($songName);
            if (isset($data['tracks'][0]) != '') {
                $playlistUrl = $this->decryptUrl($data['tracks'][0]['urls']['high']['message']);
                if (filter_var($playlistUrl, FILTER_VALIDATE_URL)) {
                    $playlistUrl = new Http($playlistUrl);
                    $playlistUrl->run();
                    $playlistUrl = explode("\n", $playlistUrl->response ?? '');
                    if (count($playlistUrl) >= 4) {
                        $playlistUrl = 'https://stream-cdn.gaana.com' . $playlistUrl[3];
                        $streamPlaylist = new Http($playlistUrl);
                        $streamPlaylist->run();
                        $streamPlaylist = $streamPlaylist->response;
                        preg_match_all('/\d{1,3}.ts(.*)/', $streamPlaylist, $matches);
                        $length = count($matches[0]);
                        if ($length >= 1) {
                            $playlistPath = parse_url($playlistUrl, PHP_URL_PATH);
                            for ($i = 0; $i < $length; $i++) {
                                $matches[0][$i] = 'https://stream-cdn.gaana.com' . str_replace('index.m3u8', $matches[0][$i], $playlistPath);
                            }
                            $streamPlaylist = $matches[0];
                            $cache = new Cache('gaana-' . $data['tracks'][0]['track_id'], 'json', json_encode($streamPlaylist));
                            $this->title = $data['tracks'][0]['track_title'];
                            $this->source = 'gaana';
                            $this->thumbnail = $data['tracks'][0]['artwork_web'];
                            $this->duration = $data['tracks'][0]['duration'];
                            $media = new Media($cache->url, '256kbps', 'mp3', false, true);
                            $chunkSize = new Http($streamPlaylist[0]);
                            $media->size = $chunkSize->getFileSize() * count($streamPlaylist);
                            array_push($this->medias, $media);
                        }
                    }
                }
            }
        }
    }

    private function gaanaApi($songName)
    {
        $http = new Http("http://api.gaana.com/?type=song&subtype=song_detail&seokey=$songName&token=b2e6d7fbc136547a940516e9b77e5990&format=JSON");
        $http->run();
        return json_decode($http->response, true);
    }

    private function decryptUrl($url)
    {
        $ciphering = "AES-128-CBC";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $decryption_iv = utf8_encode('asd!@#!@#@!12312');
        $decryption_key = utf8_encode('g@1n!(f1#r.0$)&%');
        return openssl_decrypt($url, $ciphering,
            $decryption_key, $options, $decryption_iv);
    }
}