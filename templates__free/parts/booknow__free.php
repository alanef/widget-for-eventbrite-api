<?php
defined( 'ABSPATH' ) || exit;
/**
 * @var mixed $data Custom data for the template.
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- template files escaped at output
 */
if ( property_exists( $data->event, 'cta' ) ) {
	$wfea_cta_text = $data->event->cta->text;
} else {
	$wfea_cta_text = $data->utilities->get_element( 'booknow_text', $data->args );
}
if ( $data->utilities->get_element( 'booknow', $data->args ) ) {
	?>
    <div class="eaw-booknow"> <?php
	switch ( $data->template ) {
		case 'divi':
			$wfea_button_markup = '<a %1$s class="wfea-button submit et_pb_button" %3$s  aria-label="%2$s %5$ %4$s">%2$s</a>';
			break;
		default:
			$wfea_button_markup = '<a %1$s class="wfea-button button" %3$s  aria-label="%2$s %5$ %4$s">%2$s</a>';
	}
	printf( $wfea_button_markup,
		$data->event->booknow,
		wp_kses_post( $wfea_cta_text ),
		( $data->utilities->get_element( 'newtab', $data->args ) ) ? 'target="_blank"' : '',
		esc_attr( $data->utilities->get_event_title() ),
		__( 'on Eventbrite for', 'widget-for-eventbrite-api' )
	);
	?></div><?php
}
