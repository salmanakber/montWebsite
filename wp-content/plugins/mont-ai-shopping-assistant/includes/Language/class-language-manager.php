<?php
/**
 * Language manager for the shopping assistant.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Language;

defined( 'ABSPATH' ) || exit;

/**
 * Class Language_Manager
 *
 * Supported: en, it, nb, vi — AI is instructed to reply in the active language.
 */
class Language_Manager {

	/**
	 * Supported languages.
	 *
	 * @return array
	 */
	public static function all() {
		return array(
			'en' => array(
				'code'  => 'en',
				'label' => 'English',
				'flag'  => '🇺🇸',
				'locale'=> 'en_US',
			),
			'it' => array(
				'code'  => 'it',
				'label' => 'Italiano',
				'flag'  => '🇮🇹',
				'locale'=> 'it_IT',
			),
			'nb' => array(
				'code'  => 'nb',
				'label' => 'Norsk',
				'flag'  => '🇳🇴',
				'locale'=> 'nb_NO',
			),
			'vi' => array(
				'code'  => 'vi',
				'label' => 'Tiếng Việt',
				'flag'  => '🇻🇳',
				'locale'=> 'vi',
			),
		);
	}

	/**
	 * Normalize language code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function normalize( $code ) {
		$code = strtolower( substr( (string) $code, 0, 5 ) );
		$map  = array(
			'no' => 'nb',
			'nn' => 'nb',
			'eng'=> 'en',
		);
		if ( isset( $map[ $code ] ) ) {
			$code = $map[ $code ];
		}
		$all = self::all();
		return isset( $all[ $code ] ) ? $code : 'en';
	}

	/**
	 * Instruction snippet for system prompt.
	 *
	 * @param string $code Language.
	 * @return string
	 */
	public static function prompt_instruction( $code ) {
		$code = self::normalize( $code );
		$lang = self::all()[ $code ];
		return sprintf(
			'CRITICAL: Respond entirely in %s (%s). Product names stay in their original language unless a translation exists. Keep option labels clear; you may show original Norwegian labels in parentheses when helpful.',
			$lang['label'],
			$code
		);
	}
}
