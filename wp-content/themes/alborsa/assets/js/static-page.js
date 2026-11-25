const staticPageToc = document.querySelector( '[data-static-page-toc]' );

if ( staticPageToc instanceof HTMLOListElement ) {
	const links = Array.from( staticPageToc.querySelectorAll( 'a[href^="#"]' ) );
	const sections = links
		.map( ( link ) => {
			const targetId = link.getAttribute( 'href' ).slice( 1 );
			const section = document.getElementById( targetId );

			return section instanceof HTMLHeadingElement ? { link, section } : null;
		} )
		.filter( ( sectionEntry ) => null !== sectionEntry );

	const setCurrentSection = ( targetLink ) => {
		links.forEach( ( link ) => {
			if ( link === targetLink ) {
				link.setAttribute( 'aria-current', 'location' );
				return;
			}

			link.removeAttribute( 'aria-current' );
		} );
	};

	if ( sections.length > 0 ) {
		setCurrentSection( sections[ 0 ].link );

		if ( 'IntersectionObserver' in window ) {
			const observer = new IntersectionObserver(
				( entries ) => {
					const visibleEntry = entries
						.filter( ( entry ) => entry.isIntersecting )
						.sort( ( first, second ) => first.boundingClientRect.top - second.boundingClientRect.top )[ 0 ];

					if ( visibleEntry ) {
						const current = sections.find( ( sectionEntry ) => sectionEntry.section === visibleEntry.target );

						if ( current ) {
							setCurrentSection( current.link );
						}
					}
				},
				{ rootMargin: '-15% 0px -70% 0px' }
			);

			sections.forEach( ( sectionEntry ) => observer.observe( sectionEntry.section ) );
		}

		sections.forEach( ( sectionEntry ) => {
			sectionEntry.link.addEventListener( 'click', () => setCurrentSection( sectionEntry.link ) );
		} );
	}
}
