( function( $ ) {

    'use strict';

    $( function() {

        $( document ).on( 'change', '.js-dep-location', function() {
            var $depField = $( this ).closest( 'form' ).find( '.js-field-' + $( this ).data('type') );

            if ( $( this ).val() ) {

                $depField.html( '<option>Loading...</option>' );

                $.get( Estatik.ajaxurl, { type: $( this ).data('type'), action: 'esm_get_dep_locations', term_id: $( this ).val() }, function( response ) {
                    response = response || {};

                    if ( response ) {

                        $depField.html( '<option>' + $depField.data("label") + '</option>' );
                        $depField.append( response );

                        $depField.find('[value='+$depField.data('value')+']').prop( 'selected', 'selected' );

                        $depField.trigger( 'change' );
                    }
                } );
            }
        } ).trigger( 'change' );
        
        $( '.js-dep-location' ).each( function () {
            if ( $( this ).data( 'value' ) ) {
                $( this ).val( $( this ).data( 'value' ) ).trigger( 'change' );
            }
        } );
    } );
} )( jQuery );
