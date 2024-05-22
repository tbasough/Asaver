<?php

class BluTv extends Downloader
{
    public function fetch($videoUrl)
    {
        $path = parse_url($videoUrl, PHP_URL_PATH);
        $permissionData = $this->getPermission($path);
        if (isset($permission_data['status']) && $permission_data['status'] == 'ok') {
            $this->title = $permissionData['model']['Title'];
            $this->source = 'blutv';
            $this->thumbnail = 'https://blutv-images.mncdn.com/q/t/i/bluv2/100/0x0/' . $permissionData['model']['Image'];
            $embedUrl = 'https://www.blutv.com/actions/player/create/' . $permissionData['model']['Id'] . '?seek=&platform=com.blu&token=undefined';
            $embedPage = new Http($embedUrl);
            $embedPage->run();
            $embedPage = $embedPage->response;
            preg_match_all('/var qplayer_config = (.*)/', $embedPage, $matches);
            if (isset($matches[1][0]) && !empty($matches[1][0])) {
                $embedData = json_decode(substr($matches[1][0], 0, -1), true);
                if (isset($embed_data['model']['MediaFiles']) != '') {
                    $playlist = $embedData['model']['MediaFiles'][0]['Path'];
                    $playlistData = new Http($playlist);
                    $playlistData->run();
                    $playlistData = $playlistData->response;
                    preg_match_all('/RESOLUTION=.*?x(.*?),AUDIO=".*?\s(.*.m3u8)/', $playlistData, $matches);
                    $length = count($matches[1]);
                    if ($length > 0 && ($length === count($matches[2]))) {
                        $playlistName = pathinfo($playlist, PATHINFO_BASENAME);
                        for ($i = 0; $i < $length; $i++) {
                            $chunkPlaylist = str_replace($playlistName, $matches[2][$i], $playlist);
                            $chunkList = new Http($chunkPlaylist);
                            $chunkList->run();
                            $chunkList = $chunkList->response;
                            preg_match_all('/.*.ts/', $chunkList, $matches2);
                            if (isset($matches2[0]) != '') {
                                $chunks = $matches2[0];
                                for ($j = 0; $j < count($chunks); $j++) {
                                    $chunks[$j] = str_replace(pathinfo($chunkPlaylist, PATHINFO_BASENAME), $chunks[$j], $chunkPlaylist);
                                }
                                $chunkSize = new Http($chunks[0]);
                                $chunkSize = $chunkSize->getFileSize();
                                $chunkFile = new Cache('blutv-' . $permission_data['model']['IId'] . '-' . $matches[1][$i], 'json', json_encode($chunks));
                                $media = new Media($chunkFile->url, $matches[1][$i] . 'p', 'mp4', true, true);
                                $media->size = $chunkSize * count($chunks);
                                $media->chunked = true;
                                array_push($this->medias, $media);
                            }
                        }
                    }
                }
            }
        }
    }

    private function getPermission($path)
    {
        $http = new Http('https://www.blutv.com/actions/account/getpermission');
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->addCurlOption(CURLOPT_POSTFIELDS, http_build_query(['package' => 'ALL', 'platform' => 'com.blu', 'segment' => 'default', 'url' => $path, 'usetoken' => true]));
        $http->addHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        $http->run();
        return json_decode($http->response, true);
    }
}