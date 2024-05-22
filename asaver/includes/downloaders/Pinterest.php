<?php

class Pinterest extends Downloader
{
    public function fetch($videoUrl)
    {
        $parsedUrl = parse_url($videoUrl);
        if ($parsedUrl['host'] == 'pin.it') {
            $http = new Http($videoUrl);
            $originalUrl = $http->getLongUrl();
            if (isset($originalUrl) != '') {
                $videoUrl = strtok($originalUrl, '?');
            }
        }
        preg_match('/\d{2,32}/', $videoUrl, $matches);
        $videoId = null;
        if (!empty($matches)) {
            $videoId = $matches[0];
        }
        if (!empty($videoId)) {
            $http = new Http('https://widgets.pinterest.com/v3/pidgets/pins/info/?pin_ids=' . $videoId);
            $http->run();
            $data = json_decode($http->response, true);
            if (empty($data['data'][0]['videos'])) {
                $data['data'][0]['videos'] = $data['data'][0]['story_pin_data']['pages'][0]['blocks'][0]['video'];
            }
            if (!empty($data['data'][0]['videos'])) {
                $data = $data['data'][0];
                $this->title = $data['rich_metadata']['article']['name'] ?? 'Pinterest Video';
                $this->source = 'pinterest';
                $this->thumbnail = $this->extractThumbnail($data);
                $this->duration = $this->extractDuration($data);
                foreach ($data['videos']['video_list'] as $stream) {
                    $ext = pathinfo(parse_url($stream['url'])['path'], PATHINFO_EXTENSION);
                    if ($ext != 'm3u8') {
                        array_push($this->medias, new Media($stream['url'], min($stream['height'], $stream['width']) . 'p', $ext, true, true));
                    }
                }
            }
        }
    }

    private function extractThumbnail($data)
    {
        //$key = array_key_last($data['images']);
        $key = key(array_slice($data['images'], -1, 1, true));
        return $data['images'][$key]['url'];
    }

    private function extractDuration($data)
    {
        //$key = array_key_last($data['videos']['video_list']);
        $key = key(array_slice($data['videos']['video_list'], -1, 1, true));
        return (int)$data['videos']['video_list'][$key]['duration'] / 1000 ?? null;
    }

    public function fetchLegacy($videoUrl)
    {
        $parsedUrl = parse_url($videoUrl);
        if ($parsedUrl['host'] == 'pin.it') {
            $http = new Http($videoUrl);
            $originalUrl = $http->getLongUrl();
            if (isset($originalUrl) != '') {
                $videoUrl = strtok($originalUrl, '?');
            }
        }
        $http = new Http($videoUrl);
        $http->run();
        $this->title = Helpers::getStringBetween($http->response, '<title>', '</title>');
        $this->source = 'pinterest';
        $this->thumbnail = Helpers::getStringBetween($http->response, '"image_cover_url":"', '"');
        $data = Helpers::getStringBetween($http->response, '<script id="initial-state" type="application/json">', '</script>');
        $data = json_decode($data, true);
        $streams = null;
        if (!empty($data['resourceResponses'][0]['response']['data']['videos']['video_list'])) {
            $streams = $data['resourceResponses'][0]['response']['data']['videos']['video_list'];
        } else if (!empty($data['resources']['data']['PinResource'])) {
            $streams = reset($data['resources']['data']['PinResource'])['data']['videos']['video_list'];
            $this->title = reset($data['resources']['data']['PinResource'])['data']['title'];
        } else if (!empty($data['pins']['videos']['video_list'])) {
            $streams = reset($data['pins'])['videos']['video_list'];
        } else if (!empty($data['resources']['PinResource']) && !empty(reset($data['resources']['PinResource'])['data']['videos']['video_list'])) {
            $streamUrls = reset($data['resources']['PinResource'])['data']['videos']['video_list'];
            foreach ($streamUrls as $key => $stream) {
                preg_match('/V_(\d{2,5})P/', $key, $matches);
                if (!empty($matches[1])) {
                    $this->duration = $stream['duration'] / 1000;
                    array_push($this->medias, new Media($stream['url'], $matches[1] . 'p', 'mp4', true, true));
                }
            }
        }
        if (!empty($streams)) {
            foreach ($streams as $stream) {
                $ext = pathinfo(parse_url($stream['url'])['path'], PATHINFO_EXTENSION);
                if ($ext != 'm3u8') {
                    array_push($this->medias, new Media($stream['url'], min($stream['height'], $stream['width']) . 'p', $ext, true, true));
                    if (empty($this->duration)) {
                        $this->duration = $stream['duration'];
                    }
                }
            }
        }
    }
}