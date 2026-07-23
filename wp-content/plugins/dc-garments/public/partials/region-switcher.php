<?php
/**
 * Region / Currency switcher partial.
 *
 * @package DC_Product_Manager
 *
 * @var string $current_slug
 * @var array  $current
 * @var array  $regions
 * @var string $panel_id
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($current) || !is_array($current)) {
    $current = array(
        'label'   => 'International',
        'display' => '$ USD',
        'flag'    => 'globe',
    );
}

$current_flag  = !empty($current['flag']) ? $current['flag'] : 'globe';
$current_label = $current['label'] . ' • ' . $current['display'];
$panel_id = !empty($panel_id) ? $panel_id : 'dc-region-panel';
?>
<div class="dc-region-switcher" data-current="<?php echo esc_attr($current_slug); ?>">
    <button type="button" class="dc-region-trigger" aria-expanded="false" aria-controls="<?php echo esc_attr($panel_id); ?>">
        <span class="dc-region-trigger-label">Region / Currency</span>
        <span class="dc-region-trigger-value">
            <span class="dc-region-flag dc-region-flag--<?php echo esc_attr($current_flag); ?> dc-region-flag--trigger" aria-hidden="true"></span>
            <span class="dc-region-trigger-text"><?php echo esc_html($current_label); ?></span>
            <svg class="dc-region-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
    </button>

    <div id="<?php echo esc_attr($panel_id); ?>" class="dc-region-panel" hidden aria-hidden="true">
        <div class="dc-region-panel-header">
            <div class="dc-region-panel-current">
                <span class="dc-region-panel-label">Region / Currency</span>
                <span class="dc-region-panel-selected">
                    <span class="dc-region-flag dc-region-flag--<?php echo esc_attr($current_flag); ?> dc-region-flag--trigger" aria-hidden="true"></span>
                    <?php echo esc_html($current_label); ?>
                </span>
            </div>
            <button type="button" class="dc-region-close" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <p class="dc-region-panel-title">SELECT YOUR REGION / CURRENCY</p>

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

    <div class="dc-region-overlay" hidden aria-hidden="true"></div>
</div>
