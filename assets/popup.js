/**
 * Site pop-up.
 *
 * The <dialog> and the bottom-left pill are printed by functions.php, both
 * closed/hidden. This script:
 *  - opens the dialog (once the page has settled) for visitors who have not
 *    dismissed this exact pop-up, and remembers a dismissal per pop-up id, so
 *    publishing a changed pop-up (a new data-popup-id) shows it again;
 *  - shows the pill whenever the dialog is closed, so the message can always
 *    be reread; clicking the pill reopens the dialog.
 *
 * Closing: the X button, the Escape key (native <dialog> behaviour), a click
 * on the backdrop, or following the button link. All of them fire the
 * dialog's 'close' event, which is the single place the dismissal is stored.
 */
( function () {
	var dialog = document.getElementById( 'site-popup' );
	var pill = document.querySelector( '[data-popup-open]' );
	if ( ! dialog || ! pill || typeof dialog.showModal !== 'function' ) {
		return;
	}

	var popupId = dialog.getAttribute( 'data-popup-id' ) || 'default';
	var STORAGE_KEY = '3ducation:popup-dismissed';

	var dismissed = false;
	try {
		dismissed = window.localStorage.getItem( STORAGE_KEY ) === popupId;
	} catch ( e ) {}

	function remember() {
		try {
			window.localStorage.setItem( STORAGE_KEY, popupId );
		} catch ( e ) {}
	}

	function open() {
		pill.hidden = true;
		if ( ! dialog.open ) {
			dialog.showModal();
		}
	}

	function close() {
		if ( dialog.open ) {
			dialog.close();
		}
	}

	// Every way of closing ends here: store the dismissal, bring back the pill.
	dialog.addEventListener( 'close', function () {
		remember();
		pill.hidden = false;
	} );

	dialog.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '[data-popup-close]' ) ) {
			close();
			return;
		}
		// The dialog element itself only receives clicks on its backdrop;
		// clicks inside the card land on .site-popup__card or its children.
		if ( event.target === dialog ) {
			close();
		}
	} );

	// Following the button link counts as "seen" even if the page unloads
	// before the dialog's close event fires.
	var cta = dialog.querySelector( '[data-popup-cta]' );
	if ( cta ) {
		cta.addEventListener( 'click', remember );
	}

	pill.addEventListener( 'click', open );

	if ( dismissed ) {
		pill.hidden = false;
		return;
	}

	// Let the page paint first so the pop-up doesn't fight the hero for attention.
	window.setTimeout( open, 700 );
}() );
