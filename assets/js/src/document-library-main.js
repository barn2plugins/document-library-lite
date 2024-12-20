
( function( $ ) {
    $( document ).ready( function() {
        let tables = $( '.document-library-table' );
        const adminBar = $( '#wpadminbar' );
        const clickFilterColumns = [ 'doc_categories' ];

        tables.each( function() {
            let $table = $( this ),
                config = {
                    responsive: true,
                    processing: true,
                    serverSide: document_library_params.lazy_load,
                    language: {
                        processing: '<div class="dots-loader">' +
                                    '<div class="dot"></div>' +
                                    '<div class="dot"></div>' +
                                    '<div class="dot"></div>' +
                                    '</div>'
                    }
                };
            this.id = $table.attr( 'id' );

            // Set language - defaults to English if not specified
            if ( ( typeof document_library !== 'undefined' ) && document_library.langurl ) {
                config.language = { url: document_library.langurl };
            }

            // Set the ajax URL if the lazy load is enabled
            if( document_library_params.lazy_load ) {
                config.ajax = {
                    url: document_library_params.ajax_url,
                    type: 'POST',
                    data: {
                        table_id: this.id,
                        action: document_library_params.ajax_action,
                        category: $(".category-search-" + this.id.replace( 'document-library-', '' )).val(),
                        args: document_library_params.args,
                        _ajax_nonce: document_library_params.ajax_nonce
                    },
                }
            }

            // Set the column classes
            let columns = document_library_params.columns;
            if( typeof columns === 'object' ) {
                columns = Object.values(columns);
            }
            let column_classes = [];
            columns.forEach((column) => {
                column_classes.push( { 
                    'className': 'col-' + column.trimStart(),
                    'data': column.trimStart()
                } );
            });
            config.columns = column_classes;
            
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

            // Change the animation for the loading state
            // Listen to the processing event
            $table.on('processing.dt', function(e, settings, processing) {
                if (processing) {
                    $table.find( 'tbody' ).addClass( 'loading' );  // Show custom loader
                } else {
                    $table.find( 'tbody' ).removeClass( 'loading' );  // Hide custom loader
                }
            });

            // Add category parameter just before the AJAX request is sent
            table.on('preXhr.dt', function(e, settings, data) {
                data.category = $(".category-search-" + this.id.replace( 'document-library-', '' )).val()
            });

            // If 'search on click' enabled then add click handler for links in category, author and tags columns.
            // When clicked, the table will filter by that value.
            if ( $table.data( 'click-filter' ) ) {
                $table.on( 'click', 'a', function() {

                    // Don't filter the table when opening the lightbox
                    if( $(this).hasClass( 'dlw-lightbox' ) ) {
                        return;
                    }
                              
                    let $link = $( this ), idx;
                    if( table.cell( $link.closest( 'td' ).get( 0 ) ).index() ) {
                        idx = table.cell( $link.closest( 'td' ).get( 0 ) ).index().column; // get the column index
                    }
                    // If the element is in a child row
                    if( $link.closest( 'td' ).hasClass( 'child' ) ) {
                        let parentLi = $link.closest( 'li' ).attr('class').split(' ')[0];
                        idx = table.cell( $('td.' + parentLi) ).index().column; // get the column index
                    }
                    let header = table.column( idx ).header(), // get the header cell
                    columnName = $( header ).data( 'name' ); // get the column name from header
                    // Is the column click filterable?
                    if ( -1 !== clickFilterColumns.indexOf( columnName ) ) {
                        if( ! document_library_params.lazy_load ) {
                            table.search( $link.text() ).draw();
                        }
                        else {
                            $( ".category-search-" + config.ajax.data.table_id.replace( "document-library-", "" ) ).val( $link.text() );
                            table.draw();   
                        }
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