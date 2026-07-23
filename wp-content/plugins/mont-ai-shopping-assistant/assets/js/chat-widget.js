/**
 * Mont AI Shopping Assistant — front-end widget
 */
(function () {
	'use strict';

	function boot() {
		if (!window.MontAIChat) return;

		var cfg = window.MontAIChat;
		var STORAGE_LANG = 'mont_ai_lang';
		var STORAGE_HISTORY = 'mont_ai_history';
		var STORAGE_ACTIVE = 'mont_ai_session_active';

		var root = document.getElementById('mont-ai-root');
		var bubble = document.getElementById('mont-ai-bubble');
		var panel = document.getElementById('mont-ai-panel');
		var closeBtn = document.getElementById('mont-ai-close');
		var newBtn = document.getElementById('mont-ai-new');
		var messagesEl = document.getElementById('mont-ai-messages');
		var input = document.getElementById('mont-ai-input');
		var sendBtn = document.getElementById('mont-ai-send');
		var langSelect = document.getElementById('mont-ai-lang');

		if (!root || !bubble || !panel || !messagesEl) return;
		if (root.getAttribute('data-mont-ai-ready') === '1') return;
		root.setAttribute('data-mont-ai-ready', '1');

		// History lives only for the active chat session (panel open on this page).
		// Closing the chat — or loading a new page — starts fresh.
		var history = [];
		var busy = false;
		var lastSendAt = 0;
		var language = localStorage.getItem(STORAGE_LANG) || cfg.defaultLang || 'en';

		function clearSessionHistory() {
			history = [];
			try {
				sessionStorage.removeItem(STORAGE_HISTORY);
				sessionStorage.removeItem(STORAGE_ACTIVE);
			} catch (e) {}
		}

		// Always start clean on a new page load (don't force previous conversations).
		clearSessionHistory();

		function saveHistory() {
			try {
				sessionStorage.setItem(STORAGE_ACTIVE, '1');
				sessionStorage.setItem(STORAGE_HISTORY, JSON.stringify(history.slice(-40)));
			} catch (e) {}
		}

		function loadActiveSessionHistory() {
			try {
				if (sessionStorage.getItem(STORAGE_ACTIVE) !== '1') {
					return [];
				}
				var raw = sessionStorage.getItem(STORAGE_HISTORY);
				return raw ? JSON.parse(raw) : [];
			} catch (e) {
				return [];
			}
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

		function disableActiveChoices() {
			messagesEl.querySelectorAll('.mont-ai-choice-btn:not([disabled]), .mont-ai-card--pick').forEach(function (el) {
				el.setAttribute('disabled', 'disabled');
				el.classList.add('is-disabled');
				el.style.pointerEvents = 'none';
			});
		}

		function renderChoices(wrap, choices) {
			if (!choices || !choices.choices || !choices.choices.length) return;

			var box = document.createElement('div');
			box.className = 'mont-ai-choices';
			box.setAttribute('data-field', choices.field || '');

			if (choices.title) {
				var title = document.createElement('div');
				title.className = 'mont-ai-choices__title';
				title.textContent = choices.title;
				box.appendChild(title);
			}

			var type = choices.type || 'buttons';
			var grid = document.createElement('div');
			grid.className = 'mont-ai-choices__grid mont-ai-choices__grid--' + type;

			choices.choices.forEach(function (item) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'mont-ai-choice-btn' + (item.image ? ' mont-ai-choice-btn--image' : '');
				btn.setAttribute('data-value', item.value || item.label || '');

				if (item.image) {
					var img = document.createElement('img');
					img.src = item.image;
					img.alt = item.label || '';
					img.className = 'mont-ai-choice-btn__img';
					btn.appendChild(img);
				}

				var label = document.createElement('span');
				label.className = 'mont-ai-choice-btn__label';
				label.textContent = item.label || item.value || '';
				btn.appendChild(label);

				if (item.sub) {
					var sub = document.createElement('span');
					sub.className = 'mont-ai-choice-btn__sub';
					sub.textContent = item.sub;
					btn.appendChild(sub);
				}

				btn.addEventListener('click', function () {
					if (busy || btn.disabled) return;
					var val = btn.getAttribute('data-value') || '';
					disableActiveChoices();
					btn.classList.add('is-selected');
					sendText(val);
				});

				grid.appendChild(btn);
			});

			box.appendChild(grid);
			wrap.appendChild(box);
		}

		function appendMessage(role, text, meta) {
			meta = meta || {};
			var wrap = document.createElement('div');
			wrap.className = 'mont-ai-msg mont-ai-msg--' + role;

			if (text) {
				var bubbleEl = document.createElement('div');
				bubbleEl.className = 'mont-ai-msg__bubble';
				bubbleEl.textContent = text;
				wrap.appendChild(bubbleEl);
			}

			// Selectable product cards (tap to choose)
			if (meta.cards && meta.cards.length) {
				var cards = document.createElement('div');
				cards.className = 'mont-ai-cards';
				meta.cards.forEach(function (card) {
					var row = document.createElement('div');
					row.className = 'mont-ai-card mont-ai-card--pick';
					row.setAttribute('role', 'button');
					row.tabIndex = 0;

					if (card.image) {
						var img = document.createElement('img');
						img.className = 'mont-ai-card__img';
						img.src = card.image;
						img.alt = card.name || '';
						row.appendChild(img);
					} else {
						var ph = document.createElement('div');
						ph.className = 'mont-ai-card__img';
						row.appendChild(ph);
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
					row.appendChild(info);

					var actions = document.createElement('div');
					actions.className = 'mont-ai-card__actions';

					var pick = document.createElement('button');
					pick.type = 'button';
					pick.className = 'mont-ai-card__cta';
					pick.textContent = 'Select';
					pick.addEventListener('click', function (e) {
						e.preventDefault();
						e.stopPropagation();
						if (busy) return;
						disableActiveChoices();
						sendText('I want product #' + card.id + ': ' + (card.name || ''));
					});
					actions.appendChild(pick);

					if (card.permalink) {
						var view = document.createElement('a');
						view.className = 'mont-ai-card__link';
						view.href = card.permalink;
						view.target = '_blank';
						view.rel = 'noopener';
						view.textContent = (cfg.i18n && cfg.i18n.viewProduct) || 'View';
						actions.appendChild(view);
					}

					row.appendChild(actions);

					row.addEventListener('click', function () {
						if (busy) return;
						disableActiveChoices();
						sendText('I want product #' + card.id + ': ' + (card.name || ''));
					});

					cards.appendChild(row);
				});
				wrap.appendChild(cards);
			}

			if (meta.choices) {
				renderChoices(wrap, meta.choices);
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
			panel.removeAttribute('hidden');
			panel.classList.add('is-open');
			root.classList.add('is-open');
			bubble.classList.add('is-open');
			bubble.setAttribute('aria-expanded', 'true');
			// Resume only if this chat session is still marked active (panel was open / mid-chat).
			// Otherwise start a fresh welcome.
			if (!messagesEl.childElementCount) {
				history = loadActiveSessionHistory();
				if (history.length) {
					history.forEach(function (h) {
						if (h.role === 'user' || h.role === 'assistant') {
							appendMessage(h.role, h.content);
						}
					});
				} else {
					seedWelcome();
				}
			}
			if (input) {
				window.setTimeout(function () {
					try { input.focus(); } catch (e) {}
				}, 50);
			}
		}

		function closePanel() {
			panel.hidden = true;
			panel.setAttribute('hidden', 'hidden');
			panel.classList.remove('is-open');
			root.classList.remove('is-open');
			bubble.classList.remove('is-open');
			bubble.setAttribute('aria-expanded', 'false');
			// End chat session — next open starts clean for new shopping.
			clearSessionHistory();
			messagesEl.innerHTML = '';
		}

		function startNewChat() {
			clearSessionHistory();
			messagesEl.innerHTML = '';
			seedWelcome();
			if (input) {
				input.value = '';
				autoGrow();
				input.focus();
			}
		}

		function refreshMiniCart() {
			if (window.jQuery) {
				jQuery(document.body).trigger('wc_fragment_refresh');
				jQuery(document.body).trigger('added_to_cart');
			}
			if (window.jQuery && window.ajaxurl && ajaxurl.url) {
				jQuery.post(ajaxurl.url, { action: 'update_cart_count' });
			}
		}

		function setBusy(state) {
			busy = state;
			if (sendBtn) sendBtn.disabled = state;
			if (input) input.disabled = state;
		}

		function buildApiHistory() {
			return history.map(function (h) {
				return { role: h.role, content: h.content };
			});
		}

		function sendText(text) {
			text = (text || '').trim();
			if (!text || busy) return;

			var now = Date.now();
			if (now - lastSendAt < 600) return;
			lastSendAt = now;

			if (input) {
				input.value = '';
				autoGrow();
			}

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
						product_id: cfg.productId || 0,
						channel: cfg.channel || 'b2c'
					}
				})
			})
				.then(function (res) {
					return res.json().then(function (data) {
						return { ok: res.ok, status: res.status, data: data };
					}).catch(function () {
						return { ok: false, status: res.status, data: { message: 'Server error (' + res.status + '). Please try again.' } };
					});
				})
				.then(function (payload) {
					hideTyping();
					var data = payload.data || {};
					var msg = data.message || ((cfg.i18n && cfg.i18n.error) || 'Error');
					if (data.debug_error) {
						console.warn('[Mont AI]', data.error_code || 'error', data.debug_error);
					}
					appendMessage('assistant', msg, {
						cards: data.cards || [],
						choices: data.choices || null,
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
					if (input) input.focus();
				});
		}

		function send() {
			var text = (input && input.value ? input.value : '').trim();
			sendText(text);
		}

		function autoGrow() {
			if (!input) return;
			input.style.height = 'auto';
			input.style.height = Math.min(input.scrollHeight, 110) + 'px';
		}

		function seedWelcome() {
			if (messagesEl.childElementCount) return;
			var welcome = cfg.welcome || 'Hi! How can I help you today?';
			appendMessage('assistant', welcome);
		}

		function initLang() {
			if (!langSelect) return;
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

		bubble.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openPanel();
		});

		if (closeBtn) {
			closeBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				closePanel();
			});
		}

		if (newBtn) {
			newBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				startNewChat();
			});
		}

		if (sendBtn) sendBtn.addEventListener('click', send);
		if (input) {
			input.addEventListener('input', autoGrow);
			input.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					send();
				}
			});
		}

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && panel.classList.contains('is-open')) {
				closePanel();
			}
		});

		initLang();
		// Do not force old history on page load — wait until the user opens chat.
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.setTimeout(boot, 0);
	window.setTimeout(boot, 250);
})();
