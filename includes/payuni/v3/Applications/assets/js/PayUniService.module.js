/**
 * PayUni UNi Embed 付款服務
 *
 * @file PayUniService.module.js
 * @description PayUni UNi Embed SDK 整合服務，處理信用卡免跳轉付款流程
 * @see https://docs.payuni.com.tw/web/#/29/383
 */

import {$, params} from './env.module.js';
import {isPayuni} from './utils.module.js';
import {DEFAULT_IFRAME_STYLE, IFRAME_ELEMENTS, SDK_EVENTS, WC_SELECTORS} from './constants.module.js';
import FormState from './FormState.module.js';
import UIHelper from './UIHelper.module.js';
import ApiService from './ApiService.module.js';

//region JSDoc 類型定義

/**
 * PayUni SDK 實例
 * @typedef {Object} PayuniSDK
 * @property {function(): Promise<Object>} start - 驗證 origin 或 token，並顯示輸入框在頁面上
 * @property {function(Function): void} onUpdate - 監聽使用者輸入表單的狀態變更事件
 * @property {function(): string} getTokenTypeText - 取得記憶卡號或約定信用卡的相關文案
 * @property {function(Object): Promise<TradeResult>} getTradeResult - 進行交易並取得加密的交易結果
 */

/**
 * UniPayment 全域物件
 * @typedef {Object} UniPayment
 * @property {function(string, Object): PayuniSDK} createSession - 建立 SDK 連線
 */

/**
 * SDK 初始化選項
 * @typedef {Object} SDKInitOptions
 * @property {string} env - 環境 (P: 正式, S: 測試)
 * @property {boolean} useInst - 是否啟用分期付款
 * @property {Object} elements - iframe 元素 ID 對應
 * @property {Object} style - iframe 樣式設定
 */

/**
 * 交易結果
 * @typedef {Object} TradeResult
 * @property {string} Status - 交易狀態
 * @property {string} EncryptInfo - 加密的交易資訊
 * @property {string} HashInfo - 雜湊驗證資訊
 * @property {string} [CardHash] - 信用卡綁定 Hash（當有勾選記憶卡號時回傳）
 */

/**
 * 交易設定
 * @typedef {Object} TradeConfig
 * @property {number} cardInst - 分期期數 (1=不分期)
 * @property {boolean} useDefault - 是否使用記憶卡號
 */

//endregion JSDoc 類型定義


/**
 * PayUni 付款服務類別
 *
 * @class PayUniService
 * @description 整合 PayUni UNi Embed SDK，處理信用卡免跳轉付款的完整流程
 *
 * @example
 * const service = new PayUniService();
 * await service.render();
 */
class PayUniService {
    /** @type {PayuniSDK} SDK 實例 */
    #payuniSDK;

    /** @type {FormState} 表單狀態管理器 */
    #formState;

    /** @type {UIHelper} UI 輔助工具 */
    #uiHelper;

    /** @type {ApiService} API 服務 */
    #apiService;

    /** @type {boolean} 是否已初始化 */
    #initialized = false;

    /** @type {boolean} 是否已綁定事件 */
    #eventsBound = false;

    /**
     * 建構函式
     *
     * @description 初始化 SDK 連線並設定事件監聽
     */
    constructor() {
        this.#formState = new FormState();
        this.#uiHelper = new UIHelper();
        this.#apiService = new ApiService();

        this.#initSDK();
        this.#bindCheckoutEvents();
    }

    /**
     * 初始化 PayUni SDK
     *
     * @private
     * @description 建立 SDK 連線並設定 onUpdate 監聽器
     */
    #initSDK() {
        // 檢查 SDK 是否已載入
        if (typeof UniPayment === 'undefined') {
            console.error('[PayUni] SDK 未載入，請確認 uni-payment.js 已正確引入');
            return;
        }

        // 檢查 SDK Token
        if (!params.SDK_TOKEN) {
            console.error('[PayUni] SDK Token 未設定');
            return;
        }

        /** @type {SDKInitOptions} */
        const options = {
            env: params.ENV,
            useInst: params.USE_INST || false,
            elements: IFRAME_ELEMENTS,
            style: DEFAULT_IFRAME_STYLE
        };

        // 建立 SDK 連線
        this.#payuniSDK = UniPayment.createSession(params.SDK_TOKEN, options);

        // 設定狀態更新監聽
        this.#payuniSDK.onUpdate((update) => this.#handleSDKUpdate(update));
    }

    /**
     * 處理 SDK 狀態更新
     *
     * @param {Object} update - SDK 回傳的更新物件
     * @param {Object} [update.status] - 各欄位驗證狀態
     * @param {string} [update.event] - 觸發的事件名稱
     * @param {*} [update.data] - 事件相關資料
     * @private
     */
    #handleSDKUpdate(update) {
        const {status, event, data} = update;

        console.log('[PayUni] SDK 狀態更新:', {status, event, data});

        // 更新表單狀態
        this.#formState.update(update);

        // 處理特定事件
        if (event === SDK_EVENTS.USE_TOKEN_TYPE) {
            this.#handleTokenTypeEvent(data);
        }
    }

    /**
     * 處理 Token 類型事件（記憶卡號/約定信用卡）
     *
     * @param {*} data - 事件資料
     * @private
     */
    #handleTokenTypeEvent(data) {
        // 取得 Token 類型文案（可用於顯示在 checkbox 旁）
        const tokenTypeText = this.#payuniSDK.getTokenTypeText();
        console.log('[PayUni] Token 類型文案:', tokenTypeText);
        // 可依需求在此處理 UI 顯示邏輯
    }

    /**
     * 渲染 SDK iframe
     *
     * @public
     * @async
     * @returns {Promise<void>}
     * @description 驗證 origin 或 token，並在頁面上顯示信用卡輸入框
     */
    async render() {
        if (!this.#payuniSDK) {
            console.error('[PayUni] SDK 未初始化');
            return;
        }

        try {
            const response = await this.#payuniSDK.start();
            this.#formState.setReady(true);
            this.#initialized = true;
            console.log('[PayUni] iframe 連線成功:', response);
        } catch (error) {
            this.#formState.setReady(false);
            console.error('[PayUni] iframe 連線失敗:', error);

            // 根據錯誤代碼提供對應處理
            this.#handleConnectionError(error);
        }
    }

    /**
     * 處理連線錯誤
     *
     * @param {Error} error - 錯誤物件
     * @private
     */
    #handleConnectionError(error) {
        const errorCode = error?.message?.match(/Code (\d+)/)?.[1];

        switch (errorCode) {
            case '1008':
                this.#uiHelper.showError('信用卡輸入框連線逾時，請重新整理頁面');
                break;
            case '1007':
                this.#uiHelper.showError('網域驗證失敗，請聯繫網站管理員');
                break;
            default:
                this.#uiHelper.showError(this.#getErrorMessage(error?.message) || '信用卡輸入框載入失敗');
        }
    }

    /**
     * 綁定結帳事件
     *
     * @private
     * @description 攔截 WooCommerce 結帳按鈕點擊事件
     */
    #bindCheckoutEvents() {
        if (this.#eventsBound) return;

        $(WC_SELECTORS.PLACE_ORDER_BTN).on('click', (e) => {
            if (!isPayuni()) return;

            e.preventDefault();
            e.stopPropagation();
            this.#processCheckout();
        });

        this.#eventsBound = true;
    }

    /**
     * 處理結帳流程
     *
     * @private
     * @async
     * @description 完整的結帳流程：驗證 → 取得交易結果 → 送出訂單 → 執行交易
     */
    async #processCheckout() {
        // 避免重複提交
        if (this.#uiHelper.isProcessing()) {
            return;
        }

        // 清除之前的錯誤訊息
        this.#uiHelper.clearErrors();

        // 驗證 SDK 是否已準備好
        if (!this.#formState.isReady()) {
            this.#uiHelper.showError('信用卡輸入框尚未準備完成，請稍候');
            return;
        }

        // 驗證表單欄位
        if (!this.#formState.isAllValid()) {
            const invalidFields = this.#formState.getInvalidFields();
            const fieldNames = {
                CardNo: '信用卡號',
                CardExp: '有效期限',
                CardCvc: '安全碼'
            };
            const errorMsg = invalidFields
                .map(f => fieldNames[f] || f)
                .join('、') + ' 填寫有誤，請重新確認';
            this.#uiHelper.showError(errorMsg);
            return;
        }

        this.#uiHelper.setLoading(true);

        try {
            // Step 1: 從 SDK 取得交易結果（加密的信用卡資訊）
            const tradeResult = await this.#getTradeResult();
            console.log('[PayUni] Step 1 - 取得 SDK 交易結果:', tradeResult);

            // Step 2: 送出訂單到 WooCommerce 並取得 PayUni 交易參數
            const additionalData = {
                sdk_token_tmp: params.SDK_TOKEN,
                card_hash_tmp: tradeResult.CardHash || ''
            };

            const checkoutResponse = await this.#apiService.submitCheckout(additionalData);
            console.log('[PayUni] Step 2 - 取得後端交易參數:', checkoutResponse);

            // Step 3: 執行 PayUni 幕後交易授權
            const tradeResponse = await this.#apiService.sendTradeRequest(checkoutResponse);
            console.log('[PayUni] Step 3 - PayUni 交易回應:', tradeResponse);

            // Step 4: 導向感謝頁面
            this.#handleTradeSuccess(checkoutResponse);

        } catch (error) {
            console.error('[PayUni] 結帳流程錯誤:', error);
            this.#uiHelper.showError(this.#getErrorMessage(error.message));
        } finally {
            this.#uiHelper.setLoading(false);
        }
    }

    /**
     * 取得交易結果
     *
     * @private
     * @async
     * @returns {Promise<TradeResult>} 交易結果
     * @description 呼叫 SDK 的 getTradeResult 取得加密的信用卡資訊
     */
    async #getTradeResult() {
        const config = this.#prepareTradeConfig();

        try {
            const result = await this.#payuniSDK.getTradeResult(config);
            return result;
        } catch (error) {
            console.error('[PayUni] 取得交易結果失敗:', error);
            throw new Error(error?.message || '信用卡資訊處理失敗');
        }
    }

    /**
     * 準備交易設定
     *
     * @private
     * @returns {TradeConfig} 交易設定
     */
    #prepareTradeConfig() {
        // 取得分期期數（如果有啟用分期）
        let cardInst = 1; // 預設不分期

        if (params.USE_INST) {
            // 從下拉選單取得選擇的分期數
            const selectedInst = $('#payuni_installment')?.val();
            if (selectedInst) {
                cardInst = parseInt(selectedInst, 10) || 1;
            }
        }

        return {
            cardInst,
            useDefault: false // 是否使用記憶卡號
        };
    }

    /**
     * 處理交易成功
     *
     * @param {Object} checkoutResponse - 結帳回應（含導向網址）
     * @private
     */
    #handleTradeSuccess(checkoutResponse) {
        // 交易成功後，導向訂單完成頁面
        console.log('[PayUni] 交易成功，導向訂單完成頁面', checkoutResponse);

        // if (checkoutResponse.redirect) {
        //     window.location.href = checkoutResponse.redirect;
        // } else {
        //     // 如果沒有 redirect，重新載入頁面讓 WooCommerce 處理
        //     window.location.reload();
        // }
    }

    /**
     * 取得錯誤訊息
     *
     * @param {string} status - 錯誤代碼
     * @returns {string} 對應的錯誤訊息
     * @private
     */
    #getErrorMessage(status) {
        return params?.ERROR_MAPPER?.[status] || status || '發生未知錯誤';
    }
}


export default PayUniService;