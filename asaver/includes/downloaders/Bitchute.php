<?php

class Bitchute extends Downloader
{
    public static $cookieFile = __DIR__ . '/../../cookies/bitchute-cookie.txt';

    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        if (file_exists(self::$cookieFile)) {
            $http->addCurlOption(CURLOPT_COOKIEFILE, self::$cookieFile);
        }
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
        $this->source = 'bitchute';
        $this->thumbnail = Helpers::getStringBetween($http->response, 'poster="', '"');
        $mediaUrl = Helpers::getStringBetween($http->response, '<source src="', '"');
        if (!empty($mediaUrl)) {
            $this->medias[] = new Media($mediaUrl, 'hd', 'mp4', true, true);
        } else {
            preg_match_all('/<iframe .*? src="(.*?)" frameborder="0" allowfullscreen>/', $http->response, $matches);
            if (!empty($matches[1][0])) {
                $embedUrl = $matches[1][0];
                $embed = new Http($embedUrl);
                $embed->run();
                preg_match_all('/h\.f\[".*"\]=(.*)"live":/', $embed->response, $matches);
                if (!empty($matches[1][0])) {
                    $data = json_decode($matches[1][0] . '"live:":0}', true);
                    $this->thumbnail = $data['i'];
                    if (!empty($data['ua']['mp4'])) {
                        foreach ($data['ua']['mp4'] as $quality => $video) {
                            $this->medias[] = new Media($video['url'], $quality . 'p', 'mp4', true, true);
                        }
                    }
                    if (!empty($data['ua']['webm'])) {
                        foreach ($data['ua']['webm'] as $quality => $video) {
                            $this->medias[] = new Media($video['url'], $quality . 'p', 'webm', true, true);
                        }
                    }
                }
                usort($this->medias, array('Helpers', 'sortByQuality'));
            }
        }
    }
}