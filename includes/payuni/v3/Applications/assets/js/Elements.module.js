import PayUniService from "./PayUniService.module.js";
import {$} from "./env.module.js";
import {isPayuni} from "./utils.module.js"

class Elements {

    constructor() {
        const self = this;
        self.#render()
        // 偵測 radio 狀態改變
        $('input[type="radio"][name="payment_method"]').on("change", async () => {
            self.#render()
        });
    }

    #render() {
        if (isPayuni()) {
            const _ = new PayUniService().render();
            console.log("render");
        }
    }
}

export default Elements;