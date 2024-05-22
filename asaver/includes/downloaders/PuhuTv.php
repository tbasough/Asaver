<?php

class PuhuTv extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoUrl = preg_replace('/(.*)-detay/', '$1-izle', $videoUrl);
        $http = new Http($videoUrl);
        $http->run();
        $videoId = Helpers::getStringBetween($http->response, 'data-asset-id="', '"');
        if ($videoId != '') {
            $this->title = Helpers::getStringBetween($http->response, '<title>', ' |');
            $this->source = 'puhutv';
            $this->thumbnail = Helpers::getStringBetween($http->response, "property='og:image' content='", "'");
            $apiUrl = 'https://puhutv.com/api/assets/' . $videoId . '/videos';
            $http = new Http($apiUrl);
            $http->run();
            $data = json_decode($http->response, true);
            $mainPlaylist = $this->findBestQuality($data['data']['videos'])['url'];
            $http = new Http($mainPlaylist);
            $http->run();
            $mainPlaylistData = $http->response;
            preg_match_all('/#EXT-X-STREAM-INF:BANDWIDTH=\d{3,10},RESOLUTION=\d{3,4}x(\d{3,4})\s(.*)/', $mainPlaylistData, $matches);
            if (!empty($matches[2])) {
                $limit = count($matches[1]);
                preg_match('/(.*?)playlist.m3u8/', $mainPlaylist, $cdnUrl);
                if (!empty($cdnUrl[1])) {
                    $cdnUrl = $cdnUrl[1];
                    for ($j = 0; $j < $limit; $j++) {
                        $playlistUrl = $cdnUrl . $matches[2][$j];
                        $http = new Http($playlistUrl);
                        $http->run();
                        $playlistData = $http->response;
                        preg_match_all('/.*\.ts/', $playlistData, $chunks);
                        preg_match_all('/ts\?st(.*)/', $playlistData, $signature);
                        if (!empty($signature[1])) {
                            $signature = '?st' . $signature[1][0];
                        } else {
                            $signature = '';
                        }
                        $baseUrl = dirname($playlistUrl) . '/';
                        if (!empty($chunks[0])) {
                            preg_match('/(\d{3,4})p/', $playlistUrl, $quality);
                            if (count($quality) === 2) {
                                $format = true;
                            } else {
                                $format = false;
                            }
                            $quality = $matches[1][$j];
                            $chunks = $chunks[0];
                            $length = count($chunks);
                            for ($i = 1; $i < $length; $i++) {
                                if ($format) {
                                    $chunks[$i] = $baseUrl . $chunks[$i] . $signature;
                                } else {
                                    $chunks[$i] = $cdnUrl . $chunks[$i] . $signature;
                                }
                            }
                            $cache = Helpers::createChunkCache($chunks, 'puhutv-' . $videoId . '-' . $quality);
                            $media = new Media($cache->url, $quality . 'p', 'mp4', true, true);
                            $media->size = Helpers::getChunkedSize($chunks[1], $length);
                            $media->chunked = true;
                            $this->medias[] = $media;
                        }
                    }
                }
            }
        }
    }

    private function findBestQuality($videos)
    {
        $best = $videos[0];
        $length = count($videos);
        for ($i = 1; $i < $length; $i++) {
            if ($videos[$i]['quality'] != null & $videos[$i]['quality'] > $best['quality']) {
                $best = $videos[$i];
            }
        }
        return $best;
    }
}