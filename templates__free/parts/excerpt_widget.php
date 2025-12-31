<?php
defined( 'ABSPATH' ) || exit;
/**
 * @var mixed $data Custom data for the template.
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- template files escaped at output
 */
if ( $data->utilities->get_element( 'excerpt', $data->args ) ) {
	?>
    <div class="eaw-summary">
	    <?php
		// Use get_excerpt_text() which respects long_description setting
		$wfea_excerpt_source = $data->utilities->get_excerpt_text();
	    echo wp_trim_words( apply_filters( 'eawp_excerpt', $wfea_excerpt_source ), $data->utilities->get_element( 'length', $data->args ), ' &hellip;' );
	    if ( $data->utilities->get_element( 'readmore', $data->args ) ) {
		    printf( '<a href="%1$s" %3$s aria-label="%4$s" class="more-link">%2$s</a>',
			    esc_url( $data->utilities->get_event_eb_url() ),
			    wp_kses_post( $data->utilities->get_element( 'readmore_text', $data->args ) ),
			    ( $data->utilities->get_element( 'newtab', $data->args ) ) ? 'target="_blank"' : '',
			    ( empty( $data->utilities->get_element( 'aria_label_readmore', $data->args ) ) ) ? esc_attr( $data->utilities->get_element( 'readmore_text', $data->args ) ) . ' ' . __( 'on Eventbrite for', 'widget-for-eventbrite-api' ) . ' ' . esc_attr( $data->utilities->get_event_title() ) : esc_attr( $data->utilities->get_element( 'aria_label_readmore', $data->args ) )
		    );
	    }
	    $data->template_loader->get_template_part( 'full_modal_details_button' );
	    ?>
    </div>
	<?php
}
