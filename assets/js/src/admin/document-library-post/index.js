jQuery( function( $ ) {
	/**
	 * Document Link Metabox JS
	 */
	const dlwDocumentLink = function() {
		$( '#dlw_add_file_button' ).on( 'click', this.handleAddFile );
		$( '#dlw_remove_file_button' ).on( 'click', this.handleRemoveFile );
		$( '#dlw_document_link_type' ).on( 'change', this.handleSelectBox );
		this.initMetaboxLocking();
	};

	dlwDocumentLink.wpMedia = null;

	/**
	 * Render second option
	 */
	dlwDocumentLink.prototype.handleSelectBox = function( event ) {
		const $this = $( this );
		const value = $this.find( ':selected' ).val();
		const $file_details = $( '#dlw_file_attachment_details' );
		const $url_details = $( '#dlw_link_url_details' );
		const $file_size_input = $( '#dlw_file_size_input' );

		switch ( value ) {
			case 'file':
				$url_details.removeClass( 'active' );
				$file_details.addClass( 'active' );
				$file_size_input.prop( 'disabled', true );
				break;
			case 'url':
				$url_details.addClass( 'active' );
				$file_details.removeClass( 'active' );
				$file_size_input.removeAttr( 'disabled' );
				break;
			case 'none':
				$url_details.removeClass( 'active' );
				$file_details.removeClass( 'active' );
				$file_size_input.removeAttr( 'disabled' );
				break;
			default:
				$url_details.removeClass( 'active' );
				$file_details.removeClass( 'active' );
				$file_size_input.removeAttr( 'disabled' );
				break;
		}
	};

	/**
	 * Handle Add File (WP Media)
	 */
	dlwDocumentLink.prototype.handleAddFile = function( event ) {
		event.preventDefault();

		const $this = $( this );
		const $file_name = $( '#dlw_file_name' );
		const $file_name_text = $( '.dlw_file_name_text' );
		const $file_id = $( '#dlw_file_id' );
		const $file_attached_area = $( '.dlw_file_attached' );


		if ( dlwDocumentLink.wpMedia !== null ) {
			dlwDocumentLink.wpMedia.open();
			return;
		}

		dlwDocumentLink.wpMedia = wp.media({
			title: dlwAdminObject.i18n.select_file,
			button: {
				text: dlwAdminObject.i18n.add_file
			},
			states: [
				new wp.media.controller.Library({
					title: dlwAdminObject.i18n.select_file,
					filterable: 'all',
					multiple: false
				}),
				new wp.media.controller.EditImage()
			]
		});

		dlwDocumentLink.wpMedia.on( 'select', function () {
			const selection = dlwDocumentLink.wpMedia.state().get('selection');

			selection.map( function (attachment) {
				attachment = attachment.toJSON();

				$file_name.val( attachment.filename );
				$file_name_text.text( attachment.filename );
				$file_id.val( attachment.id );
				$file_attached_area.addClass( 'active' );
				$this.text( dlwAdminObject.i18n.replace_file );
			});
		});

		dlwDocumentLink.wpMedia.open();
	};

	/**
	 * Handle Remove File
	 */
	dlwDocumentLink.prototype.handleRemoveFile = function( event ) {
		event.preventDefault();

		const $file_name = $( '#dlw_file_name' );
		const $file_id = $( '#dlw_file_id' );
		const $file_attached_area = $( '.dlw_file_attached' );
		const $add_file_button = $( '#dlw_add_file_button' );

		$file_attached_area.removeClass( 'active' );
		$file_name.val('');
		$file_id.val('');
		$add_file_button.text( dlwAdminObject.i18n.add_file );
	};

	/**
	 * Initialize metabox locking functionality to prevent dragging
	 */
	dlwDocumentLink.prototype.initMetaboxLocking = function () {
		const $metabox = $( '#document_link' );
		const $container = $( '#dlp_below_title-sortables' );

		if ( $metabox.length ) {
			$metabox.addClass( 'postbox-locked' );

			$metabox
				.removeAttr( 'draggable' )
				.removeClass( 'ui-sortable-handle' );
			$metabox
				.find( '*' )
				.removeAttr( 'draggable' )
				.removeClass( 'ui-sortable-handle' );

			$metabox.find( '.postbox-header h2' ).css( 'cursor', 'default' );

			$metabox
				.find( '.handle-order-higher, .handle-order-lower' )
				.remove();

			$metabox
				.off( '.dlp-lock' )
				.on(
					'mousedown.dlp-lock dragstart.dlp-lock drag.dlp-lock',
					function ( e ) {
						// Allow specific interactive elements
						if (
							$( e.target ).is(
								'input, select, button, textarea, option, .handlediv'
							) ||
							$( e.target ).closest(
								'input, select, button, textarea, .handlediv'
							).length
						) {
							return true;
						}
						e.stopImmediatePropagation();
						e.preventDefault();
						return false;
					}
				);

			// Configure sortable to exclude this metabox completely
			if ( $container.length && $container.hasClass( 'ui-sortable' ) ) {
				$container.sortable( 'option', {
					cancel: '#document_link, #document_link *:not(.handlediv)',
					items: '> .postbox:not(#document_link)',
				} );
			}
		}
	};

	/**
	 * Init dlwDocumentLink.
	 */
	new dlwDocumentLink();
} );