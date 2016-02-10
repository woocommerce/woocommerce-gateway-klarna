<?php
/**
 * Klarna PClasses for KPM
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Klarna_PClasses {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $klarna = false, $type = false, $country = false ) {

		$this->klarna  = $klarna;
		$this->country = $country;
		$this->type    = $type;

	}


	/**
	 * Retrieves PClasses for country.
	 *
	 * @since 1.0.0
	 *
	 * @return $pclasses Key value array of countries and their PClasses
	 */
	function get_pclasses_for_country_and_type() {

		$pclasses_country_all  = $this->fetch_pclasses();
		$pclasses_country_type = array();
		unset( $pclasses_country_type );

		if ( $pclasses_country_all ) {
			foreach ( $pclasses_country_all as $pclass ) {
				if ( in_array( $pclass->getType(), $this->type ) ) { // Passed from parent file
					$pclasses_country_type[] = $pclass;
				}
			}
		}

		if ( ! empty( $pclasses_country_type ) ) {
			return $pclasses_country_type;
		}

	}


	/**
	 * Displays available PClasses in admin settings page.
	 *
	 * @since 1.0.0
	 */
	function display_pclasses_for_country_and_type() {

		$pclasses = $this->get_pclasses_for_country_and_type( $this->country, $this->type );
		if ( $pclasses ) { ?>
			<h5 style="margin-bottom:0.25em;"><?php echo $this->country; ?></h5>
			<?php
			$pclass_string = '';
			foreach ( $pclasses as $pclass ) {
				if ( in_array( $pclass->getType(), $this->type ) ) { // Passed from parent file
					$pclass_string .= $pclass->getDescription() . ', ';
				}
			}
			$pclass_string = substr( $pclass_string, 0, - 2 );
			?>
			<p style="margin-top:0;"><?php echo $pclass_string; ?></p>
		<?php }

	}

	/**
	 * Retrieve the PClasses from Klarna
	 *
	 * @since 1.0.0
	 */
	function fetch_pclasses() {

		$klarna = $this->klarna;

		if ( $klarna->getPClasses() ) {
			return $klarna->getAllPClasses();
		} else {
			try {
				// You can specify country (and language, currency if you wish) if you don't want 
				// to use the configured country.
				$klarna->fetchPClasses( $this->country );

				return $klarna->getAllPClasses();
			} catch ( Exception $e ) {
				return false;
			}
		}

	}


}