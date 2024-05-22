<?php

class Likee extends Downloader
{
    public function fetch($videoUrl)
    {
        $parsedUrl = parse_url($videoUrl);
        if ($parsedUrl['host'] == 'l.likee.video') {
            $http = new Http($videoUrl);
            $longUrl = $http->getLongUrl();
            $http = new Http($longUrl);
            $http->run();
            $videoId = Helpers::getStringBetween($http->response, '"post_id":"', '"');
            if(empty($videoId)){
                $videoId = Helpers::getStringBetween($http->response, '?postId=', '">');
                $videoId = trim($videoId);
            }
        } else {
            if (strpos($parsedUrl["path"], '/video/') !== false) {
                preg_match('/\/video\/(\d{3,})/', $videoUrl, $videoId);
                if (is_numeric($videoId[1] ?? "")) {
                    $videoId = $videoId[1];
                }
            } else {
                preg_match('/(\d{3,})/', $videoUrl, $videoId);
                if (is_numeric($videoId[0] ?? "")) {
                    $videoId = $videoId[0];
                }
            }
        }
        $data = $this->getVideoData($videoId);
        if (!empty($data['data'][0])) {
            $data = $data['data'][0];
            $this->title = ($data['msgText'] != '') ? $data['msgText'] : 'Likee Video ' . $videoId;
            $this->source = 'likee';
            $this->thumbnail = (isset($data['image2']) != "") ? $data['image2'] : $data['image1'];
            $this->duration = $data['optionData']['dur'] / 1000;
            array_push($this->medias, new Media($data['videoUrl'], min($data['videoHeight'], $data['videoWidth']) . 'p', 'mp4', true, true));
        }
    }

    private function getVideoData($videoId)
    {
        $http = new Http('https://likee.com/app/videoApi/getVideoInfo');
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->addCurlOption(CURLOPT_POSTFIELDS, "postIds=$videoId");
        $http->run();
        return json_decode($http->response, true);
    }
}