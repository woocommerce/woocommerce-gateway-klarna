<?php
/**
 * This displays shipping in KCO widget.
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 2.0.0
 *
 * @package WC_Gateway_Klarna
 */

?>
<tr>
	<td style="text-align:right">
		<?php
			WC()->cart->calculate_shipping();
			$packages = WC()->shipping->get_packages();
			foreach ( $packages as $i => $package ) {
				$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
				$available_methods = $package['rates'];
				$show_package_details = sizeof( $packages ) > 1;
				$index = $i;
				?>
					<?php if ( ! empty( $available_methods ) ) { ?>
			
						<?php if ( 1 === count( $available_methods ) ) {
							$method = current( $available_methods );
							echo wp_kses_post( wc_cart_totals_shipping_method_label( $method ) ); ?>
							<input type="hidden" name="shipping_method[<?php echo $index; ?>]" data-index="<?php echo $index; ?>" id="shipping_method_<?php echo $index; ?>" value="<?php echo esc_attr( $method->id ); ?>" class="shipping_method" />
			
						<?php } else { ?>
			
							<ul id="shipping_method">
								<?php foreach ( $available_methods as $method ) : ?>
									<li>
										<input type="radio" name="shipping_method[<?php echo $index; ?>]" data-index="<?php echo $index; ?>" id="shipping_method_<?php echo $index; ?>_<?php echo sanitize_title( $method->id ); ?>" value="<?php echo esc_attr( $method->id ); ?>" <?php checked( $method->id, $chosen_method ); ?> class="shipping_method" />
										<label for="shipping_method_<?php echo $index; ?>_<?php echo sanitize_title( $method->id ); ?>"><?php echo wp_kses_post( wc_cart_totals_shipping_method_label( $method ) ); ?></label>
									</li>
								<?php endforeach; ?>
							</ul>
			
						<?php } ?>
			
					<?php } ?>				
				<?php
			}
		?>
	</td>
	<td class="kco-rightalign">30kr</td>
</tr>
