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
        'overhang.min': ['jquery'],
    },
    paths : {
        jquery : 'lib/jquery',
        custom : 'lib/custom',
        'jquery.ui' : 'lib/jquery-ui',
        'overhang.min' : 'lib/overhang.min',
    },
    waitSeconds: 5,
});

// 目录中其他js直接引入
// require(['other']);

// requirejs 主逻辑
requirejs(['jquery', 'custom', 'jquery.ui'], function($, custom){
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

    /**
     * 展示审查dedecms遗漏的标签
     * @param data
     */
    function show_inspect_dede_miss_tags(data){

        if("object" == typeof data.data.inspect){
            var elementId = 'convertList' + Math.random().toString().substring(2);
            var html = '<ol id="' + elementId +'">';
            var i = 1;

            for(var key  in data.data.inspect){
                var inspect_html = '';
                if(data.data.inspect.hasOwnProperty(key)){
                    var inspect_array = data.data.inspect[key];

                    if(null != inspect_array){
                        for(var k=0, len=inspect_array.length; k < len; k++){
                            inspect_html += '<span>' + inspect_array[k] + '</span>';
                        }
                    }
                }

                html +=  '<li>\n' +
                    '        <input type="checkbox" name="inspect[]" id="inspect' + i + '">\n' +
                    '        <label for="inspect' + i + '">\n' +
                    '                  模板文件：' + key + ' 遗漏标签：' + inspect_html + '\n' +
                    '        </label>\n' +
                    '    </li>\n';
                i++;
            }

            html += '</ol>';

            // 赋值并加载jqueryui特效
            $( "#convertContainer" ).empty().append(html);
            $( "#" + elementId ).controlgroup();
        }
    }

    // ===========================================================
    // 函数库end
    // ===========================================================

    //返回菜单
    $( "#backMenu" ).off("click").click(function( event ) {
        window.location.href=myDomain;
        event.preventDefault();
    });

    // 开始导入
    $( "#start-button" ).off("click").click(function( event ) {

        // custom.alert('这是一个错误的提示信息！');
        console.log(myDomain);

        custom.start('v3', show_inspect_dede_miss_tags);

        event.preventDefault();
    });

});

//自定义模块
require(['custom'], function (custom) {
    // custom.change();

});