<?php
/**
 * Admin settings view.
 *
 * @package Mont_AI_Assistant
 * @var array $settings
 * @var array $logs
 * @var array|\WP_Error $categories
 */

defined( 'ABSPATH' ) || exit;

$s = $settings;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Mont AI Shopping Assistant', 'mont-ai-assistant' ); ?></h1>
	<?php settings_errors( 'mont_ai' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'mont_ai_save_settings', 'mont_ai_settings_nonce' ); ?>

		<h2 class="title"><?php esc_html_e( 'AI Providers', 'mont-ai-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="groq_api_key"><?php esc_html_e( 'Groq API Key', 'mont-ai-assistant' ); ?></label></th>
				<td><input type="password" class="regular-text" id="groq_api_key" name="mont_ai[groq_api_key]" value="<?php echo esc_attr( $s['groq_api_key'] ); ?>" autocomplete="off" /></td>
			</tr>
			<tr>
				<th><label for="gemini_api_key"><?php esc_html_e( 'Gemini API Key', 'mont-ai-assistant' ); ?></label></th>
				<td><input type="password" class="regular-text" id="gemini_api_key" name="mont_ai[gemini_api_key]" value="<?php echo esc_attr( $s['gemini_api_key'] ); ?>" autocomplete="off" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Primary provider', 'mont-ai-assistant' ); ?></th>
				<td>
					<select name="mont_ai[primary_provider]">
						<option value="groq" <?php selected( $s['primary_provider'], 'groq' ); ?>>Groq</option>
						<option value="gemini" <?php selected( $s['primary_provider'], 'gemini' ); ?>>Gemini</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Fallback provider', 'mont-ai-assistant' ); ?></th>
				<td>
					<select name="mont_ai[fallback_provider]">
						<option value="gemini" <?php selected( $s['fallback_provider'], 'gemini' ); ?>>Gemini</option>
						<option value="groq" <?php selected( $s['fallback_provider'], 'groq' ); ?>>Groq</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="groq_model"><?php esc_html_e( 'Groq model', 'mont-ai-assistant' ); ?></label></th>
				<td><input type="text" class="regular-text" id="groq_model" name="mont_ai[groq_model]" value="<?php echo esc_attr( $s['groq_model'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="gemini_model"><?php esc_html_e( 'Gemini model', 'mont-ai-assistant' ); ?></label></th>
				<td><input type="text" class="regular-text" id="gemini_model" name="mont_ai[gemini_model]" value="<?php echo esc_attr( $s['gemini_model'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="temperature"><?php esc_html_e( 'Temperature', 'mont-ai-assistant' ); ?></label></th>
				<td><input type="number" step="0.1" min="0" max="2" id="temperature" name="mont_ai[temperature]" value="<?php echo esc_attr( $s['temperature'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="max_tokens"><?php esc_html_e( 'Max tokens', 'mont-ai-assistant' ); ?></label></th>
				<td><input type="number" min="256" max="8192" id="max_tokens" name="mont_ai[max_tokens]" value="<?php echo esc_attr( $s['max_tokens'] ); ?>" /></td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Assistant behaviour', 'mont-ai-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="welcome_message"><?php esc_html_e( 'Welcome message', 'mont-ai-assistant' ); ?></label></th>
				<td><textarea class="large-text" rows="3" id="welcome_message" name="mont_ai[welcome_message]"><?php echo esc_textarea( $s['welcome_message'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="system_prompt"><?php esc_html_e( 'Extra system prompt', 'mont-ai-assistant' ); ?></label></th>
				<td><textarea class="large-text" rows="6" id="system_prompt" name="mont_ai[system_prompt]"><?php echo esc_textarea( $s['system_prompt'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Appended to the built-in shopping concierge instructions.', 'mont-ai-assistant' ); ?></p></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Allowed categories', 'mont-ai-assistant' ); ?></th>
				<td>
					<?php if ( ! is_wp_error( $categories ) ) : ?>
						<select name="mont_ai[allowed_categories][]" multiple style="min-width:280px;min-height:140px;">
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( (int) $cat->term_id, array_map( 'intval', (array) $s['allowed_categories'] ), true ) ); ?>>
									<?php echo esc_html( $cat->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Leave empty to index all products.', 'mont-ai-assistant' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'UI & languages', 'mont-ai-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="theme_color"><?php esc_html_e( 'Theme color', 'mont-ai-assistant' ); ?></label></th>
				<td><input type="color" id="theme_color" name="mont_ai[theme_color]" value="<?php echo esc_attr( $s['theme_color'] ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Languages', 'mont-ai-assistant' ); ?></th>
				<td>
					<?php
					$all = \Mont_AI_Assistant\Language\Language_Manager::all();
					foreach ( $all as $code => $lang ) :
						?>
						<label style="display:inline-block;margin-right:12px;">
							<input type="checkbox" name="mont_ai[languages][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, (array) $s['languages'], true ) ); ?> />
							<?php echo esc_html( $lang['flag'] . ' ' . $lang['label'] ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Default language', 'mont-ai-assistant' ); ?></th>
				<td>
					<select name="mont_ai[default_language]">
						<?php foreach ( $all as $code => $lang ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $s['default_language'], $code ); ?>><?php echo esc_html( $lang['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Debug', 'mont-ai-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Enable logging', 'mont-ai-assistant' ); ?></th>
				<td><label><input type="checkbox" name="mont_ai[enable_logging]" value="1" <?php checked( ! empty( $s['enable_logging'] ) ); ?> /> <?php esc_html_e( 'Log provider usage & errors', 'mont-ai-assistant' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Debug mode', 'mont-ai-assistant' ); ?></th>
				<td><label><input type="checkbox" name="mont_ai[enable_debug]" value="1" <?php checked( ! empty( $s['enable_debug'] ) ); ?> /> <?php esc_html_e( 'Expose provider name in API responses (for testing)', 'mont-ai-assistant' ); ?></label></td>
			</tr>
		</table>

		<?php submit_button( __( 'Save settings', 'mont-ai-assistant' ) ); ?>
	</form>

	<hr />
	<h2><?php esc_html_e( 'Product index', 'mont-ai-assistant' ); ?></h2>
	<p><?php esc_html_e( 'Rebuild the searchable product cache used by the assistant.', 'mont-ai-assistant' ); ?></p>
	<button type="button" class="button" id="mont-ai-rebuild-index"><?php esc_html_e( 'Rebuild index now', 'mont-ai-assistant' ); ?></button>
	<span id="mont-ai-rebuild-status" style="margin-left:8px;"></span>

	<?php if ( ! empty( $logs ) ) : ?>
		<hr />
		<h2><?php esc_html_e( 'Recent logs', 'mont-ai-assistant' ); ?></h2>
		<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:280px;overflow:auto;"><?php echo esc_html( implode( "\n", $logs ) ); ?></pre>
	<?php endif; ?>
</div>
<script>
(function($){
	$('#mont-ai-rebuild-index').on('click', function(){
		var $btn = $(this), $st = $('#mont-ai-rebuild-status');
		$btn.prop('disabled', true);
		$st.text('Rebuilding…');
		$.post(ajaxurl, {
			action: 'mont_ai_rebuild_index',
			nonce: '<?php echo esc_js( wp_create_nonce( 'mont_ai_admin' ) ); ?>'
		}).done(function(res){
			$st.text(res && res.success ? ('Indexed ' + res.data.count + ' products') : 'Failed');
		}).fail(function(){
			$st.text('Failed');
		}).always(function(){
			$btn.prop('disabled', false);
		});
	});
})(jQuery);
</script>
