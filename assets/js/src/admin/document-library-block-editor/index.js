// import { __, sprintf } from '@wordpress/i18n';

jQuery( function ( $ ) {
	/*
	 * Safely clone a jQuery element, preserving input values.
	 */
	function safeClone( $source ) {
		const $clone = $source.clone( false );

		$source.find( 'input, textarea, select' ).each( function () {
			const name = $( this ).attr( 'name' );
			if ( ! name ) return;

			const $targetField = $clone.find( '[name="' + name + '"]' );
			const type = $( this ).attr( 'type' );

			if ( type === 'checkbox' || type === 'radio' ) {
				$targetField.prop( 'checked', $( this ).prop( 'checked' ) );
			} else {
				$targetField.val( $( this ).val() );
			}
		} );

		return $clone;
	}

	/*
	 * Move the document link meta box above the editor.
	 */
	if ( ! wp || ! wp.data ) return;

	const editorObserver = new MutationObserver( ( mutations, obs ) => {
		if ( document.querySelector( '.editor-visual-editor' ) && document.querySelector( '#document_link' ) ) {
			$( '#side-sortables' ).sortable( 'option', 'cancel', '#document_link' );

			$( '#document_link' )
				.wrap( '<div id="document_link_wrapper"></div>' )
				.parent()
				.insertBefore( '.editor-visual-editor' );

			fixActionButtons();

			obs.disconnect();
		}
	} );

	editorObserver.observe( document.body, {
		childList: true,
		subtree: true,
	} );

	const { subscribe, select } = wp.data;

	let wasSavingPost = false;

	subscribe( function () {
		const editor = select( 'core/editor' );

		// Detect save lifecycle.
		const isSavingPost = editor.isSavingPost();
		const isAutosavingPost = editor.isAutosavingPost();

		// Before save.
		if ( isSavingPost && ! wasSavingPost ) {
			if ( ! isAutosavingPost ) {
				const $original = $( '#document_link' );
				const $clone = safeClone( $original );

				$( '#side-sortables #document_link' ).remove();
				$clone.appendTo( '#side-sortables' );
			}
		}

		// After save.
		if ( wasSavingPost && ! isSavingPost ) {
			if ( ! isAutosavingPost ) {
				$( '#side-sortables #document_link' ).remove();
			}
		}

		wasSavingPost = isSavingPost;
	} );

	/*
	 * Fix bug with the postbox action buttons (move up/down) not being disabled when the document link metabox is moved up.
	 */
	function fixActionButtons() {
		$( '#normal-sortables .postbox:visible:first .handle-order-higher' ).attr( 'aria-disabled', 'true' );
		$( '#side-sortables .postbox:visible:last .handle-order-lower' ).attr( 'aria-disabled', 'true' );
	}

	$( document ).on( 'click.postboxes', function ( event, movedBox ) {
		fixActionButtons();
	} );

	$( '.meta-box-sortables' ).on( 'sortstop', function ( event, ui ) {
		setTimeout( function () {
			fixActionButtons();
		}, 200 );
	} );

	/*
	 * Make the File Types panel input readonly.
	 */
	const fileTypesObserver = new MutationObserver( function ( mutations, obs ) {
		$( '.components-panel__body' ).each( function () {
			const $panel = $( this );
			const $title = $panel.find( '.components-panel__body-title' ).first();

			if ( $title.length && $title.text().trim() === 'File Types' ) {
				$panel.addClass( 'file-type-panel' );

				const $input = $panel.find( 'input[type="text"]' ).first();

				if ( $input.length ) {
					$input.attr( 'readonly', 'readonly' );

					obs.disconnect();
				}
			}
		} );
	} );

	fileTypesObserver.observe( document.body, { childList: true, subtree: true } );
} );
