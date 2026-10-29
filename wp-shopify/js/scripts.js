/* External Store for Shopify - admin scripts */
jQuery(document).ready(function ($) {
	'use strict';

	var $wrap = $('.wpsy_settings_div');
	var cfg = window.wpsy_object || {};
	var i18n = cfg.i18n || {};

	/* ---------- Tabs ---------- */
	function showTab(tab) {
		var $content = $wrap.find('.nav-tab-content.tab-' + tab);
		if (!$content.length) {
			return;
		}
		$wrap.find('h2.nav-tab-wrapper a.nav-tab').removeClass('nav-tab-active');
		$wrap.find('h2.nav-tab-wrapper a.nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
		$wrap.find('.nav-tab-content').addClass('hides').hide();
		$content.removeClass('hides').show();
	}

	$wrap.on('click', 'h2.nav-tab-wrapper a.nav-tab', function (e) {
		e.preventDefault();
		var tab = $(this).data('tab');
		showTab(tab);
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + tab);
		}
	});

	$wrap.on('click', '.wpsy-need-help', function (e) {
		e.preventDefault();
		showTab('help');
	});

	if (window.location.hash) {
		showTab(window.location.hash.replace('#', ''));
	}

	/* ---------- AJAX tools ---------- */
	function runTool(action, busyText, $btn) {
		var $result = $('#wpsy-tool-result');
		$btn.prop('disabled', true);
		$result.removeClass('is-error is-ok').text(busyText);

		$.post(cfg.url, { action: action, nonce: cfg.nonce })
			.done(function (response) {
				var message = (response && response.data && response.data.message) ? response.data.message : '';
				if (response && response.success) {
					$result.addClass('is-ok').text(message);
				} else {
					$result.addClass('is-error').text(message || i18n.failed);
				}
			})
			.fail(function () {
				$result.addClass('is-error').text(i18n.failed);
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	}

	$('#wpsy-test-connection').on('click', function () {
		runTool('wpsy_test_connection', i18n.testing, $(this));
	});
	$('#wpsy-clear-cache').on('click', function () {
		runTool('wpsy_clear_cache', i18n.clearing, $(this));
	});
	$('#wpsy-disconnect').on('click', function (e) {
		if (!window.confirm(i18n.confirm)) {
			e.preventDefault();
		}
	});

	/* ---------- Shortcode generator ---------- */
	function clean(value) {
		return String(value || '').replace(/[^A-Za-z0-9_\-]/g, '');
	}

	function buildShortcode() {
		var type = $('#wpsy-gen-type').val() === 'collection' ? 'collection' : 'products';
		var parts = ['[wp-shopify', 'type="' + type + '"'];
		var id = clean($('#wpsy-gen-id').val());
		var limit = parseInt($('#wpsy-gen-limit').val(), 10);
		var columns = parseInt($('#wpsy-gen-columns').val(), 10);
		var sort = clean($('#wpsy-gen-sort').val());

		if (type === 'collection' && id) {
			parts.push('id="' + id + '"');
		}
		if (limit > 0) {
			parts.push('limit="' + Math.min(limit, 250) + '"');
		}
		if (columns > 0) {
			parts.push('columns="' + Math.min(columns, 8) + '"');
		}
		if (sort) {
			parts.push('sort="' + sort + '"');
			if ($('#wpsy-gen-reverse').is(':checked')) {
				parts.push('reverse="yes"');
			}
		}
		if ($('#wpsy-gen-urltype').val() === 'shopify') {
			parts.push('url-type="shopify"');
		}
		if ($('#wpsy-gen-search').is(':checked')) {
			parts.push('searchfilter="yes"');
		}
		if (!$('#wpsy-gen-price').is(':checked')) {
			parts.push('show-price="no"');
		}
		return parts.join(' ') + ']';
	}

	function refreshGenerator() {
		var isCollection = $('#wpsy-gen-type').val() === 'collection';
		$('#wpsy-gen-id').prop('disabled', !isCollection);
		$('#wpsy-gen-output').val(buildShortcode());
	}

	$('.wpsy-generator').on('input change', 'input, select', refreshGenerator);
	refreshGenerator();

	$('#wpsy-gen-copy').on('click', function () {
		var $out = $('#wpsy-gen-output');
		var $btn = $(this);
		var original = $btn.text();
		$out.trigger('focus').trigger('select');
		var done = function () {
			$btn.text(i18n.copied);
			setTimeout(function () { $btn.text(original); }, 1500);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText($out.val()).then(done);
		} else {
			try { document.execCommand('copy'); done(); } catch (err) { /* selection stays highlighted */ }
		}
	});
});
