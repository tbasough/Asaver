<?php

class Vkontakte extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoUrl = str_replace('m.vk.com', 'vk.com', $videoUrl);
        $http = new Http($videoUrl);
        $http->run();
        $query = html_entity_decode(Helpers::getStringBetween($http->response, 'https://vk.com/video_ext.php?', '"'));
        parse_str($query, $videoIds);
        $videoId = Helpers::getStringBetween($http->response, '"video_id":"', '",');
        if (empty($videoId) && isset($videoIds['oid']) != '' && isset($videoIds['id']) != '') {
            $videoId = $videoIds['oid'] . '_' . $videoIds['id'];
        }
        $this->title = 'VK Video';
        $this->source = 'vkontakte';
        $this->thumbnail = Helpers::getStringBetween($http->response, 'data-id="' . $videoId . '" data-add-hash="" data-thumb="', '"');
        if ($this->thumbnail == '') {
            $this->thumbnail = html_entity_decode(Helpers::getStringBetween($http->response, 'property="og:image:secure_url" content="', '"/>'));
        }
        $this->duration = Helpers::getStringBetween($http->response, 'property="og:video:duration" content="', '"/>');
        $this->duration = $this->duration;
        preg_match_all('/"url(\d{3})":"(.*?)"/', $http->response, $output);
        if (count($output) === 3) {
            $length = count($output[1]);
            for ($i = 0; $i < $length; $i++) {
                $videoUrl = str_replace("\\", "", $output[2][$i]);
                array_push($this->medias, new Media($videoUrl, $output[1][$i] . 'p', 'mp4', true, true));
            }
        } else {
            $data = $this->getVideoData($videoId);
            preg_match_all('/"cache(\d{3})":"(.*?)"/', $data, $matches);
            if (!empty($matches[1]) && !empty($matches[2])) {
                $length = count($matches[1]);
                for ($i = 0; $i < $length; $i++) {
                    $videoUrl = str_replace("\\", "", $matches[2][$i]);
                    array_push($this->medias, new Media($videoUrl, $matches[1][$i] . 'p', 'mp4', true, true));
                }
            } else if (!empty(str_replace("\\", "", Helpers::getStringBetween($data, '"postlive_mp4":"', '"')))) {
                $videoUrl = str_replace("\\", "", Helpers::getStringBetween($data, '"postlive_mp4":"', '"'));
                if (!empty($videoUrl)) {
                    array_push($this->medias, new Media($videoUrl, 'hd', 'mp4', true, true));
                }
            } else if (!empty(Helpers::getStringBetween($http->response, '/><source src="', '" type="video/mp4" />'))) {
                $this->thumbnail = Helpers::getStringBetween($http->response, 'poster="', '"');
                $videoUrl = Helpers::getStringBetween($http->response, '/><source src="', '" type="video/mp4" />');
                if (!empty($videoUrl)) {
                    array_push($this->medias, new Media($videoUrl, 'hd', 'mp4', true, true));
                }
            }
        }
    }

    private function getVideoData($videoId)
    {
        $http = new Http('https://vk.com/al_video.php?act=show');
        $http->addCurlOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $http->addCurlOption(CURLOPT_POSTFIELDS, 'act=show&al=1&autoplay=0&list=&module=videocat&video=' . $videoId);
        $http->addHeader('x-requested-with', 'XMLHttpRequest');
        $http->addHeader('referer', 'https://vk.com');
        $http->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $http->run();
        return $http->response;
    }
}