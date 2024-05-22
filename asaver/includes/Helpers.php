<?php

class Helpers
{
    public static function redirect($url)
    {
        header('Location: ' . $url);
    }

    public static function beautifyFilename($filename)
    {
        // reduce consecutive characters
        $filename = preg_replace(array(
            // "file   name.zip" becomes "file-name.zip"
            '/ +/',
            // "file___name.zip" becomes "file-name.zip"
            '/_+/',
            // "file---name.zip" becomes "file-name.zip"
            '/-+/'
        ), '-', $filename);
        $filename = preg_replace(array(
            // "file--.--.-.--name.zip" becomes "file.name.zip"
            '/-*\.-*/',
            // "file...name..zip" becomes "file.name.zip"
            '/\.{2,}/'
        ), '.', $filename);
        // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        $filename = mb_strtolower($filename, mb_detect_encoding($filename));
        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, '.-');
        return $filename;
    }

    public static function filterFilename($filename, $beautify = true)
    {
        // sanitize filename
        $filename = preg_replace(
            '~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
            '-', $filename);
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // optional beautification
        if ($beautify) $filename = self::beautifyFilename($filename);
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
        return $filename;
    }

    public static function sanitizeFilename($name, $extension)
    {
        return (self::filterFilename($name) ?? "video") . "." . $extension;
    }

    public static function formatSeconds($seconds)
    {
        return gmdate(($seconds > 3600 ? "H:i:s" : "i:s"), $seconds);
    }

    public static function isContains($string, $keyword)
    {
        return strpos($string, $keyword) !== false;
    }

    public static function getStringBetween($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public static function sortByQuality($a, $b)
    {
        return (int)$a->quality - (int)$b->quality;
    }

    public static function generateString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function formatSize($bytes)
    {
        switch ($bytes) {
            case $bytes < 1024:
                $size = $bytes . " B";
                break;
            case $bytes < 1048576:
                $size = round($bytes / 1024, 2) . " KB";
                break;
            case $bytes < 1073741824:
                $size = round($bytes / 1048576, 2) . " MB";
                break;
            case $bytes < 1099511627776:
                $size = round($bytes / 1073741824, 2) . " GB";
                break;
        }
        if (!empty($size)) {
            return $size;
        } else {
            return "";
        }
    }

    public static function getMainDomain($host)
    {
        $main_host = strtolower(trim($host));
        $count = substr_count($main_host, '.');
        if ($count === 2) {
            if (strlen(explode('.', $main_host)[1]) > 3) $main_host = explode('.', $main_host, 2)[1];
        } else if ($count > 2) {
            $main_host = self::getMainDomain(explode('.', $main_host, 2)[1]);
        }
        return $main_host;
    }

    public static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function generateToken()
    {
        if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION > 5) {
            return bin2hex(random_bytes(32));
        } else {
            if (function_exists('mcrypt_create_iv')) {
                return bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
            } else {
                return bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
    }

    public static function getChunkedSize($chunkUrl, $chunkCount){
        $chunkSize = new Http($chunkUrl);
        $chunkSize = $chunkSize->getFileSize();
        return $chunkSize * $chunkCount;
    }

    public static function createChunkCache($chunks, $fileName){
        return new Cache($fileName, 'json', json_encode($chunks));
    }
}