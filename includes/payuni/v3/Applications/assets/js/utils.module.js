import {$} from "./env.module";


/**
 * 判斷是否選中 Payuni 免跳轉付款方式
 * @returns {boolean}
 */
function isPayuni() {
    const $input = $("#payment_method_payuni-credit-v3")
    return !!$input.is(":checked")
}

export {isPayuni};
