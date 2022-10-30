<?php if ( $data->utilities->get_element( 'date', $data->args ) ): ?>
    <div class="eaw-calendar-date">
        <?php
            $timestamp = strtotime( $data->utilities->get_event_start()->local );
        ?>
        <div class="eaw-calendar-date-month"><?php echo date('M', $timestamp); ?></div>
        <div class="eaw-calendar-date-day"><?php echo date('j', $timestamp); ?></div>
    </div>
<?php endif;
