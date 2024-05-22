<?php

class Ted extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('/"__INITIAL_DATA__":(.*?)}\)/', $http->response, $match);
        if (isset($match[1][0]) != '') {
            $data = json_decode($match[1][0], true);
            $this->title = $data['name'];
            $this->source = 'ted';
            $this->thumbnail = $data['talks'][0]['hero'];
            $this->duration = $data['talks'][0]['duration'];
            if (isset($data['talks'][0]['downloads']['nativeDownloads']) != '') {
                foreach ($data['talks'][0]['downloads']['nativeDownloads'] as $quality => $url) {
                    $this->medias[] = new Media($url, $quality, 'mp4', true, true);
                }
            } else if (isset($json['talks'][0]['player_talks'][0]['resources']['h264']) != '') {
                $url = $data['talks'][0]['player_talks'][0]['resources']['h264'][0]['file'];
                $this->medias[] = new Media($url, 'sd', 'mp4', true, true);
            }
            if (isset($data['talks'][0]['downloads']['audioDownload']) != '') {
                $url = new Http($data['talks'][0]['downloads']['audioDownload']);
                $url = $url->getLongUrl();
                $this->medias[] = new Media($url, '128kbps', 'mp3', false, true);
            }
        }
        preg_match_all('/<script id="__NEXT_DATA__" type="application\/json">(.*)<\/script><script nomodule=""/', $http->response, $match);
        if(!empty($match[1][0])){
            $data = json_decode($match[1][0], true);
            if(!empty($data['props']['pageProps']['videoData'])){
                $data = $data['props']['pageProps']['videoData'];
                $this->title = $data['title'];
                $this->source = 'ted';
                $this->thumbnail = $data['primaryImageSet'][0]['url'];
                $this->duration = $data['duration'];
                $playerData = json_decode($data['playerData'], true);
                if(!empty($playerData['resources']['h264'])){
                    foreach ($playerData['resources']['h264'] as $video){
                        $this->medias[] = new Media($video['file'], 'HD', 'mp4', true, true);
                    }
                }
            }
        }
    }
}