<?php
$mont_size_placeholder = 'data:image/svg+xml,' . rawurlencode(
	'<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><rect width="64" height="64" fill="#ececec"/><path d="M18 42l9-12 7 8 5-6 7 10H18z" fill="#cfcfcf"/><circle cx="24" cy="24" r="5" fill="#cfcfcf"/></svg>'
);
$mt = function_exists( 'mont_pdp_t' ) ? 'mont_pdp_t' : null;
$ts = function ( $key, $fallback = '' ) use ( $mt ) {
	return $mt ? $mt( $key ) : ( $fallback !== '' ? $fallback : $key );
};
$pick   = esc_html( $ts( 'measure_pick_fit_size', 'Choose fit + size' ) );
$free   = esc_html( $ts( 'free_of_charge', 'Free of charge' ) );
$change = esc_html( $ts( 'change', 'Change' ) );
$close  = esc_html( $ts( 'close_measure', 'Close' ) );
$left   = esc_html( $ts( 'left', 'Left' ) );
$right  = esc_html( $ts( 'right', 'Right' ) );
$more   = esc_html( $ts( 'show_more_measures', 'Show more measurements' ) );
$less   = esc_html( $ts( 'show_less_measures', 'Show fewer measurements' ) );
?>
<form id="customizationForm">
      <ul class="mont_sizes-measurement-list mont_option-list">
        <li class="mont_sizes-measurement-item" data-mont-size="shirt_length">
            <span class="mont_sizes-measurement-icon-wrap">
            <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="<?php echo esc_attr( $ts( 'shirt_length' ) ); ?>" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
            <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
            </span>
            <div class="mont_sizes-measurement-row">
                <div class="mont_sizes-measurement-details">
                    <h3 class="mont_sizes-measurement-name"><?php echo esc_html( $ts( 'shirt_length' ) ); ?></h3>
                    <p class="mont_sizes-measurement-value"><?php echo $pick; ?></p>
                    <span class="mont_sizes-measurement-price"><?php echo $free; ?></span>
                </div>
                <div class="mont_sizes-controls">
                    <div class="mont_sizes-control-group">
                        <button type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                        <span class="mont_sizes-control-value">0 cm</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                    </div>
                    <input type="hidden" name="mont_sizes[shirt_length]" value="0" class="mont_sizes-hidden-input" clicked="false">
                </div>
            </div>
            <a href="#" class="mont_sizes-change-btn"><?php echo $change; ?></a>
            <a href="#" class="mont_sizes-close-btn"><?php echo $close; ?></a>
        </li>

        <li class="mont_sizes-measurement-item" data-mont-size="sleeve_length">
            <span class="mont_sizes-measurement-icon-wrap">
            <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="<?php echo esc_attr( $ts( 'sleeve_length' ) ); ?>" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
            <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
            </span>
            <div class="mont_sizes-measurement-row">
                <div class="mont_sizes-measurement-details">
                    <h3 class="mont_sizes-measurement-name"><?php echo esc_html( $ts( 'sleeve_length' ) ); ?></h3>
                    <p class="mont_sizes-measurement-value"><?php echo $pick; ?></p>
                    <span class="mont_sizes-measurement-price"><?php echo $free; ?></span>
                </div>
                <div class="mont_sizes-controls">
                    <div class="mont_sizes-control-group">
                        <span class="mont_sizes-control-label"><?php echo $left; ?>:</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-minus" data-side="left">-</button>
                        <span class="mont_sizes-control-value" data-side="left">0 cm</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-plus" data-side="left">+</button>
                    </div>
                    <div class="mont_sizes-control-group">
                        <span class="mont_sizes-control-label"><?php echo $right; ?>:</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-minus" data-side="right">-</button>
                        <span class="mont_sizes-control-value" data-side="right">0 cm</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-plus" data-side="right">+</button>
                    </div>
                    <input type="hidden" name="mont_sizes[sleeve_length_left]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    <input type="hidden" name="mont_sizes[sleeve_length_right]" value="0" class="mont_sizes-hidden-input" clicked="false">
                </div>
            </div>
            <a href="#" class="mont_sizes-change-btn"><?php echo $change; ?></a>
            <a href="#" class="mont_sizes-close-btn"><?php echo $close; ?></a>
        </li>

        <div class="mont_sizes-additional-measurements mont_sizes-hidden">
            <li class="mont_sizes-measurement-item" data-mont-size="half_waist">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="<?php echo esc_attr( $ts( 'waist' ) ); ?>" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name"><?php echo esc_html( $ts( 'waist' ) ); ?></h3>
                        <p class="mont_sizes-measurement-value"><?php echo $pick; ?></p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                        </div>
                        <input type="hidden" name="mont_sizes[waist]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    </div>
                </div>
                <a href="#" class="mont_sizes-change-btn"><?php echo $change; ?></a>
                <a href="#" class="mont_sizes-close-btn"><?php echo $close; ?></a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="half_chest">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="<?php echo esc_attr( $ts( 'chest' ) ); ?>" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name"><?php echo esc_html( $ts( 'chest' ) ); ?></h3>
                        <p class="mont_sizes-measurement-value"><?php echo $pick; ?></p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                        </div>
                        <input type="hidden" name="mont_sizes[chest]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    </div>
                </div>
                <a href="#" class="mont_sizes-change-btn"><?php echo $change; ?></a>
                <a href="#" class="mont_sizes-close-btn"><?php echo $close; ?></a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="half_bottom">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="<?php echo esc_attr( $ts( 'bottom' ) ); ?>" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name"><?php echo esc_html( $ts( 'bottom' ) ); ?></h3>
                        <p class="mont_sizes-measurement-value"><?php echo $pick; ?></p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                        </div>
                        <input type="hidden" name="mont_sizes[half_bottom]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    </div>
                </div>
                <a href="#" class="mont_sizes-change-btn"><?php echo $change; ?></a>
                <a href="#" class="mont_sizes-close-btn"><?php echo $close; ?></a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="shoulder">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="<?php echo esc_attr( $ts( 'shoulder' ) ); ?>" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name"><?php echo esc_html( $ts( 'shoulder' ) ); ?></h3>
                        <p class="mont_sizes-measurement-value"><?php echo $pick; ?></p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                        </div>
                        <input type="hidden" name="mont_sizes[shoulder]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    </div>
                </div>
                <a href="#" class="mont_sizes-change-btn"><?php echo $change; ?></a>
                <a href="#" class="mont_sizes-close-btn"><?php echo $close; ?></a>
            </li>
        </div>
        <a href="#" class="mont_sizes-toggle-more" data-show-text="<?php echo esc_attr( $more ); ?>" data-hide-text="<?php echo esc_attr( $less ); ?>"><?php echo $more; ?></a>
    </ul>
</form>
