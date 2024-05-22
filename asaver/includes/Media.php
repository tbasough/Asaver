<?php

class Media
{
    public $url = null;
    public $quality = null;
    public $extension = null;
    public $size = null;
    public $formattedSize = null;
    public $videoAvailable = null;
    public $audioAvailable = null;
    public $chunked = false;
    public $cached = false;

    /**
     * Media constructor.
     * @param $url
     * @param $quality
     * @param $extension
     * @param $videoAvailable
     * @param $audioAvailable
     */
    public function __construct($url, $quality, $extension, $videoAvailable, $audioAvailable)
    {
        $this->url = $url;
        $this->quality = $quality;
        $this->extension = $extension;
        $this->videoAvailable = $videoAvailable;
        $this->audioAvailable = $audioAvailable;
    }

    public function getMediaInfo()
    {
        if ($this->size == null) {
            $http = new Http($this->url);
            $this->size = $http->getFileSize();
        }
        $this->formattedSize = Helpers::formatSize($this->size);
    }
}