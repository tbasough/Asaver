<?php

class Twitch extends Downloader
{
    public function fetch($videoUrl)
    {
        $clipName = $this->extractClipName($videoUrl);
        if ($clipName != '') {
            $this->title = 'Twitch Video Clip from ' . $this->extractPosterName($videoUrl);
            $this->source = 'twitch';
            $this->thumbnail = 'https://blog.twitch.tv/assets/uploads/generic-email-header-1.jpg';
            $data = $this->apiRequest($clipName);
            //print_r($data);
            if (isset($data['data']['clip']['videoQualities']) != '') {
                foreach ($data['data']['clip']['videoQualities'] as $video) {
                    array_push($this->medias, new Media($video['sourceURL'], $video['quality'] . 'p', 'mp4', true, true));
                }
                usort($this->medias, array('Helpers', 'sortByQuality'));
            }
        }
    }

    private function extractClipName($videoUrl)
    {
        $parsedUrl = parse_url($videoUrl);
        $path = explode('/', $parsedUrl['path']);
        if (count($path) == 2) {
            return $path[1];
        } else if ($path[2] == 'clip' && isset($path[3]) != '') {
            return $path[3];
        } else {
            return false;
        }
    }

    private function extractPosterName($videoUrl)
    {
        $parsedUrl = parse_url($videoUrl);
        $path = explode('/', $parsedUrl['path']);
        if (count($path) == 2) {
            return $path[1];
        } else if ($path[2] == 'clip' && isset($path[3]) != '') {
            return $path[1];
        } else {
            return false;
        }
    }

    private function generateOperation($clipName)
    {
        $operation = array(
            0 =>
                array(
                    'operationName' => 'VideoAccessToken_Clip',
                    'variables' =>
                        array(
                            'slug' => $clipName,
                        ),
                    'extensions' =>
                        array(
                            'persistedQuery' =>
                                array(
                                    'version' => 1,
                                    'sha256Hash' => '9bfcc0177bffc730bd5a5a89005869d2773480cf1738c592143b5173634b7d15',
                                ),
                        ),
                ),
        );
        return json_encode($operation);
    }

    private function apiRequest($clipName)
    {
        $http = new Http('https://gql.twitch.tv/gql');
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->addCurlOption(CURLOPT_POSTFIELDS, $this->generateOperation($clipName));
        $http->addCurlOption(CURLOPT_HTTPHEADER, [
            'Client-Id: kimne78kx3ncx6brgo4mv6wki5h1ko',
            'Content-Type: application/json'
        ]);
        $http->run();
        return json_decode($http->response, true)[0];
    }
}