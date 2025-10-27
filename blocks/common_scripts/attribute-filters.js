import {__} from '@wordpress/i18n';

import {
    CheckboxControl,
    Dashicon,
    __experimentalNumberControl as NumberControl
} from '@wordpress/components';

import {Fragment} from '@wordpress/element';
import {addFilter} from '@wordpress/hooks';
import { bool } from 'prop-types';
import { v4 as uuid } from "uuid";
import {ControlsForPlan} from './inspector.jsx';

const indexCard = () => (
    <Dashicon icon="index-card"/>
);

const unorderedList = () => (
    <Dashicon icon="list-view"/>
);

document.addEventListener('focusout', (el) => {
    /**
     * We are using the isPressEnterToChange property on <InputControl> components to avoid rerendering on keyboard input.
     * But we still want to update the block to rerender when user leaves a text input.
     */
    if (!el.classList || !el.classList.contains('wfea_input')) {
        return;
    }
    el.click();
});

addFilter('wfea-block-layouts',
    'widget-for-eventbrite-api/display-eventbrite-events',
    function (options, setAttributes) {
        return [
            {
                title: 'Widget',
                icon: unorderedList,
                onClick: () => {
                    setAttributes({limit: 5})
                    setAttributes({layout: 'widget'});
                },
            },
            {
                title: 'Card',
                icon: indexCard,
                onClick: () => {
                    setAttributes({limit: 5})
                    setAttributes({layout: 'card'});
                },
            }
        ];
    },
    1
);

addFilter('wfea-block-display',
    'widget-for-eventbrite-api/display-eventbrite-events',
    function (component, setAttributes, attributes) {
        return (
            <Fragment>
                <ControlsForPlan { ...{optionType: "display", attributes, setAttributes, booleanOnly:true }} />
            </Fragment>
        );
    },
    1
);

addFilter('wfea-block-filters',
    'widget-for-eventbrite-api/display-eventbrite-events',
    function (component, setAttributes, attributes) {
        return (
            <Fragment>
                <ControlsForPlan { ...{optionType: "filtering", attributes, setAttributes, booleanOnly:true }} />
            </Fragment>
        );
    },
    1
);

addFilter('wfea-block-enablers',
    'widget-for-eventbrite-api/display-eventbrite-events',
    function (component, setAttributes, attributes) {
        return (
            <Fragment>
                <ControlsForPlan { ...{optionType: "enabling", attributes, setAttributes, booleanOnly:true }} />
            </Fragment>
        );
    },
    1
);


