// 全局变量设置
var myDomain = (function(){
    var cur_href = window.location.href;
    var cur_href_arr = cur_href.split('/');
    return cur_href.replace(cur_href_arr[cur_href_arr.length-1], '');
})();

// 私有全局变量
var _GLOBALS = {};
// console.log(myDomain);
// requirejs配置
requirejs.config({
    baseUrl: "public/js",
    shim: {
        'jquery.ui': ['jquery'],
        'jquery.ztree.all.min': ['jquery'],
        'overhang.min': ['jquery'],
    },
    paths : {
        jquery : 'lib/jquery',
        custom : 'lib/custom',
        'jquery.ui' : 'lib/jquery-ui',
        'jquery.ztree.all.min' : 'lib/jquery.ztree.all.min',
        'overhang.min' : 'lib/overhang.min',
    },
    waitSeconds: 5,
});

// 目录中其他js直接引入
// require(['other']);

// requirejs 主逻辑
requirejs(['jquery', 'custom', 'jquery.ui', 'jquery.ztree.all.min'], function($, custom){
    // ===========================================================
    // 初始化操作start
    // ===========================================================
    // 配置状态
    $( "#controlgroup" ).controlgroup();

    // 折叠菜单
    $( "#accordion" ).accordion();

    //进度条初始化
    $( "#progressbar" ).progressbar({
        value: 0,
    });

    // ===========================================================
    // 初始化操作end
    // ===========================================================


    // ===========================================================
    // 函数库start
    // ===========================================================
    // 自定义函数库变量
    var functionLib = {};

    functionLib.checkExportDBConfig = function() {
        var formData = {};

        $.ajax({
            url: myDomain + 'api.php?act=from_cfg_test',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                console.log(data);
                if(1 == data.state){
                    $('#from-db-state').removeClass('ui-state-error').addClass('ui-state-active').html('已OK');

                    functionLib.resetExportTreeData();
                }else{
                    $('#from-db-state').html('连接失败');
                }
                // $("#messageBox").html(data.desc);
            },
            error: function (data) {

            },
            complete: function (XHR, TS) {

            }
        });
    };

    functionLib.checkImportDBConfig = function() {
        var formData = {};

        $.ajax({
            url: myDomain + 'api.php?act=to_cfg_test',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                console.log(data);
                if(1 == data.state){
                    $('#to-db-state').removeClass('ui-state-error').addClass('ui-state-active').html('已OK');
                    functionLib.resetImportTreeData();
                }else{
                    $('#to-db-state').html('连接失败');
                }
                // $("#messageBox").html(data.desc);
            },
            error: function (data) {

            },
            complete: function (XHR, TS) {

            }
        });
    };

    functionLib.saveExportDBConfig = function() {
        var formData = $("#from_cfg_form").serialize();

        $.ajax({
            url: myDomain + 'api.php?act=from_cfg_save',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                console.log(data);
                if(1 == data.state){

                    //选择状态
                    functionLib.checkExportDBConfig();

                    custom.alert('保存成功！', 'success');
                }else{
                    custom.alert('保存失败，请检查！');
                }
                // $("#messageBox").html(data.desc);
            },
            error: function (data) {

            },
            complete: function (XHR, TS) {

            }
        });
    };

    functionLib.saveImportDBConfig = function() {
        var formData = $("#to_cfg_form").serialize();

        $.ajax({
            url: myDomain + 'api.php?act=to_cfg_save',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                console.log(data);
                if(1 == data.state){

                    //多选勾选状态
                    functionLib.checkImportDBConfig();

                    custom.alert('保存成功！', 'success');
                }else{
                    custom.alert('保存失败，请检查！');
                }
                // $("#messageBox").html(data.desc);
            },
            error: function (data) {

            },
            complete: function (XHR, TS) {

            }
        });
    };

    functionLib.saveImportTypeidConfig = function() {
        var to_tree = $.fn.zTree.getZTreeObj("toTree");


        if(to_tree == null) {
            custom.alert('未加载导入数据库配置！');
            return;
        }

        var to_nodes = to_tree.getCheckedNodes();

        if(typeof to_nodes[0] == 'undefined') {
            custom.alert('未选择导入数据库配置！');
            return;
        }

        var formData = {to_typeid: to_nodes[0].id};


        $.ajax({
            url: myDomain + 'api.php?act=import_cfg_save',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {

                if(data.state == 1){
                    //保存成功
                    custom.alert(data.msg, 'success');
                    $('#to-type-state').removeClass('ui-state-error').addClass('ui-state-active').html(to_nodes[0].name);

                }else{
                    custom.alert(data.msg);
                }
            }
        });
    };

    functionLib.resetExportTreeData = function() {

        var formData = {};

        // 栏目树插件
        var from_tree_setting = {
            check: {
                enable: true,
                chkStyle: "radio",
                radioType: "all"
            },
            data: {
                simpleData: {
                    enable: true
                }
            }
        };

        // 树插件节点
        var zNodes;

        $.ajax({
            url: myDomain + 'api.php?act=import_cfg_reset_from',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                console.log(data);
                zNodes = data.data;

                if(data.state == -1){
                    custom.alert(data.msg + ' 错误请检查');
                    return;
                }

                if(zNodes){
                    $.fn.zTree.init($("#fromTree"), from_tree_setting, zNodes);
                }else{
                    console.log('formTree Data Error!');
                    custom.alert('formTree Data Error!');
                }
            }
        });
    };

    functionLib.resetImportTreeData = function() {
        var formData = {};

        // 栏目树插件
        var to_tree_setting = {
            check: {
                enable: true,
                chkStyle: "radio",
                radioType: "all"
            },
            data: {
                simpleData: {
                    enable: true
                }
            }
        };

        // 树插件节点
        var zNodes;

        $.ajax({
            url: myDomain + 'api.php?act=import_cfg_reset_to',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                console.log(data);
                zNodes = data.data;

                if(data.state == -1){
                    custom.alert(data.msg + ' 错误请检查');
                    return;
                }

                if(zNodes){
                    $.fn.zTree.init($("#toTree"), to_tree_setting, zNodes);
                }else{
                    console.log('toTree Data Error!');
                    custom.alert('toTree Data Error!');
                }

            }
        });
    };

    functionLib.saveExportTypeid = function() {
        var from_tree = $.fn.zTree.getZTreeObj("fromTree");

        if(from_tree == null) {
            custom.alert('未加载导出数据库配置！');
            return;
        }

        var from_nodes = from_tree.getCheckedNodes();

        console.log(from_nodes);

        if(typeof from_nodes[0] == 'undefined') {
            custom.alert('未选择导出数据库配置！');
            return;
        }

        var formData = {from_typeid: from_nodes[0].id};


        $.ajax({
            url: myDomain + 'api.php?act=import_cfg_save',
            data: formData,
            type: 'POST',
            dataType: 'json',
            success: function (data) {

                if(data.state == 1){
                    //保存成功
                    custom.alert(data.msg, 'success');
                    $('#from-type-state').removeClass('ui-state-error').addClass('ui-state-active').html(from_nodes[0].name);

                }else{
                    custom.alert(data.msg);
                }
            }
        });
    };

    functionLib.setCookie = function(name, value) {
        var Days = 1;
        var exp = new Date();
        exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
        document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
    }

    functionLib.getCookie = function(name) {
        var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");

        if (arr = document.cookie.match(reg)) return unescape(arr[2]);
        else return null;
    }

    functionLib.delCookie = function(name) {
        var exp = new Date();
        exp.setTime(exp.getTime() - 1);
        var cval = functionLib.getCookie(name);
        if (cval != null) document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString();
    }

    /**
     * 显示导出附加表不同的字段
     * @param data
     */
    function show_different_field(data){

        console.log(data);
        console.log(data.data.length);

        if(data.data.length){
            var field_html = '';

            for(var i=0, length=data.data.length; i < length; i++){
                var obj = data.data[i];

                field_html += (i+1) + '、字段名称：【' + obj['Field'] + '】 数据类型：' + obj['Type']  + "\n";
            }

            var html = '该栏目模型附加表差异的字段：' + "\n\n" + field_html;

            // 延时弹出
            setTimeout(function(){alert(html);}, 500);
        }
    }
    // ===========================================================
    // 函数库end
    // ===========================================================


    //返回菜单
    $( "#backMenu" ).off("click").click(function( event ) {
        window.location.href = myDomain;
        event.preventDefault();
    });

    // 检测导出配置
    $( "#from-cfg-button" ).off("click").click(function( event ) {
        // alert('from-cfg-button');
        functionLib.checkExportDBConfig();

        event.preventDefault();
    });
    // 检测导入配置
    $( "#to-cfg-button" ).off("click").click(function( event ) {
        // alert('to-cfg-button');
        functionLib.checkImportDBConfig();
        event.preventDefault();
    });


    // 保存导出配置
    $( "#from-cfg-save-button" ).off("click").click(function( event ) {
        // alert('from-cfg-save-button');
        functionLib.saveExportDBConfig();

        event.preventDefault();
    });

    // 保存导入配置
    $( "#to-cfg-save-button" ).off("click").click(function( event ) {
        // alert('to-cfg-save-button');
        functionLib.saveImportDBConfig();

        event.preventDefault();
    });

    // 重置导出字段ID
    $( "#import-cfg-reset-from-button" ).off("click").click(function( event ) {
        // alert('import-cfg-reset-button');
        functionLib.resetExportTreeData();

        event.preventDefault();
    });

    // 重置导入字段ID
    $( "#import-cfg-reset-to-button" ).off("click").click(function( event ) {
        // alert('import-cfg-reset-button');
        functionLib.resetImportTreeData();

        event.preventDefault();
    });

    // 开始导入
    $( "#start-button" ).off("click").click(function( event ) {

        // custom.alert('这是一个错误的提示信息！');
        console.log(myDomain);
        console.log($("#is-export-category").is(':checked'));
        var isFastCategory = $("#is-export-category").is(':checked');

        // 先导入栏目数据
        if(isFastCategory){
            custom.start('v1');
            return;
        }

        console.log(isFastCategory);
        // 移除临时添加站位的无用数据
        var isRemoveUseless = $("#remove-useless").is(':checked');
        if(isRemoveUseless){
            custom.start('v4');
            return;
        }

        // 文章id是否重新排序导入
        var archiveidReset = $("#archiveid-reset").is(':checked');
        if(archiveidReset){
            functionLib.setCookie("is_archiveid_reset", "xslooi");
        }
        else{
            functionLib.delCookie("is_archiveid_reset");
        }

        var confirmStr = '确定从【' + $("#from-type-state").text() + '】导入到【' + $("#to-type-state").text() + '】？';

        if(!confirm(confirmStr)){
            return;
        }

        custom.start('v2', show_different_field);

        event.preventDefault();
    });

    //保存导入栏目id
    $("#import-type-save-button").off("click").click(function( event ){
        functionLib.saveImportTypeidConfig();
    });

    // 保存导出栏目ID
    $("#export-cfg-save-button").off("click").click(function( event ){
        functionLib.saveExportTypeid();
    });

});

//自定义模块
require(['custom'], function (custom) {
    // custom.change();


});