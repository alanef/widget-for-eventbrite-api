<?php
/**
 * @var mixed $data Custom data for the template.
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- template files escaped at output
 */
?>
<div>
	<p class='not-available'><?php echo esc_html__( 'Display EventBrite Layout:', 'widget-for-eventbrite-api' ) . ' ' . esc_html($data->event->layout_name) . ' :' . esc_html__( 'is not available in your plan. contact your web admin to upgrade your plan', 'widget-for-eventbrite-api' ); ?></p>
    <hr>
</div>