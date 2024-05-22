<?php

class Twitter extends Downloader
{
    public $accessToken = 'AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA';

    public function fetch($videoUrl)
    {
        $videoUrl = preg_replace('/\?.*/', '', $videoUrl);
        $tweetId = $this->extractVideoId($videoUrl);
        $data = $this->getTweetData($tweetId);
        if (!isset($data['entities']['media']) && isset($data['entities']['urls'][0]['expanded_url']) && Helpers::isContains($data['entities']['urls'][0]['expanded_url'], 'https://twitter.com/i/broadcasts/')) {
            preg_match('/https:\/\/twitter.com\/i\/broadcasts\/(.*)/', $data['entities']['urls'][0]['expanded_url'], $matches);
            if (count($matches) >= 2) {
                $broadcastId = $matches[1];
            }
        }
        if (!empty($data['full_text'])) {
            $this->title = $this->cleanTitle($data['full_text']);
        } else {
            $this->title = 'Twitter Video';
        }
        $this->source = 'twitter';
        $this->thumbnail = $data['entities']['media'][0]['media_url_https'] ?? null;
        if (isset($data['extended_entities']['media'][0]) != '') {
            foreach ($data['extended_entities']['media'][0]['video_info']['variants'] as $video) {
                if ($video['content_type'] == 'video/mp4') {
                    $this->medias[] = new Media($video['url'], $this->getQuality($video['url']), 'mp4', true, true);
                }
            }
        }
        usort($this->medias, array('Helpers', 'sortByQuality'));
    }

    private function extractVideoId($videoUrl)
    {
        $domain = str_ireplace('www.', '', parse_url($videoUrl, PHP_URL_HOST));
        $last_char = substr($videoUrl, -1);
        if ($last_char == '/') {
            $videoUrl = substr($videoUrl, 0, -1);
        }
        switch ($domain) {
            default:
                $arr = explode('/', $videoUrl);
                return end($arr);
                break;
        }
    }

    private function getTweetData($tweetId)
    {
        return $this->codebirdRequest("1.1/statuses/show/$tweetId.json?tweet_mode=extended&include_entities=true");
    }

    private function broadcastData($broadcastId)
    {
        return $this->codebirdRequest("1.1/broadcasts/show.json?ids=$broadcastId&include_events=true");
    }

    private function codebirdRequest($path)
    {
        $http = new Http(site_url() . '/wp-content/plugins/asaver/assets/codebird-cors-proxy/' . $path);
        $http->addHeader('x-authorization', 'Bearer ' . $this->accessToken);
        $http->run();
        return json_decode($http->response, true);
    }

    private function cleanTitle($string)
    {
        $title = preg_replace('/(https?:\/\/([-\w\.]+[-\w])+(:\d+)?(\/([\w\/_\.#-]*(\?\S+)?[^\.\s])?).*$)|(\n)/', '', $string);
        return !empty($title) ? $title : $string;
    }

    private function getQuality($url)
    {
        preg_match_all('/vid\/(.*?)x(.*?)\//', $url, $output);
        if (!empty($output[2][0])) {
            return $output[2][0] . 'p';
        } else {
            return 'gif';
        }
    }
}