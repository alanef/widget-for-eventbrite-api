jQuery( function( $ ) {
    const updateWidgetContent = ( params, widgetPreviewElement ) => {
        jQuery.ajax({
            url: customAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_widget_content',
                nonce: customAjax.nonce,
                params: params
            },
            success: function(response) {
                widgetPreviewElement.find( '.wfea-preview' ).html( response );
                if ( typeof wfea_render_calendar === 'function' ) {
                    wfea_render_calendar();
                }
                widgetPreviewElement.find( '[data-eb-id]' ).wfeaBindLinksToEB();
            }
        });
    };

    if ( typeof elementorFrontend === 'undefined' ) {
        return;
    }
 
    const getShortcodeArgs = ( $scope ) => {
        const widgetInstance = elementorFrontend.config.elements.data[ $scope.data('model-cid') ];
        const data = Object.keys( widgetInstance.attributes )
        .filter( key => key.startsWith( 'wfea_' ) )
        .reduce( ( result, key ) => {
            if ( isNonBooleanAttribute( key ) ) {
                result[ key.slice(5) ] = widgetInstance.attributes[key];
            } else {
                result[ key.slice(5) ] = !! widgetInstance.attributes[key];
            }

            return result;
        }, {});

        return data;
    };

    const isNonBooleanAttribute = ( attribute ) => {
        const nonBooleanAtts = [ 'wfea_layout', 'wfea_set_style_venue', 'wfea_set_style_slider', 'wfea_limit', 'wfea_search', 'wfea_filter_location', 'wfea_filter_title', 'wfea_tags', 'wfea_start_date_range_start', 'wfea_start_date_range_end', 'wfea_eb_id', 'wfea_organizer_id', 'wfea_organization_id', 'wfea_collection_id', 'wfea_venue_id', 'wfea_booknow_text', 'wfea_css_class', 'wfea_cssID', 'wfea_thumb_default', 'wfea_length',
                                'wfea_readmore_text', 'wfea_no_events_found_text', 'wfea_online_events_address_text', 'wfea_location_title', 'wfea_tickets_at_the_door_button', 'wfea_canceled_event_button', 'wfea_coming_soon_button', 'wfea_link_custom_page', 'wfea_past_event_button', 'wfea_postponed_button', 'wfea_sales_ended_button', 'wfea_sold_out_button',
                                'wfea_started_event_button', 'wfea_unavailable_button', 'wfea_search_box_button', 'wfea_search_box_text', 'wfea_category_id', 'wfea_subcategory_id', 'wfea_subcategory_id', 'wfea_format_id', 'wfea_order_by', 'wfea_location', 'wfea_paginate_position', 'wfea_status', 'wfea_show_availability', 'wfea_events_per_page', 'wfea_api_key',
                                'wfea_style', 'wfea_accordion_tab_attr', 'wfea_cal_default_view', 'wfea_cal_list_header_left', 'wfea_cal_list_header_right', 'wfea_cal_list_days', 'wfea_thumb_align', 'wfea_thumb_width', 'wfea_filter_by_attr', 'wfea_order_by_attr' ];
        return nonBooleanAtts.includes( attribute );
    };

    if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hasOwnProperty( 'hooks' ) ) {
        elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function( $scope ) {
            if ( $scope.data( 'widget_type') !== 'eventbrite-widget.default' ) {
                return;
            }
    
            const data = getShortcodeArgs( $scope );
            data['css_class'] = data['css_class'] + ' wfea-blocks';
            updateWidgetContent( data, $scope );
        } );
    }
} );

jQuery( document ).on( 'change', ( e ) => {
    if ( e.target.dataset.setting !== 'wfea_start_date_range_start' && e.target.dataset.setting !== 'wfea_start_date_range_end' ) {
        return;
    }

    jQuery.ajax({
        url: customAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'validate_date',
            nonce: customAjax.nonce,
            wfea_date_value: e.target.value
        },
        success: function( response ) {
            const controlContainer = e.target.closest( '.elementor-control-content' );
            if ( ! controlContainer ) {
                return;
            }
            if ( ! response ) {
                if (  ! controlContainer.querySelector( '.message' ) ) {
                    jQuery( controlContainer ).append( jQuery( '<p class="message">Could not convert the date try something like last day of march</p>' ) );
                }
            } else {
                controlContainer.querySelector( '.message' )?.remove();
            }
        }
    });
});