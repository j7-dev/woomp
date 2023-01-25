<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2017/11/13
 * Time: 下午3:44
 */

defined('ABSPATH') || exit;

class ApiException
{
    // auth error
    const INVALID_USER_OR_PASS = 10001;
    const INVALID_SERVER_IP = 10002;
    const TOKEN_INVALID = 10003;
    const TOKEN_EXPIRED = 10004;
    const API_CLIENT_NOT_OPEN_YET = 10005;

    // invalid request error
    const ORDER_DUPLICATE = 20001;
    const ORDER_NOT_EXIST = 20002;
    const PAY_TYPE_NOT_SUPPORT = 20003;
    const MEMBER_NOT_EXIST = 20004;
    const INVALID_PARAMS = 20005;
    const INSUFFICIENT_FUNDS = 20006;
    const CHECKING_DATE_INVALID = 20007;
    const ORDER_ITEMS_TOO_LONG = 20008;

    // pay error
    const INVALID_ATM_EXPIRE_DATE = 40001;
    const ORDER_LIMIT_EXCEED = 40002;

    // refund error
    const REFUND_DUPLICATE = 50001;
    const ORDER_NOT_CONFIRM = 50002;
    const ORDER_NOT_FOUND = 50003;
    const AMOUNT_FEE_INVALID = 50004;
    const BALANCE_NOT_ENOUGH = 50005;
    const REFUND_NOT_FOUND = 50006;
    const INSTALLMENT_CANT_PARTIALLY_REFUND = 50007;
    const COVER_TRANSFEE_MUST_SET_WHILE_ATM_EACH = 50008;
    const ATM_REFUND_NOT_READY = 50009;

    // withdraw error
    const WITHDRAW_LESS_THAN_10 = 70001;
    const MEMBER_VERIFY_NOT_COMPLETE = 70002;
    const WITHDRAW_BIGGER_THAN_BALANCE = 70003;
    const WITHDRAW_BIGGER_THAN_DAILY_LIMIT = 70004;
    const WITHDRAW_BANK_ACCT_NOT_SET = 70005;

    // audit error
    const INVALID_STATUS = 80001;

    public function getApiCode()
    {
        return 401;
    }

    public function getApiType($code)
    {
        $errorType = [
            '1' => 'auth error',
            '2' => 'invalid request error',
            '4' => 'pay error',
            '5' => 'refund error',
            '7' => 'withdraw error',
            '8' => 'audit error',
        ];

        return ($errorType[substr($code, 0, 1)]);

    }

    public static function getErrMsg($code)
    {
        $msg = [
            // auth error
            static::INVALID_USER_OR_PASS => "帳號密碼錯誤",
            static::INVALID_SERVER_IP => "伺服器 IP 設定錯誤",
            static::TOKEN_INVALID => "token 錯誤",
            static::TOKEN_EXPIRED => "token 逾期錯誤",
            static::API_CLIENT_NOT_OPEN_YET => "API 客戶尚未開通",

            // invalid request error
            static::ORDER_DUPLICATE => "訂單編號不可重複",
            static::ORDER_NOT_EXIST => "訂單不存在",
            static::PAY_TYPE_NOT_SUPPORT => "付款類別錯誤",
            static::MEMBER_NOT_EXIST => "會員編號不存在",
            static::INVALID_PARAMS => "參數錯誤",
            static::INSUFFICIENT_FUNDS => "信用卡分期付款之訂單金額不得小於 30 元",
            static::CHECKING_DATE_INVALID => "不允許查詢當日資料",
            static::ORDER_ITEMS_TOO_LONG => "商品名稱過長",

            // pay error
            static::INVALID_ATM_EXPIRE_DATE => "ATM 逾期錯誤",
            static::ORDER_LIMIT_EXCEED => "order limit exceed",

            // refund error
            static::REFUND_DUPLICATE => "退款編號不可重複",
            static::ORDER_NOT_CONFIRM => "該筆訂單並未建單成功,無法退款",
            static::ORDER_NOT_FOUND => "訂單編號不存在",
            static::AMOUNT_FEE_INVALID => "退款金額需大於 0",
            static::BALANCE_NOT_ENOUGH => "帳戶餘額不足以退款",
            static::REFUND_NOT_FOUND => "查無此退款編號之資料",
            static::INSTALLMENT_CANT_PARTIALLY_REFUND => "信用卡分期之退款只能全額退,不支援部分退款",
            static::COVER_TRANSFEE_MUST_SET_WHILE_ATM_EACH => "ATM/銀行支付之退款需設定 cover_transferfee 欄位",
            static::ATM_REFUND_NOT_READY => "ATM 退款資訊未備齊,請隔日再試",

            // withdraw error
            static::WITHDRAW_LESS_THAN_10 => "本行提領金額需 > 1 元 / 他行提領金額需 ≧ 11 元",
            static::MEMBER_VERIFY_NOT_COMPLETE => "會員認證未完成",
            static::WITHDRAW_BIGGER_THAN_BALANCE => "提領金額超過可提領餘額",
            static::WITHDRAW_BIGGER_THAN_DAILY_LIMIT => "提領金額超過本日提領額度上限",
            static::WITHDRAW_BANK_ACCT_NOT_SET => "提領銀行尚未設定",

            // audit error
            static::INVALID_STATUS => "目前狀態不可被執行",
        ];

        return $msg[$code];
    }
}