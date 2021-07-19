(function( $ ) {
    'use strict';
    
    $(document).on('click', '#funcashier-accept-payment-btn', function() {
        $('.funcashier-update-notice').remove();

        var orderId = $('#order-id').data('order-id');

        var data = {
            'orderId': orderId,
            'security': ajax_funcashier_object.nonce
        };

        if ( typeof( orderId ) === "undefined" || orderId === null ) {
            $('#funcashier-accept-payment-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：此筆交易無交易ID, 請確認訂單付款交易是否完成。</span>');
            return;
        }
            
        $.get( ajax_funcashier_object.ajax_accept_payment_url, data, function( response ) {
            if ( response.success ) {
                console.log(response.data.trans_status);
                $('#trans-status').text(response.data.trans_status);
                $('#funcashier-accept-payment-btn').after('<span class="funcashier-update-notice" style="color:green">更新成功</span>');
            } else {
                console.log(response.data.error['0']);
                $('#funcashier-accept-payment-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：' + response.data.error['0'] + '</span>');
            }
            //need handle error status
        }).fail(function( jqXHR, textStatus, error ) {
            console.log( error );
            $('#funcashier-accept-payment-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：' + error + '</span>');
        });

    });

    $(document).on('click', '#funcashier-query-order-btn', function() {
        $('.funcashier-update-notice').remove();

        var orderId = $('#order-id').data('order-id');

        var data = {
            'orderId': orderId,
            'security': ajax_funcashier_object.query_nonce
        };

        if ( typeof( orderId ) === "undefined" || orderId === null ) {
            $('#funcashier-query-order-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：此筆交易無交易ID, 請確認訂單付款交易是否完成。</span>');
            return;
        }
            
        $.get( ajax_funcashier_object.ajax_query_order_url, data, function( response ) {
            if ( response.success ) {
                console.log(response.data.trans_status);
                $('#trans-status').text(response.data.trans_status);
                $('#funcashier-query-order-btn').after('<span class="funcashier-update-notice" style="color:green">更新成功</span>');
            } else {
                console.log(response.data.error['0']);
                $('#funcashier-query-order-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：' + response.data.error['0'] + '</span>');
            }
            //need handle error status
        }).fail(function( jqXHR, textStatus, error ) {
            console.log( error );
            $('#funcashier-query-order-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：' + error + '</span>');
        });

    });


    //已出貨
    $(document).on('click', '#funcashier-ship-order-btn', function() {
        $('.funcashier-update-notice').remove();

        var orderId = $('#order-id').data('order-id');

        var data = {
            'orderId': orderId,
            'security': ajax_funcashier_object.ship_nonce
        };

        if ( typeof( orderId ) === "undefined" || orderId === null ) {
            $('#funcashier-ship-order-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗</span>');
            return;
        }
            
        $.get( ajax_funcashier_object.ajax_ship_order_url, data, function( response ) {
            if ( response.success ) {
                console.log(response.data.trans_status);
                // $('#trans-status').text(response.data.trans_status);
                $('#funcashier-ship-order-btn').after('<span class="funcashier-update-notice" style="color:green">更新成功</span>');
                $('#funcashier-ship-order-btn').html('已通出出貨');
                $('#funcashier-ship-order-btn').prop('disabled', true);
            } else {
                console.log(response.data.error['0']);
                $('#funcashier-ship-order-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：' + response.data.error['0'] + '</span>');
            }
            //need handle error status
        }).fail(function( jqXHR, textStatus, error ) {
            console.log( error );
            $('#funcashier-ship-order-btn').after('<span class="funcashier-update-notice" style="color:red">更新失敗：' + error + '</span>');
        });

    });


})( jQuery );
