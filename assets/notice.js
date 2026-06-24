/**
 * Site notice banner.
 *
 * The bar is shown server-side and hidden before paint (by a small inline
 * script) for visitors who already dismissed this exact notice. This file
 * only wires the close button: it hides the bar and remembers the dismissal
 * per notice id, so publishing a new notice (a new data-notice-id) shows the
 * bar again to everyone.
 */
( function () {
	var bar = document.getElementById( 'site-notice' );
	if ( ! bar ) {
		return;
	}

	var noticeId = bar.getAttribute( 'data-notice-id' ) || 'default';
	var STORAGE_KEY = '3ducation:notice-dismissed';

	bar.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest( '[data-notice-close]' ) ) {
			return;
		}
		bar.classList.remove( 'is-visible' );
		try {
			window.localStorage.setItem( STORAGE_KEY, noticeId );
		} catch ( e ) {}
	} );
}() );
