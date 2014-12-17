(function($) {

	// console.log( 'change' );

	$('#klarna_account_pclass').live('change', function() {

		// console.log( 'change' );
		var selectedOption = $(this).find('option:selected');
		// console.log( $(selectedOption).data());
				
		var klarnaDetails = '<ul style="list-style:none">';
		$.each($( selectedOption ).data(), function(i, v) {
			console.log( i + ': ' + v );
			if ( i.match('^details') ) {
				klarnaDetails += '<li>' + v + '</li>';
			}
		});
		klarnaDetails += '</ul>';

		if ( $( selectedOption ).data( 'use_case' ) ) {
			klarnaDetails += '<p>' + $( selectedOption ).data( 'use_case' ) + '</p>';
		}

		if ( $( selectedOption ).data( 'terms_uri' ) ) {
			klarnaDetails += '<p>' + $( selectedOption ).data( 'terms_uri' ) + '</p>';
		}
		
		if ( $( selectedOption ).data( 'logo_uri' ) ) {
			klarnaDetails += '<div><img src="' + $( selectedOption ).data( 'logo_uri' ) + '" width="100" /></div>';
		}
		// console.log(klarnaDetails);

		$('#klarna-pms-details').html(klarnaDetails);

	});

})(jQuery);