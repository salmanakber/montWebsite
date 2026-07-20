jQuery(function ($) {
  $(document).on('click', '.fiken-dismissible .notice-dismiss', function () {
    const $n  = $(this).closest('.fiken-dismissible');
    const key = $n.data('key');
    const nonce = $n.data('dismiss-nonce');

    if (!key) {
      console.warn('[Fiken] Missing data-key on notice');
      return;
    }

    if ($n.data('dismissing')) return;
    $n.data('dismissing', true);

    const ajaxUrl = (window.fikenData && fikenData.ajaxUrl) ? fikenData.ajaxUrl : (window.ajaxurl || '');
    const primary = {
      action: (window.fikenData && fikenData.dismissAction) || 'pekifiken_dismiss_notice',
      key: key,
      nonce: nonce || ''
    };

    if (window.fikenData && fikenData.debug) {
      console.log('[Fiken] Dismissing', key, '→', primary.action);
    }

    $.post(ajaxUrl, primary)
      .done(function (resp) {
        if (window.fikenData && fikenData.debug) console.log('[Fiken] OK', resp);
      })
      .fail(function (xhr) {
        if (window.fikenData && fikenData.debug) console.warn('[Fiken] Primary failed', xhr && xhr.status, xhr.responseText);
        console.error('[Fiken] Dismiss failed', xhr && xhr.responseText);
      })
      .always(function(){
        $n.data('dismissing', false);
      });
  });

  // Log when script loads
  if (window.fikenData && fikenData.debug) console.log('[Fiken] notice-dismiss.js loaded');
});

