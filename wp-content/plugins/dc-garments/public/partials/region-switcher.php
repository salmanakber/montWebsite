<?php
/**
 * Region / Currency switcher partial.
 *
 * @package DC_Product_Manager
 *
 * @var string $current_slug
 * @var array  $current
 * @var array  $regions
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_label = $current['label'] . ' • ' . $current['display'];
?>
<div class="dc-region-switcher" data-current="<?php echo esc_attr($current_slug); ?>">
    <button type="button" class="dc-region-trigger" aria-expanded="false" aria-controls="dc-region-panel">
        <span class="dc-region-trigger-label"><?php esc_html_e('Region / Currency', 'dc-product-manager'); ?></span>
        <span class="dc-region-trigger-value">
            <?php echo esc_html($current_label); ?>
            <svg class="dc-region-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </button>

    <div id="dc-region-panel" class="dc-region-panel" hidden>
        <div class="dc-region-panel-header">
            <div class="dc-region-panel-current">
                <span class="dc-region-panel-label"><?php esc_html_e('Region / Currency', 'dc-product-manager'); ?></span>
                <span class="dc-region-panel-selected"><?php echo esc_html($current_label); ?></span>
            </div>
            <button type="button" class="dc-region-close" aria-label="<?php esc_attr_e('Close', 'dc-product-manager'); ?>">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <p class="dc-region-panel-title"><?php esc_html_e('SELECT YOUR REGION / CURRENCY', 'dc-product-manager'); ?></p>

        <ul class="dc-region-list" role="listbox">
            <?php foreach ($regions as $slug => $region) : ?>
                <li>
                    <button
                        type="button"
                        class="dc-region-option<?php echo $slug === $current_slug ? ' is-active' : ''; ?>"
                        data-region="<?php echo esc_attr($slug); ?>"
                        role="option"
                        aria-selected="<?php echo $slug === $current_slug ? 'true' : 'false'; ?>"
                    >
                        <span class="dc-region-flag dc-region-flag--<?php echo esc_attr($region['flag']); ?>" aria-hidden="true"></span>
                        <span class="dc-region-option-text">
                            <span class="dc-region-option-name"><?php echo esc_html($region['label']); ?></span>
                            <span class="dc-region-option-currency"><?php echo esc_html($region['display']); ?></span>
                        </span>
                        <svg class="dc-region-option-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M4.5 3L7.5 6L4.5 9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="dc-region-overlay" hidden></div>
</div>
