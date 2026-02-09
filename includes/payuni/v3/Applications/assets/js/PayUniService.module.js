//region 類別定義
import {$, params} from "./env.module.js";
import {isPayuni} from "./utils.module.js";

class PayUniService {

    //region JS doc 定義

    /**
     * PayuniSDK
     * @typedef {Object} PayuniSDK
     * @property {function(): Promise<Object>} start 驗證 origin 或 token，並顯示輸入框在頁面上
     * @property {function(Function): void} onUpdate 獲取使用者輸入表單的狀態，即 SDK 的觸發事件
     * @property {function(): string} getTokenTypeText 當使用記憶卡號或約定信用卡時，可取得相關文案設置在核取方塊旁或其他提示位置
     * @property {function(Object): Object} getTradeResult 進行交易並取得加密的交易結果
     */

    /**
     * UniPayment
     * @typedef {Object} UniPayment
     * @property {function(token: String, initOption: Object): Object} createSession 建立 iframe 連線
     */

    /**
     * @typedef {Object} Params
     * @property {string} EncryptInfo - 加密字串
     * @property {string} HashInfo - 加密字串
     * @property {string} MerID - 商店代號
     * @property {string} Version - 固定 1.0
     * @property {int} order_id - 是否為管理員
     * @property {string} ApiUrl - endpoint
     */

    //endregion JS doc 定義


    /** @type {PayuniSDK} */
    payuniSDK;

    $checkoutForm;

    constructor() {
        this.$checkoutForm = $("form.checkout");
        const options = {
            env: params.ENV, // P: 正式環境, S: 測試環境
            useInst: params.USE_INST, // 是否啟用分期付款功能
            elements: {
                CardNo: "put_card_no",
                CardExp: "put_card_exp",
                CardCvc: "put_card_cvc",
            },
            style: {
                color: "#000000",
                errorColor: "#FF0000",
                fontSize: "14px",
                fontWeight: "400",
                lineHeight: "24px",
            },
        };

        // 建立 iframe 連線
        this.payuniSDK = UniPayment.createSession(params.SDK_TOKEN, options);

        this.payuniSDK.onUpdate(function (update) {
            const {status, event, data} = update;

            console.log("update:", {status, event, data});

            // 表單驗證狀態處理
            if (status) {
                // 從 status 取得元件的輸入狀態與驗證狀態
                // {
                //     "CardNo": null,
                //     "CardExp": true,
                //     "CardCvc": true
                // }
            }

            // 特定事件處理
            if (event === "useTokenType") {
                // ... 記憶卡號相關邏輯
            }
        });

        this.#preventCheckoutFormSubmit();
    }

    /** Render Iframe */
    async render() {
        try {
            const resp = await this.payuniSDK.start();
            console.log("連線成功:", resp);
        } catch (error) {
            console.error("連線失敗:", error);
            // 可以取得 error.message 作客製化錯誤處理, 範例如下:
            // if (error.message === "Code 1008") alert("iframe 連線超時(timeout), 請重新整理")
        }
    }

    /** 取得信用卡綁定 TOKEN 結果 */
    async #getCardToken() {
        try {
            const config = this.#prepareConfig();
            const result = await this.payuniSDK.getTradeResult(config);
            console.log("取得信用卡號綁定 TOKEN 結果:", result);
            // 取得成功後再將原始的 SDK_TOKEN 進行幕後交易授權
        } catch (error) {
            console.error("信用卡號綁定 TOKEN 失敗:", error);
            // 建議於此處進行 Error Handle
        }
    }


    /**
     * 處理付款
     * @param {Params} args - 參數
     * */
    async #processPayment(args) {
        const self = this;
        const {ApiUrl, ...rest} = args;

        try {
            const response = await fetch(ApiUrl, {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(rest)
            });
            if (!response.ok) {
                throw new Error("Network response was not ok " + response.statusText);
            }


            /**
             * @type {{
             *     Status: string;
             *     MerID: string;
             *     Version: string;
             *     EncryptInfo: string;
             *     HashInfo: string;
             * }}
             */
            const result = await response.json();
            if ('SUCCESS' !== result.Status) {
                throw new Error(`${result.Status}: ${self.#getErrorMessage(result.Status)}`);
            }
            console.log("成功回應:", result);
        } catch (error) {
            console.error("處理付款失敗:", error);
            self.#showError(error.message);
            // 建議於此處進行 Error Handle
        }
    }

    // TODO
    #prepareConfig() {
        if (!params.USE_INST) {
            return {
                cardInst: 1, // 分期期數
                useDefault: true, // 使用信用卡記憶卡號進行快速結帳
            };
        }
        //TODO
        return {
            cardInst: 1, // 分期期數
            useDefault: true, // 使用信用卡記憶卡號進行快速結帳
        };
    }

    /** 攔截原本的 submit 事件 */
    #preventCheckoutFormSubmit() {
        $("#place_order").on('click', (e) => {
            if (!isPayuni()) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            this.#processCheckout();
        })
    }

    /**
     * 處理結帳
     * copy from wc_checkout_form.submit()
     * @see \wp-content\plugins\woocommerce\assets\js\frontend\checkout.js
     *
     * */
    #processCheckout() {

        try {


            const self = this;
            /**
             * @type {{
             *  ajax_url: string;
             *  apply_coupon_nonce: string;
             *  checkout_url: string;
             *  debug_mode: string;
             *  i18n_checkout_error: string;
             *  is_checkout: string;
             *  option_guest_checkout: string;
             *  remove_coupon_nonce: string;
             *  update_order_review_nonce: string;
             *  wc_ajax_url: string;
             * }}
             */
            const wc_checkout_params = window.wc_checkout_params;
            const $form = $("form.checkout");
            if ($form.is('.processing')) {
                return false;
            }

            const formData = $form.serializeArray();
            formData.push({name: 'card_hash_tmp', value: 'value'});
            formData.push({name: 'sdk_token_tmp', value: 'value'});

            this.#setLoading(true);
            $.ajax({
                type: 'POST',
                url: wc_checkout_params.checkout_url,
                data: $.param(formData),
                dataType: 'json',
                success: async (result) => {
                    $('.checkout-inline-error-message').remove();

                    try {
                        if ('success' === result.result && self.#isValid(result)) {
                            await self.#processPayment(result);
                        } else if ('failure' === result.result) {
                            throw 'Result failure';
                        } else {
                            throw 'Invalid response';
                        }
                    } catch (err) {
                        console.error(result, err);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    // This is just a technical error fallback. i18_checkout_error is expected to be always defined and localized.
                    let errorMessage = errorThrown;

                    if (
                        typeof wc_checkout_params === 'object' &&
                        wc_checkout_params !== null &&
                        wc_checkout_params.hasOwnProperty(
                            'i18n_checkout_error'
                        ) &&
                        typeof wc_checkout_params.i18n_checkout_error ===
                        'string' &&
                        wc_checkout_params.i18n_checkout_error.trim() !== ''
                    ) {
                        errorMessage =
                            wc_checkout_params.i18n_checkout_error;
                    }

                    console.error(errorMessage);
                },

            });

            return false;
        } catch (e) {
            console.error(e);
        } finally {
            this.#setLoading(false);
        }
    }


    /**
     * 驗證參數
     *
     * @param {object} args
     * */
    #isValid(args) {
        const required_keys = ['EncryptInfo', 'HashInfo', 'MerID', 'Version', 'ApiUrl'];
        required_keys.forEach(key => {
            if (!args?.[key]) {
                console.error(`Missing required key: ${key}`);
                return false;
            }
        })
        return true;
    }

    /**
     * 顯示錯誤
     * @param {string} message
     */
    #showError(message) {
        this.$checkoutForm.removeClass('processing');
        this.$checkoutForm.before(`
        <div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
            <div role="alert">
                <ul class="woocommerce-error" tabindex="-1">
                    <li>${message}</li>
                </ul>
            </div>
        </div>
        `);

        this.#scroll_to_notices();
    }

    /**
     *
     * @param {string} Status
     * @returns {string}
     */
    #getErrorMessage(Status) {
        return params?.ERROR_MAPPER?.[Status] ?? Status;
    }

    /**
     *
     * @param {boolean} isLoading
     */
    #setLoading(isLoading) {
        if (isLoading) {
            this.$checkoutForm.addClass('processing')?.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6,
                },
            });
        } else {
            this.$checkoutForm.removeClass('processing')?.unblock();
        }
    }

    #scroll_to_notices() {
        const scrollElement = $(
            '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout'
        );
        $?.scroll_to_notices(this.$checkoutForm);
    }
}


export default PayUniService;