<?php

class Douyin extends Downloader
{
    public function fetch($videoUrl)
    {
        $videoId = $this->extractVideoId($videoUrl);
        if (!empty($videoId)) {
            $videoInfo = $this->getVideoInfo($videoId);
            if (!empty($videoInfo)) {
                $this->title = $videoInfo['item_list'][0]['desc'];
                $this->source = 'douyin';
                $this->thumbnail = $videoInfo['item_list'][0]['video']['cover']['url_list'][0];
                $this->duration = $videoInfo['item_list'][0]['video']['duration'] / 1000;
                if (!empty($videoInfo['item_list'][0]['video']['vid'])) {
                    $videoUrl = 'https://aweme.snssdk.com/aweme/v1/play/?video_id=' . $videoInfo['item_list'][0]['video']['vid'] . '&ratio=default&line=0';
                    $fileId = rand(0, 4);
                    $fileName = 'douyin-' . $fileId . '.mp4';
                    $cacheFile = __DIR__ . '/../../cache/' . $fileName;
                    //$this->downloadVideo($videoUrl, $cacheFile);
                    $headers = get_headers($videoUrl, 1, stream_context_create(array(
                        'http' => array(
                            'header' => "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1" // i.e. An iPad
                        )
                    )));
                    if (!empty($headers['Location'])) {
                        $videoUrl = $headers['Location'];
                        $this->medias[] = new Media($videoUrl, $videoInfo['item_list'][0]['video']['ratio'], 'mp4', true, true);
                    }
                }
                if (isset($videoInfo['item_list'][0]['music']['play_url']['uri']) != '') {
                    $musicUrl = $videoInfo['item_list'][0]['music']['play_url']['uri'];
                    $this->medias[] = new Media($musicUrl, '128kbps', 'mp3', false, true);
                }
            }
        }
    }

    private function extractVideoId($videoUrl)
    {
        $http = new Http($videoUrl);
        $url = $http->getLongUrl();
        $url = strtok($url, '?');
        $last_char = substr($url, -1);
        if ($last_char == "/") {
            $url = substr($url, 0, -1);
        }
        $arr = explode('/', $url);
        return end($arr);
    }

    private function getVideoInfo($videoId)
    {
        $http = new Http('https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=' . $videoId);
        $http->run();
        return json_decode($http->response, true);
    }

    private function downloadVideo($url, $filePath)
    {
        $fp = fopen($filePath, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Connection: keep-alive'
        ));
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }
}