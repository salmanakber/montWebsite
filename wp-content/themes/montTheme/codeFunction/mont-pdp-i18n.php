<?php
/**
 * Region-aware static UI copy for B2C / B2B single product pages.
 *
 * Languages follow DC_Region_Currency: en | it | nb | vi
 *
 * @package montTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Active PDP language code.
 *
 * @return string en|it|nb|vi
 */
function mont_pdp_lang() {
	$lang = 'en';
	if ( class_exists( 'DC_Product_Manager\\DC_Region_Currency' ) ) {
		$lang = \DC_Product_Manager\DC_Region_Currency::get_current_lang();
	}
	$lang = strtolower( sanitize_key( (string) $lang ) );
	if ( $lang === 'no' ) {
		$lang = 'nb';
	}
	$ok = array( 'en', 'it', 'nb', 'vi' );
	return in_array( $lang, $ok, true ) ? $lang : 'en';
}

/**
 * Full string catalog for the active (or given) language.
 *
 * @param string|null $lang Language code.
 * @return array<string, string>
 */
function mont_pdp_i18n( $lang = null ) {
	if ( null === $lang ) {
		$lang = mont_pdp_lang();
	}
	$lang = strtolower( sanitize_key( (string) $lang ) );
	if ( $lang === 'no' ) {
		$lang = 'nb';
	}

	$all = array(
		'en' => array(
			// Shared
			'back'                    => 'Back',
			'size_guide'              => 'Size Guide',
			'close'                   => 'Close',
			'loading_prev'            => 'Loading previous page...',
			'free_shipping'           => 'Free shipping worldwide',
			'see_more_images'         => 'See more images',
			'read_more'               => 'Read more...',
			'please_complete'         => 'Please complete',
			'pre_order'               => 'Pre-order',
			'available'               => 'Available',
			'recommendations'         => 'Shirts: Our recommendations',

			// B2C sections
			'fit_size_required'       => 'Fit & Size (Required)',
			'size_required'           => 'Size (Required)',
			'choose_collar'           => 'Choose collar (optional)',
			'choose_cuff'             => 'Choose cuff (optional)',
			'tailoring'               => 'Tailoring (optional)',
			'add_to_cart'             => 'Add to bag',
			'alert_cancel_title'      => 'CANCELLATION AND RETURNS FOR MADE-TO-ORDER SHIRTS.',
			'alert_cancel_text'       => 'All made-to-order shirts are 100% customized to the customer’s preferences. Therefore we do NOT accept returns for any reason except manufacturing defects.',
			'alert_delivery_title'    => 'DELIVERY TIME FOR MADE-TO-ORDER SHIRTS.',
			'alert_delivery_text'     => 'All made-to-order shirts require extra work and part changes, so we may add up to seven (7) extra days on top of the normal delivery time.',
			'drawer_title'            => 'Choose fit and size',
			'drawer_fit'              => 'Fit',
			'drawer_size'             => 'Size',
			'drawer_hint_fit_first'   => 'Choose fit first',
			'drawer_no_sizes'         => 'No sizes for this fit',
			'drawer_load_error'       => 'Could not load sizes',
			'drawer_updating'         => 'Updating sizes…',
			'drawer_continue'         => 'Continue',
			'size_locked_title'       => 'Not available for Contemporary fit',
			'measure_pick_fit_size'   => 'Choose fit + size',
			'free_of_charge'          => 'Free of charge',
			'change'                  => 'Change',
			'close_measure'           => 'Close',
			'shirt_length'            => 'Shirt length',
			'sleeve_length'           => 'Sleeve length',
			'waist'                   => 'Waist',
			'chest'                   => 'Chest',
			'bottom'                  => 'Bottom hem',
			'shoulder'                => 'Shoulder',
			'show_more_measures'      => 'Show more measurements',
			'show_less_measures'      => 'Show fewer measurements',
			'left'                    => 'Left',
			'right'                   => 'Right',

			// B2B
			'quotation_title'         => 'Information required for quotation',
			'quotation_moq'           => 'Total MOQ',
			'quotation_moq_suffix'    => 'pcs/color',
			'size_breakdown'          => 'Size breakdown',
			'total_pieces'            => 'Total pieces:',
			'body_fit'                => 'Body fit',
			'slim_fit'                => 'Slim fit',
			'regular_fit'             => 'Regular fit',
			'contemporary_fit'        => 'Contemporary fit',
			'notes_supplier'          => 'Notes for supplier',
			'collar_type'             => 'Collar type',
			'cuff_type'               => 'Cuff type',
			'save_add_colour'         => 'Save & add new colour',
			'done_choosing'           => 'I\'m done choosing',
			'min_order_applies'       => 'Minimum order applies',
			'shirts'                  => 'Shirts',
			'notify_fill_sizes'       => 'Please fill in any of the size fields.',
			'notify_added'            => 'Product added to cart.',
			'notify_save_first'       => 'Save a colour first with “Save & add new colour”, then click I’m done choosing.',
			'moq_message'             => 'Minimum order is %1$s shirts. You’re only adding %2$s to your cart.',
		),
		'nb' => array(
			'back'                    => 'Tilbake',
			'size_guide'              => 'Størrelsesguide',
			'close'                   => 'Lukk',
			'loading_prev'            => 'Laster forrige side...',
			'free_shipping'           => 'Gratis frakt over hele verden',
			'see_more_images'         => 'Se flere bilder',
			'read_more'               => 'Les mer...',
			'please_complete'         => 'Vennligst fyll ut',
			'pre_order'               => 'Forhåndsbestilling',
			'available'               => 'Tilgjengelig',
			'recommendations'         => 'Skjorter: Våre anbefalinger',
			'fit_size_required'       => 'Passform & Størrelse (Obligatorisk)',
			'size_required'           => 'Størrelse (Obligatorisk)',
			'choose_collar'           => 'Velg snipp (valgfritt)',
			'choose_cuff'             => 'Velg mansjetter (valgfritt)',
			'tailoring'               => 'Skreddersydd (valgfritt)',
			'add_to_cart'             => 'Legg i handleposen',
			'alert_cancel_title'      => 'KANSELLERING OG RETUR FOR SKREDDERSYDDE SKJORTER.',
			'alert_cancel_text'       => 'Alle skreddersydde skjorter er 100% individuelt tilpasset etter kundens preferanser. Derfor aksepterer vi IKKE returer av noen grunn bortsett fra produksjonsfeil.',
			'alert_delivery_title'    => 'LEVERINGSTID FOR SKREDDERSYDDE SKJORTER.',
			'alert_delivery_text'     => 'Alle skreddersydde skjorter krever mer arbeid og skifter av nye deler, derfor må vi legge til opptil syv (7) dager ekstra i tillegg til normal leveringstid.',
			'drawer_title'            => 'Velg passform og størrelse',
			'drawer_fit'              => 'Passform',
			'drawer_size'             => 'Størrelse',
			'drawer_hint_fit_first'   => 'Velg passform først',
			'drawer_no_sizes'         => 'Ingen størrelser for denne passformen',
			'drawer_load_error'       => 'Kunne ikke laste størrelser',
			'drawer_updating'         => 'Oppdaterer størrelser…',
			'drawer_continue'         => 'Fortsett',
			'size_locked_title'       => 'Ikke tilgjengelig for Contemporary fit',
			'measure_pick_fit_size'   => 'Velg passform + størrelse',
			'free_of_charge'          => 'Gratis',
			'change'                  => 'Endre',
			'close_measure'           => 'Lukke',
			'shirt_length'            => 'Skjortelengde',
			'sleeve_length'           => 'Ermelengde',
			'waist'                   => 'Midje',
			'chest'                   => 'Bryst',
			'bottom'                  => 'Nederste kant',
			'shoulder'                => 'Skulder',
			'show_more_measures'      => 'Vis flere mål',
			'show_less_measures'      => 'Vis færre mål',
			'left'                    => 'Venstre',
			'right'                   => 'Høyre',
			'quotation_title'         => 'Informasjon nødvendig for tilbud',
			'quotation_moq'           => 'Total MOQ',
			'quotation_moq_suffix'    => 'stk/farge',
			'size_breakdown'          => 'Størrelsesfordeling',
			'total_pieces'            => 'Totalt antall:',
			'body_fit'                => 'Passform',
			'slim_fit'                => 'Slim fit',
			'regular_fit'             => 'Regular fit',
			'contemporary_fit'        => 'Contemporary fit',
			'notes_supplier'          => 'Merknader til leverandør',
			'collar_type'             => 'Sniptype',
			'cuff_type'               => 'Mansjettype',
			'save_add_colour'         => 'Lagre & legg til ny farge',
			'done_choosing'           => 'Jeg er ferdig',
			'min_order_applies'       => 'Minimumsordre gjelder',
			'shirts'                  => 'Skjorter',
			'notify_fill_sizes'       => 'Fyll inn minst ett størrelsesfelt.',
			'notify_added'            => 'Produkt lagt i handlekurven.',
			'notify_save_first'       => 'Lagre en farge først med «Lagre & legg til ny farge», deretter klikk Jeg er ferdig.',
			'moq_message'             => 'Minimumsordre er %1$s skjorter. Du legger bare til %2$s i handlekurven.',
		),
		'it' => array(
			'back'                    => 'Indietro',
			'size_guide'              => 'Guida alle taglie',
			'close'                   => 'Chiudi',
			'loading_prev'            => 'Caricamento pagina precedente...',
			'free_shipping'           => 'Spedizione gratuita in tutto il mondo',
			'see_more_images'         => 'Vedi altre immagini',
			'read_more'               => 'Leggi di più...',
			'please_complete'         => 'Compila per favore',
			'pre_order'               => 'Preordine',
			'available'               => 'Disponibile',
			'recommendations'         => 'Camicie: le nostre raccomandazioni',
			'fit_size_required'       => 'Vestibilità e taglia (obbligatorio)',
			'size_required'           => 'Taglia (obbligatorio)',
			'choose_collar'           => 'Scegli collo (facoltativo)',
			'choose_cuff'             => 'Scegli polsini (facoltativo)',
			'tailoring'               => 'Su misura (facoltativo)',
			'add_to_cart'             => 'Aggiungi alla borsa',
			'alert_cancel_title'      => 'CANCELLAZIONE E RESO PER CAMICIE SU MISURA.',
			'alert_cancel_text'       => 'Tutte le camicie su misura sono personalizzate al 100% secondo le preferenze del cliente. Pertanto NON accettiamo resi per alcun motivo, tranne difetti di fabbricazione.',
			'alert_delivery_title'    => 'TEMPI DI CONSEGNA PER CAMICIE SU MISURA.',
			'alert_delivery_text'     => 'Le camicie su misura richiedono lavoro aggiuntivo e cambio pezzi, quindi potremmo aggiungere fino a sette (7) giorni rispetto ai tempi di consegna normali.',
			'drawer_title'            => 'Scegli vestibilità e taglia',
			'drawer_fit'              => 'Vestibilità',
			'drawer_size'             => 'Taglia',
			'drawer_hint_fit_first'   => 'Scegli prima la vestibilità',
			'drawer_no_sizes'         => 'Nessuna taglia per questa vestibilità',
			'drawer_load_error'       => 'Impossibile caricare le taglie',
			'drawer_updating'         => 'Aggiornamento taglie…',
			'drawer_continue'         => 'Continua',
			'size_locked_title'       => 'Non disponibile per Contemporary fit',
			'measure_pick_fit_size'   => 'Scegli vestibilità + taglia',
			'free_of_charge'          => 'Gratuito',
			'change'                  => 'Modifica',
			'close_measure'           => 'Chiudi',
			'shirt_length'            => 'Lunghezza camicia',
			'sleeve_length'           => 'Lunghezza manica',
			'waist'                   => 'Vita',
			'chest'                   => 'Petto',
			'bottom'                  => 'Orlo inferiore',
			'shoulder'                => 'Spalla',
			'show_more_measures'      => 'Mostra più misure',
			'show_less_measures'      => 'Mostra meno misure',
			'left'                    => 'Sinistra',
			'right'                   => 'Destra',
			'quotation_title'         => 'Informazioni richieste per il preventivo',
			'quotation_moq'           => 'MOQ totale',
			'quotation_moq_suffix'    => 'pz/colore',
			'size_breakdown'          => 'Ripartizione taglie',
			'total_pieces'            => 'Pezzi totali:',
			'body_fit'                => 'Vestibilità',
			'slim_fit'                => 'Slim fit',
			'regular_fit'             => 'Regular fit',
			'contemporary_fit'        => 'Contemporary fit',
			'notes_supplier'          => 'Note per il fornitore',
			'collar_type'             => 'Tipo di collo',
			'cuff_type'               => 'Tipo di polsino',
			'save_add_colour'         => 'Salva e aggiungi nuovo colore',
			'done_choosing'           => 'Ho finito di scegliere',
			'min_order_applies'       => 'Si applica l’ordine minimo',
			'shirts'                  => 'Camicie',
			'notify_fill_sizes'       => 'Compila almeno un campo taglia.',
			'notify_added'            => 'Prodotto aggiunto al carrello.',
			'notify_save_first'       => 'Salva prima un colore con «Salva e aggiungi nuovo colore», poi clicca Ho finito di scegliere.',
			'moq_message'             => 'L’ordine minimo è di %1$s camicie. Stai aggiungendo solo %2$s al carrello.',
		),
		'vi' => array(
			'back'                    => 'Quay lại',
			'size_guide'              => 'Bảng kích cỡ',
			'close'                   => 'Đóng',
			'loading_prev'            => 'Đang tải trang trước...',
			'free_shipping'           => 'Miễn phí vận chuyển toàn cầu',
			'see_more_images'         => 'Xem thêm ảnh',
			'read_more'               => 'Xem thêm...',
			'please_complete'         => 'Vui lòng hoàn tất',
			'pre_order'               => 'Đặt trước',
			'available'               => 'Còn hàng',
			'recommendations'         => 'Áo sơ mi: Gợi ý của chúng tôi',
			'fit_size_required'       => 'Form dáng & kích cỡ (Bắt buộc)',
			'size_required'           => 'Kích cỡ (Bắt buộc)',
			'choose_collar'           => 'Chọn cổ áo (tuỳ chọn)',
			'choose_cuff'             => 'Chọn cổ tay (tuỳ chọn)',
			'tailoring'               => 'May đo (tuỳ chọn)',
			'add_to_cart'             => 'Thêm vào túi',
			'alert_cancel_title'      => 'HUỶ ĐƠN VÀ ĐỔI TRẢ CHO ÁO MAY ĐO.',
			'alert_cancel_text'       => 'Tất cả áo may đo được tuỳ chỉnh 100% theo yêu cầu khách hàng. Vì vậy chúng tôi KHÔNG chấp nhận đổi trả vì bất kỳ lý do nào ngoại trừ lỗi sản xuất.',
			'alert_delivery_title'    => 'THỜI GIAN GIAO HÀNG CHO ÁO MAY ĐO.',
			'alert_delivery_text'     => 'Áo may đo cần thêm công đoạn và thay đổi chi tiết, vì vậy có thể cộng thêm tới bảy (7) ngày so với thời gian giao hàng thông thường.',
			'drawer_title'            => 'Chọn form dáng và kích cỡ',
			'drawer_fit'              => 'Form dáng',
			'drawer_size'             => 'Kích cỡ',
			'drawer_hint_fit_first'   => 'Hãy chọn form dáng trước',
			'drawer_no_sizes'         => 'Không có kích cỡ cho form này',
			'drawer_load_error'       => 'Không tải được kích cỡ',
			'drawer_updating'         => 'Đang cập nhật kích cỡ…',
			'drawer_continue'         => 'Tiếp tục',
			'size_locked_title'       => 'Không khả dụng với Contemporary fit',
			'measure_pick_fit_size'   => 'Chọn form + kích cỡ',
			'free_of_charge'          => 'Miễn phí',
			'change'                  => 'Đổi',
			'close_measure'           => 'Đóng',
			'shirt_length'            => 'Chiều dài áo',
			'sleeve_length'           => 'Chiều dài tay',
			'waist'                   => 'Eo',
			'chest'                   => 'Ngực',
			'bottom'                  => 'Gấu áo',
			'shoulder'                => 'Vai',
			'show_more_measures'      => 'Hiện thêm số đo',
			'show_less_measures'      => 'Ẩn bớt số đo',
			'left'                    => 'Trái',
			'right'                   => 'Phải',
			'quotation_title'         => 'Thông tin cần thiết để báo giá',
			'quotation_moq'           => 'Tổng MOQ',
			'quotation_moq_suffix'    => 'sp/màu',
			'size_breakdown'          => 'Phân bổ kích cỡ',
			'total_pieces'            => 'Tổng số lượng:',
			'body_fit'                => 'Form dáng',
			'slim_fit'                => 'Slim fit',
			'regular_fit'             => 'Regular fit',
			'contemporary_fit'        => 'Contemporary fit',
			'notes_supplier'          => 'Ghi chú cho nhà cung cấp',
			'collar_type'             => 'Kiểu cổ áo',
			'cuff_type'               => 'Kiểu cổ tay',
			'save_add_colour'         => 'Lưu & thêm màu mới',
			'done_choosing'           => 'Tôi đã chọn xong',
			'min_order_applies'       => 'Áp dụng đơn hàng tối thiểu',
			'shirts'                  => 'Áo',
			'notify_fill_sizes'       => 'Vui lòng điền ít nhất một ô kích cỡ.',
			'notify_added'            => 'Đã thêm sản phẩm vào giỏ.',
			'notify_save_first'       => 'Hãy lưu một màu bằng «Lưu & thêm màu mới» trước, rồi nhấn Tôi đã chọn xong.',
			'moq_message'             => 'Đơn tối thiểu là %1$s áo. Bạn chỉ đang thêm %2$s vào giỏ.',
		),
	);

	$base = $all['en'];
	$pick = isset( $all[ $lang ] ) ? $all[ $lang ] : $all['en'];
	return array_merge( $base, $pick );
}

/**
 * Translate one PDP UI key.
 *
 * @param string      $key  String key.
 * @param string|null $lang Optional language.
 * @return string
 */
function mont_pdp_t( $key, $lang = null ) {
	$strings = mont_pdp_i18n( $lang );
	$key     = (string) $key;
	return isset( $strings[ $key ] ) ? $strings[ $key ] : $key;
}

/**
 * Map of keys useful for JS localization.
 *
 * @param string|null $lang Optional language.
 * @return array<string, string>
 */
function mont_pdp_js_i18n( $lang = null ) {
	$s = mont_pdp_i18n( $lang );
	return array(
		'lang'              => mont_pdp_lang(),
		'shirts'            => $s['shirts'],
		'sizeGuide'         => $s['size_guide'],
		'close'             => $s['close'],
		'left'              => $s['left'],
		'right'             => $s['right'],
		'drawerHintFitFirst'=> $s['drawer_hint_fit_first'],
		'drawerNoSizes'     => $s['drawer_no_sizes'],
		'drawerLoadError'   => $s['drawer_load_error'],
		'drawerUpdating'    => $s['drawer_updating'],
		'drawerContinue'    => $s['drawer_continue'],
		'sizeLockedTitle'   => $s['size_locked_title'],
		'measurePickFitSize'=> $s['measure_pick_fit_size'],
		'showMoreMeasures'  => $s['show_more_measures'],
		'showLessMeasures'  => $s['show_less_measures'],
		'notifyFillSizes'   => $s['notify_fill_sizes'],
		'notifyAdded'       => $s['notify_added'],
		'notifySaveFirst'   => $s['notify_save_first'],
		'saveAddColour'     => $s['save_add_colour'],
		'doneChoosing'      => $s['done_choosing'],
		'minOrderApplies'   => $s['min_order_applies'],
		'available'         => $s['available'],
		'preOrder'          => $s['pre_order'],
		'addToCart'         => $s['add_to_cart'],
		'readMore'          => $s['read_more'],
	);
}
