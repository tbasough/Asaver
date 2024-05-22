<?php
if (!defined('ABSPATH')) {
    exit;
}

class ASR_Downloaders
{

    /**
     * The single instance of ASR_Downloaders.
     *
     * @var     object
     * @access  private
     * @since   1.0.0
     */
    private static $_instance = null; //phpcs:ignore

    /**
     * The main plugin object.
     *
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $parent = null;

    public static $downloaders = array(
        array('slug' => '9gag', 'color' => '#000000', 'name' => '9GAG', 'text' => '', 'type' => 'video'),
        array('slug' => 'akillitv', 'color' => '#3e3e3e', 'name' => 'Akıllı TV', 'text' => '', 'type' => 'video'),
        array('slug' => 'bandcamp', 'color' => '#21759b', 'name' => 'Bandcamp', 'text' => '', 'type' => 'music'),
        array('slug' => 'bilibili', 'color' => '#00a1d6', 'name' => 'Bilibili', 'text' => '', 'type' => 'video'),
        array('slug' => 'bitchute', 'color' => '#ef4137', 'name' => 'Bitchute', 'text' => '', 'type' => 'video'),
        array('slug' => 'blogger', 'color' => '#fc4f08', 'name' => 'Blogger', 'text' => '', 'type' => 'video'),
        array('slug' => 'blutv', 'color' => '#0270fb', 'name' => 'BluTV', 'text' => '', 'type' => 'video'),
        array('slug' => 'buzzfeed', 'color' => '#df2029', 'name' => 'Buzzfeed', 'text' => '', 'type' => 'video'),
        array('slug' => 'dailymotion', 'color' => '#0077b5', 'name' => 'Dailymotion', 'text' => '', 'type' => 'video'),
        array('slug' => 'douyin', 'color' => '#131418', 'name' => 'Douyin', 'text' => '', 'type' => 'video'),
        array('slug' => 'espn', 'color' => '#df2029', 'name' => 'ESPN', 'text' => '', 'type' => 'video'),
        array('slug' => 'facebook', 'color' => '#3b5998', 'name' => 'Facebook', 'text' => '', 'type' => 'video'),
        array('slug' => 'febspot', 'color' => '#f02730', 'name' => 'Febspot', 'text' => '', 'type' => 'video'),
        array('slug' => 'flickr', 'color' => '#ff0084', 'name' => 'Flickr', 'text' => '', 'type' => 'video'),
        array('slug' => 'gaana', 'color' => '#e72c30', 'name' => 'Gaana', 'text' => '', 'type' => 'music'),
        array('slug' => 'ifunny', 'color' => '#fc0', 'name' => 'Ifunny', 'text' => '', 'type' => 'video'),
        array('slug' => 'imdb', 'color' => '#e8c700', 'name' => 'IMDB', 'text' => '', 'type' => 'video'),
        array('slug' => 'imgur', 'color' => '#02b875', 'name' => 'Imgur', 'text' => '', 'type' => 'video'),
        array('slug' => 'instagram', 'color' => '#e4405f', 'name' => 'Instagram', 'text' => '', 'type' => 'video'),
        array('slug' => 'izlesene', 'color' => '#ff6600', 'name' => 'Izlesene', 'text' => '', 'type' => 'video'),
        array('slug' => 'kickstarter', 'color' => '#05ce78', 'name' => 'Kickstarter', 'text' => '', 'type' => 'video'),
        array('slug' => 'kwai', 'color' => '#ff9000', 'name' => 'Kwai', 'text' => '', 'type' => 'video'),
        array('slug' => 'likee', 'color' => '#be3cfa', 'name' => 'Likee', 'text' => '', 'type' => 'video'),
        array('slug' => 'linkedin', 'color' => '#0e76a8', 'name' => 'LinkedIn', 'text' => '', 'type' => 'video'),
        array('slug' => 'mashable', 'color' => '#0084ff', 'name' => 'Mashable', 'text' => '', 'type' => 'video'),
        array('slug' => 'mixcloud', 'color' => '#f3b2a6', 'name' => 'Mixcloud', 'text' => '', 'type' => 'audio'),
        array('slug' => 'mxtakatak', 'color' => '#6de4ff', 'name' => 'MxTakatak', 'text' => '', 'type' => 'video'),
        array('slug' => 'odnoklassniki', 'color' => '#f57d00', 'name' => 'Ok.ru', 'text' => '', 'type' => 'video'),
        array('slug' => 'periscope', 'color' => '#3fa4c4', 'name' => 'Periscope', 'text' => '', 'type' => 'video'),
        array('slug' => 'pinterest', 'color' => '#bf1f24', 'name' => 'Pinterest', 'text' => '', 'type' => 'video'),
        array('slug' => 'puhutv', 'color' => '#18191a', 'name' => 'PuhuTV', 'text' => '', 'type' => 'video'),
        array('slug' => 'reddit', 'color' => '#ff4301', 'name' => 'Reddit', 'text' => '', 'type' => 'video'),
        array('slug' => 'rumble', 'color' => '#74a642', 'name' => 'Rumble', 'text' => '', 'type' => 'video'),
        array('slug' => 'sharechat', 'color' => '#ff3300', 'name' => 'Share Chat', 'text' => '', 'type' => 'video'),
        array('slug' => 'soundcloud', 'color' => '#ff3300', 'name' => 'Soundcloud', 'text' => '', 'type' => 'music'),
        array('slug' => 'streamable', 'color' => '#2c2c2c', 'name' => 'Streamable', 'text' => '', 'type' => 'video'),
        array('slug' => 'ted', 'color' => '#e62b1e', 'name' => 'TED', 'text' => '', 'type' => 'video'),
        array('slug' => 'tiktok', 'color' => '#131418', 'name' => 'Tiktok', 'text' => '', 'type' => 'video'),
        array('slug' => 'tumblr', 'color' => '#32506d', 'name' => 'Tumblr', 'text' => '', 'type' => 'video'),
        array('slug' => 'twitch', 'color' => '#6441a5', 'name' => 'Twitch', 'text' => '', 'type' => 'clip'),
        array('slug' => 'twitter', 'color' => '#00aced', 'name' => 'Twitter', 'text' => '', 'type' => 'video'),
        array('slug' => 'vimeo', 'color' => '#1ab7ea', 'name' => 'Vimeo', 'text' => '', 'type' => 'video'),
        array('slug' => 'vkontakte', 'color' => '#4a76a8', 'name' => 'VK', 'text' => '', 'type' => 'video'),
        array('slug' => 'youtube', 'color' => '#d82624', 'name' => 'YouTube', 'text' => '', 'type' => 'video'),
    );
}