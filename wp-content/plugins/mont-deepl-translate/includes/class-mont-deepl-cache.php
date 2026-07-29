<?php
/**
 * Persistent DeepL string cache + monthly usage counter.
 *
 * @package Mont_DeepL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Mont_DeepL_Cache {

    const USAGE_OPTION = 'mont_deepl_usage';

    /**
     * Create / upgrade cache table.
     */
    public static function install() {
        global $wpdb;

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            hash char(40) NOT NULL,
            source_lang varchar(8) NOT NULL DEFAULT '',
            target_lang varchar(8) NOT NULL DEFAULT '',
            source_text longtext NOT NULL,
            translated_text longtext NOT NULL,
            char_count int(11) unsigned NOT NULL DEFAULT 0,
            hits bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY hash_langs (hash, source_lang, target_lang),
            KEY target_lang (target_lang)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        if (!get_option(self::USAGE_OPTION)) {
            update_option(self::USAGE_OPTION, array(
                'month'      => gmdate('Y-m'),
                'characters' => 0,
            ), false);
        }
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . MONT_DEEPL_TABLE;
    }

    public static function make_hash($text) {
        return sha1((string) $text);
    }

    /**
     * @param string $source_lang
     * @param string $target_lang
     * @param array  $texts       Unique source strings.
     * @return array Map source_text => translated_text (cache hits only).
     */
    public static function get_many($source_lang, $target_lang, array $texts) {
        global $wpdb;

        $texts = array_values(array_unique(array_filter(array_map('strval', $texts))));
        if (!$texts) {
            return array();
        }

        $table = self::table_name();
        $map   = array();
        $hashes = array();

        foreach ($texts as $text) {
            $hashes[self::make_hash($text)] = $text;
        }

        $hash_list = array_keys($hashes);
        $placeholders = implode(',', array_fill(0, count($hash_list), '%s'));

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $wpdb->prepare(
            "SELECT hash, source_text, translated_text FROM {$table}
             WHERE source_lang = %s AND target_lang = %s AND hash IN ({$placeholders})",
            array_merge(array($source_lang, $target_lang), $hash_list)
        );

        $rows = $wpdb->get_results($sql);
        if (!$rows) {
            return array();
        }

        $hit_ids = array();
        foreach ($rows as $row) {
            $map[$row->source_text] = $row->translated_text;
            $hit_ids[] = $row->hash;
        }

        if ($hit_ids) {
            $in = implode(',', array_fill(0, count($hit_ids), '%s'));
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET hits = hits + 1 WHERE source_lang = %s AND target_lang = %s AND hash IN ({$in})",
                    array_merge(array($source_lang, $target_lang), $hit_ids)
                )
            );
        }

        return $map;
    }

    /**
     * Store translations permanently.
     *
     * @param string $source_lang
     * @param string $target_lang
     * @param array  $pairs source => translated
     */
    public static function put_many($source_lang, $target_lang, array $pairs) {
        global $wpdb;

        if (!$pairs) {
            return;
        }

        $table = self::table_name();

        foreach ($pairs as $source => $translated) {
            $source     = (string) $source;
            $translated = (string) $translated;
            if ($source === '' || $translated === '') {
                continue;
            }

            $hash  = self::make_hash($source);
            $chars = self::char_count($source);

            $wpdb->replace(
                $table,
                array(
                    'hash'            => $hash,
                    'source_lang'     => $source_lang,
                    'target_lang'     => $target_lang,
                    'source_text'     => $source,
                    'translated_text' => $translated,
                    'char_count'      => $chars,
                    'hits'            => 0,
                ),
                array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
            );
        }
    }

    public static function char_count($text) {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen((string) $text, 'UTF-8');
        }
        return strlen((string) $text);
    }

    public static function get_usage() {
        $usage = get_option(self::USAGE_OPTION, array());
        $month = gmdate('Y-m');

        if (empty($usage['month']) || $usage['month'] !== $month) {
            $usage = array(
                'month'      => $month,
                'characters' => 0,
            );
            update_option(self::USAGE_OPTION, $usage, false);
        }

        return $usage;
    }

    public static function add_usage($chars) {
        $chars = max(0, (int) $chars);
        if (!$chars) {
            return self::get_usage();
        }

        $usage = self::get_usage();
        $usage['characters'] = (int) $usage['characters'] + $chars;
        update_option(self::USAGE_OPTION, $usage, false);
        return $usage;
    }

    public static function count_entries() {
        global $wpdb;
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    public static function clear_all() {
        global $wpdb;
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("TRUNCATE TABLE {$table}");
    }
}
