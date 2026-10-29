/* External Store for Shopify - front-end scripts (no dependencies) */
(function () {
	'use strict';

	/* Search & filter bar */
	document.addEventListener('input', function (event) {
		var input = event.target;
		if (!input || !input.classList || !input.classList.contains('wpsy-filter-bar')) {
			return;
		}
		var wrapper = input.closest('.wpsy-shop');
		if (!wrapper) {
			return;
		}
		var value = input.value.toLowerCase();
		var visible = 0;

		wrapper.querySelectorAll('ul.wp_shopify > li').forEach(function (item) {
			var match = item.textContent.toLowerCase().indexOf(value) > -1;
			item.style.display = match ? '' : 'none';
			if (match) {
				visible++;
			}
		});

		var empty = wrapper.querySelector('.wpsy-no-results');
		if (!empty) {
			empty = document.createElement('p');
			empty.className = 'wpsy-no-results';
			empty.setAttribute('role', 'status');
			empty.hidden = true;
			var list = wrapper.querySelector('ul.wp_shopify');
			if (list && list.parentNode) {
				list.parentNode.insertBefore(empty, list.nextSibling);
			}
		}
		empty.textContent = (window.wpsyFront && window.wpsyFront.noResults) ? window.wpsyFront.noResults : '';
		empty.hidden = visible > 0 || !empty.textContent;
	});

	/* Product gallery: click a thumbnail to swap the main image */
	document.addEventListener('click', function (event) {
		var link = event.target.closest ? event.target.closest('.wp_shopify_product .prod-gallery a') : null;
		if (!link) {
			return;
		}
		var thumb = link.querySelector('img');
		var main = document.querySelector('.wp_shopify_product .prod-left > a img');
		if (thumb && main) {
			event.preventDefault();
			main.setAttribute('src', thumb.getAttribute('src'));
		}
	});
})();
