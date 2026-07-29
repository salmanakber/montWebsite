<?php
/**
 * DeepL API client.
 *
 * @package Mont_DeepL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Mont_DeepL_API {

    /**
     * Languages DeepL supports as targets (uppercase API codes).
     */
    public static function supported_targets() {
        return array(
            'EN-US' => 'English (US)',
            'EN-GB' => 'English (UK)',
            'IT'    => 'Italian',
            'NB'    => 'Norwegian (Bokmål)',
            'DE'    => 'German',
            'FR'    => 'French',
            'ES'    => 'Spanish',
            'NL'    => 'Dutch',
            'PL'    => 'Polish',
            'SV'    => 'Swedish',
            'DA'    => 'Danish',
            'FI'    => 'Finnish',
            'PT'    => 'Portuguese',
            'RU'    => 'Russian',
            'JA'    => 'Japanese',
            'ZH'    => 'Chinese',
        );
    }

    /**
     * Map site / region lang codes → DeepL codes.
     */
    public static function normalize_lang($code) {
        $code = strtolower(trim((string) $code));
        $map  = array(
            'en'    => 'EN-US',
            'en-us' => 'EN-US',
            'en-gb' => 'EN-GB',
            'it'    => 'IT',
            'nb'    => 'NB',
            'no'    => 'NB',
            'nn'    => 'NB',
            'vi'    => '',
            'vn'    => '',
        );

        if (isset($map[$code])) {
            return $map[$code];
        }

        $upper = strtoupper($code);
        if ($upper === 'EN') {
            return 'EN-US';
        }

        return isset(self::supported_targets()[$upper]) ? $upper : '';
    }

    /**
     * Free keys end with :fx — use api-free.deepl.com automatically.
     */
    public static function detect_plan_from_key($api_key) {
        $api_key = trim((string) $api_key);
        if ($api_key !== '' && substr($api_key, -3) === ':fx') {
            return 'free';
        }
        return 'pro';
    }

    public static function endpoint($plan = null) {
        if ($plan === null) {
            $settings = Mont_DeepL_Plugin::settings();
            $plan     = !empty($settings['api_plan']) ? $settings['api_plan'] : 'free';
            if (!empty($settings['api_key'])) {
                $plan = self::detect_plan_from_key($settings['api_key']);
            }
        }
        return $plan === 'free'
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';
    }

    public static function usage_endpoint($plan = null) {
        if ($plan === null) {
            $settings = Mont_DeepL_Plugin::settings();
            $plan     = !empty($settings['api_plan']) ? $settings['api_plan'] : 'free';
            if (!empty($settings['api_key'])) {
                $plan = self::detect_plan_from_key($settings['api_key']);
            }
        }
        return $plan === 'free'
            ? 'https://api-free.deepl.com/v2/usage'
            : 'https://api.deepl.com/v2/usage';
    }

    /**
     * @param string $api_key
     * @return array|\WP_Error
     */
    private static function request($url, $api_key, $body = null, $method = 'POST') {
        $api_key = trim((string) $api_key);
        if ($api_key === '') {
            return new WP_Error('mont_deepl_no_key', __('DeepL API key is not configured.', 'mont-deepl'));
        }

        $args = array(
            'timeout' => 45,
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'DeepL-Auth-Key ' . $api_key,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'MontDeepLTranslate/' . MONT_DEEPL_VERSION,
            ),
        );

        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        if ($code < 200 || $code >= 300) {
            $msg = '';
            if (is_array($data)) {
                if (!empty($data['message'])) {
                    $msg = (string) $data['message'];
                } elseif (!empty($data['error']['message'])) {
                    $msg = (string) $data['error']['message'];
                }
            }
            if ($msg === '') {
                $msg = sprintf(
                    /* translators: 1: HTTP status code */
                    __('DeepL API request failed (HTTP %d).', 'mont-deepl'),
                    $code
                );
            }
            return new WP_Error('mont_deepl_api', $msg, array(
                'status' => $code,
                'body'   => $raw,
                'url'    => $url,
            ));
        }

        return is_array($data) ? $data : array('raw' => $raw);
    }

    /**
     * Test API key with a tiny translation (always hits DeepL, skips cache).
     *
     * @param string|null $api_key
     * @param string      $source_lang
     * @param string      $target_lang
     * @return array|\WP_Error
     */
    public static function test_connection($api_key = null, $source_lang = 'NB', $target_lang = 'EN-US') {
        $settings = Mont_DeepL_Plugin::settings();
        $api_key  = $api_key !== null ? trim((string) $api_key) : trim((string) $settings['api_key']);
        $source   = self::normalize_lang($source_lang);
        $target   = self::normalize_lang($target_lang);

        if ($api_key === '') {
            return new WP_Error('mont_deepl_no_key', __('DeepL API key is not configured.', 'mont-deepl'));
        }

        $plan = self::detect_plan_from_key($api_key);
        $sample = 'Mont DeepL test — gratis frakt over hele verden.';

        $payload = array(
            'text'        => array($sample),
            'source_lang' => $source,
            'target_lang' => $target,
        );

        $data = self::request(self::endpoint($plan), $api_key, $payload, 'POST');
        if (is_wp_error($data)) {
            return $data;
        }

        $translated = '';
        if (!empty($data['translations'][0]['text'])) {
            $translated = (string) $data['translations'][0]['text'];
        }

        $usage = self::get_remote_usage($api_key, $plan);

        Mont_DeepL_Cache::add_usage(self::char_count($sample));

        return array(
            'plan'       => $plan,
            'endpoint'   => self::endpoint($plan),
            'source'     => $sample,
            'translated' => $translated,
            'source_lang'=> $source,
            'target_lang'=> $target,
            'remote_usage' => $usage,
        );
    }

    /**
     * @return array|\WP_Error|null
     */
    public static function get_remote_usage($api_key = null, $plan = null) {
        $settings = Mont_DeepL_Plugin::settings();
        $api_key  = $api_key !== null ? trim((string) $api_key) : trim((string) $settings['api_key']);
        if ($api_key === '') {
            return null;
        }
        if ($plan === null) {
            $plan = self::detect_plan_from_key($api_key);
        }

        $data = self::request(self::usage_endpoint($plan), $api_key, null, 'GET');
        if (is_wp_error($data)) {
            return $data;
        }

        return array(
            'character_count' => isset($data['character_count']) ? (int) $data['character_count'] : 0,
            'character_limit' => isset($data['character_limit']) ? (int) $data['character_limit'] : 0,
        );
    }

    public static function char_count($text) {
        return Mont_DeepL_Cache::char_count($text);
    }

    /**
     * Translate strings, using cache first. Only uncached strings hit DeepL.
     *
     * @param array  $texts
     * @param string $target_lang
     * @param string $source_lang
     * @param bool   $force_api Skip cache (for tests).
     * @return array|\WP_Error
     */
    public static function translate_batch(array $texts, $target_lang, $source_lang, $force_api = false) {
        $target_lang = self::normalize_lang($target_lang);
        $source_lang = self::normalize_lang($source_lang);

        if (!$target_lang) {
            return new WP_Error('mont_deepl_unsupported', __('Target language is not supported by DeepL.', 'mont-deepl'));
        }

        if (!$source_lang) {
            return new WP_Error('mont_deepl_unsupported_source', __('Source language is not supported by DeepL.', 'mont-deepl'));
        }

        if ($target_lang === $source_lang) {
            $identity = array();
            foreach ($texts as $t) {
                $identity[(string) $t] = (string) $t;
            }
            return $identity;
        }

        $clean = array();
        foreach ($texts as $t) {
            $t = (string) $t;
            $trimmed = trim($t);
            if ($trimmed === '' || !preg_match('/\p{L}/u', $trimmed)) {
                continue;
            }
            if (strlen($trimmed) > 4500) {
                $t = function_exists('mb_substr') ? mb_substr($t, 0, 4500, 'UTF-8') : substr($t, 0, 4500);
            }
            $clean[$t] = $t;
        }
        $clean = array_values($clean);

        if (!$clean) {
            return array();
        }

        $result = array();
        $missing = $clean;

        if (!$force_api) {
            if (!Mont_DeepL_Cache::table_exists()) {
                Mont_DeepL_Cache::install();
            }
            $cached = Mont_DeepL_Cache::get_many($source_lang, $target_lang, $clean);
            $result = $cached;
            $missing = array();
            foreach ($clean as $text) {
                if (!array_key_exists($text, $cached)) {
                    $missing[] = $text;
                }
            }
            if (!$missing) {
                return $result;
            }
        }

        $settings = Mont_DeepL_Plugin::settings();
        $api_key  = trim((string) $settings['api_key']);
        if ($api_key === '') {
            return new WP_Error('mont_deepl_no_key', __('DeepL API key is not configured.', 'mont-deepl'));
        }

        $limit  = max(1000, (int) $settings['monthly_limit']);
        $usage  = Mont_DeepL_Cache::get_usage();
        $needed = 0;
        foreach ($missing as $m) {
            $needed += Mont_DeepL_Cache::char_count($m);
        }

        if (!$force_api && ((int) $usage['characters'] + $needed) > $limit) {
            return new WP_Error(
                'mont_deepl_limit',
                sprintf(
                    __('DeepL monthly character limit reached (%1$s / %2$s). Cached strings still work.', 'mont-deepl'),
                    number_format_i18n((int) $usage['characters']),
                    number_format_i18n($limit)
                )
            );
        }

        $plan   = self::detect_plan_from_key($api_key);
        $chunks = array_chunk($missing, 40);
        $fresh  = array();

        foreach ($chunks as $chunk) {
            $payload = array(
                'text'        => array_values($chunk),
                'source_lang' => $source_lang,
                'target_lang' => $target_lang,
            );

            $data = self::request(self::endpoint($plan), $api_key, $payload, 'POST');
            if (is_wp_error($data)) {
                return $data;
            }

            if (empty($data['translations']) || !is_array($data['translations'])) {
                return new WP_Error('mont_deepl_api', __('DeepL returned an unexpected response.', 'mont-deepl'));
            }

            $used = 0;
            foreach ($data['translations'] as $i => $row) {
                if (!isset($chunk[$i])) {
                    continue;
                }
                $src = $chunk[$i];
                $dst = isset($row['text']) ? (string) $row['text'] : $src;
                $fresh[$src] = $dst;
                $used += Mont_DeepL_Cache::char_count($src);
            }

            if ($used > 0) {
                Mont_DeepL_Cache::add_usage($used);
            }
        }

        if (!$force_api && $fresh) {
            Mont_DeepL_Cache::put_many($source_lang, $target_lang, $fresh);
        }

        return array_merge($result, $fresh);
    }
}
