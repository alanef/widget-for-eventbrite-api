<?php
defined( 'ABSPATH' ) || exit;
/**
 * @var mixed $data Custom data for the template.
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- template files escaped at output
 */
if ( $data->utilities->get_element( 'date', $data->args ) ): ?>
    <div class="eaw-calendar-date">
        <?php
            $wfea_timestamp = strtotime( $data->utilities->get_event_start()->local );
        ?>
        <div class="eaw-calendar-date-month"><?php echo date_i18n('M', $wfea_timestamp); ?></div>
        <div class="eaw-calendar-date-day"><?php echo date_i18n('j', $wfea_timestamp); ?></div>
    </div>
<?php endif;
