<?php

class Flickr extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $secretKey = Helpers::getStringBetween($http->response, '"secret":"', '"');
        $siteKey = Helpers::getStringBetween($http->response, 'flickr.api.site_key = "', '";');
        $mediaId = Helpers::getStringBetween($http->response, '"photoId":"', '"');
        $apiUrl = "https://api.flickr.com/services/rest?photo_id=$mediaId&secret=$secretKey&method=flickr.video.getStreamInfo&api_key=$siteKey&format=json&nojsoncallback=1";
        $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
        $this->source = 'flickr';
        $this->thumbnail = Helpers::getStringBetween($http->response, '<meta property="og:image" content="', '"/>');
        if ($mediaId != '' && $siteKey != '' && $secretKey != '') {
            $streams = new Http($apiUrl);
            $streams->run();
            $streams = json_decode($streams->response, true)['streams']['stream'];
            foreach ($streams as $stream){
                $fileSize = new Http($stream['_content']);
                $fileSize = $fileSize->getFileSize();
                if (!empty($fileSize)) {
                    $media = new Media($stream['_content'], (string)$stream['type'], 'mp4', true, true);
                    $media->size = $fileSize;
                    array_push($this->medias, $media);
                }
            }
            usort($this->medias, array('Helpers', 'sortByQuality'));
        }
    }
}