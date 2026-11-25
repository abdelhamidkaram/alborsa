( () => {
	'use strict';

	const shareButton = document.querySelector( '[data-news-share]' );
	const status = document.querySelector( '[data-share-status]' );
	const text = window.alborsaNews;

	if ( ! ( shareButton instanceof HTMLButtonElement ) || ! ( status instanceof HTMLElement ) ) {
		return;
	}

	const announce = ( message ) => {
		status.textContent = message;
	};

	shareButton.addEventListener( 'click', async () => {
		const url = shareButton.dataset.shareUrl || window.location.href;
		const title = shareButton.dataset.shareTitle || document.title;

		try {
			if ( 'function' === typeof navigator.share ) {
				await navigator.share( { title, url } );
				announce( text.shared );
				return;
			}

			if ( navigator.clipboard && 'function' === typeof navigator.clipboard.writeText ) {
				await navigator.clipboard.writeText( url );
				announce( text.copied );
				return;
			}

			announce( text.copyFailed );
		} catch ( error ) {
			if ( error instanceof DOMException && 'AbortError' === error.name ) {
				return;
			}

			announce( text.copyFailed );
		}
	} );
} )();
