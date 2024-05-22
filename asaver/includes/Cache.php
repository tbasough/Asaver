<?php

class Cache
{
    public $filename;
    public $content;
    public $id;
    public $extension = '';
    public $url;
    public static $cachePath = __DIR__ . '/../cache/';

    public function __construct($filename, $extension, $content)
    {
        $path = $filename . '.' . $extension;
        file_put_contents(self::$cachePath . $path, $content);
        $this->url = self::getSiteUrl() . '/wp-content/plugins/asaver/cache/' . $path;
        return $this->url;
    }

    public static function getContent($filename, $extension)
    {
        return file_get_contents(self::$cachePath . $filename . '.' . $extension);
    }

    public static function getCacheUrl()
    {
        return self::getSiteUrl()  . '/wp-content/plugins/asaver/cache/';
    }

    private static function getSiteUrl()
    {
        return get_site_url();
    }
}