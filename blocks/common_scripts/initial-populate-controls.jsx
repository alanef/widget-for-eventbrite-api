// hooks.js
import {useEffect} from '@wordpress/element';
import { populateOptionsForControl } from './populate-controls.jsx';

export function usePopulation(setAttributes, attributes) {
    useEffect(() => {
        (function ($) {
            $(function () {
                try {
                    $("[data-eb-id]").wfeaBindLinksToEB();
                } catch (e) {
                    // that is OK just no popup
                }
            });
        })(jQuery);
        populateOptionsForControl({setAttributes, action: 'fetch_api_key_options', optionToUpdate: 'apiKeyOptions'});
        populateOptionsForControl({
            setAttributes,
            token: attributes['api_key'],
            action: 'fetch_organizations_for_key',
            optionToUpdate: 'organizationOptions'
        });
        populateOptionsForControl({
            setAttributes,
            token: attributes['api_key'],
            organizationID: attributes['organization_id'],
            action: 'fetch_events_for_key',
            optionToUpdate: 'eventOptions'
        });
        populateOptionsForControl({
            setAttributes,
            token: attributes['api_key'],
            organizationID: attributes['organization_id'],
            action: 'fetch_organizers_for_key',
            optionToUpdate: 'organizerOptions'
        });
        populateOptionsForControl({
            setAttributes,
            token: attributes['api_key'],
            organizationID: attributes['organization_id'],
            action: 'fetch_venues_options',
            optionToUpdate: 'venueOptions'
        });
    }, []);
}