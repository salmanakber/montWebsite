<div class="mont_single_product_container b2b-pdp">
	<div class="mont_top_layout b2b-pdp__layout">
		<div class="mont_layout_sixty b2b-pdp__gallery">
			<div class="mont_back_button" id="mont_backButton">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="m15 18-6-6 6-6"/>
				</svg>
				<span>{{t_back}}</span>
				<div class="mont_popover" id="mont_prevPagePopover">
					<div class="mont_popover_arrow"></div>
					<div class="mont_popover_content">
						<div class="mont_popover_text" id="mont_prevPageTitle">{{t_loading_prev}}</div>
					</div>
				</div>
			</div>
			{{images}}
		</div>

		<div class="mont_layout_fourty b2b-pdp__panel" id="details-column">
			<div class="b2b-pdp__quotation">
				<h3 class="b2b-pdp__quotation-title">{{t_quotation_title}}</h3>
				<p class="b2b-pdp__quotation-moq">{{t_quotation_moq}} <span class="b2b-pdp__quotation-moq-value">{{moq}}</span> {{t_quotation_moq_suffix}}</p>
			</div>

			<div class="b2b-pdp__summary">
				{{details}}
			</div>

			{{return_form_block}}

			<div class="b2b-pdp__grid row">
				<div class="col-md-6 column-top column-left-top">
					<div class="size-type">
						<h4 class="b2b-pdp__section-title">{{t_size_breakdown}}</h4>
						<div class="b2b-size-list">
							<ul>
								<li><label class="b2b-size-cell" data-size-num="37"><span class="size-name">S/37</span><input type="number" class="b2b-size-input" data-value="37(S)" data-size-num="37" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="S/37"></label></li>
								<li><label class="b2b-size-cell" data-size-num="38"><span class="size-name">S/38</span><input type="number" class="b2b-size-input" data-value="38(S)" data-size-num="38" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="S/38"></label></li>
								<li><label class="b2b-size-cell" data-size-num="39"><span class="size-name">M/39</span><input type="number" class="b2b-size-input" data-value="39(M)" data-size-num="39" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="M/39"></label></li>
								<li><label class="b2b-size-cell" data-size-num="40"><span class="size-name">M/40</span><input type="number" class="b2b-size-input" data-value="40(M)" data-size-num="40" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="M/40"></label></li>
								<li><label class="b2b-size-cell" data-size-num="41"><span class="size-name">L/41</span><input type="number" class="b2b-size-input" data-value="41(L)" data-size-num="41" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="L/41"></label></li>
								<li><label class="b2b-size-cell" data-size-num="42"><span class="size-name">L/42</span><input type="number" class="b2b-size-input" data-value="42(L)" data-size-num="42" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="L/42"></label></li>
								<li><label class="b2b-size-cell" data-size-num="43"><span class="size-name">XL/43</span><input type="number" class="b2b-size-input" data-value="43(XL)" data-size-num="43" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="XL/43"></label></li>
								<li><label class="b2b-size-cell" data-size-num="44"><span class="size-name">XL/44</span><input type="number" class="b2b-size-input" data-value="44(XL)" data-size-num="44" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="XL/44"></label></li>
								<li><label class="b2b-size-cell" data-size-num="45"><span class="size-name">2XL/45</span><input type="number" class="b2b-size-input" data-value="45(2XL)" data-size-num="45" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="2XL/45"></label></li>
								<li><label class="b2b-size-cell" data-size-num="46"><span class="size-name">2XL/46</span><input type="number" class="b2b-size-input" data-value="46(2XL)" data-size-num="46" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="2XL/46"></label></li>
								<li><label class="b2b-size-cell" data-size-num="47"><span class="size-name">3XL/47</span><input type="number" class="b2b-size-input" data-value="47(3XL)" data-size-num="47" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="3XL/47"></label></li>
								<li><label class="b2b-size-cell" data-size-num="48"><span class="size-name">3XL/48</span><input type="number" class="b2b-size-input" data-value="48(3XL)" data-size-num="48" name="breakDown_quantity[]" min="0" max="10000" placeholder="00" inputmode="numeric" aria-label="3XL/48"></label></li>
							</ul>
						</div>
						<div class="b2b-pdp__total-pieces">
							<span class="b2b-pdp__total-label">{{t_total_pieces}}</span>
							<strong class="b2b-pdp__total-value" id="b2b-total-pieces">0</strong>
						</div>
					</div>
				</div>

				<div class="col-md-6 column-top column-right-top">
					<h4 class="b2b-pdp__section-title">{{t_body_fit}}</h4>
					<ul class="b2b-ul-list">
						<li class="b2b-fit-row">
							<input type="checkbox" name="slim_fit" class="b2b-checked-form" id="b2b-fit-slim">
							<span class="b2b-left-text-inner">{{t_slim_fit}}</span>
							<a href="javascript:void(0)" class="b2b-size-guide">{{t_size_guide}}</a>
						</li>
						<li class="b2b-fit-row">
							<input type="checkbox" name="regular_fit" class="b2b-checked-form" id="b2b-fit-regular">
							<span class="b2b-left-text-inner">{{t_regular_fit}}</span>
							<a href="javascript:void(0)" class="b2b-size-guide">{{t_size_guide}}</a>
						</li>
						<li class="b2b-fit-row">
							<input type="checkbox" name="contemporary" class="b2b-checked-form" id="b2b-fit-contemporary">
							<span class="b2b-left-text-inner">{{t_contemporary_fit}}</span>
							<a href="javascript:void(0)" class="b2b-size-guide">{{t_size_guide}}</a>
						</li>
					</ul>
				</div>
			</div>

			<input type="hidden" class="price-b2b" name="totalprice" value="">

			<div class="comment-box-b2b">
				<label for="s_comment" class="b2b-pdp__notes-label">{{t_notes_supplier}}</label>
				<textarea id="s_comment" name="comment" placeholder=""></textarea>
			</div>

			<div class="b2b-measure-boxes collar-type-b2b b2b-option-section">
				<h4 class="b2b-h4">{{t_collar_type}}</h4>
				<div class="collardiv b2b-option-row">{{collar}}</div>
			</div>

			<div class="measure-boxes-b2b cuff-type-b2b b2b-option-section">
				<h4 class="b2b-h4">{{t_cuff_type}}</h4>
				<div class="collardiv b2b-option-row">{{cuff}}</div>
			</div>

			<div class="b2b-add-to-cart-button">
				<button type="submit" class="send-it-to-cart">{{t_save_add_colour}}</button>
				<a href="javascript:void(0)" class="submit-it-directly {{done}}">{{t_done_choosing}}</a>
			</div>
		</div>
	</div>
</div>

<div class="mont-mobile-sticky-cta mont-b2b-sticky-cta" id="mont-b2b-sticky-cta" hidden>
	<div class="mont-mobile-sticky-cta__meta">
		<span class="mont-mobile-sticky-cta__price mont-b2b-sticky-price">0 {{t_shirts}}</span>
		<span class="mont-mobile-sticky-cta__stock">{{t_min_order_applies}}</span>
	</div>
	<a href="javascript:void(0)" class="mont-mobile-sticky-cta__action is-cart mont-b2b-sticky-done">
		{{t_done_choosing}}
	</a>
</div>

<script type="text/javascript">
jQuery(document).ready(function($){
	$('#mont_backButton').on('click', function() {
		if (document.referrer) {
			window.location.href = document.referrer;
		} else {
			window.history.back();
		}
	});

	var shirtsWord = (window.b2bI18n && b2bI18n.shirts) ? b2bI18n.shirts : 'Shirts';
	var $sticky = $('#mont-b2b-sticky-cta');
	if ($sticky.length && window.matchMedia('(max-width: 1024px)').matches) {
		$sticky.prop('hidden', false);
		$('body').addClass('has-mont-mobile-sticky has-mont-b2b-sticky');
		function syncB2bStickyPrice() {
			var txt = ($('.price-b2b').val() || '').trim();
			$('.mont-b2b-sticky-price').text(txt || ('0 ' + shirtsWord));
		}
		syncB2bStickyPrice();
		$(document).on('input change', '.b2b-size-input, .price-b2b', syncB2bStickyPrice);
		$(document).on('click', '.mont-b2b-sticky-done', function(e) {
			e.preventDefault();
			var $done = $('.submit-it-directly').first();
			if ($done.length) $done.trigger('click');
		});
	}
});
</script>
