(function( $ ) {

    'use strict';

    $(document).ready(function(){

        var $ajaxInAction = false;

        $( '#export-logs' ).on( 'click', function( e ){

            if( $(this).attr( 'href' ) === '#' && ! $ajaxInAction ) {

                var linkElement = $(this);

                linkElement.addClass( 'disabled' ).text( 'Wait...' );
                $ajaxInAction = true;

                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'ct_export_logs',
                        nonce : cleantalk_debug.nonce
                    },
                    success: function( response ) {

                        linkElement.removeClass( 'disabled' );

                        if( response.success ) {

                            //$( '#export-logs' ).attr( 'href', response.data ).attr( 'download', '' ).text( 'Download the log' );
                            var url = URL.createObjectURL(new Blob([response.data]));

                            var dummy = document.createElement('a');
                            dummy.href = url;
                            dummy.download = 'skipped_log.csv';

                            document.body.appendChild(dummy);
                            dummy.click();
                        } else {

                            console.log( response.data );

                        }

                        $ajaxInAction = false;

                    }
                });

            }

        });

    });

})( jQuery );