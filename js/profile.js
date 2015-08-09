jQuery( function( $ ) {
	function xltTotpAuthChange( event ) {
		var checked = $( this ).is( ":checked" );
		if ( checked ) {
			$( '.secrow' ).show();
		} else {
			$( '.secrow' ).hide();
		}
	}

	$( '#token_auth' ).on( 'change', xltTotpAuthChange );
	$( '#submit' ).click( function() {
		if ( !$( '#token_auth' ).is( ':checked' ) ) {
			return true;
		}
		var l = $( '#token_auth_code' ).val().length;
		if ( l !== 10 ) {
			alert( 'Secret code must be 10 chars long.' );
			return false;
		}
		return true;
	} );
	$( '#newtoken' ).click(
		function() {
			$( '#waito' ).show();
			$.post(
				ajaxurl,
				{
					action: 'xlttotpauth_newtoken'
				},
				function( data ) {
					if ( data.res == 0 ) {
						$( '#token_auth_code' ).val( data.code );
					}
					$( '#waito' ).hide();
				},
				json
			).error( function() {
					alert( 'Communication error' );
					$( '#waito' ).hide();
				} );
			return false;
		}
	);
} );