
( function( $ ) {

    $( document ).ready( function() {

        let tables = $( '.document-library-table' );

        const adminBar = $( '#wpadminbar' );
        const clickFilterColumns = [ 'doc_categories' ];

        tables.each( function() {
            let $table = $( this ),
                config = {
                    responsive: true,
                    processing: true // display 'processing' indicator when loading
                };

            // Set language - defaults to English if not specified
            if ( ( typeof document_library !== 'undefined' ) && document_library.langurl ) {
                config.language = { url: document_library.langurl };
            }

            // Initialise DataTable
            let table = $table.DataTable( config );

            // If scroll offset defined, animate back to top of table on next/previous page event
            $table.on( 'page.dt', function() {
                if ( $( this ).data( 'scroll-offset' ) !== false ) {
                    let tableOffset = $( this ).parent().offset().top - $( this ).data( 'scroll-offset' );

                    if ( adminBar.length ) { // Adjust offset for WP admin bar
                        let adminBarHeight = adminBar.outerHeight();
                        tableOffset -= ( adminBarHeight ? adminBarHeight : 32 );
                    }

                    $( 'html,body' ).animate( { scrollTop: tableOffset }, 300 );
                }
            } );

            // If 'search on click' enabled then add click handler for links in category, author and tags columns.
            // When clicked, the table will filter by that value.
            if ( $table.data( 'click-filter' ) ) {
                $table.on( 'click', 'a', function() {
                    let $link = $( this ),
                        idx = table.cell( $link.closest( 'td' ).get( 0 ) ).index().column, // get the column index
                        header = table.column( idx ).header(), // get the header cell
                        columnName = $( header ).data( 'name' ); // get the column name from header

                    // Is the column click filterable?
                    if ( -1 !== clickFilterColumns.indexOf( columnName ) ) {
                        table.search( $link.text() ).draw();
                        return false;
                    }

                    return true;
                } );
            }

        } ); // each table

        /**
         * Open Lightbox
         */
        $( document ).on( 'click', '.document-library-table a.dlw-lightbox', function( event ) {
            event.preventDefault();
            event.stopPropagation();

            const pswpElement = $( '.pswp' )[0];
            const $img = $( this ).find( 'img' );

            if ( $img.length < 1 ) {
                return;
            }

            const items = [ {
                src: $img.attr( 'data-large_image' ),
                w: $img.attr( 'data-large_image_width' ),
                h: $img.attr( 'data-large_image_height' ),
                title: ( $img.attr( 'data-caption' ) && $img.attr( 'data-caption' ).length ) ? $img.attr( 'data-caption' ) : $img.attr( 'title' )
            } ];

            const options = {
                index: 0,
                shareEl: false,
                closeOnScroll: false,
                history: false,
                hideAnimationDuration: 0,
                showAnimationDuration: 0
            };

            // Initializes and opens PhotoSwipe
            let photoswipe = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options );
            photoswipe.init();

            return false;
        } );

    } ); // end document.ready

} )( jQuery );