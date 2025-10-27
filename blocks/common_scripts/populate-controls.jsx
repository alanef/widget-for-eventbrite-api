export const populateOptionsForControl = (args) => {
    let atts = {
        nonce: wfea_controls_meta.nonce,
    };

    atts = Object.assign({}, atts, args);

    let {setAttributes, optionToUpdate, ...data} = atts;

    jQuery.ajax({
        url: wfea_controls_meta.ajaxurl,
        type: 'POST',
        data,
        success: function (response) {
            setAttributes({[optionToUpdate]: response});
        }
    });
};