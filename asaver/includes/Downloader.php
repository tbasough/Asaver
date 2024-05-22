<?php

class Downloader implements JsonSerializable
{
    public $url = null;
    public $title = null;
    public $thumbnail = null;
    public $thumbnailHotlinkProtection = false;
    public $duration = null;
    public $medias = [];
    public $source = null;

    public function fetch($videoUrl)
    {
    }

    public function qualityExists($quality)
    {
        foreach ($this->medias as $media) {
            if ($media->quality == $quality) {
                return true;
            }
        }
        return false;
    }

    public function mediaDetails()
    {
        if (!empty($this->duration)) {
            $this->duration = Helpers::formatSeconds($this->duration);
        }
        foreach ($this->medias as $media) {
            $media->getMediaInfo();
        }
    }

    public function saveToDatabase($clientIp)
    {
        $latestDownloads = get_option('asr_latest_downloads');
        $latestDownloads = json_decode($latestDownloads, true);
        if (!is_array($latestDownloads)) {
            $latestDownloads = array();
        }
        $limit = (int)get_option('asr_latest_downloads_count');
        array_unshift($latestDownloads, [
            'url' => $this->url,
            'title' => $this->title,
            'thumbnail' => $this->thumbnail,
            'source' => $this->source,
            'clientIp' => $clientIp
        ]);
        $count = count($latestDownloads);
        while ($count > $limit) {
            $latestDownloads = array_pop($latestDownloads);
            $count--;
        }
        update_option('asr_latest_downloads', json_encode($latestDownloads));
    }

    public function jsonSerialize()
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'thumbnail' => $this->thumbnail,
            'duration' => $this->duration,
            'source' => $this->source,
            'medias' => $this->medias
        ];
    }

    public function isValid()
    {
        return $this->title != '' && isset($this->medias[0]->url) != '';
    }
}