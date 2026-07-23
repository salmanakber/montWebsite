/**
 * Mont AI Shopping Assistant — front-end widget
 */
(function () {
	'use strict';

	if (!window.MontAIChat) return;

	var cfg = window.MontAIChat;
	var STORAGE_LANG = 'mont_ai_lang';
	var STORAGE_HISTORY = 'mont_ai_history';

	var root = document.getElementById('mont-ai-root');
	var bubble = document.getElementById('mont-ai-bubble');
	var panel = document.getElementById('mont-ai-panel');
	var closeBtn = document.getElementById('mont-ai-close');
	var messagesEl = document.getElementById('mont-ai-messages');
	var input = document.getElementById('mont-ai-input');
	var sendBtn = document.getElementById('mont-ai-send');
	var langSelect = document.getElementById('mont-ai-lang');

	if (!root || !bubble || !panel) return;

	var history = loadHistory();
	var busy = false;
	var language = localStorage.getItem(STORAGE_LANG) || cfg.defaultLang || 'en';

	function loadHistory() {
		try {
			var raw = sessionStorage.getItem(STORAGE_HISTORY);
			return raw ? JSON.parse(raw) : [];
		} catch (e) {
			return [];
		}
	}

	function saveHistory() {
		try {
			sessionStorage.setItem(STORAGE_HISTORY, JSON.stringify(history.slice(-40)));
		} catch (e) {}
	}

	function formatTime(date) {
		try {
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
		} catch (e) {
			return '';
		}
	}

	function scrollBottom() {
		messagesEl.scrollTop = messagesEl.scrollHeight;
	}

	function appendMessage(role, text, meta) {
		meta = meta || {};
		var wrap = document.createElement('div');
		wrap.className = 'mont-ai-msg mont-ai-msg--' + role;

		var bubbleEl = document.createElement('div');
		bubbleEl.className = 'mont-ai-msg__bubble';
		bubbleEl.textContent = text;
		wrap.appendChild(bubbleEl);

		if (meta.cards && meta.cards.length) {
			var cards = document.createElement('div');
			cards.className = 'mont-ai-cards';
			meta.cards.forEach(function (card) {
				var a = document.createElement('a');
				a.className = 'mont-ai-card';
				a.href = card.permalink || '#';
				a.target = '_blank';
				a.rel = 'noopener';

				if (card.image) {
					var img = document.createElement('img');
					img.className = 'mont-ai-card__img';
					img.src = card.image;
					img.alt = card.name || '';
					a.appendChild(img);
				} else {
					var ph = document.createElement('div');
					ph.className = 'mont-ai-card__img';
					a.appendChild(ph);
				}

				var info = document.createElement('div');
				var name = document.createElement('p');
				name.className = 'mont-ai-card__name';
				name.textContent = card.name || '';
				var price = document.createElement('p');
				price.className = 'mont-ai-card__price';
				price.textContent = card.price || '';
				info.appendChild(name);
				info.appendChild(price);
				a.appendChild(info);

				var cta = document.createElement('span');
				cta.className = 'mont-ai-card__cta';
				cta.textContent = (cfg.i18n && cfg.i18n.viewProduct) || 'View';
				a.appendChild(cta);

				cards.appendChild(a);
			});
			wrap.appendChild(cards);
		}

		if (meta.cartUpdated) {
			var notice = document.createElement('div');
			notice.className = 'mont-ai-notice';
			notice.textContent = (cfg.i18n && cfg.i18n.addedCart) || 'Cart updated';
			wrap.appendChild(notice);
			refreshMiniCart();
		}

		var time = document.createElement('div');
		time.className = 'mont-ai-msg__time';
		time.textContent = formatTime(new Date());
		wrap.appendChild(time);

		messagesEl.appendChild(wrap);
		scrollBottom();
		return wrap;
	}

	function showTyping() {
		var el = document.createElement('div');
		el.className = 'mont-ai-msg mont-ai-msg--assistant';
		el.id = 'mont-ai-typing';
		el.innerHTML = '<div class="mont-ai-typing" aria-label="Thinking"><span></span><span></span><span></span></div>';
		messagesEl.appendChild(el);
		scrollBottom();
	}

	function hideTyping() {
		var el = document.getElementById('mont-ai-typing');
		if (el) el.remove();
	}

	function openPanel() {
		panel.hidden = false;
		bubble.classList.add('is-open');
		bubble.setAttribute('aria-expanded', 'true');
		input.focus();
	}

	function closePanel() {
		panel.hidden = true;
		bubble.classList.remove('is-open');
		bubble.setAttribute('aria-expanded', 'false');
	}

	function refreshMiniCart() {
		// WooCommerce fragments refresh if available
		if (window.jQuery) {
			jQuery(document.body).trigger('wc_fragment_refresh');
			jQuery(document.body).trigger('added_to_cart');
		}
		// Theme cart count endpoint (montTheme)
		if (window.jQuery && window.ajaxurl && ajaxurl.url) {
			jQuery.post(ajaxurl.url, { action: 'update_cart_count' });
		}
	}

	function setBusy(state) {
		busy = state;
		sendBtn.disabled = state;
		input.disabled = state;
	}

	function buildApiHistory() {
		return history.map(function (h) {
			return { role: h.role, content: h.content };
		});
	}

	function send() {
		var text = (input.value || '').trim();
		if (!text || busy) return;

		input.value = '';
		autoGrow();
		appendMessage('user', text);
		history.push({ role: 'user', content: text });
		saveHistory();

		setBusy(true);
		showTyping();

		fetch(cfg.restUrl + '/chat', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify({
				message: text,
				language: language,
				history: buildApiHistory().slice(0, -1),
				context: {
					product_id: cfg.productId || 0
				}
			})
		})
			.then(function (res) {
				return res.json().then(function (data) {
					if (!res.ok) {
						throw new Error((data && data.message) || (cfg.i18n && cfg.i18n.error) || 'Error');
					}
					return data;
				});
			})
			.then(function (data) {
				hideTyping();
				var msg = (data && data.message) ? data.message : '';
				appendMessage('assistant', msg, {
					cards: data.cards || [],
					cartUpdated: !!data.cart_updated
				});
				history.push({ role: 'assistant', content: msg });
				saveHistory();
			})
			.catch(function (err) {
				hideTyping();
				appendMessage('assistant', err.message || (cfg.i18n && cfg.i18n.error) || 'Error');
			})
			.finally(function () {
				setBusy(false);
				input.focus();
			});
	}

	function autoGrow() {
		input.style.height = 'auto';
		input.style.height = Math.min(input.scrollHeight, 110) + 'px';
	}

	function seedWelcome() {
		if (messagesEl.childElementCount) return;
		var welcome = cfg.welcome || 'Hi! How can I help you today?';
		appendMessage('assistant', welcome);
	}

	function initLang() {
		if (langSelect) {
			langSelect.value = language;
			langSelect.addEventListener('change', function () {
				language = langSelect.value;
				localStorage.setItem(STORAGE_LANG, language);
				appendMessage(
					'assistant',
					language === 'it' ? 'Perfetto — continuo in italiano.' :
					language === 'nb' ? 'Flott — jeg fortsetter på norsk.' :
					language === 'vi' ? 'Tuyệt — tôi sẽ trả lời bằng tiếng Việt.' :
					'Got it — I\'ll continue in English.'
				);
			});
		}
	}

	function restoreHistory() {
		if (!history.length) {
			seedWelcome();
			return;
		}
		history.forEach(function (h) {
			if (h.role === 'user' || h.role === 'assistant') {
				appendMessage(h.role, h.content);
			}
		});
	}

	bubble.addEventListener('click', openPanel);
	closeBtn.addEventListener('click', closePanel);
	sendBtn.addEventListener('click', send);
	input.addEventListener('input', autoGrow);
	input.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			send();
		}
	});

	initLang();
	restoreHistory();
})();
