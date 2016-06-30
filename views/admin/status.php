<?php
/**
 * Admin View: Page - Status Report.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Klarna">
			<h2><?php _e( 'Klarna Checkout', 'woocommerce' ); ?><?php echo wc_help_tip( __( 'Klarna Checkout System Status.', 'woocommerce-gateway-klarna' ) ); ?></h2>
		</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
	$kco_countries     = array(
		'se' => 'Sweden',
		'no' => 'Norway',
		'fi' => 'Finland',
		'de' => 'Germany',
		'at' => 'Austria',
		'uk' => 'UK',
		'us' => 'USA'
	);
	?>
	<tr>
		<td data-export-label="Test mode">Test mode:</td>
		<td class="help">&nbsp;</td>
		<td>
			<?php
			if ( $checkout_settings['testmode'] ) {
				echo '<strong>Enabled</strong><br /> <span style="font-size:0.8em;">Your Klarna Checkout account must be in test mode for all configured countries in order for it to work, please get in touch with Klarna to check your account status</span>';
			} else {
				echo '<strong>Disabled</strong><br /> <span style="font-size:0.8em;">Your Klarna Checkout account must be in live mode for all configured countries in order for it to work, please get in touch with Klarna to check your account status</span>';
			}
			?>
		</td>
	</tr>
	<?php
	foreach ( $kco_countries as $kco_country_key => $kco_country_name ) { ?>
	<tr>
		<td data-export-label="<?php echo $kco_country_name; ?>"><?php echo $kco_country_name; ?>:</td>
		<td class="help">&nbsp;</td>
		<td>
		<?php
		$errors = array();
		if ( '' == $checkout_settings[ 'eid_' . $kco_country_key ] ) {
			$errors[] = 'Eid not set ';
		}
		if ( '' == $checkout_settings[ 'secret_' . $kco_country_key ] ) {
			$errors[] = 'Secret key not set ';
		}
		if ( '' != $checkout_settings[ 'klarna_checkout_url_' . $kco_country_key ] ) {
			$page = url_to_postid( $checkout_settings[ 'klarna_checkout_url_' . $kco_country_key ] );

			if ( 0 == $page ) {
				$errors[] = 'Klarna Checkout page points to an invalid URL ';
			} else {
				$kco_page = get_post( $page );
				if ( ! has_shortcode( $kco_page->post_content, 'woocommerce_klarna_checkout' ) ) {
					$errors[] = 'Klarna Checkout page doesn\'t contain [woocommerce_klarna_checkout] shortcode ';
				}
			}
		} else {
			$errors[] = 'Checkout URL not set';
		}

		if ( empty( $errors ) ) {
			echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
		} else {
			foreach ( $errors as $error ) {
				echo $error . '<br />';
			}
		}
		?>
		</td>
	</tr>
	<?php } ?>
	</tbody>
</table>
