<h2>
    <?=__('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice') ?> <?=__('Tools', 'ry-woocommerce-ecpay-invoice') ?>
</h2>

<p>將 <a href="https://tw.wordpress.org/plugins/ecpay-invoice-for-woocommerce/" target="_black">ECPay Invoice for WooCommerce</a>
    的發票資料轉變為本外掛的儲存格式。</p>

<p>這會<strong>修改</strong>使用者的資料，進行資料轉換前請備份你的資料，以確保當發生任何意外之時你有辦法回復到原本的狀態。</p>

<button name="ecpay_official_invoice_transfer" class="button-primary" type="submit" value="ecpay_official_invoice_transfer">
    <?php _e('Data transfer', 'ry-woocommerce-ecpay-invoice'); ?></button>

<button name="ecpay_official_invoice_transfer_delete" class="button-primary" type="submit" value="ecpay_official_invoice_transfer_delete">
    <?php _e('Data transfer & DELET', 'ry-woocommerce-ecpay-invoice'); ?></button>

<button name="ecpay_official_invoice_delete" class="button-primary" type="submit" value="ecpay_official_invoice_delete">
    <?php _e('Data DELET', 'ry-woocommerce-ecpay-invoice'); ?></button>
