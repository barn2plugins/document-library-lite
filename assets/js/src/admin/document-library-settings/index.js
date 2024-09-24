
( function( $, window, document, undefined ) {
	"use strict";

	var toggleChildSettings = function( $parent, $children ) {
		var show = false;
		var toggleVal = $parent.data( 'toggleVal' );

		if ( 'radio' === $parent.attr( 'type' ) ) {
			show = $parent.prop( 'checked' ) && toggleVal == $parent.val();
		} else if ( 'checkbox' === $parent.attr( 'type' ) ) {
			if ( typeof toggleVal === 'undefined' || 1 == toggleVal ) {
				show = $parent.prop( 'checked' );
			} else {
				show = !$parent.prop( 'checked' );
			}
		} else {
			show = ( toggleVal == $parent.val() );
		}

		$children.toggle( show );
	};

	$( document ).ready( function() {
		$( '.form-table .toggle-parent' ).each( function() {
			var $parent = $( this );
			var $children = $parent.closest( '.form-table' ).find( '.' + $parent.data( 'childClass' ) ).closest( 'tr' );

			toggleChildSettings( $parent, $children );

			$parent.on( 'change', function() {
				toggleChildSettings( $parent, $children );
			} );
		} );

	} );

} )( jQuery, window, document );

