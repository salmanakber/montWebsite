<form id="customizationForm">
      <ul class="mont_sizes-measurement-list mont_option-list">
        <li class="mont_sizes-measurement-item" data-mont-size="shirt_length">
            <img src="<?php echo get_stylesheet_directory_uri().'/woocommerce/custom-sizes/images/length.jpg'; ?>" alt="Shirt Length" class="mont_sizes-measurement-icon">
            <div class="mont_sizes-measurement-row">
                <div class="mont_sizes-measurement-details">
                    <h3 class="mont_sizes-measurement-name">Skjortelengde</h3>
                    <p class="mont_sizes-measurement-value">0 cm</p>
                    <span class="mont_sizes-measurement-price">Free of Charge</span>
                </div>
                <div class="mont_sizes-controls">
                    <div class="mont_sizes-control-group">
                        <button  type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                        <span class="mont_sizes-control-value">0 cm</span>
                        <button  type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                    </div>
                    <input type="hidden" name="mont_sizes[shirt_length]" value="0" class="mont_sizes-hidden-input" clicked="false">
                </div>
            </div>
            <a href="#" class="mont_sizes-change-btn">Endre</a>
            <a href="#" class="mont_sizes-close-btn">Lukke</a>
        </li>

        <li class="mont_sizes-measurement-item" data-mont-size="sleeve_length">
            <img src="<?php echo get_stylesheet_directory_uri().'/woocommerce/custom-sizes/images/sleeve-length.jpg'; ?>" alt="Sleeve Length" class="mont_sizes-measurement-icon">
            <div class="mont_sizes-measurement-row">
                <div class="mont_sizes-measurement-details">
                    <h3 class="mont_sizes-measurement-name">Ermelengde</h3>
                    <p class="mont_sizes-measurement-value">Left: 0 cm, Right: 0 cm</p>
                    <span class="mont_sizes-measurement-price">Free of Charge</span>
                </div>
                <div class="mont_sizes-controls">
                    <div class="mont_sizes-control-group">
                        <span class="mont_sizes-control-label">Left:</span>
                        <button  type="button" class="mont_sizes-control-btn mont_sizes-minus" data-side="left">-</button>
                        <span class="mont_sizes-control-value" data-side="left">0 cm</span>
                        <button  type="button" class="mont_sizes-control-btn mont_sizes-plus" data-side="left">+</button>
                    </div>
                    <div class="mont_sizes-control-group">
                        <span class="mont_sizes-control-label">Right:</span>
                        <button  type="button" class="mont_sizes-control-btn mont_sizes-minus" data-side="right">-</button>
                        <span class="mont_sizes-control-value" data-side="right">0 cm</span>
                        <button  type="button" class="mont_sizes-control-btn mont_sizes-plus" data-side="right">+</button>
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
                <img src="<?php echo get_stylesheet_directory_uri().'/woocommerce/custom-sizes/images/half-waist.jpg'; ?>" alt="Waist" class="mont_sizes-measurement-icon">
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Midje</h3>
                        <p class="mont_sizes-measurement-value">0 cm</p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                        </div>
                        <input type="hidden" name="mont_sizes[waist]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    </div>
                </div>
                <a href="#" class="mont_sizes-change-btn">Endre</a>
                <a href="#" class="mont_sizes-close-btn">Lukke</a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="half_chest">
                <img src="<?php echo get_stylesheet_directory_uri().'/woocommerce/custom-sizes/images/half_chest.jpg'; ?>" alt="Chest" class="mont_sizes-measurement-icon">
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Bryststørrelse</h3>
                        <p class="mont_sizes-measurement-value">0 cm</p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                        </div>
                        <input type="hidden" name="mont_sizes[chest]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    </div>
                </div>
                <a href="#" class="mont_sizes-change-btn">Endre</a>
                <a href="#" class="mont_sizes-close-btn">Lukke</a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="half_bottom">
                <img src="<?php echo get_stylesheet_directory_uri().'/woocommerce/custom-sizes/images/half-bottom.jpg'; ?>



                " alt="Half Bottom" class="mont_sizes-measurement-icon">
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Nederst kant</h3>
                        <p class="mont_sizes-measurement-value">0 cm</p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
                        </div>
                        <input type="hidden" name="mont_sizes[half_bottom]" value="0" class="mont_sizes-hidden-input" clicked="false">
                    </div>
                </div>
                <a href="#" class="mont_sizes-change-btn">Endre</a>
                <a href="#" class="mont_sizes-close-btn">Lukke</a>
            </li>

            <li class="mont_sizes-measurement-item" data-mont-size="shoulder">
                <img src="<?php echo get_stylesheet_directory_uri().'/woocommerce/custom-sizes/images/Shoulder.jpg'; ?>" alt="Shoulder" class="mont_sizes-measurement-icon">
                <div class="mont_sizes-measurement-row">
                    <div class="mont_sizes-measurement-details">
                        <h3 class="mont_sizes-measurement-name">Skulder</h3>
                        <p class="mont_sizes-measurement-value">0 cm</p>
                        <span class="mont_sizes-measurement-price">$10</span>
                    </div>
                    <div class="mont_sizes-controls">
                        <div class="mont_sizes-control-group">
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-minus">-</button>
                            <span class="mont_sizes-control-value">0 cm</span>
                            <button  type="button" class="mont_sizes-control-btn mont_sizes-plus">+</button>
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