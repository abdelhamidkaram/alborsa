document.documentElement.classList.add('has-js');

const menuToggle = document.querySelector('[data-menu-toggle]');
const primaryNavigation = document.querySelector('[data-primary-navigation]');

if (menuToggle instanceof HTMLButtonElement && primaryNavigation instanceof HTMLElement) {
	const closeMenu = () => {
		menuToggle.setAttribute('aria-expanded', 'false');
		primaryNavigation.classList.remove('is-open');
		document.body.classList.remove('has-open-menu');
	};

	const openMenu = () => {
		menuToggle.setAttribute('aria-expanded', 'true');
		primaryNavigation.classList.add('is-open');
		document.body.classList.add('has-open-menu');

		const firstLink = primaryNavigation.querySelector('a');

		if (firstLink instanceof HTMLAnchorElement) {
			firstLink.focus();
		}
	};

	menuToggle.addEventListener('click', () => {
		if ('true' === menuToggle.getAttribute('aria-expanded')) {
			closeMenu();
			return;
		}

		openMenu();
	});

	primaryNavigation.addEventListener('click', (event) => {
		if (event.target instanceof HTMLAnchorElement) {
			closeMenu();
		}
	});

	document.addEventListener('keydown', (event) => {
		if ('Escape' !== event.key || 'true' !== menuToggle.getAttribute('aria-expanded')) {
			return;
		}

		closeMenu();
		menuToggle.focus();
	});

	window.addEventListener('resize', () => {
		if (window.matchMedia('(min-width: 56.25rem)').matches) {
			closeMenu();
		}
	});
}
