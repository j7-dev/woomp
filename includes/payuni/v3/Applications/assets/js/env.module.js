/** @type {{ENV: string, SDK_TOKEN: string, USE_INST: boolean, ENABLE_3D_AUTH: boolean, API_URL: string}} */
const params = window.payuni_payment_v3_checkout_params || {
    ENV: "P",
    SDK_TOKEN: "",

    // TODO 由後端傳送
    USE_INST: false,
    ENABLE_3D_AUTH: false,
    API_URL: ""
};

/** @type {jQuery} */
const $ = window.jQuery;

export {params, $};
