<?php
$mont_size_placeholder = 'data:image/svg+xml,' . rawurlencode(
	'<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><rect width="64" height="64" fill="#ececec"/><path d="M18 42l9-12 7 8 5-6 7 10H18z" fill="#cfcfcf"/><circle cx="24" cy="24" r="5" fill="#cfcfcf"/></svg>'
);
?>
<form id="customizationForm">
      <ul class="mont_sizes-measurement-list mont_option-list">
        <li class="mont_sizes-measurement-item" data-mont-size="shirt_length">
            <span class="mont_sizes-measurement-icon-wrap">
            <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="Shirt Length" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
            <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
            </span>
            <div class="mont_sizes-measurement-row">
                <div class="mont_sizes-measurement-details">
                    <h3 class="mont_sizes-measurement-name">Skjortelengde</h3>
                    <p class="mont_sizes-measurement-value">Velg passform + størrelse</p>
                    <span class="mont_sizes-measurement-price">Free of Charge</span>
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
            <a href="#" class="mont_sizes-change-btn">Endre</a>
            <a href="#" class="mont_sizes-close-btn">Lukke</a>
        </li>

        <li class="mont_sizes-measurement-item" data-mont-size="sleeve_length">
            <span class="mont_sizes-measurement-icon-wrap">
            <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="Sleeve Length" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
            <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
            </span>
            <div class="mont_sizes-measurement-row">
                <div class="mont_sizes-measurement-details">
                    <h3 class="mont_sizes-measurement-name">Ermelengde</h3>
                    <p class="mont_sizes-measurement-value">Velg passform + størrelse</p>
                    <span class="mont_sizes-measurement-price">Free of Charge</span>
                </div>
                <div class="mont_sizes-controls">
                    <div class="mont_sizes-control-group">
                        <span class="mont_sizes-control-label">Left:</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-minus" data-side="left">-</button>
                        <span class="mont_sizes-control-value" data-side="left">0 cm</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-plus" data-side="left">+</button>
                    </div>
                    <div class="mont_sizes-control-group">
                        <span class="mont_sizes-control-label">Right:</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-minus" data-side="right">-</button>
                        <span class="mont_sizes-control-value" data-side="right">0 cm</span>
                        <button type="button" class="mont_sizes-control-btn mont_sizes-plus" data-side="right">+</button>
                    </div>
                    <input type="hidden" name="mont_sizes[sleeve_length_left]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    <input type="hidden" name="mont_sizes[sleeve_length_right]" value="0" class="mont_sizes-hidden-input" clicked="false">
                </div>
            </div>
            <a href="#" class="mont_sizes-change-btn">Endre</a>
            <a href="#" class="mont_sizes-close-btn">Lukke</a>
        </li>

        <div class="mont_sizes-additional-measurements mont_sizes-hidden">
            <li class="mont_sizes-measurement-item" data-mont-size="half_waist">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="Waist" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Midje</h3>
                        <p class="mont_sizes-measurement-value">Velg passform + størrelse</p>
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
                <a href="#" class="mont_sizes-change-btn">Endre</a>
                <a href="#" class="mont_sizes-close-btn">Lukke</a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="half_chest">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="Chest" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Bryststørrelse</h3>
                        <p class="mont_sizes-measurement-value">Velg passform + størrelse</p>
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
                <a href="#" class="mont_sizes-change-btn">Endre</a>
                <a href="#" class="mont_sizes-close-btn">Lukke</a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="half_bottom">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="Half Bottom" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Nederst kant</h3>
                        <p class="mont_sizes-measurement-value">Velg passform + størrelse</p>
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
                <a href="#" class="mont_sizes-change-btn">Endre</a>
                <a href="#" class="mont_sizes-close-btn">Lukke</a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="shoulder">
                <span class="mont_sizes-measurement-icon-wrap">
                <img src="<?php echo esc_attr( $mont_size_placeholder ); ?>" alt="Shoulder" class="mont_sizes-measurement-icon is-placeholder" data-placeholder="<?php echo esc_attr( $mont_size_placeholder ); ?>" loading="eager" decoding="async" width="64" height="64">
                <span class="mont_sizes-img-spinner" aria-hidden="true"></span>
                </span>
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Skulder</h3>
                        <p class="mont_sizes-measurement-value">Velg passform + størrelse</p>
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
                <a href="#" class="mont_sizes-change-btn">Endre</a>
                <a href="#" class="mont_sizes-close-btn">Lukke</a>
            </li>
        </div>
        <a href="#" class="mont_sizes-toggle-more" data-show-text="Vis flere alternativer" data-hide-text="Skjul">Vis flere alternativer</a>
    </ul>
</form>
