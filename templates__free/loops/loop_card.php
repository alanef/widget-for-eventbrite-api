<?php
/**
 * @var mixed $data Custom data for the template.
 */
?>

<article class="wfea-card-list-item">
    <div class="wfea-card-item">
    	<?php $data->template_loader->get_template_part( 'thumb_widget' . $data->event->plan ); ?>
        <div class="eaw-content-wrap">
            <?php $data->template_loader->get_template_part( 'calendar_date__free' ); ?>
            <div class="eaw-content-block">
                <?php $data->template_loader->get_template_part( 'title_widget' . $data->event->plan ); ?>
                <?php $data->template_loader->get_template_part( 'date_widget'  ); ?>
	            <?php $data->template_loader->get_template_part( 'venue' . $data->event->plan ); ?>
	            <?php $data->template_loader->get_template_part( 'location' . $data->event->plan ); ?>
                <div class="eaw-buttons">
                    <button class="eaw-button-details"><?php echo apply_filters('wfea_layout_card_details',esc_html__( 'Details', 'widget-for-eventbrite-api')) ; ?>
                    <div class="eaw-card-details">
	                    <?php  $data->template_loader->get_template_part( 'excerpt_widget' ); ?>
                    </div>
                    </button>
                    <?php $data->template_loader->get_template_part( 'booknow' . $data->event->plan ); ?>
                </div>
            </div>
        </div>
    </div>
</article>


