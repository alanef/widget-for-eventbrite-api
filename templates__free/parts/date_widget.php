<?php
/**
 * @var mixed $data Custom data for the template.
 */
if ( $data->utilities->get_element( 'date', $data->args ) ) {
		$date = $data->utilities->get_event_time($data->args);
		printf( '<time class="eaw-time published" datetime="%1$s">%2$s</time>', esc_html( get_the_modified_date( 'c' ) ), esc_html( $date ) );
}
