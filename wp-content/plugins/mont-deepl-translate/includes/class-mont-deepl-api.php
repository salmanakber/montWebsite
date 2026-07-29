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
     *
     * Note: Vietnamese (VI) is not supported by DeepL.
     */
    public static function supported_targets() {
        return array(
            'EN' => 'English',
            'IT' => 'Italian',
            'NB' => 'Norwegian (Bokmål)',
            'DE' => 'German',
            'FR' => 'French',
            'ES' => 'Spanish',
            'NL' => 'Dutch',
            'PL' => 'Polish',
            'SV' => 'Swedish',
            'DA' => 'Danish',
            'FI' => 'Finnish',
            'PT' => 'Portuguese',
            'RU' => 'Russian',
            'JA' => 'Japanese',
            'ZH' => 'Chinese',
        );
    }

    /**
     * Map site / region lang codes → DeepL codes.
     */
    public static function normalize_lang($code) {
        $code = strtolower(trim((string) $code));
        $map  = array(
            'en'    => 'EN',
            'en-us' => 'EN',
            'en-gb' => 'EN',
            'it'    => 'IT',
            'nb'    => 'NB',
            'no'    => 'NB',
            'nn'    => 'NB',
            'vi'    => '', // unsupported
            'vn'    => '',
        );

        if (isset($map[$code])) {
            return $map[$code];
        }

        $upper = strtoupper($code);
        return isset(self::supported_targets()[$upper]) ? $upper : '';
    }

    public static function endpoint() {
        $settings = Mont_DeepL_Plugin::settings();
        $plan     = !empty($settings['api_plan']) ? $settings['api_plan'] : 'free';
        return $plan === 'pro'
            ? 'https://api.deepl.com/v2/translate'
            : 'https://api-free.deepl.com/v2/translate';
    }

    /**
     * Translate strings, using cache first. Only uncached strings hit DeepL.
     *
     * @param array  $texts
     * @param string $target_lang DeepL code e.g. IT
     * @param string $source_lang DeepL code e.g. NB
     * @return array|\WP_Error Map source => translated
     */
    public static function translate_batch(array $texts, $target_lang, $source_lang) {
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

        // Clean + unique.
        $clean = array();
        foreach ($texts as $t) {
            $t = (string) $t;
            $trimmed = trim($t);
            if ($trimmed === '' || !preg_match('/\p{L}/u', $trimmed)) {
                continue; // skip empty / numbers-only
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

        $cached = Mont_DeepL_Cache::get_many($source_lang, $target_lang, $clean);
        $missing = array();
        foreach ($clean as $text) {
            if (!array_key_exists($text, $cached)) {
                $missing[] = $text;
            }
        }

        $result = $cached;

        if (!$missing) {
            return $result;
        }

        $settings = Mont_DeepL_Plugin::settings();
        $api_key  = trim((string) $settings['api_key']);
        if ($api_key === '') {
            return new WP_Error('mont_deepl_no_key', __('DeepL API key is not configured.', 'mont-deepl'));
        }

        $limit   = max(1000, (int) $settings['monthly_limit']);
        $usage   = Mont_DeepL_Cache::get_usage();
        $needed  = 0;
        foreach ($missing as $m) {
            $needed += Mont_DeepL_Cache::char_count($m);
        }

        if (((int) $usage['characters'] + $needed) > $limit) {
            return new WP_Error(
                'mont_deepl_limit',
                sprintf(
                    /* translators: 1: used chars 2: monthly limit */
                    __('DeepL monthly character limit reached (%1$s / %2$s). Cached strings still work.', 'mont-deepl'),
                    number_format_i18n((int) $usage['characters']),
                    number_format_i18n($limit)
                )
            );
        }

        // DeepL accepts multiple text params — chunk to stay safe.
        $chunks = array_chunk($missing, 40);
        $fresh  = array();

        foreach ($chunks as $chunk) {
            $body = array(
                'auth_key'            => $api_key,
                'source_lang'         => $source_lang,
                'target_lang'         => $target_lang,
                'tag_handling'        => 'xml',
                'split_sentences'     => '1',
                'preserve_formatting' => '1',
            );

            // Build application/x-www-form-urlencoded with repeated text=
            $pairs = array();
            foreach ($body as $k => $v) {
                $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
            }
            foreach ($chunk as $text) {
                $pairs[] = 'text=' . rawurlencode($text);
            }

            $response = wp_remote_post(
                self::endpoint(),
                array(
                    'timeout' => 45,
                    'headers' => array(
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ),
                    'body'    => implode('&', $pairs),
                )
            );

            if (is_wp_error($response)) {
                return $response;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            $raw  = wp_remote_retrieve_body($response);
            $data = json_decode($raw, true);

            if ($code < 200 || $code >= 300 || empty($data['translations']) || !is_array($data['translations'])) {
                $msg = !empty($data['message']) ? $data['message'] : __('DeepL API request failed.', 'mont-deepl');
                return new WP_Error('mont_deepl_api', $msg, array('status' => $code, 'body' => $raw));
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

        Mont_DeepL_Cache::put_many($source_lang, $target_lang, $fresh);

        return array_merge($result, $fresh);
    }
}
