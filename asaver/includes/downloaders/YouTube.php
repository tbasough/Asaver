<?php

require_once __DIR__ . '/vendor/autoload.php';

use YouTube\YouTubeDownloader;

class YouTube extends Downloader
{
    private $hideDashVideos = false;
    private $showMp3 = false;
    private $enableRedirector = true;

    public function fetch($videoUrl)
    {
        if (get_option('asr_hide_dash') == 'on') {
            $this->hideDashVideos = true;
        }
        if (get_option('asr_show_mp3') == 'on') {
            $this->showMp3 = true;
        }
        $yt = new YouTubeDownloader();
        $data = $yt->getDownloadLinks($videoUrl);
        $formats = $data->getAllFormats();
        $info = $data->getInfo();
        if (!empty($formats) && !empty($info)) {
            $this->title = $info->getTitle();
            $this->source = 'youtube';
            $this->thumbnail = 'https://i.ytimg.com/vi/' . $info->getId() . '/mqdefault.jpg';
            $this->duration = $info->getDuration();
            $audios = [];
            $videos = [];
            $dashVideos = [];
            foreach ($formats as $media) {
                preg_match('/(audio|video)\/(.*?);/', $media->mimeType, $matches);
                $isAudio = $matches[1] == 'audio';
                $isDash = empty($media->audioQuality);
                if ($isDash && $this->hideDashVideos) {
                    continue;
                }
                if (empty($media->contentLength)) {
                    $media->contentLength = new Http($media->url);
                    $media->contentLength = $media->contentLength->getFileSize();
                }
                if (empty($media->contentLength)) {
                    continue;
                }
                if (!$isAudio && !empty($media->qualityLabel)) {
                    $quality = $media->qualityLabel;
                } else if ($isAudio && !empty($media->audioQuality)) {
                    $qualities = [
                        '139' => '48kbps',
                        '140' => '128kbps',
                        '141' => '256kbps',
                        '171' => '128kbps',
                        '249' => '48kbps',
                        '250' => '64kbps',
                        '251' => '128kbps',
                        '256' => '192kbps',
                        '258' => '384kbps',
                    ];
                    $quality = $qualities[$media->itag];
                } else {
                    $quality = 'hd';
                }
                $extension = $matches[2];
                if ($isAudio && $extension == 'mp4') {
                    $extension = 'm4a';
                }
                if ($extension == '3gpp') {
                    $extension = '3gp';
                }
                $stream = new Media($media->url, $quality, $extension, !$isAudio, !$isDash);
                $stream->size = $media->contentLength;
                if ($isDash) {
                    array_push($dashVideos, $stream);
                } else if (!$isAudio) {
                    array_push($videos, $stream);
                } else if ($isAudio) {
                    array_push($audios, $stream);
                }
                if ($this->showMp3 && $extension == 'm4a') {
                    $stream = new Media($media->url, $quality, 'mp3', !$isAudio, !$isDash);
                    $stream->size = $media->contentLength;
                    array_push($audios, $stream);
                }
            }
            usort($audios, array('Helpers', 'sortByQuality'));
            usort($dashVideos, array('Helpers', 'sortByQuality'));
            $this->medias = array_merge($this->medias, $videos);
            $this->medias = array_merge($this->medias, $audios);
            $this->medias = array_merge($this->medias, $dashVideos);
        }
    }

    private function roundBitrate($bitrate)
    {
        $bitrates = [48, 64, 128, 256, 512, 1024];
        $rounded = $bitrate;
        for ($i = 0; $i < 5; $i++) {
            if (abs($bitrates[$i] - $bitrate) < abs($bitrates[$i + 1] - $bitrate)) {
                $rounded = $bitrates[$i];
                break;
            }
        }
        return $rounded;
    }

    private function formatBitrate($bitrate)
    {
        $factor = floor((strlen($bitrate) - 1) / 3);
        $bitrate = $bitrate / pow(1024, $factor);
        $kb = $this->roundBitrate((int)$bitrate);
        return $kb . 'kbps';
    }
}