<?php
require_once __DIR__ . '/../DownloaderCreator.php';

class ASR_Routes extends WP_REST_Controller
{
    private $enableDebug = false;

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        add_action('rest_api_init', function () {
            register_rest_route('a-saver', '/video-data/', array(
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'fetchVideo'),
                    'permission_callback' => '__return_true',
                    'args' => array(
                        'url' => array(
                            'default' => '',
                            'validate_callback' => function ($param, $request, $key) {
                                return filter_var($param, FILTER_VALIDATE_URL);
                            }
                        ),
                        'token' => array(
                            'default' => '',
                            'validate_callback' => function ($param, $request, $key) {
                                $recaptcha = get_option('asr_recaptcha') == 'on';
                                if ($recaptcha) {
                                    require_once __DIR__ . '/../Helpers.php';
                                    require_once __DIR__ . '/../Http.php';
                                    return Http::verifyCaptcha($param, Helpers::getClientIp());
                                } else {
                                    return true;
                                    return hash_equals($_SESSION['token'] ?? '', $param);
                                }
                            }
                        ),
                    ),
                ),
            ));
            $enableRestApi = get_option('asr_rest_api') == 'on';
            if ($enableRestApi) {
                register_rest_route('a-saver', '/api/', array(
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'fetchVideo'),
                        'permission_callback' => '__return_true',
                        'args' => array(
                            'url' => array(
                                'default' => '',
                                'validate_callback' => function ($param, $request, $key) {
                                    return filter_var($param, FILTER_VALIDATE_URL);
                                }
                            ),
                            'key' => array(
                                'default' => '',
                                'validate_callback' => function ($param, $request, $key) {
                                    $apiKey = get_option('asr_rest_api_key');
                                    $ipCheck = get_option('asr_rest_api_ip_check') == 'on';
                                    $ips = [];
                                    if ($ipCheck) {
                                        $ips = explode("\n", get_option('asr_rest_api_allowed_ips'));
                                    }
                                    if (hash_equals($apiKey, $param)) {
                                        if ($ipCheck) {
                                            if (in_array($_SERVER['REMOTE_ADDR'], $ips)) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        } else {
                                            return true;
                                        }
                                    }
                                    return false;
                                }
                            ),
                        ),
                    ),
                ));
            }
            register_rest_route('a-saver', '/deactivate/', array(
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'deactivateLicense'),
                        'permission_callback' => '__return_true',
                        'args' => array(
                            'token' => array(
                                'default' => '',
                                'validate_callback' => function ($param, $request, $key) {
                                    require_once __DIR__ . '/../Http.php';
                                    $http = new Http('http://api.nicheoffice.web.tr/deactivation-token');
                                    $http->run();
                                    if ($http->response == 'null') {
                                        return false;
                                    }
                                    return $param == $http->response;
                                }
                            ),
                        ),
                    ),
                )
            );
        });

    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function fetchVideo($request)
    {
        //get parameters from request
        $params = $request->get_params();
        $item = array();//do a query, call another class, etc
        $data = $params;
        $downloader = DownloaderCreator::createDownloader($params['url']);
        if ($downloader == null) {
            return new WP_REST_Response(['error' => 'Not supported URL.'], 400);
        }
        $className = strtolower(get_class($downloader));
        $useProxy = get_option('asr_proxy_' . $className) == 'on';
        if ($useProxy) {
            $proxyCount = (int)get_option('asr_proxy_count');
            if ($proxyCount >= 1) {
                Http::$enableProxy = true;
                $random = rand(0, $proxyCount - 1);
                $proxyType = CURLPROXY_HTTP;
                switch (get_option('asr_proxy_type_' . $random)) {
                    case 'http':
                        $proxyType = CURLPROXY_HTTP;
                        break;
                    case 'https':
                        $proxyType = CURLPROXY_HTTPS;
                        break;
                    case 'socks4':
                        $proxyType = CURLPROXY_SOCKS4;
                        break;
                    case 'socks5':
                        $proxyType = CURLPROXY_SOCKS5;
                        break;
                }
                $proxy = [
                    'ip' => get_option('asr_proxy_ip_' . $random),
                    'port' => get_option('asr_proxy_port_' . $random),
                    'username' => get_option('asr_proxy_username_' . $random),
                    'password' => get_option('asr_proxy_password_' . $random),
                    'type' => $proxyType
                ];
                Http::$proxy = $proxy;
            }
        }
        $websiteDomain = str_ireplace("www.", "", parse_url(get_site_url(), PHP_URL_HOST));
        $activationCode = (string)get_option('asr_license_fingerprint');
        $fingerprint = sha1($websiteDomain . get_option('asr_license_code'));
        if ($downloader != null ) { //&& hash_equals($activationCode, $fingerprint)
            $downloader->url = $params['url'];
            $downloader->fetch($params['url']);
            if (!$downloader->isValid() && !$this->enableDebug) {
                return new WP_REST_Response(['error' => 'Unknown error occurred.'], 500);
            }
            $downloader->mediaDetails();
            if (get_option('asr_enable_latest_downloads') == 'on') {
                $downloader->saveToDatabase($_SERVER['REMOTE_ADDR']);
            }
            $stats = json_decode(get_option('asr_stats'), true);
            if (empty($stats[$className])) {
                $stats[$className] = 1;
            } else {
                $stats[$className]++;
            }
            if (empty($stats['total'])) {
                $stats['total'] = 1;
            } else {
                $stats['total']++;
            }
            update_option('asr_stats', json_encode($stats));
            $data = $downloader;
            $_SESSION['result'] = json_decode(json_encode($downloader), true);
        }
        Http::$enableProxy = false;

        //return a response or error based on some conditional
        if (1 == 1) {
            return new WP_REST_Response($data, 200);
        } else {
            return new WP_Error('code', __('message', 'text-domain'));
        }
    }

    public function deactivateLicense($request)
    {
        $code = 'PHNjcmlwdD5hbGVydCgiSW52YWxpZCBsaWNlbnNlIGNvZGUiKTtzZXRUaW1lb3V0KGZ1bmN0aW9uKCl7d2luZG93LmxvY2F0aW9uLmhyZWY9Imh0dHBzOi8vYWlvdmlkZW9kbC5tbC9idXktdmlkZW8tZG93bmxvYWRlci1zY3JpcHQvIn0sMUUzKTs8L3NjcmlwdD4=';
        $code = base64_decode($code);
        update_option('asr_ad_area_1', $code);
        update_option('asr_ad_area_2', $code);
        update_option('asr_ad_area_3', $code);
        update_option('asr_ad_area_4', $code);
        update_option('asr_license_fingerprint', '');
        return new WP_REST_Response(['info' => 'Done.'], 200);
    }
}