<?php

class Instagram extends Downloader
{
    public static $cookieFile = __DIR__ . '/../../cookies/ig-cookie.txt';
    private $postPage;

    public function fetch($videoUrl)
    {
        $videoUrl = strtok($videoUrl, '?');
        if (substr($videoUrl, -1) != '/') {
            $videoUrl .= '/';
        }
        $this->source = 'instagram';
        preg_match('/https:\/\/www.instagram.com\/reel\/(.*?)\//', $videoUrl, $isReel);
        if (!empty($isReel[1])) {
            $this->postPage = $this->urlGetContents($videoUrl);
            preg_match_all('/window._sharedData = (.*);<\/script>/', $this->postPage, $matches);
            if (!empty($matches[1][0])) {
                $data = json_decode($matches[1][0], true);
                if (!empty($data['entry_data']['PostPage'][0]['graphql']['shortcode_media'])) {
                    $data = $data['entry_data']['PostPage'][0]['graphql']['shortcode_media'];
                    $this->title = 'Instagram Video';
                    $this->thumbnail = $data['display_url'];
                    $this->medias[] = new Media($data['video_url'], 'hd', 'mp4', true, true);
                    $this->thumbnail = html_entity_decode($this->thumbnail);
                    $this->saveThumbnail();
                    $this->thumbnailHotlinkProtection = true;
                }
            }
        } else {
            $this->postPage = $this->urlGetContents($videoUrl . 'embed/captioned/');
            if (strpos($this->postPage, 'WatchOnInstagram')) {
                $this->mediaInfoLegacy($videoUrl);
            }
            preg_match_all('/window.__additionalDataLoaded\(\'extra\',(.*?)\);<\/script>/', $this->postPage, $matches);
            if (isset($matches[1][0]) != '') {
                $data = json_decode($matches[1][0], true);
                if (!isset($data['shortcode_media'])) {
                    preg_match_all('/<img class="EmbeddedMediaImage" alt=".*" src="(.*?)"/', $this->postPage, $matches);
                    if (isset($matches[1][0]) != '') {
                        $this->title = Helpers::getStringBetween($this->postPage, '<img class="EmbeddedMediaImage" alt="', '"');
                        $this->thumbnail = $matches[1][0];
                        $mediaUrl = html_entity_decode($matches[1][0]);
                        $this->medias[] = new Media($mediaUrl, 'hd', 'jpg', true, false);
                    }
                } else {
                    $this->title = $data['shortcode_media']['edge_media_to_caption']['edges'][0]['node']['text'] ?? '';
                    if (empty($video['title']) && isset($data['shortcode_media']['owner']['username']) != '') {
                        $this->title = 'Instagram Post from ' . $data['shortcode_media']['owner']['username'];
                    } else {
                        $this->title = 'Instagram Post';
                    }
                    $this->thumbnail = $data['shortcode_media']['display_resources'][0]['src'];
                    if ($data['shortcode_media']['__typename'] == "GraphImage") {
                        $imagesData = $data['shortcode_media']['display_resources'];
                        $length = count($imagesData);
                        $this->medias[] = new Media($imagesData[$length - 1]['src'], 'hd', 'jpg', true, false);
                    } else {
                        if ($data['shortcode_media']['__typename'] == 'GraphSidecar') {
                            $multipleData = $data['shortcode_media']['edge_sidecar_to_children']['edges'];
                            foreach ($multipleData as $media) {
                                $audioAvailable = false;
                                if ($media['node']['is_video'] == 'true') {
                                    $mediaUrl = $media['node']['video_url'];
                                    $type = 'mp4';
                                    $audioAvailable = true;
                                } else {
                                    $length = count($media['node']['display_resources']);
                                    $mediaUrl = $media['node']['display_resources'][$length - 1]['src'];
                                    $type = 'jpg';
                                }
                                $this->medias[] = new Media($mediaUrl, 'hd', $type, true, $audioAvailable);
                            }
                        } else {
                            if ($data['shortcode_media']['__typename'] == 'GraphVideo') {
                                $this->medias[] = new Media($data['shortcode_media']['video_url'], 'hd', 'mp4', true, true);
                            }
                        }
                    }
                }
            }

            $this->thumbnail = html_entity_decode($this->thumbnail);
            $this->saveThumbnail();
            $this->thumbnailHotlinkProtection = true;
        }
    }

    function mediaInfoLegacy($url)
    {
        $this->postPage = $this->urlGetContents($url);
        $mediaInfo = $this->mediaData($this->postPage);
        $this->title = $this->getTitle($this->postPage);
        $this->source = 'instagram';
        $this->thumbnail = $this->getThumbnail($this->postPage);
        $this->saveThumbnail();
        foreach ($mediaInfo['links'] as $link) {
            if (empty($link['type'])) {
                continue;
            }
            switch ($link['type']) {
                case 'video':
                    array_push($this->medias, new Media($link['url'], 'hd', 'mp4', true, true));
                    break;
                case 'image':
                    array_push($this->medias, new Media($link['url'], 'hd', 'jpg', true, false));
                    break;
                default:
                    break;
            }
        }
    }

    private function saveThumbnail()
    {
        $id = sha1($this->thumbnail);
        $cache = new Cache('ig-' . $id, 'jpg', $this->urlGetContents($this->thumbnail));
        $this->thumbnail = $cache->url;
    }

    private function urlGetContents($url)
    {
        $http = new Http($url);
        $http->addCurlOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if (file_exists(self::$cookieFile)) {
            $http->addCurlOption(CURLOPT_COOKIEFILE, self::$cookieFile);
        }
        $http->run();
        return $http->response;
    }

    function mediaData($postPage)
    {
        preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $postPage, $matches);
        if (!$matches) {
            return false;
        } else {
            preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $postPage, $matches);
            preg_match_all('/<script type="text\/javascript">window._sharedData = (.*?);<\/script>/', $postPage, $output);
            if (isset($matches[1][0]) != '') {
                $json = $matches[1][0];
            } else if (isset($output[1][0]) != '') {
                $json = $output[1][0];
            }
            $data = json_decode($json, true);
            if (!empty($data['entry_data']['PostPage'][0]['graphql'])) {
                $data = $data['entry_data']['PostPage'][0];
            }
            if (empty($data['graphql'])) {
                return false;
            }
            if ($data['graphql']['shortcode_media']['__typename'] == 'GraphImage') {
                $imagesData = $data['graphql']['shortcode_media']['display_resources'];
                $length = count($imagesData);
                $mediaInfo['links'][0]['type'] = 'image';
                $mediaInfo['links'][0]['url'] = $imagesData[$length - 1]['src'];
                $mediaInfo['links'][0]['status'] = 'success';
            } else {
                if ($data['graphql']['shortcode_media']['__typename'] == "GraphSidecar") {
                    $counter = 0;
                    $multipledata = $data['graphql']['shortcode_media']['edge_sidecar_to_children']['edges'];
                    foreach ($multipledata as $media) {
                        if ($media['node']['is_video'] == 'true') {
                            $mediaInfo['links'][$counter]["url"] = $media['node']['video_url'];
                            $mediaInfo['links'][$counter]["type"] = 'video';
                        } else {
                            $length = count($media['node']['display_resources']);
                            $mediaInfo['links'][$counter]["url"] = $media['node']['display_resources'][$length - 1]['src'];
                            $mediaInfo['links'][$counter]["type"] = 'image';
                        }
                        $counter++;
                        $mediaInfo['type'] = 'media';
                    }
                    $mediaInfo['status'] = 'success';
                } else {
                    if ($data['graphql']['shortcode_media']['__typename'] == 'GraphVideo') {
                        $videolink = $data['graphql']['shortcode_media']['video_url'];
                        $mediaInfo['links'][0]['type'] = 'video';
                        $mediaInfo['links'][0]['url'] = $videolink;
                        $mediaInfo['links'][0]['status'] = 'success';
                    } else {
                        $mediaInfo['links']['status'] = 'fail';
                    }
                }
            }
            return $mediaInfo;
        }
    }

    private function getHighlight($reelId)
    {

        $baseUrl = 'https://www.instagram.com/graphql/query/?query_hash=ba71ba2fcb5655e7e2f37b05aec0ff98&variables=';
        $variables = '{"reel_ids":[],"tag_names":[],"location_ids":[],"highlight_reel_ids":["' . $reelId . '"],"precomposed_overlay":false,"show_story_viewer_list":true,"story_viewer_fetch_count":50,"story_viewer_cursor":"","stories_video_dash_manifest":false}';
        $reel = $this->urlGetContents($baseUrl . urlencode($variables), true);
        $data = json_decode($reel, true);
        if (empty($data)) {
            return null;
        }
        $reelMedias = $data['data']['reels_media'];
        $medias = array();
        if (empty($reelMedias)) {
            return null;
        }
        foreach ($reelMedias as $reelMedia) {
            foreach ($reelMedia['items'] as $item) {
                switch ($item['__typename']) {
                    case 'GraphStoryImage':
                        $i = count($item['display_resources']) - 1;
                        $medias[] = array(
                            'type' => 'image',
                            'fileType' => 'jpg',
                            'url' => $item['display_resources'][$i]['src'],
                            'downloadUrl' => $item['display_resources'][$i]['src'] . '&dl=1'
                        );
                        break;
                    case 'GraphStoryVideo':
                        $i = count($item['video_resources']) - 1;
                        $medias[] = array(
                            'type' => 'video',
                            'fileType' => 'mp4',
                            'preview' => $item['display_url'],
                            'url' => $item['video_resources'][$i]['src'],
                            'downloadUrl' => $item['video_resources'][$i]['src'] . '&dl=1'
                        );
                        break;
                }
            }
        }
        return $medias;
    }

    function getThumbnail($postPage)
    {
        preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $postPage, $matches);
        preg_match_all('/<script type="text\/javascript">window._sharedData = (.*?);<\/script>/', $postPage, $output);
        if (isset($matches[1][0]) != '') {
            $json = $matches[1][0];
        } else if (isset($output[1][0]) != '') {
            $json = $output[1][0];
        }
        $data = json_decode($json, true);
        if (!empty($data['entry_data']['PostPage'][0]['graphql'])) {
            $data = $data['entry_data']['PostPage'][0];
        }
        return $data['graphql']['shortcode_media']['display_resources'][0]['src'];
    }

    function getTitle($postPage)
    {
        $title = '';
        if (preg_match_all('@<title>(.*?)</title>@si', $postPage, $match)) {
            $title = $match[1][0];
        }
        return $title;
    }
}