( () => {
	'use strict';

	const root = document.documentElement;
	const storageKey = 'alborsa-theme';
	const systemPreference = window.matchMedia( '(prefers-color-scheme: dark)' );
	const text = window.alborsaTheme;

	function storedTheme() {
		try {
			const value = window.localStorage.getItem( storageKey );

			return 'light' === value || 'dark' === value ? value : null;
		} catch {
			return null;
		}
	}

	function applyTheme( theme ) {
		root.dataset.theme = theme;
		root.style.colorScheme = theme;
	}

	function updateToggle( button ) {
		if ( ! ( button instanceof HTMLButtonElement ) ) {
			return;
		}

		const isDark = 'dark' === root.dataset.theme;
		button.setAttribute( 'aria-pressed', String( isDark ) );
		button.setAttribute(
			'aria-label',
			isDark ? text.lightMode : text.darkMode
		);
	}

	applyTheme( storedTheme() || ( systemPreference.matches ? 'dark' : 'light' ) );

	document.addEventListener( 'DOMContentLoaded', () => {
		const toggle = document.querySelector( '[data-theme-toggle]' );

		updateToggle( toggle );

		if ( ! ( toggle instanceof HTMLButtonElement ) ) {
			return;
		}

		toggle.addEventListener( 'click', () => {
			const nextTheme = 'dark' === root.dataset.theme ? 'light' : 'dark';

			applyTheme( nextTheme );

			try {
				window.localStorage.setItem( storageKey, nextTheme );
			} catch {
				// The selected mode still applies for the current page view.
			}

			updateToggle( toggle );
		} );
	} );

	systemPreference.addEventListener( 'change', ( event ) => {
		if ( null === storedTheme() ) {
			applyTheme( event.matches ? 'dark' : 'light' );
			updateToggle( document.querySelector( '[data-theme-toggle]' ) );
		}
	} );
} )();
