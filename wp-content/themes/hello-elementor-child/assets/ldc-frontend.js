(function () {
	'use strict';

	// Header shadow after scrolling
	function initScrollShadow() {
		var onScroll = function () {
			document.body.classList.toggle('ldc-scrolled', window.scrollY > 10);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	// TOC collapse toggle (mobile-friendly) + active-section highlight
	function initToc() {
		var toc = document.querySelector('.ldc-toc');
		if (!toc) return;

		var toggle = toc.querySelector('.ldc-toc-toggle');
		var list = toc.querySelector('.ldc-toc-list');
		if (toggle && list) {
			toggle.addEventListener('click', function () {
				var expanded = toggle.getAttribute('aria-expanded') === 'true';
				toggle.setAttribute('aria-expanded', String(!expanded));
				list.style.display = expanded ? 'none' : 'block';
				toggle.querySelector('.ldc-toc-chevron').textContent = expanded ? '+' : '−';
			});
			if (window.innerWidth < 768) {
				toggle.setAttribute('aria-expanded', 'false');
				list.style.display = 'none';
				toggle.querySelector('.ldc-toc-chevron').textContent = '+';
			}
		}

		var links = toc.querySelectorAll('a[href^="#"]');
		var targets = [];
		links.forEach(function (link) {
			var id = link.getAttribute('href').slice(1);
			var el = document.getElementById(id);
			if (el) targets.push({ link: link, el: el });
		});
		if (!targets.length) return;

		var onScrollSpy = function () {
			var current = targets[0];
			targets.forEach(function (t) {
				if (t.el.getBoundingClientRect().top - 120 <= 0) current = t;
			});
			links.forEach(function (l) { l.classList.remove('is-active'); });
			current.link.classList.add('is-active');
		};
		window.addEventListener('scroll', onScrollSpy, { passive: true });
		onScrollSpy();
	}

	// FAQ accordion (shortcode-based, used inside blog post content)
	function initFaqAccordion() {
		document.querySelectorAll('.ldc-faq-question').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				var answer = document.getElementById(btn.getAttribute('aria-controls'));
				btn.setAttribute('aria-expanded', String(!expanded));
				if (answer) answer.hidden = expanded;
				btn.querySelector('.ldc-faq-icon').textContent = expanded ? '+' : '−';
			});
		});
	}

	// Sticky CTA: hide once the visitor reaches the footer / final CTA area
	function initStickyCta() {
		var bar = document.getElementById('ldc-sticky-cta');
		if (!bar) return;
		var footer = document.querySelector('footer');
		var onScroll = function () {
			bar.classList.toggle('is-visible', window.scrollY > 400);
			if (footer) {
				var rect = footer.getBoundingClientRect();
				if (rect.top < window.innerHeight) {
					bar.classList.remove('is-visible');
				}
			}
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	// Progressively enhance native <select> fields into a custom-styled dropdown.
	// The real <select> stays in the DOM (visually hidden) so form submission and
	// no-JS fallback both keep working — only the presentation changes.
	function enhanceSelects() {
		document.querySelectorAll('.elementor-field-type-select select').forEach(function (select) {
			if (select.dataset.ldcEnhanced) return;
			select.dataset.ldcEnhanced = 'true';

			// Elementor renders its own decorative arrow as a SEPARATE sibling element
			// (.select-caret-down-wrapper) next to the <select>, not just on the select
			// itself — so hiding the select alone leaves that arrow behind. Hiding the
			// whole host wrapper (select + arrow together) avoids that, and the real
			// <select> stays in the DOM/untouched for form submission and no-JS fallback.
			var hostWrapper = select.closest('.elementor-select-wrapper') || select.parentNode;
			hostWrapper.classList.add('ldc-select-native-host');
			select.setAttribute('tabindex', '-1');
			select.setAttribute('aria-hidden', 'true');

			var wrapper = document.createElement('div');
			wrapper.className = 'ldc-select';
			hostWrapper.insertAdjacentElement('afterend', wrapper);

			var trigger = document.createElement('button');
			trigger.type = 'button';
			trigger.className = 'ldc-select-trigger';
			trigger.setAttribute('aria-haspopup', 'listbox');
			trigger.setAttribute('aria-expanded', 'false');

			var label = document.createElement('span');
			label.textContent = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : '';
			var chevron = document.createElement('span');
			chevron.className = 'ldc-select-chevron';
			chevron.setAttribute('aria-hidden', 'true');
			trigger.appendChild(label);
			trigger.appendChild(chevron);
			wrapper.appendChild(trigger);

			var list = document.createElement('ul');
			list.className = 'ldc-select-list';
			list.setAttribute('role', 'listbox');
			list.hidden = true;

			var items = Array.prototype.map.call(select.options, function (opt, i) {
				var li = document.createElement('li');
				li.textContent = opt.text;
				li.setAttribute('role', 'option');
				li.dataset.index = i;
				if (select.selectedIndex === i) li.classList.add('is-selected');
				list.appendChild(li);
				return li;
			});
			wrapper.appendChild(list);

			function selectIndex(i) {
				select.selectedIndex = i;
				select.dispatchEvent(new Event('change', { bubbles: true }));
				label.textContent = select.options[i].text;
				items.forEach(function (el, idx) {
					el.classList.toggle('is-selected', idx === i);
				});
			}
			function openList() {
				list.hidden = false;
				wrapper.classList.add('is-open');
				trigger.setAttribute('aria-expanded', 'true');
			}
			function closeList() {
				list.hidden = true;
				wrapper.classList.remove('is-open');
				trigger.setAttribute('aria-expanded', 'false');
			}

			items.forEach(function (li, i) {
				li.addEventListener('click', function () {
					selectIndex(i);
					closeList();
					trigger.focus();
				});
			});
			trigger.addEventListener('click', function () {
				if (list.hidden) openList(); else closeList();
			});
			trigger.addEventListener('keydown', function (e) {
				if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					openList();
					(items[select.selectedIndex] || items[0]).focus();
				}
			});
			list.addEventListener('keydown', function (e) {
				var current = document.activeElement;
				var idx = items.indexOf(current);
				if (e.key === 'ArrowDown') {
					e.preventDefault();
					(items[Math.min(idx + 1, items.length - 1)] || items[0]).focus();
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					(items[Math.max(idx - 1, 0)] || items[0]).focus();
				} else if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					if (idx > -1) { selectIndex(idx); closeList(); trigger.focus(); }
				} else if (e.key === 'Escape') {
					closeList();
					trigger.focus();
				}
			});
			items.forEach(function (li) { li.tabIndex = -1; });

			document.addEventListener('click', function (e) {
				if (!wrapper.contains(e.target)) closeList();
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initScrollShadow();
		initToc();
		initFaqAccordion();
		initStickyCta();
		enhanceSelects();
	});
})();
