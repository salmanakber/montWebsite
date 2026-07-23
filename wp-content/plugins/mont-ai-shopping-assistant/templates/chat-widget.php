<?php
/**
 * Floating chat widget markup.
 *
 * @package Mont_AI_Assistant
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="mont-ai-root" class="mont-ai-root" aria-live="polite">
	<button type="button" class="mont-ai-bubble" id="mont-ai-bubble" aria-label="<?php esc_attr_e( 'Open shopping assistant', 'mont-ai-assistant' ); ?>" aria-expanded="false">
		<svg class="mont-ai-bubble__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6a4 4 0 014-4h8a4 4 0 014 4v7a4 4 0 01-4 4H9l-4.2 3.15A1 1 0 013 19.4V6z" fill="currentColor"/></svg>
	</button>

	<section class="mont-ai-panel" id="mont-ai-panel" hidden>
		<header class="mont-ai-panel__header">
			<div class="mont-ai-panel__titles">
				<span class="mont-ai-panel__eyebrow">Mont</span>
				<strong class="mont-ai-panel__title"><?php esc_html_e( 'Shopping Assistant', 'mont-ai-assistant' ); ?></strong>
			</div>
			<div class="mont-ai-panel__actions">
				<label class="mont-ai-lang" for="mont-ai-lang">
					<span class="screen-reader-text"><?php esc_html_e( 'Language', 'mont-ai-assistant' ); ?></span>
					<select id="mont-ai-lang" class="mont-ai-lang__select" aria-label="<?php esc_attr_e( 'Language', 'mont-ai-assistant' ); ?>">
						<option value="en">🇺🇸 EN</option>
						<option value="it">🇮🇹 IT</option>
						<option value="nb">🇳🇴 NO</option>
						<option value="vi">🇻🇳 VI</option>
					</select>
				</label>
				<button type="button" class="mont-ai-icon-btn" id="mont-ai-new" title="<?php esc_attr_e( 'New chat', 'mont-ai-assistant' ); ?>" aria-label="<?php esc_attr_e( 'New chat', 'mont-ai-assistant' ); ?>">＋</button>
				<button type="button" class="mont-ai-icon-btn" id="mont-ai-close" aria-label="<?php esc_attr_e( 'Close', 'mont-ai-assistant' ); ?>">×</button>
			</div>
		</header>

		<div class="mont-ai-messages" id="mont-ai-messages" role="log"></div>

		<footer class="mont-ai-composer">
			<textarea id="mont-ai-input" class="mont-ai-composer__input" rows="1" placeholder="<?php esc_attr_e( 'Ask me anything…', 'mont-ai-assistant' ); ?>"></textarea>
			<button type="button" class="mont-ai-composer__send" id="mont-ai-send"><?php esc_html_e( 'Send', 'mont-ai-assistant' ); ?></button>
		</footer>
	</section>
</div>
