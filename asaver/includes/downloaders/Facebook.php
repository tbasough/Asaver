<?php

class Facebook extends Downloader
{
    private $cookies = '';

    public function fetch($videoUrl)
    {
        $this->cookies = get_option('asr_facebook_cookies');
        $url = $this->getLongUrl($this->removeM($videoUrl));
        $webPage = $this->urlGetContents($url);
        preg_match_all('/<script type="application\/ld\+json" nonce="\w{3,10}">(.*?)<\/script><link rel="canonical"/', $webPage, $matches);
        preg_match_all('/"video":{(.*?)},"video_home_www_injection_related_chaining_section"/', $webPage, $matches2);
        preg_match_all('/"playable_url":"(.*?)"/', $webPage, $matches3);
        preg_match_all('/<script type="application\/ld\+json" nonce=".*?">(.*?)<\/script>/', $webPage, $matches4);
        preg_match_all('/RelayPrefetchedStreamCache","next",\[\],(.*)],\["VideoPlayerSpinner\.react"]/', $webPage, $matches5);
        $this->source = 'facebook';
        switch (true) {
            case(!empty($matches5[1][0])):
                $data = json_decode($matches5[1][0], true)[1];
                $postData = $data['__bbox']['result']['data']['node']['comet_sections']['content']['story']['comet_sections']['message']['story']['message']['text'] ?? null;
                $videoData = $data['__bbox']['result']['data']['node']['comet_sections']['content']['story']['attachments'][0]['styles']['attachment']['media'];
                $this->title = !empty($postData) ? $postData : 'Facebook Video';
                $this->thumbnail = $videoData['thumbnailImage']['uri'];
                if (!empty($videoData['playable_url'])) {
                    $this->medias[] = new Media($videoData['playable_url'], 'sd', 'mp4', true, true);
                }
                if (!empty($videoData['playable_url_quality_hd'])) {
                    $this->medias[] = new Media($videoData['playable_url_quality_hd'], 'sd', 'mp4', true, true);
                }
                break;
            case (!empty($matches4[1][0]) && empty($matches3[1][0])):
                $data = json_decode($matches4[1][0], true);
                if(!empty($data['video'])){
                    $this->title = $data['video']['name'];
                    $this->thumbnail = $data['video']['thumbnailUrl'];
                    $this->medias[] = new Media($data['video']['embedUrl'], $data['video']['videoQuality'], 'mp4', true, true);
                }
                break;
            case (!empty($matches[1][0])):
                $data = json_decode($matches[1][0], true);
                if (!empty($data['@type']) && $data['@type'] == 'VideoObject') {
                    $this->title = $data['name'];
                    $this->thumbnail = $data['thumbnailUrl'];
                    if (isset($data['contentUrl']) != "") {
                        $this->medias[] = new Media($data['contentUrl'], 'sd', 'mp4', true, true);
                    }
                    $hdLink = Helpers::getStringBetween($webPage, 'hd_src:"', '"');
                    if (!empty($hdLink)) {
                        $this->medias[] = new Media($hdLink, 'hd', 'mp4', true, true);
                    }
                }
                break;
            case (!empty($matches2[1][0])):
                $json = '{' . $matches2[1][0] . '}';
                $data = json_decode($json, true);
                if (isset($data['story']['attachments'][0]['media']['__typename']) != '' && $data['story']['attachments'][0]['media']['__typename'] == 'Video') {
                    $this->title = $data['story']['message']['text'];
                    $this->thumbnail = $data['story']['attachments'][0]['media']['thumbnailImage']['uri'];
                    if (isset($data['story']['attachments'][0]['media']['playable_url']) != '') {
                        $this->medias[] = new Media($data['story']['attachments'][0]['media']['playable_url'], 'sd', 'mp4', true, true);
                    }
                    if (isset($data['story']['attachments'][0]['media']['playable_url_quality_hd']) != '') {
                        $this->medias[] = new Media($data['story']['attachments'][0]['media']['playable_url_quality_hd'], 'hd', 'mp4', true, true);
                    }
                }
                break;
            case (!empty($matches3[1][0])):
                preg_match('/"preferred_thumbnail":{"image":{"uri":"(.*?)"/', $webPage, $thumbnail);
                preg_match_all('/"playable_url_quality_hd":"(.*?)"/', $webPage, $hdLink);
                $this->title = 'Facebook Video';
                $this->thumbnail = isset($thumbnail[1]) ? $this->decodeJsonText($thumbnail[1]) : '';
                $sdLink = $this->decodeJsonText($matches3[1][0]);
                if (filter_var($sdLink, FILTER_VALIDATE_URL)) {
                    $this->medias[] = new Media($sdLink, 'sd', 'mp4', true, true);
                    if (isset($hdLink[1][0]) != "") {
                        $hdLink = $this->decodeJsonText($hdLink[1][0]);
                        $this->medias[] = new Media($hdLink, 'hd', 'mp4', true, true);
                    }
                }
                break;
        }
    }

    function urlGetContents($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'authority: www.facebook.com',
                'cache-control: max-age=0',
                'sec-ch-ua: "Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
                'sec-ch-ua-mobile: ?0',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'sec-fetch-site: none',
                'sec-fetch-mode: navigate',
                'sec-fetch-user: ?1',
                'sec-fetch-dest: document',
                'accept-language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6',
                'cookie: ' . $this->cookies
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    function getLongUrl($url, $maxRedirs = 3)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirs);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'authority: www.facebook.com',
            'cache-control: max-age=0',
            'sec-ch-ua: "Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
            'sec-ch-ua-mobile: ?0',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'sec-fetch-site: none',
            'sec-fetch-mode: navigate',
            'sec-fetch-user: ?1',
            'sec-fetch-dest: document',
            'accept-language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6',
            'cookie: ' . $this->cookies
        ));
        curl_exec($ch);
        $longUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        parse_str(parse_url($longUrl, PHP_URL_QUERY), $query);
        if (!empty($query['next'])) {
            return $query['next'];
        } else {
            return $longUrl;
        }
    }

    function decodeJsonText($text)
    {
        $json = '{"text":"' . $text . '"}';
        $json = json_decode($json, 1);
        return $json["text"];
    }

    function removeM($url)
    {
        $url = str_replace('m.facebook.com', 'www.facebook.com', $url);
        return $url;
    }
}