console.log('checkout');

const options = {
    env: "P",  // P: 正式環境, S: 測試環境
    useInst: false,
    elements: {
        CardNo: "put_card_no",
        CardExp: "put_card_exp",
        CardCvc: "put_card_cvc"
    },
    style: {
        color: "#000000",
        errorColor: "#FF0000",
        fontSize: "14px",
        fontWeight: "400",
        lineHeight: "24px"
    }
};

// SDK_TOKEN 由後端串接 PAYUNi API 所取得
const payuniSDK = UniPayment.createSession(SDK_TOKEN, options);
