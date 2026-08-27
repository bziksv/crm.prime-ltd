<?php

namespace App\Libraries;

use App\Models\Outbound_proxies_model;
use App\Models\Settings_model;

/**
 * Outbound HTTP with direct-first, proxy-fallback (like cabinet.titlo.ru telegram-proxy).
 */
class Outbound_http {

    const PROBE_URL = 'https://api.telegram.org/';
    const SEND_ORDER_SETTING = 'outbound_proxy_send_order';
    const STATUS_SETTING = 'outbound_proxy_status';

    public static function isValidProxyUrl($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        return in_array(strtolower($parts['scheme']), ['http', 'https', 'socks5', 'socks5h'], true);
    }

    public static function maskProxyUrl($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return '***';
        }
        $user = isset($parts['user']) ? $parts['user'] . ':***@' : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $scheme = $parts['scheme'] ?? 'http';
        return $scheme . '://' . $user . $parts['host'] . $port;
    }

    /**
     * @return array{ok:bool,http_code:int,curl_error:string,elapsed_ms:int}
     */
    public static function probe($targetUrl, $proxy = null, $timeout = 12) {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'http_code' => 0, 'curl_error' => 'curl missing', 'elapsed_ms' => 0];
        }

        $started = microtime(true);
        $ch = curl_init($targetUrl);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_NOSIGNAL => true,
        ];

        if ($proxy) {
            $options[CURLOPT_PROXY] = $proxy;
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $elapsed = (int) round((microtime(true) - $started) * 1000);
        $ok = $httpCode >= 200 && $httpCode < 400 && $curlError === '';

        return [
            'ok' => $ok,
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'elapsed_ms' => $elapsed,
        ];
    }

    public static function forgetSendOrderCache() {
        $settings = model(Settings_model::class);
        $settings->save_setting(self::SEND_ORDER_SETTING, '');
        $settings->save_setting(self::STATUS_SETTING, '');
    }

    /**
     * @return array<int, string> direct|proxy:{id}
     */
    public static function sendAttemptOrder($fresh = false) {
        $settings = model(Settings_model::class);

        if (!$fresh) {
            $cached = $settings->get_setting(self::SEND_ORDER_SETTING);
            if ($cached) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded) && $decoded !== []) {
                    return $decoded;
                }
            }
        }

        $order = self::computeSendAttemptOrder();
        $settings->save_setting(self::SEND_ORDER_SETTING, json_encode($order));

        return $order;
    }

    /**
     * @return array<int, string>
     */
    public static function computeSendAttemptOrder($probeUrl = self::PROBE_URL) {
        $proxiesModel = model(Outbound_proxies_model::class);
        $enabled = $proxiesModel->get_enabled();

        $direct = self::probe($probeUrl, null, 6);
        $directOk = !empty($direct['ok']);

        $working = [];
        foreach ($enabled as $proxy) {
            $probe = self::probe($probeUrl, $proxy->url, 10);
            if (!empty($probe['ok'])) {
                $working[] = (string) $proxy->id;
            }
        }

        if ($working === []) {
            return ['direct'];
        }

        if (!$directOk) {
            return array_map(static function ($id) {
                return 'proxy:' . $id;
            }, $working);
        }

        $order = ['direct'];
        foreach ($working as $id) {
            $order[] = 'proxy:' . $id;
        }

        return $order;
    }

    /**
     * @return array<int, array{send_via:string,proxy:?string}>
     */
    public static function buildSendAttempts($skipDirectIfProxies = true) {
        $proxiesModel = model(Outbound_proxies_model::class);
        $enabled = $proxiesModel->get_enabled();
        $attempts = [];

        foreach (self::sendAttemptOrder() as $mode) {
            if ($mode === 'direct') {
                $attempts[] = ['send_via' => 'direct', 'proxy' => null];
                continue;
            }
            if (strpos($mode, 'proxy:') === 0) {
                $id = (int) substr($mode, 6);
                $row = $proxiesModel->get_one($id);
                if ($row && !(int) $row->deleted && (int) $row->enabled === 1 && $row->url) {
                    $attempts[] = ['send_via' => $mode, 'proxy' => $row->url];
                }
            }
        }

        if ($attempts === []) {
            $attempts[] = ['send_via' => 'direct', 'proxy' => null];
        }

        if ($skipDirectIfProxies && $enabled !== []) {
            $filtered = array_values(array_filter($attempts, static function ($a) {
                return $a['send_via'] !== 'direct';
            }));
            if ($filtered !== []) {
                $attempts = $filtered;
            }
        }

        return $attempts;
    }

    /**
     * @return array{ok:bool,body:string,http_code:int,curl_error:string,send_via:string,proxy_masked:string}
     */
    public static function request($url, array $options = [], $timeout = 30) {
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'body' => '',
                'http_code' => 0,
                'curl_error' => 'curl missing',
                'send_via' => '',
                'proxy_masked' => '',
            ];
        }

        $last = [
            'ok' => false,
            'body' => '',
            'http_code' => 0,
            'curl_error' => 'no attempts',
            'send_via' => '',
            'proxy_masked' => '',
        ];

        foreach (self::buildSendAttempts() as $attempt) {
            $ch = curl_init($url);
            $curlOpts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => min(15, $timeout),
                CURLOPT_NOSIGNAL => true,
            ];

            if ($attempt['proxy']) {
                $curlOpts[CURLOPT_PROXY] = $attempt['proxy'];
                $curlOpts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }

            foreach ($options as $key => $value) {
                $curlOpts[$key] = $value;
            }

            curl_setopt_array($ch, $curlOpts);
            $body = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $last = [
                'ok' => ($body !== false) && $curlError === '',
                'body' => $body === false ? '' : (string) $body,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'send_via' => $attempt['send_via'],
                'proxy_masked' => $attempt['proxy'] ? self::maskProxyUrl($attempt['proxy']) : '',
            ];

            if ($last['ok'] && self::isSuccessfulResponse($httpCode, $last['body'])) {
                return $last;
            }
        }

        return $last;
    }

    private static function isSuccessfulResponse($httpCode, $body) {
        if ($httpCode < 200 || $httpCode >= 300) {
            return false;
        }
        $json = json_decode($body, true);
        if (is_array($json) && array_key_exists('ok', $json)) {
            return !empty($json['ok']);
        }
        return true;
    }

    /**
     * @return array{ok:bool,json:?object,error:string,send_via:string}
     */
    public static function postJson($url, array $postFields, $timeout = 30) {
        $result = self::request($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
        ], $timeout);

        if (!$result['ok']) {
            return [
                'ok' => false,
                'json' => null,
                'error' => $result['curl_error'] ?: ('HTTP ' . $result['http_code']),
                'send_via' => $result['send_via'],
            ];
        }

        $decoded = json_decode($result['body']);
        $apiOk = is_object($decoded) && !empty($decoded->ok);

        return [
            'ok' => $apiOk,
            'json' => is_object($decoded) ? $decoded : null,
            'error' => $apiOk ? '' : (is_object($decoded) ? ($decoded->description ?? 'API error') : 'invalid JSON'),
            'send_via' => $result['send_via'],
        ];
    }

    /**
     * Build admin status payload for settings UI.
     */
    public static function connectivityStatus($fresh = false) {
        $settings = model(Settings_model::class);
        if (!$fresh) {
            $cached = $settings->get_setting(self::STATUS_SETTING);
            if ($cached) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded) && !empty($decoded['checked_at'])) {
                    return $decoded;
                }
            }
        }

        $proxiesModel = model(Outbound_proxies_model::class);
        $rows = [];
        foreach ($proxiesModel->get_all_active() as $row) {
            $probe = self::probe(self::PROBE_URL, $row->url, 12);
            $rows[] = [
                'id' => (int) $row->id,
                'label' => $row->label,
                'supplier' => $row->supplier,
                'url' => $row->url,
                'url_masked' => self::maskProxyUrl($row->url),
                'priority' => (int) $row->priority,
                'enabled' => (int) $row->enabled === 1,
                'probe' => $probe,
            ];
        }

        $direct = self::probe(self::PROBE_URL, null, 8);
        $enabledCount = count($proxiesModel->get_enabled());
        $tokenConfigured = false;
        if (function_exists('get_telegram_notification_setting')) {
            $tokenConfigured = (bool) get_telegram_notification_setting('bot_token');
        }

        $status = [
            'checked_at' => get_current_utc_time(),
            'probes_checked' => true,
            'direct' => $direct,
            'proxies' => $rows,
            'send_order' => self::sendAttemptOrder(true),
            'proxy_count' => $enabledCount,
            'token_configured' => $tokenConfigured,
        ];

        $settings->save_setting(self::STATUS_SETTING, json_encode($status));

        return $status;
    }
}
