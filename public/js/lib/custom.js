define(['jquery', 'overhang.min', 'jquery.ui'], function ($) {

    // ======================================================
    // 封装的Ajax、步进、进度条函数
    var obj = {};

    obj.start = function (version, callable) {
        obj.step = 0;
        obj.total = 0;
        obj.progressbar_value = 0;
        obj.ajax_pull(version, callable);
    };

    obj.ajax_pull = function (version, callable) {
      var ver = '';

        if(version){
            ver  = 'start_' + version;
        }else{
            ver = 'start';
        }

        $.ajax({
            url: myDomain + 'api.php?act=' + ver,
            type: 'POST', //GET
            async: true,    //或false,是否异步
            data: {step: obj.step},
            timeout: 5000,    //超时时间
            dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
            beforeSend: function (xhr) {
            },
            success: function (data, textStatus, jqXHR) {
//            成功后加载内容
//                 console.log(data);
                if (data.state == 1) {
                    obj.step++;
                    obj.total = data.data.total;

                    //进度条
                    if(100 >= obj.progressbar_value){
                        $( "#progressbar" ).progressbar({value: obj.progressbar_value});
                        $( "#progressbar div" ).html(obj.progressbar_value + "%");
                        obj.progressbar_value = Math.floor(obj.step / obj.total * 100);
                    }

                    // 心跳调用
                    setTimeout(function () {
                        obj.ajax_pull(version, callable);
                    }, 100);

                }else if(data.state == 0){
                    C_overhang.alert(data.msg, 'success');
                }else{
                    C_overhang.alert(data.msg);
                }

                // 函数回调
                if("function" == typeof callable){
                    callable(data);
                }
            },
            error: function (xhr, textStatus) {
            },
            complete: function () {
            }
        });
    };

    // ======================================================
    // 封装的弹窗函数 根据overhang
    var C_overhang = {};

    /**
     * 封装的顶部弹出提示框
     * @param msg
     * @param type
     * @param cfg
     */
    C_overhang.alert = function (msg, type, cfg) {
        if(typeof cfg === 'object'){
            $("body").overhang(cfg);
        }else{
            $("body").overhang({
                type: type || 'error',
                message: msg,
                duration: 3
            });
        }
    };

    // ======================================================
    // 测试插件函数
    function test(args){
        if(args){
            console.log(args);
        }else{
            console.log('this is custom.js test Function');
        }
    }
    // ======================================================
    // 返回自定义的插件函数
    return {
        test: test,
        start: obj.start,
        alert: C_overhang.alert,
    }
});