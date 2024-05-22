<?php

class DownloaderCreator
{
    public static function createDownloader($url)
    {
        preg_match('/^(?:https?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/\n]+)/', $url, $matches);
        if (empty($matches[1])) {
            return null;
        }
        $domain = self::extractMainDomain($matches[1]);
        if ($matches[1] == 'soundcloud.app.goo.gl') {
            $domain = 'soundcloud.app.goo.gl';
        }
        require_once __DIR__ . '/Helpers.php';
        require_once __DIR__ . '/Http.php';
        require_once __DIR__ . '/Downloader.php';
        require_once __DIR__ . '/Media.php';
        require_once __DIR__ . '/Settings.php';
        require_once __DIR__ . '/Cache.php';
        switch ($domain) {
            case 'instagram.com':
                if (self::isEnabled('instagram')) {
                    require_once __DIR__ . '/downloaders/Instagram.php';
                    return new Instagram();
                }
                break;
            case 'twitter.com':
                if (self::isEnabled('twitter')) {
                    require_once __DIR__ . '/downloaders/Twitter.php';
                    return new Twitter();
                }
                break;
            case 'youtube.com':
            case 'youtu.be':
                if (self::isEnabled('youtube')) {
                    require_once __DIR__ . '/downloaders/YouTube.php';
                    return new YouTube();
                }
                break;
            case 'web.facebook.com':
            case 'facebook.com':
            case 'fb.watch':
            case 'fb.gg':
                if (self::isEnabled('facebook')) {
                    require_once __DIR__ . '/downloaders/Facebook.php';
                    return new Facebook();
                }
                break;
            case 'dailymotion.com':
            case 'dai.ly':
                if (self::isEnabled('dailymotion')) {
                    require_once __DIR__ . '/downloaders/Dailymotion.php';
                    return new Dailymotion();
                }
                break;
            case 'vimeo.com':
            case 'player.vimeo.com':
                if (self::isEnabled('vimeo')) {
                    require_once __DIR__ . '/downloaders/Vimeo.php';
                    return new Vimeo();
                }
                break;
            case 'tumblr.com':
                if (self::isEnabled('tumblr')) {
                    require_once __DIR__ . '/downloaders/Tumblr.php';
                    return new Tumblr();
                }
                break;
            case 'pinterest.com':
            case 'pin.it':
            case 'pinterest.com.br':
            case 'pinterest.fr':
            case 'pinterest.it':
            case 'pinterest.es':
                if (self::isEnabled('pinterest')) {
                    require_once __DIR__ . '/downloaders/Pinterest.php';
                    return new Pinterest();
                }
                break;
            case 'imgur.com':
                if (self::isEnabled('imgur')) {
                    require_once __DIR__ . '/downloaders/Imgur.php';
                    return new Imgur();
                }
                break;
            case 'ted.com':
                if (self::isEnabled('ted')) {
                    require_once __DIR__ . '/downloaders/Ted.php';
                    return new Ted();
                }
                break;
            case 'mashable.com':
                if (self::isEnabled('mashable')) {
                    require_once __DIR__ . '/downloaders/Mashable.php';
                    return new Mashable();
                }
                break;
            case 'vk.com':
                if (self::isEnabled('vkontakte')) {
                    require_once __DIR__ . '/downloaders/Vkontakte.php';
                    return new Vkontakte();
                }
                break;
            case '9gag.com':
                if (self::isEnabled('9gag')) {
                    require_once __DIR__ . '/downloaders/NineGag.php';
                    return new NineGag();
                }
                break;
            case 'soundcloud.app.goo.gl':
            case 'soundcloud.com':
                if (self::isEnabled('soundcloud')) {
                    require_once __DIR__ . '/downloaders/Soundcloud.php';
                    return new Soundcloud();
                }
                break;
            case 'flickr.com':
            case 'flic.kr':
                if (self::isEnabled('flickr')) {
                    require_once __DIR__ . '/downloaders/Flickr.php';
                    return new Flickr();
                }
                break;
            case 'bandcamp.com':
                if (self::isEnabled('bandcamp')) {
                    require_once __DIR__ . '/downloaders/Bandcamp.php';
                    return new BandCamp();
                }
                break;
            case 'espn.com':
            case 'espn.com.br':
            case 'espn.in':
                if (self::isEnabled('espn')) {
                    require_once __DIR__ . '/downloaders/Espn.php';
                    return new Espn();
                }
                break;
            case 'imdb.com':
                if (self::isEnabled('imdb')) {
                    require_once __DIR__ . '/downloaders/Imdb.php';
                    return new Imdb();
                }
                break;
            case 'izlesene.com':
            case 'izl.sn':
                if (self::isEnabled('izlesene')) {
                    require_once __DIR__ . '/downloaders/Izlesene.php';
                    return new Izlesene();
                }
                break;
            case 'buzzfeed.com':
                if (self::isEnabled('buzzfeed')) {
                    require_once __DIR__ . '/downloaders/Buzzfeed.php';
                    return new BuzzFeed();
                }
                break;
            case 'tiktok.com':
                if (self::isEnabled('tiktok')) {
                    require_once __DIR__ . '/downloaders/Tiktok.php';
                    return new Tiktok();
                }
                break;
            case 'ok.ru':
                if (self::isEnabled('odnoklassniki')) {
                    require_once __DIR__ . '/downloaders/Odnoklassniki.php';
                    return new Odnoklassniki();
                }
                break;
            case 'likee.com':
            case 'likee.video':
            case 'like.video':
                if (self::isEnabled('likee')) {
                    require_once __DIR__ . '/downloaders/Likee.php';
                    return new Likee();
                }
                break;
            case 'twitch.tv':
                if (self::isEnabled('twitch')) {
                    require_once __DIR__ . '/downloaders/Twitch.php';
                    return new Twitch();
                }
                break;
            case 'blogspot.com':
                if (self::isEnabled('blogger')) {
                    require_once __DIR__ . '/downloaders/Blogger.php';
                    return new Blogger();
                }
                break;
            case 'reddit.com':
                if (self::isEnabled('reddit')) {
                    require_once __DIR__ . '/downloaders/Reddit.php';
                    return new Reddit();
                }
                break;
            case 'douyin.com':
            case 'iesdouyin.com':
                if (self::isEnabled('douyin')) {
                    require_once __DIR__ . '/downloaders/Douyin.php';
                    return new Douyin();
                }
                break;
            case 'kwai.com':
            case 'kw.ai':
                if (self::isEnabled('kwai')) {
                    require_once __DIR__ . '/downloaders/Kwai.php';
                    return new Kwai();
                }
                break;
            case 'linkedin.com':
                if (self::isEnabled('linkedin')) {
                    require_once __DIR__ . '/downloaders/LinkedIn.php';
                    return new LinkedIn();
                }
                break;
            case 'streamable.com':
                if (self::isEnabled('streamable')) {
                    require_once __DIR__ . '/downloaders/Streamable.php';
                    return new Streamable();
                }
                break;
            case 'bitchute.com':
                if (self::isEnabled('bitchute')) {
                    require_once __DIR__ . '/downloaders/Bitchute.php';
                    return new Bitchute();
                }
                break;
            case 'akilli.tv':
                if (self::isEnabled('akillitv')) {
                    require_once __DIR__ . '/downloaders/AkilliTv.php';
                    return new AkilliTv();
                }
                break;
            case 'gaana.com':
                if (self::isEnabled('gaana')) {
                    require_once __DIR__ . '/downloaders/Gaana.php';
                    return new Gaana();
                }
                break;
            case 'bilibili.com':
            case 'bilibili.tv':
                if (self::isEnabled('bilibili')) {
                    require_once __DIR__ . '/downloaders/Bilibili.php';
                    return new Bilibili();
                }
                break;
            case 'febspot.com':
                if (self::isEnabled('febspot')) {
                    require_once __DIR__ . '/downloaders/Febspot.php';
                    return new Febspot();
                }
                break;
            case 'rumble.com':
                if (self::isEnabled('rumble')) {
                    require_once __DIR__ . '/downloaders/Rumble.php';
                    return new Rumble();
                }
                break;
            case 'periscope.tv':
            case 'pscp.tv':
                if (self::isEnabled('periscope')) {
                    require_once __DIR__ . '/downloaders/Periscope.php';
                    return new Periscope();
                }
                break;
            case 'puhutv.com':
                if (self::isEnabled('puhutv')) {
                    require_once __DIR__ . '/downloaders/PuhuTv.php';
                    return new PuhuTv();
                }
                break;
            case 'blutv.com':
                if (self::isEnabled('blutv')) {
                    require_once __DIR__ . '/downloaders/BluTv.php';
                    return new BluTv();
                }
                break;
            case 'mxtakatak.com':
                if (self::isEnabled('mxtakatak')) {
                    require_once __DIR__ . '/downloaders/MxTakatak.php';
                    return new MxTakatak();
                }
                break;
            case 'ifunny.co':
                if (self::isEnabled('ifunny')) {
                    require_once __DIR__ . '/downloaders/Ifunny.php';
                    return new Ifunny();
                }
                break;
            case 'kickstarter.com':
                if (self::isEnabled('kickstarter')) {
                    require_once __DIR__ . '/downloaders/Kickstarter.php';
                    return new Kickstarter();
                }
                break;
            case 'mixcloud.com':
                if (self::isEnabled('mixcloud')) {
                    require_once __DIR__ . '/downloaders/Mixcloud.php';
                    return new Mixcloud();
                }
                break;
            case 'sharechat.com':
                if (self::isEnabled('sharechat')) {
                    require_once __DIR__ . '/downloaders/ShareChat.php';
                    return new ShareChat();
                }
                break;
            default:
                return null;
                break;
        }
        return null;
    }

    private static function extractMainDomain($domain)
    {
        $parts = explode('.', $domain);
        $mainDomain = null;
        $length = count($parts);
        if ($length <= 2) {
            return $domain;
        }
        for ($i = $length - 1; $i > 0; $i--) {
            $mainDomain = $parts[$i] . ($mainDomain !== null ? '.' : '') . $mainDomain;
        }
        return $mainDomain;
    }

    private static function isEnabled($slug)
    {
        return get_option('asr_downloader_' . $slug) == 'on';
    }
}