<?php

class ShareChat extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        preg_match_all('/<\/script><script data-rh="true" type="application\/ld\+json">(.*)<\/script>/', $http->response, $matches);
        if (!empty($matches[1][0])) {
            $data = json_decode($matches[1][0], true);
            if (!empty($data['contentUrl'])) {
                $this->title = $data['name'];
                $this->source = 'sharechat';
                $this->thumbnail = $data['thumbnailUrl'][1];
                $videoUrl = Helpers::getStringBetween($http->response, 'compressedVideoUrl":"', '"');
                $media = new Media($videoUrl, 'hd', 'mp4', true, true);
                $media->size = Helpers::getStringBetween($http->response, 'videoCompressedSize"":"', '"');
                $this->medias[] = $media;
            }
        }
    }
}