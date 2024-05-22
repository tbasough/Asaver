<?php

class Bandcamp extends Downloader
{
    public function fetch($videoUrl)
    {
        $http = new Http($videoUrl);
        $http->run();
        $data = json_decode(Helpers::getStringBetween($http->response, '<script type="application/ld+json">', '</script>'), true);
        if (isset($data['additionalProperty']) && !empty($data['additionalProperty'])) {
            $this->title = $data['name'] . ' ' . $data['byArtist']['name'];
            $this->source = 'bandcamp';
            $this->thumbnail = Helpers::getStringBetween($http->response, 'property="og:image" content="', '">');
            if ($data['@type'] == 'MusicAlbum') {
                if (isset($data['track']['itemListElement']) != "") {
                    foreach ($data['track']['itemListElement'] as $item) {
                        $item = $item['item'];
                        $property = [];
                        $length = count($item['additionalProperty']);
                        for ($j = 0; $j < $length; $j++) {
                            $property[$item['additionalProperty'][$j]['name']] = $item['additionalProperty'][$j]['value'];
                        }
                        if (isset($property['file_mp3-128']) != '') {
                            $this->medias[] = new Media($property['file_mp3-128'], '128kbps', 'mp3', false, true);
                        }
                    }
                }
            } else {
                $property = [];
                $length = count($data['additionalProperty']);
                for ($i = 0; $i < $length; $i++) {
                    $property[$data['additionalProperty'][$i]['name']] = $data['additionalProperty'][$i]['value'];
                }
                $this->duration = $property['duration_secs'];
                if (!empty($property['file_mp3-128'])) {
                    $this->medias[] = new Media($property['file_mp3-128'], '128kbps', 'mp3', false, true);
                }
            }
        }
        if (empty($this->medias)) {
            preg_match_all('/data-tralbum="(.*?)"/', $http->response, $matches);
            if (!empty($matches[1][0])) {
                $data = json_decode(html_entity_decode($matches[1][0]), true);
                if (!empty($data['trackinfo'])) {
                    foreach ($data['trackinfo'] as $track) {
                        if (!empty($track['file']['mp3-128'])) {
                            $this->medias[] = new Media($track['file']['mp3-128'], '128kbps', 'mp3', false, true);
                        }
                    }
                }
            }
        }
    }
}