<?php

class AkilliTv extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
        $this->source = 'akillitv';
        $this->thumbnail = $this->cleanUrl(Helpers::getStringBetween($http->response, 'property="og:image" content="', '"'));
        preg_match_all('/<source src="(.*?)" type="video\/mp4" data-quality="(.*?)"/', $http->response, $matches);
        if (isset($matches[1]) && isset($matches[2])) {
            $length = count($matches[1]);
            for ($i = 0; $i < $length; $i++) {
                $url = $this->cleanUrl($matches[1][$i]);
                array_push($this->medias, new Media('https:' . $url, $matches[2][$i], 'mp4', true, true));
            }
            $this->medias = array_reverse($this->medias);
        }
    }

    private function cleanUrl($url)
    {
        return str_replace('////', 'https://', $url);
    }
}