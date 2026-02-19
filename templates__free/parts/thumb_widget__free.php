<?php
defined( 'ABSPATH' ) || exit;
/**
 * @var mixed $data Custom data for the template.
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- template files escaped at output
 */
if ( $data->utilities->get_element( 'thumb', $data->args ) ) {
	?>
	<?php
	$use_thumb_width = ( 'card' !== ( $data->event->layout_name ?? '' ) );
	$thumb_width     = (int) $data->utilities->get_element( 'thumb_width', $data->args );
	?>
	<div class="eaw-thumb-wrap"
	     <?php if ( $use_thumb_width && $thumb_width ) : ?>style="max-width:<?php echo $thumb_width; ?>px"<?php endif; ?>>
                <span>
                 <?php
                 // Check if post has post thumbnail.
                 if ( ! empty( $data->utilities->get_event_logo_url() ) ) {
	                 // Thumbnails
	                 printf( '<a class="eaw-img %2$s" %1$s rel="bookmark" %6$s><img class="%2$s eaw-thumb eaw-default-thumb" src="%3$s" alt="%4$s" %5$s></a>',
		                 $data->event->booknow,
		                 esc_attr( $data->utilities->get_element( 'thumb_align', $data->args ) ),
		                 esc_url( $data->utilities->get_event_logo_url() ),
		                 esc_attr( $data->utilities->get_event_title() ),
		                 $use_thumb_width && $thumb_width ? 'width="' . $thumb_width . '"' : '',
		                 ( $data->utilities->get_element( 'newtab', $data->args ) ) ? 'target="_blank"' : ''
	                 );

	                 // Display default image.
                 } elseif ( ! empty( $data->utilities->get_element( 'thumb_default', $data->args ) ) ) {
	                 printf( '<a class="eaw-img %2$s" %1$s rel="bookmark" %6$s><img class="%2$s eaw-thumb eaw-default-thumb" src="%3$s" alt="%4$s" %5$s></a>',
		                 $data->event->booknow,
		                 esc_attr( $data->utilities->get_element( 'thumb_align', $data->args ) ),
		                 esc_url( $data->utilities->get_element( 'thumb_default', $data->args ) ),
		                 esc_attr( $data->utilities->get_event_title() ),
		                 $use_thumb_width && $thumb_width ? 'width="' . $thumb_width . '"' : '',
		                 ( $data->utilities->get_element( 'newtab', $data->args ) ) ? 'target="_blank"' : ''
	                 );
                 }
                 ?>
                 </span>
	</div>
	<?php
}

