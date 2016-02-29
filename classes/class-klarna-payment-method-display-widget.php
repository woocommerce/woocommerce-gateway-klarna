<?php

/**
 * Class WC_Klarna_Payment_Method_Display_Widget
 *
 * Using Klarna's Payment method display you generate trust and increase your conversion.
 *
 * @class        WC_Klarna_Payment_Method_Display_Widget
 * @version        1.0
 * @since        2.0
 * @category    Class
 * @author        Krokedil
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Klarna_Payment_Method_Display_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct( 'klarna_pmd', // Base ID
			__( 'Klarna Payment Method Display Widget', 'woocommerce-gateway-klarna' ), // Name
			array( 'description' => __( 'Displays an image that informs the consumer about the payment methods that are available in your store.', 'woocommerce-gateway-klarna' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		$design = ! empty( $instance['design'] ) ? $instance['design'] : 'short';
		$color  = ! empty( $instance['color'] ) ? $instance['color'] : 'blue';
		$width  = ! empty( $instance['width'] ) ? $instance['width'] : 440;

		switch ( get_locale() ) {
			case 'de_DE' :
				$klarna_locale = 'de_de';
				break;
			case 'no_NO' :
			case 'nb_NO' :
			case 'nn_NO' :
				$klarna_locale = 'nb_no';
				break;
			case 'fi_FI' :
			case 'fi' :
				$klarna_locale = 'fi_fi';
				break;
			case 'sv_SE' :
				$klarna_locale = 'sv_se';
				break;
			case 'de_AT' :
				$klarna_locale = 'de_at';
				break;
			case 'en_GB' :
				$klarna_locale = 'en_gb';
				break;
			default:
				$klarna_locale = 'sv_se';
		}

		echo "<img style='display:block;width:100%;height:auto;' src='https://cdn.klarna.com/1.0/shared/image/generic/badge/$klarna_locale/checkout/$design-$color.png?width=$width' />";

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$design = ! empty( $instance['design'] ) ? $instance['design'] : '';
		$color  = ! empty( $instance['color'] ) ? $instance['color'] : '';
		$width  = ! empty( $instance['width'] ) ? $instance['width'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'design' ) ); ?>"><?php _e( 'Design:' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'design' ) ); ?>"
			        name="<?php echo esc_attr( $this->get_field_name( 'design' ) ); ?>">
				<option value="long" <?php selected( $design, 'long' ); ?>>Long</option>
				<option value="short" <?php selected( $design, 'short' ); ?>>Short</option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>"><?php _e( 'Color:' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>"
			        name="<?php echo esc_attr( $this->get_field_name( 'color' ) ); ?>">
				<option value="blue" <?php selected( $color, 'blue' ); ?>>Blue</option>
				<option value="white" <?php selected( $color, 'white' ); ?>>White</option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'width' ) ); ?>"><?php _e( 'Width (px):' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'width' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'width' ) ); ?>" type="number"
			       value="<?php echo esc_attr( $width ); ?>" min="100"/>
		</p>
		<p>Read more about the options <a target="_blank"
		                                  href="http://developers.klarna.com/en/se+php/kco-v2/payment-method-display">here</a>.
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance           = array();
		$instance['design'] = ( 'long' == $new_instance['design'] || 'short' == $new_instance['design'] ) ? strip_tags( $new_instance['design'] ) : '';
		$instance['color']  = ( 'blue' == $new_instance['color'] || 'white' == $new_instance['color'] ) ? $new_instance['color'] : '';
		$instance['width']  = (int) $new_instance['width'];

		return $instance;
	}

}