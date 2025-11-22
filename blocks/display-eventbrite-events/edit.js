/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import {__} from '@wordpress/i18n';


/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
import {
    useBlockProps,
    BlockControls,
} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import {
    applyFilters,
} from '@wordpress/hooks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';
import json from './block.json';

const {name} = json;
import {
    Spinner,
    DropdownMenu,
    Button,
    Dropdown,
    MenuGroup,
    TextControl
} from '@wordpress/components';
import {
    layout, styles,
} from '@wordpress/icons';


const spinner = () => {
    return <Spinner/>;
}

import {Inspector} from '../common_scripts/inspector.jsx';
import {usePopulation} from '../common_scripts/initial-populate-controls.jsx';



import {Toolbar} from '@wordpress/components';

import {Fragment} from '@wordpress/element';

import '../common_scripts/attribute-filters.js';
import './premium__only/filters.js';


function handleClick(e) {
    const blockWrapper = e.target.closest('.block-editor-block-list__block.wp-block-widget-for-eventbrite-api-display-eventbrite-events');
    if (!blockWrapper) {
        return;
    }
    e.preventDefault();
    const unusedHandleSearch = applyFilters('wfea-handle-search', blockWrapper, e);


}

document.addEventListener('click', handleClick);

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
    const {attributes, setAttributes} = props
    const layouts = applyFilters('wfea-block-layouts', [], setAttributes);
    const display = applyFilters('wfea-block-display', '', setAttributes, attributes);
    const filters = applyFilters('wfea-block-filters', '', setAttributes, attributes);
    const enablers = applyFilters('wfea-block-enablers', '', setAttributes, attributes);
    const themeColors = applyFilters('wfea-block-theme-colors', '', setAttributes, attributes);
    const extra_theme_styles = applyFilters('wfea-block-extra-theme-styles', '', setAttributes, attributes);
    const extra_theme_contrast = applyFilters('wfea-block-extra-theme-contrast', '', setAttributes, attributes);
    const computed_style = applyFilters('wfea-block-calc-style', attributes.style, attributes);

    usePopulation(setAttributes, attributes);

    return (
        <div {...useBlockProps()}>
            {
                <BlockControls>
                    <Toolbar>
                        <DropdownMenu
                            icon={layout}
                            label={__("Select a layout", "widget-for-eventbrite-api")}
                            controls={layouts}
                            position="bottom center"
                        />
                        {extra_theme_styles}
                        {extra_theme_contrast}
                        <Dropdown
                            position="bottom center"
                            renderToggle={({isOpen, onToggle}) => (
                                <Button icon="edit"
                                        onClick={onToggle}
                                        aria-expanded={isOpen}
                                        label={__("Edit Display Options", "widget-for-eventbrite-api")}
                                />
                            )}
                            renderContent={() => {
                                return (
                                    <Fragment>
                                        <MenuGroup>
                                            {display}
                                        </MenuGroup>
                                    </Fragment>

                                )
                            }}
                        />

                        <Dropdown
                            position="bottom center"
                            renderToggle={({isOpen, onToggle}) => (
                                <Button
                                    icon="filter"
                                    onClick={onToggle}
                                    aria-expanded={isOpen}
                                    label={__("Set Filters", "widget-for-eventbrite-api")}
                                />
                            )}
                            renderContent={() => {
                                return (
                                    <Fragment>
                                        <MenuGroup>
                                            {filters}
                                        </MenuGroup>
                                    </Fragment>
                                );
                            }}
                        />
                        <Dropdown
                            position="bottom center"
                            renderToggle={({isOpen, onToggle}) => (
                                <Button icon="yes-alt"
                                        onClick={onToggle}
                                        aria-expanded={isOpen}
                                        label={__("Set Enabling", "widget-for-eventbrite-api")}
                                />
                            )}
                            renderContent={() => {
                                return (
                                    <Fragment>
                                        <MenuGroup>
                                            {enablers}
                                        </MenuGroup>
                                    </Fragment>

                                )
                            }}
                        />

                        <DropdownMenu
                            icon="color-picker"
                            label={__("Theme Colors", "widget-for-eventbrite-api")}
                            controls={themeColors}
                            position="bottom center"
                            className="wfea-block-control__theme_colors"
                        />
                    </Toolbar>
                </BlockControls>
            }
            <Inspector attributes={attributes} setAttributes={setAttributes}/>
            <ServerSideRender
                block={name}
                httpMethod='POST'
                attributes={{
                    ...attributes,
                    style: computed_style
                }}
                LoadingResponsePlaceholder={spinner}
            />
        </div>
    );
}
