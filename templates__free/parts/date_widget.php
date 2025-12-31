<?php
defined( 'ABSPATH' ) || exit;
/**
 * @var mixed $data Custom data for the template.
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- template files escaped at output
 */
if ( $data->utilities->get_element( 'date', $data->args ) ) {
		$wfea_date = $data->utilities->get_event_time($data->args);
		printf( '<time class="eaw-time published" datetime="%1$s">%2$s</time>', esc_html( get_the_modified_date( 'c', $data->utilities->get_event() ) ), esc_html( $wfea_date ) );
}
