<?php
/**
 * @var mixed $data Custom data for the template.
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- template files escaped at output
 */
?>
<h3 class="eaw-title">
	<?php
	printf( '<a %1$s title="%2$s" rel="bookmark" %4$s>%3$s</a>',
		$data->event->booknow,
		sprintf(
                // translators: placeholder is title attribute
                esc_attr__( 'Eventbrite link to %1$s', 'widget-for-eventbrite-api' ), the_title_attribute( array( 'echo' => false, 'post' => $data->utilities->get_event() ) ) ),
		the_title_attribute( array( 'echo' => false, 'post' => $data->utilities->get_event() ) ),
		( $data->utilities->get_element( 'newtab', $data->args ) ) ? 'target="_blank"' : ''
	);
	?>
</h3>
