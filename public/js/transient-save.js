jQuery(function($){
  // 亂數產生
  function randomusefloor(min,max) {
    return Math.floor(Math.random()*(max-min+1)+min);
  }
  
  // 亂數英文字
  function makerandomletter(max) {
    var text = "";
    var possible = "abcdefghijklmnopqrstuvwxyz";
    for (var i = 0; i < max; i++)
      text += possible.charAt(Math.floor(Math.random() * possible.length));
    return text;
  }
  
  if(!$('body').hasClass('logged-in') && !localStorage.hasOwnProperty('woomp')) {
    var uid = "uid_" + makerandomletter(2) + randomusefloor(1, 999999);
    localStorage.setItem("woomp", uid);
  }
  
  $(document).ready(function($){
  
    var fields    = [],
        customer_details = $("#customer_details .form-row");
  
    customer_details.each(function(e){
      var fieldName = $(this).find("[name]").attr('name');
      fields.push(fieldName);
    })
  
    customer_details.find('input, select, textarea, checkbox').on('change',function(){
      var userInfo = {};
      for (let i = 0; i < fields.length; i++) {
        userInfo[fields[i]] = $('[name="' + fields[i] + '"]').val();
      }
      var data = {
        action: "checkout_autosave",
        nonce: ajax_params.nonce,
        user_data: JSON.stringify(userInfo),
        user_id: localStorage.getItem('woomp'),
      };
      $.ajax({
        url: ajax_params.ajaxurl,
        data: data,
        type: "POST",
        dataType: "json",
        success: function (data) {
          
        },
      });
    })
  })
})
