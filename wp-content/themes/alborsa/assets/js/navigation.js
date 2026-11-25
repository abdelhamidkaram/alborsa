document.documentElement.classList.add( 'has-js' );

const navigationText = window.alborsaNavigation;
const menuToggle = document.querySelector( '[data-menu-toggle]' );
const primaryNavigation = document.querySelector( '[data-primary-navigation]' );
const menuToggleLabel = document.querySelector( '[data-menu-toggle-label]' );

if ( menuToggle instanceof HTMLButtonElement && primaryNavigation instanceof HTMLElement ) {
	const closeMenu = ( restoreFocus = false ) => {
		menuToggle.setAttribute( 'aria-expanded', 'false' );
		primaryNavigation.classList.remove( 'is-open' );
		document.body.classList.remove( 'has-open-menu' );

		if ( menuToggleLabel instanceof HTMLElement ) {
			menuToggleLabel.textContent = navigationText.openMenu;
		}

		if ( restoreFocus ) {
			menuToggle.focus();
		}
	};

	const openMenu = () => {
		menuToggle.setAttribute( 'aria-expanded', 'true' );
		primaryNavigation.classList.add( 'is-open' );
		document.body.classList.add( 'has-open-menu' );

		if ( menuToggleLabel instanceof HTMLElement ) {
			menuToggleLabel.textContent = navigationText.closeMenu;
		}

		const firstLink = primaryNavigation.querySelector( 'a' );

		if ( firstLink instanceof HTMLAnchorElement ) {
			firstLink.focus();
		}
	};

	menuToggle.addEventListener( 'click', () => {
		if ( 'true' === menuToggle.getAttribute( 'aria-expanded' ) ) {
			closeMenu();
			return;
		}

		openMenu();
	} );

	primaryNavigation.addEventListener( 'click', ( event ) => {
		if ( event.target instanceof HTMLAnchorElement ) {
			closeMenu();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && 'true' === menuToggle.getAttribute( 'aria-expanded' ) ) {
			closeMenu( true );
		}
	} );

	window.addEventListener( 'resize', () => {
		if (
			window.matchMedia( '(min-width: 64rem)' ).matches ||
			window.matchMedia( '(max-width: 48rem)' ).matches
		) {
			closeMenu();
		}
	} );
}

const marketTicker = document.querySelector( '[data-market-ticker]' );
const tickerToggle = document.querySelector( '[data-ticker-toggle]' );
const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

if ( marketTicker instanceof HTMLElement && tickerToggle instanceof HTMLButtonElement ) {
	const setTickerState = ( paused ) => {
		marketTicker.dataset.tickerState = paused ? 'paused' : 'running';
		tickerToggle.setAttribute( 'aria-pressed', String( paused ) );
		tickerToggle.setAttribute(
			'aria-label',
			paused ? navigationText.playTicker : navigationText.pauseTicker
		);
	};

	setTickerState( reducedMotion.matches );

	tickerToggle.addEventListener( 'click', () => {
		setTickerState( 'paused' !== marketTicker.dataset.tickerState );
	} );

	reducedMotion.addEventListener( 'change', ( event ) => {
		if ( event.matches ) {
			setTickerState( true );
		}
	} );
}
