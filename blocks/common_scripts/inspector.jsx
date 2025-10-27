import {InspectorControls} from '@wordpress/block-editor';
import {Panel, PanelBody, ExternalLink} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import {
    __experimentalInputControl as InputControl,
    __experimentalNumberControl as NumberControl,
    SelectControl,
    CheckboxControl
} from '@wordpress/components';
import {v4 as uuid} from "uuid";
import {applyFilters} from '@wordpress/hooks';
const {can_use_premium_code, is_plan_silver, is_plan_gold, is_plan_platinum} = wfea_freemius;


/* @TODO this can be retired by adding into helper conditions */
export function filterOptionsAvailableForCalendarBlock() {
    const calendarOptions = JSON.parse(JSON.stringify(wfea_controls_meta));
    const optionTypes = ['common', 'display', 'enabling', 'filtering', 'selection', 'settings'];
    const excludedOptions = ['wfea_booknow', 'wfea_booknow_text', 'wfea_cssid', 'wfea_css_class', 'wfea_date', 'wfea_debug', 'wfea_excerpt', 'wfea_order_by', 'wfea_readmore_text', 'wfea_thumb_align', 'wfea_thumb_width', 'wfea_widgetwrap', 'wfea_add_to_cal_button', 'wfea_eb_id', 'wfea_first_of_series', 'wfea_no_events_found_text', 'wfea_online_events_address_text', 'wfea_show_date_time', 'wfea_show_end_date', 'wfea_show_end_time', 'wfea_social_share_button', 'wfea_start_date_range_end', 'wfea_start_date_range_start', 'wfea_set_style_venue', 'wfea_set_style_slider', 'wfea_accordion_tab_attr', 'wfea_api_key', 'wfea_api_key_name', 'wfea_canceled_event_button', 'wfea_coming_soon_button', 'wfea_long_description_modal', 'wfea_past_event_button', 'wfea_postponed_button', 'wfea_sales_ended_button', 'wfea_sold_out_button', 'wfea_started_event_button', 'wfea_tickets_at_the_door_button', 'wfea_unavailable_button', 'wfea_events_per_page', 'wfea_paginate_position', 'wfea_paged'];
    optionTypes.forEach(optionType => {
        const controlsForOptionType = calendarOptions[optionType];
        Object.keys(controlsForOptionType).forEach(licenseType => {
            calendarOptions[optionType][licenseType] = calendarOptions[optionType][licenseType].filter(singleOption => {
                return !excludedOptions.includes(singleOption.name);
            })
        });
    })

    return calendarOptions;
};

let optionsAvailableForBlock = {};

export function prepareOptionsAvailableForBlock(isCalendarBlock) {
    if (!isCalendarBlock) {
        optionsAvailableForBlock = JSON.parse(JSON.stringify(wfea_controls_meta));
        return;
    }
    optionsAvailableForBlock = filterOptionsAvailableForCalendarBlock();
};

const getOptionsForControl = (attributes, item) => {
    let options;
    if (item.name === 'wfea_eb_id') {
        options = attributes['eventOptions'];
    } else if (item.name === 'wfea_organization_id') {
        options = attributes['organizationOptions'];
    } else if (item.name === 'wfea_organizer_id') {
        options = attributes['organizerOptions'];
    } else if (item.name === 'wfea_venue_id') {
        options = attributes['venueOptions'];
    } else {
        options = item.args.options;
    }

    return Object.entries(options).map(([value, label]) => {
        return {label: label, value: value};
    });
};

const ControlsForPlan = ({optionType, attributes, setAttributes, booleanOnly = false}) => {
    let optionsForLicense = [];
    prepareOptionsAvailableForBlock(attributes.isCalendarBlock);

    optionsAvailableForBlock = applyFilters('wfea-dynamic-options', optionsAvailableForBlock);

    if (typeof optionsAvailableForBlock[optionType] !== 'undefined' && typeof optionsAvailableForBlock[optionType]['free'] !== 'undefined') {
        optionsForLicense[optionType] = optionsAvailableForBlock[optionType]['free'];
    }

    optionsForLicense = applyFilters('wfea-options-for-license', optionsForLicense, optionType, attributes);

    for (let plans of Object.values(optionsForLicense)) {  // iterate through values of optionsForLicense

        for (let planKey in plans) {
            let option = plans[planKey];

            // Check if this option has any conditions
            if (!option.args || !option.args.condition) {
                continue;
            }

            for (let [conditionKey, conditionValue] of Object.entries(option.args.condition)) {
                let isNotCondition = conditionKey.endsWith('!');
                let actualKey = isNotCondition ? conditionKey.slice(0, -1) : conditionKey;
                let conditionAttribute = actualKey.replace('wfea_', '');

                // Check attributes existence
                if (!attributes.hasOwnProperty(conditionAttribute)) {
                   // console.log(`Attribute: ${conditionAttribute} does not exist`);
                    delete plans[planKey];
                    break;
                }

                // Check "!" conditions
                if (isNotCondition && (
                    (Array.isArray(conditionValue) && conditionValue.includes(String(attributes[conditionAttribute]))) ||
                    String(attributes[conditionAttribute]) === String(conditionValue)
                )) {
                   // console.log(`Attribute: ${conditionAttribute} should not equal conditionValue in a not condition`);
                    delete plans[planKey];
                    break;
                }

                // Check "=" conditions and "includes" conditions and not array match
                else if (!isNotCondition) {
                    if (String(attributes[conditionAttribute]) !== String(conditionValue) ||
                        (Array.isArray(conditionValue) && !conditionValue.includes(String(attributes[conditionAttribute])))) {
                        // console.log(`Attribute: ${conditionAttribute} does not meet = or includes condition`);
                        delete plans[planKey];
                        break;
                    }
                }
            }
        }
    }

    let booleanOptions = [];
    let textOptions = [];
    let numberOptions = [];
    let selectOptions = [];
    if ( optionsForLicense[optionType] ) {
        if (booleanOnly) {
            booleanOptions = optionsForLicense[optionType].filter(option => {
                return !option.hasOwnProperty('type');
            });
        } else {
            textOptions = optionsForLicense[optionType].filter(option => {
                // switch eb_id and organization_id to select as elementor cant hack it
                return option.hasOwnProperty('type') && option.type === 'text' && ['wfea_eb_id', 'wfea_organization_id'].indexOf(option.name) === -1;
            });
            numberOptions = optionsForLicense[optionType].filter(option => {
                return option.hasOwnProperty('type') && option.type === 'number';
            });
            selectOptions = optionsForLicense[optionType].filter(option => {
                // switch eb_id and organization_id to select as elementor cant hack it
                return option.hasOwnProperty('type') && (option.type === 'select2' || option.type === 'select' || ['wfea_eb_id', 'wfea_organization_id'].indexOf(option.name) !== -1);
            });
        }
    } else {
        return (
            <div style={{ padding: '20px' }}>
                <ExternalLink href="/wp-admin/options-general.php?billing_cycle=annual&page=widget-for-eventbrite-api-settings-pricing">
                    {__('Upgrade', 'widget-for-eventbrite-api')}
                </ExternalLink>  {__(' for more options. See all options in our ', 'widget-for-eventbrite-api')}
                <ExternalLink href="https://fullworksplugins.com/products/widget-for-eventbrite/eventbrite-shortcode-demo/?mtm_campaign=block&mtm_kwd=side%20link">
                    {__('demo page', 'widget-for-eventbrite-api')}
                </ExternalLink>

            </div>
        )
    }


    return (
        <>
            {
                booleanOptions.map((item) => {
                    const trimmedName = item.name.replace('wfea_', '');
                    return (
                        <div style={{whiteSpace: "nowrap"}} key={uuid()}>
                            <CheckboxControl
                                key={uuid()}
                                label={item.label}
                                onChange={(val) => {
                                    if (trimmedName === 'thumb_original' && true === val) {
                                        setAttributes({thumb: true})
                                    }
                                    setAttributes({[trimmedName]: val})
                                    if (trimmedName === 'long_description_modal' && true === val) {
                                        setAttributes({readmore: false})
                                    }
                                    if (trimmedName === 'readmore' && true === val) {
                                        setAttributes({long_description_modal: false})
                                    }
                                }}
                                checked={attributes[trimmedName]}
                            />
                        </div>
                    );
                })
            }
            {
                textOptions.map((item) => {
                    const trimmedName = item.name.replace('wfea_', '');
                    return (
                        <div style={{whiteSpace: "nowrap"}} key={uuid()}>
                            <InputControl
                                key={uuid()}
                                isPressEnterToChange
                                className='wfea_input'
                                label={item.label}
                                value={attributes[trimmedName]}
                                onChange={(val) => {
                                    setAttributes({[trimmedName]: val});
                                }}
                            />
                        </div>
                    );
                })
            }
            {
                numberOptions.map((item) => {
                    const trimmedName = item.name.replace('wfea_', '');
                    return (
                        <div style={{whiteSpace: "nowrap"}} key={uuid()}>
                            <NumberControl
                                isPressEnterToChange
                                className='wfea_input'
                                key={uuid()}
                                label={item.label}
                                onChange={(val) => {
                                    setAttributes({[trimmedName]: parseInt(val)})
                                }}
                                value={isNaN(parseInt(attributes[trimmedName])) ? 0 : parseInt(attributes[trimmedName])}
                            />
                        </div>
                    );
                })
            }
            {
                selectOptions.map((item) => {
                    const trimmedName = item.name.replace('wfea_', '');
                    return (
                        <div id={item.name} key={uuid()}>
                            <SelectControl
                                key={uuid()}
                                multiple={item.args.multiple ? true : false}
                                label={item.label}
                                value={item.args.multiple ? attributes[trimmedName].split(',') : attributes[trimmedName]}
                                options={getOptionsForControl(attributes, item)}
                                onChange={(val) => {
                                    setAttributes({[trimmedName]: item.args.multiple ? val.join(',') : val});
                                    if (trimmedName === 'organization_id') {
                                        item.args.onchange({
                                            setAttributes,
                                            token: attributes['api_key'],
                                            organizationID: val,
                                            action: 'fetch_events_for_key',
                                            optionToUpdate: 'eventOptions'
                                        });
                                        item.args.onchange({
                                            setAttributes,
                                            token: attributes['api_key'],
                                            organizationID: val,
                                            action: 'fetch_organizers_for_key',
                                            optionToUpdate: 'organizerOptions'
                                        });
                                        item.args.onchange({
                                            setAttributes,
                                            token: attributes['api_key'],
                                            organizationID: val,
                                            action: 'fetch_venues_options',
                                            optionToUpdate: 'venueOptions'
                                        });
                                    } else if (trimmedName === 'venue_id') {
                                        item.args.onchange({
                                            setAttributes,
                                            token: attributes['api_key'],
                                            venueID: val,
                                            action: 'fetch_events_for_key',
                                            optionToUpdate: 'eventOptions'
                                        });
                                    }
                                }
                                }
                                __nextHasNoMarginBottom
                            />
                        </div>
                    );
                })
            }
        </>
    )
};

export {ControlsForPlan};

export function Inspector(props) {
    const {attributes, setAttributes, isCalendarBlock} = props;
    prepareOptionsAvailableForBlock(isCalendarBlock);

    function shouldDisplayPanelForOptionType(optionType) {
        const optionsForType = optionsAvailableForBlock[optionType];
        const plans = applyFilters('wfea-block-plans', ['free']);

        let shouldDisplayPanelForOptionType = false;
        plans.forEach(plan => {
            if (optionsForType.hasOwnProperty(plan)) {
                const optionsForPlan = optionsForType[plan];
                const optionsForInspectorPanel = optionsForPlan.find(opt => opt.hasOwnProperty('type'));

                if (optionsForInspectorPanel !== undefined) {
                    shouldDisplayPanelForOptionType = true;
                }
            }
        });

        return shouldDisplayPanelForOptionType;
    }

    const APIKeySelect = applyFilters('wfea-api-key-select', null, attributes, setAttributes);
    const calendarListOptions = applyFilters('wfea-cal-list-options', null, attributes, setAttributes, isCalendarBlock);


    const GeneralOptions = () => {
        return (
            <>
                {APIKeySelect}
                {calendarListOptions}
            </>
        );
    };

    const GeneralPanel = () => {
        if (!calendarListOptions && !APIKeySelect) {
            return null;
        }
        return (
            <PanelBody
                title={__('General Options', 'widget-for-eventbrite-api')}
            >
                <GeneralOptions {...{attributes, setAttributes}}/>
            </PanelBody>
        )
    }

    const UpSell = () => {
        if ( can_use_premium_code ) {
            return null;
        }
        return (
            <div style={{ padding: '20px' }}>
                <ExternalLink href="/wp-admin/options-general.php?billing_cycle=annual&page=widget-for-eventbrite-api-settings-pricing">
                    {__('Upgrade', 'widget-for-eventbrite-api')}
                </ExternalLink>  {__(' to see more layouts. See all options in our ', 'widget-for-eventbrite-api')}
                <ExternalLink href="https://fullworksplugins.com/products/widget-for-eventbrite/eventbrite-shortcode-demo/?mtm_campaign=block&mtm_kwd=side%20link">
                    {__('demo page', 'widget-for-eventbrite-api')}
                </ExternalLink>

            </div>
        )
    }

    return (
        <InspectorControls>
            <Panel header={__('Display Eventbrite', 'widget-for-eventbrite-api')}>
                <UpSell />
                <GeneralPanel {...{attributes, setAttributes}}/>

                {shouldDisplayPanelForOptionType('common') &&
                    (
                        <PanelBody
                            title={__('Common Settings', 'widget-for-eventbrite-api')}
                        >
                            <ControlsForPlan {...{optionType: "common", attributes, setAttributes}} />
                        </PanelBody>
                    )
                }
                {shouldDisplayPanelForOptionType('display') &&
                    (
                        <PanelBody
                            title={__('Display Options', 'widget-for-eventbrite-api')}
                        >
                            <ControlsForPlan {...{optionType: "display", attributes, setAttributes}} />
                        </PanelBody>
                    )
                }
                {shouldDisplayPanelForOptionType('enabling') &&
                    (
                        <PanelBody
                            title={__('Enabling Options', 'widget-for-eventbrite-api')}
                        >
                            <ControlsForPlan {...{optionType: "enabling", attributes, setAttributes}} />
                        </PanelBody>
                    )
                }
                {shouldDisplayPanelForOptionType('filtering') &&
                    (
                        <PanelBody
                            title={__('Filtering Options', 'widget-for-eventbrite-api')}
                        >
                            <ControlsForPlan {...{optionType: "filtering", attributes, setAttributes}} />
                        </PanelBody>
                    )
                }
                {shouldDisplayPanelForOptionType('settings') &&
                    (
                        <PanelBody
                            title={__('Settings Options', 'widget-for-eventbrite-api')}
                        >
                            <ControlsForPlan {...{optionType: "settings", attributes, setAttributes}} />
                        </PanelBody>
                    )
                }
                {shouldDisplayPanelForOptionType('selection') &&
                    (
                        <PanelBody
                            title={__('Selection Options', 'widget-for-eventbrite-api')}
                        >
                            <ControlsForPlan {...{optionType: "selection", attributes, setAttributes}} />
                        </PanelBody>
                    )
                }
            </Panel>
        </InspectorControls>
    );
}


