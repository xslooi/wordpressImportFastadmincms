<?php
/**
 * 此文件为程序入口文件-ajax接口文件
 * 1、织梦数据库数据导入到Fastadmin-CMS数据表
 * 2、织梦模板文件转换为Fastadmin-cms模板文件
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
set_time_limit(0);  //不限制 执行时间
date_default_timezone_set('Asia/Shanghai');
header("content-Type: text/javascript; charset=utf-8"); //语言强制
header('Cache-Control:no-cache,must-revalidate');
header('Pragma:no-cache');
//===================================================================
//文件说明区
//===================================================================

//echo dirname(__FILE__);exit;
//===================================================================
//定义常量区
//===================================================================
define('IS_MAGIC_QUOTES_GPC', get_magic_quotes_gpc()); //todo 转义常量暂未使用
define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);
define('ROOT_DIR', str_replace("\\", '/', dirname(__FILE__)));
define('ROOT_WEB', str_replace(strrchr(ROOT_DIR, '/'), '', ROOT_DIR));
//echo ROOT_WEB;

//===================================================================
//路由逻辑区
//===================================================================
$respondData = array(
    'id' => 0,
    'state' => 0,
    'msg' => 'fail',
    'data' => null
);

//todo 此处过滤数据
$receiveData = $_POST;
if(!IS_MAGIC_QUOTES_GPC){

}

$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
$action = 'act_' . $act;


if(IS_AJAX)
{
//    $respondData = array(
//        'id' => 1,
//        'state' => 1,
//        'msg' => 'success',
//        'data' => $receiveData
//    );

    if(function_exists($action))
    {
        $respondData = call_user_func($action, $receiveData);
    }

    respondMsg($respondData);
}
else
{
    respondMsg($respondData);
}

//===================================================================
//函数库区
//===================================================================
function respondMsg($data)
{
    exit(json_encode($data));
}

function configFileFormat($data)
{
    return "<?php\r\n" . "return " . var_export($data, true) . ";";
}

function config($name, $data=array())
{
    //todo 此处用数组还是json文件？
    $path = ROOT_DIR . '/config/' . $name . '.php';

    if(file_exists($path)) {
        if(empty($data)){
            $result = include($path);
            return $result;
        }else{
            file_put_contents($path, configFileFormat($data));
            return true;
        }
    }else{
        file_put_contents($path, configFileFormat($data));
        return true;
    }

}


//===================================================================
//动作函数区
//===================================================================
function act_from_cfg_save($data)
{

    config('from_db', $data);
    $rs = config('from_db');

    return array(
        'id' => 1,
        'state' => 1,
        'msg' => 'success',
        'data' => $rs
    );
}

function act_to_cfg_save($data)
{

    config('to_db', $data);
    $rs = config('to_db');

    return array(
        'id' => 2,
        'state' => 1,
        'msg' => 'success',
        'data' => $rs
    );
}

function act_from_cfg_test($data)
{
    $config = config('from_db');
    $from_db = new DataBase($config);
    $rs = $from_db->testconnect();

    if($rs){
        return array(
            'id' => 4,
            'state' => 1,
            'msg' => 'success',
            'data' => $config
        );
    }
    else{
        return array(
            'id' => 4,
            'state' => 0,
            'msg' => 'fail',
            'data' => $config
        );
    }


}

function act_to_cfg_test($data)
{
    $config = config('to_db');
    $to_db = new DataBase($config);
    $rs = $to_db->testconnect();

    if($rs){
        return array(
            'id' => 5,
            'state' => 1,
            'msg' => 'success',
            'data' => $config
        );
    }
    else{
        return array(
            'id' => 5,
            'state' => 0,
            'msg' => 'fail',
            'data' => $config
        );
    }
}

function act_import_cfg_reset_from($data) {

    $rs = array();
//    来源数据
    $from_config = config('from_db');
    $from_db = new DataBase($from_config);
    $from_db->query(" SELECT term_id AS id, term_group AS pId, name AS name FROM `#@__terms` ORDER BY term_id ASC ");
    $from_rs = $from_db->fetch_array();
    if($from_rs){
        foreach($from_rs as $item){
            $item['name'] = '[' . $item['id'] . ']' . $item['name'];
            $rs[] = $item;
        }
    }else{
        $rs = array();
    }

    //返回数据
    return array(
        'id' => 7,
        'state' => 1,
        'msg' => 'success',
        'data' => $rs
    );
}

function act_import_cfg_reset_to($data) {

    $rs = array();
//导入数据
    $to_config = config('to_db');
    $to_db = new DataBase($to_config);
    $to_db->query(" SELECT id, parent_id AS pId, name FROM `#@__cms_channel` ORDER BY weigh ASC, id ASC ");
    $to_rs = $to_db->fetch_array();
    if($to_rs){
        foreach($to_rs as $item){
            $item['name'] = '[' . $item['id'] . ']' . $item['name'];
            $rs[] = $item;
        }
    }else{
        $rs = array();
    }

    //返回数据
    return array(
        'id' => 7,
        'state' => 1,
        'msg' => 'success',
        'data' => $rs
    );
}


function act_import_cfg_save($data){
    $rs = config('import_typeid');

    if(isset($rs['to_typeid']) && !isset($data['to_typeid'])){
        $data['to_typeid'] = $rs['to_typeid'];
    }

    if(isset($rs['from_typeid']) && !isset($data['from_typeid'])){
        $data['from_typeid'] = $rs['from_typeid'];
    }

    $rs = config('import_typeid', $data);

    return array(
        'id' => 8,
        'state' => 1,
        'msg' => '恭喜，保存成功！',
        'data' => $rs
    );
}

//2021年9月28日14:09:32 xslooi 添加 V1 函数
function act_start_v1($data) {
    $step = isset($data['step']) ? intval($data['step']) : 1;

    $from_config = config('from_db');
    $to_config = config('to_db');

    //转移数据库
    $FROM_DB = new DataBase($from_config);

    //目标数据库
    $TO_DB = new DataBase($to_config);

    // 重置导入配置文件
    reset_config();

    // 转换类别到数据库
    // todo 栏目diyname按照规则生成，可以调取“文件保存目录”目录驼峰法作为名称
    convert_category($FROM_DB, $TO_DB);

    // 转换栏目idg关系
    convert_category_reid($TO_DB);

    // 去重栏目模板文件名称列表
    unique_templets_filenames();

    // 返回参数
    return array(
        'id' => 6,
        'state' => 0,
        'msg' => '恭喜，导入完成！',
        'data' => array()
    );
}


function act_generate_dedecms_addon(){
//    $result = array();
//    $from_config = config('from_db');
//
//    //转移数据库
//    $FROM_DB = new DataBase($from_config);

//    $result[] = array();
//    $result[] = getAddonTable($from_config['dbprefix'] . 'addonarticle', $FROM_DB);
//    $result[] = getAddonTable($from_config['dbprefix'] . 'addonimages', $FROM_DB);

    $result = array (
        -100 => array (
            0 =>
                array (
                    'Field' => 'aid',
                    'Type' => 'int(11)',
                ),
            1 =>
                array (
                    'Field' => 'typeid',
                    'Type' => 'int(11)',
                ),
            2 =>
                array (
                    'Field' => 'channel',
                    'Type' => 'smallint(6)',
                ),
            3 =>
                array (
                    'Field' => 'arcrank',
                    'Type' => 'smallint(6)',
                ),
            4 =>
                array (
                    'Field' => 'mid',
                    'Type' => 'mediumint(8) unsigned',
                ),
            5 =>
                array (
                    'Field' => 'click',
                    'Type' => 'int(10) unsigned',
                ),
            6 =>
                array (
                    'Field' => 'title',
                    'Type' => 'varchar(60)',
                ),
            7 =>
                array (
                    'Field' => 'senddate',
                    'Type' => 'int(11)',
                ),
            8 =>
                array (
                    'Field' => 'flag',
                    'Type' => 'set(\'c\',\'h\',\'p\',\'f\',\'s\',\'j\',\'a\',\'b\')',
                ),
            9 =>
                array (
                    'Field' => 'litpic',
                    'Type' => 'varchar(60)',
                ),
            10 =>
                array (
                    'Field' => 'userip',
                    'Type' => 'char(15)',
                ),
            11 =>
                array (
                    'Field' => 'lastpost',
                    'Type' => 'int(10) unsigned',
                ),
            12 =>
                array (
                    'Field' => 'scores',
                    'Type' => 'mediumint(8)',
                ),
            13 =>
                array (
                    'Field' => 'goodpost',
                    'Type' => 'mediumint(8) unsigned',
                ),
            14 =>
                array (
                    'Field' => 'badpost',
                    'Type' => 'mediumint(8) unsigned',
                ),
            15 =>
                array (
                    'Field' => 'weight',
                    'Type' => 'int(10)',
                ),
        ),
        0 => array (
            0 =>
                array (
                    'Field' => 'aid',
                    'Type' => 'mediumint(8) unsigned',
                ),
            1 =>
                array (
                    'Field' => 'typeid',
                    'Type' => 'smallint(5) unsigned',
                ),
            2 =>
                array (
                    'Field' => 'body',
                    'Type' => 'mediumtext',
                ),
            3 =>
                array (
                    'Field' => 'redirecturl',
                    'Type' => 'varchar(255)',
                ),
            4 =>
                array (
                    'Field' => 'templet',
                    'Type' => 'varchar(30)',
                ),
            5 =>
                array (
                    'Field' => 'userip',
                    'Type' => 'char(15)',
                ),
            ),
        1 => array (
                0 =>
                    array (
                        'Field' => 'aid',
                        'Type' => 'mediumint(8) unsigned',
                    ),
                1 =>
                    array (
                        'Field' => 'typeid',
                        'Type' => 'smallint(5) unsigned',
                    ),
                2 =>
                    array (
                        'Field' => 'body',
                        'Type' => 'mediumtext',
                    ),
                3 =>
                    array (
                        'Field' => 'redirecturl',
                        'Type' => 'varchar(255)',
                    ),
                4 =>
                    array (
                        'Field' => 'templet',
                        'Type' => 'varchar(30)',
                    ),
                5 =>
                    array (
                        'Field' => 'userip',
                        'Type' => 'char(15)',
                    ),

            ),
        2 => array (
                0 =>
                    array (
                        'Field' => 'aid',
                        'Type' => 'mediumint(8) unsigned',
                    ),
                1 =>
                    array (
                        'Field' => 'typeid',
                        'Type' => 'smallint(5) unsigned',
                    ),
                2 =>
                    array (
                        'Field' => 'pagestyle',
                        'Type' => 'smallint(6)',
                    ),
                3 =>
                    array (
                        'Field' => 'maxwidth',
                        'Type' => 'smallint(6)',
                    ),
                4 =>
                    array (
                        'Field' => 'imgurls',
                        'Type' => 'text',
                    ),
                5 =>
                    array (
                        'Field' => 'row',
                        'Type' => 'smallint(6)',
                    ),
                6 =>
                    array (
                        'Field' => 'col',
                        'Type' => 'smallint(6)',
                    ),
                7 =>
                    array (
                        'Field' => 'isrm',
                        'Type' => 'smallint(6)',
                    ),
                8 =>
                    array (
                        'Field' => 'ddmaxwidth',
                        'Type' => 'smallint(6)',
                    ),
                9 =>
                    array (
                        'Field' => 'pagepicnum',
                        'Type' => 'smallint(6)',
                    ),
                10 =>
                    array (
                        'Field' => 'templet',
                        'Type' => 'varchar(30)',
                    ),
                11 =>
                    array (
                        'Field' => 'userip',
                        'Type' => 'char(15)',
                    ),
                12 =>
                    array (
                        'Field' => 'redirecturl',
                        'Type' => 'varchar(255)',
                    ),
                13 =>
                    array (
                        'Field' => 'body',
                        'Type' => 'mediumtext',
                    ),
            ),
    );

    config('dedecms_addon_table_desc', $result);

    return array(
        'id' => 6,
        'state' => 0,
        'msg' => 'success',
        'data' => array()
    );
}

//2018年11月28日17:25:34 xslooi 添加 V2 函数

function act_start_v2($data) {
    $step = isset($data['step']) ? intval($data['step']) : 0;

    $import_typeid = config('import_typeid');

    $from_config = config('from_db');
    $to_config = config('to_db');

    //转移数据库
    $FROM_DB = new DataBase($from_config);

    //目标数据库
    $TO_DB = new DataBase($to_config);


//    2、获得导出数据库数据
    //TODO 此处每次获得所有数据但只用一条，内部可加个缓存，只调用需要的那一条
    $from_data = getFromData($import_typeid['from_typeid'], $FROM_DB);
//var_dump($from_data);
//    4、循环插入数据

    if(isset($from_data[$step])){
        // 获取WordPress postmeta数据
        $sql = " SELECT * FROM `#@__postmeta` WHERE post_id = '{$from_data[$step]['ID']}' ";
        $FROM_DB->query($sql);
        $postmeta = $FROM_DB->fetch_array();
        foreach($postmeta as $item){
            $from_data[$step][$item['meta_key']] = $item['meta_value'];
        }

        // 获取WordPress 文章封面图
        $from_data[$step]['image'] = '';
        if(isset($from_data[$step]['_thumbnail_id'])){
            $sql = " SELECT * FROM `#@__posts` WHERE ID = '{$from_data[$step]['_thumbnail_id']}' ";
            $FROM_DB->query($sql);
            $postmeta = $FROM_DB->fetch_array();
            $from_data[$step]['image'] = $postmeta[0]['guid'];
        }

//var_dump($from_data[$step]);

        insertFastadminOne($from_data[$step], $import_typeid['to_typeid'], $TO_DB);
    }

    //返回参数
    // TODO 栏目的文章数量统计
    $total = count($from_data);
    $rs = array('total' => $total);

    if($step == $total){
        setFastadminChannelItems($total, $import_typeid['to_typeid'], $TO_DB);
    }

    if($step > $total){
        return array(
            'id' => 6,
            'state' => 0,
            'msg' => '恭喜，导入完成！',
            'data' => $rs
        );
    }
    else{
        return array(
            'id' => 6,
            'state' => 1,
            'msg' => 'success',
            'data' => $rs
        );
    }
}

//2018年12月14日13:36:50 xslooi 添加 V3 函数
//2021年10月8日15:16:29 xslooi 修改
// TODO 模板文件存在多余无用的情况 未改名，直接替换标签
function act_start_v3($data) {
    $step = isset($data['step']) ? intval($data['step']) : 0;

    //    1、查看dedecms模板文件是否为空
    $dedecms_path = ROOT_DIR . '/templets_dedecms/';
    $fastadmincms_path = ROOT_DIR . '/templets_fastadmincms/';


    if(0 == $step) {
        // 重置生成文件夹
        $fastadmincms_path = ROOT_DIR . '/templets_fastadmincms/';
        deldir($fastadmincms_path);
        // 重置审查日志
        $path_inspect_log = ROOT_DIR . '/__inspect_dede_miss_tags.log';
        if(file_exists($path_inspect_log)){
            unlink($path_inspect_log);
        }

        if(!is_dir($fastadmincms_path . 'common')){
            mkdir($fastadmincms_path . 'common'); //创建公共文件夹
        }

        // 手机模板文件夹
        if(!is_dir($fastadmincms_path . 'mobile')){
            mkdir($fastadmincms_path . 'mobile'); //创建公共文件夹
        }

        if(!is_dir($fastadmincms_path . 'mobile/common')){
            mkdir($fastadmincms_path . 'mobile/common'); //创建公共文件夹
        }
    }


    $dedecms_files = get_file_list($dedecms_path . '*');

    if(empty($dedecms_files)){
        return array(
            'id' => 1,
            'state' => -1,
            'msg' => 'templets_dedecms 文件夹为空，请放入dedecms模板',
            'data' => array()
        );
    }

    if(isset($dedecms_files[$step])){
        convert_templets_one($dedecms_files[$step], $dedecms_path, $fastadmincms_path);
    }

//返回参数
    $total = count($dedecms_files);
    $inspect_result = 0;

    if($step > $total){
        // 模板文件去重
        unique_templets_filenames();

        // 审查没有替换的dedecms模板标签
        $inspect_result = inspect_dede_miss_tags();
    }

    $rs = array('total' => $total, 'inspect' => $inspect_result);

    if($step > $total){
        return array(
            'id' => 6,
            'state' => 0,
            'msg' => '恭喜，模板转换完成！',
            'data' => $rs
        );
    }
    else{
        return array(
            'id' => 6,
            'state' => 1,
            'msg' => 'success',
            'data' => $rs
        );
    }
}

function act_start_v4($data) {
    $rs = fastadmincms_remove_useless_data();

    if($rs){
        return array(
            'id' => 8,
            'state' => 0,
            'msg' => '恭喜，无用的数据（useless）已删除！',
            'data' => array()
        );
    }
    else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' => 'error',
            'data' => array()
        );
    }
}

//===================================================================
//其他作用区
//===================================================================

/**
 * 得到栏目数据
 * @param $tid
 * @param $link
 * @param $config
 * @return array
 */
function getArcType($tid, $db){

    //栏目表
    $sql = " SELECT * FROM `#@__arctype` WHERE id = {$tid} LIMIT 1 ";
    $db->query($sql);
    $result = $db->fetch_array();
    //栏目表
    if($result){
        return $result[0];
    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' => __FUNCTION__ . "栏目表数据异常",
            'data' => array()
        );
    }


}

/**
 * 得到模型数据
 * @param $cid
 * @param $link
 * @param $config
 * @return array
 */
function getChannelType($cid, $db){

    $sql = " SELECT * FROM `#@__channeltype` WHERE id = {$cid} LIMIT 1 ";
    $db->query($sql);
    $result = $db->fetch_array();
    //模型表
    if($result){
        return $result[0];
    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' =>  __FUNCTION__ . "模型表数据异常",
            'data' => array()
        );
    }

}

/**
 * 查询附加表结构信息
 * @param $tid
 * @param $link
 * @param $config
 * @return array
 */
function getChannelTypeAddonTable($tid, $db){

    //栏目表
    $result = getArcType($tid, $db);

    //模型表
    if($result){
        $result = getChannelType($result['channeltype'], $db);
    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' =>  __FUNCTION__ . "栏目表数据异常",
            'data' => array()
        );
    }

    //查询 附加表 信息
    $addonTable = array();
    if($result){
        $addonTable = getAddonTable($result['addtable'], $db);
    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' =>  __FUNCTION__ . "模型表数据异常",
            'data' => array()
        );
    }

    return $addonTable;

}

/**
 * 得到附加表 结构 数据
 * @param $tablename
 * @param $link
 * @return array
 */
function getAddonTable($tablename, $db){
    // todo 根据数据库配置设置表前缀 var_dump($db->db_config);
    $sql = " DESC {$tablename} ";
    $db->query($sql);
    $result = $db->fetch_array();

    //模型表
    if($result){
        //处理附加表 结构信息
        $addonTable = array();
        foreach($result as $i=>$item){
            foreach($item as $key=>$value){
                if('Field' == $key || 'Type' == $key){
                    $addonTable[$i][$key] = $value;
                }
            }

        }

        return $addonTable;

    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' => __FUNCTION__ . "附加表数据异常",
            'data' => array()
        );
    }
}

/**
 * 得到来源数据 - V1
 * @param $tid
 * @param $db
 * @return mixed
 */
function getFromData($tid, $db){

    $fromQuery = " SELECT arc.*, rel.* FROM `#@__posts` AS arc LEFT JOIN `#@__term_relationships` AS rel ON arc.ID = rel.object_id WHERE rel.term_taxonomy_id = '{$tid}' AND arc.post_status = 'publish' ORDER BY arc.ID ASC ";

    $db->query($fromQuery);
    $fromResults = $db->fetch_array();

    //todo 数据可能需要转换编码

    return $fromResults;
}


//2021年10月7日15:42:40 xslooi 添加函数 fastadmin-cms
/**
 * 查询Fastadmin-CMS附加表结构信息
 * @param $tid
 * @param $link
 * @param $config
 * @return array
 */
function getChannelToAddonTable($tid, $db){

    //栏目表
    $result = getFastadminChannel($tid, $db);

    //模型表
    if($result){
        $result = getFastadminModel($result['model_id'], $db);
    }
    else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' =>  __FUNCTION__ . "栏目表数据异常",
            'data' => array()
        );
    }

    //查询 附加表 信息
    $addonTable = array();
    if($result){
        $addonTable = getAddonTable('fa_' . $result['table'], $db);
    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' =>  __FUNCTION__ . "模型表数据异常",
            'data' => array()
        );
    }

    return $addonTable;

}

/**
 * 得到Fastadmin-CMS栏目数据
 * @param $tid
 * @param $link
 * @param $config
 * @return array
 */
function getFastadminChannel($tid, $db){

    //栏目表
    $sql = " SELECT * FROM `#@__cms_channel` WHERE id = {$tid} LIMIT 1 ";
    $db->query($sql);
    $result = $db->fetch_array();
    //栏目表
    if($result){
        return $result[0];
    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' => __FUNCTION__ . "栏目表数据异常",
            'data' => array()
        );
    }
}

/**
 * 得到Fastadmin-CMS模型数据
 * @param $cid
 * @param $link
 * @param $config
 * @return array
 */
function getFastadminModel($cid, $db){

    $sql = " SELECT * FROM `#@__cms_model` WHERE id = {$cid} LIMIT 1 ";
    $db->query($sql);
    $result = $db->fetch_array();
    //模型表
    if($result){
        return $result[0];
    }else{
        return array(
            'id' => 6,
            'state' => -1,
            'msg' =>  __FUNCTION__ . "模型表数据异常",
            'data' => array()
        );
    }

}


/**
 * 向目标Fastadmin-CMS数据库中插入一条数据
 * @param $fromResults
 * @param $totype
 * @param $toLink
 */
function insertFastadminOne($fromResults, $totype, $db){
    $is_archiveid_reset = isset($_COOKIE['is_archiveid_reset']) ? true : false;

    $channel = getFastadminChannel($totype, $db);
    $model = getFastadminModel($channel['model_id'], $db);
    $table_desc = getAddonTable('fa_' . $model['table'], $db);

    $addontablestruct = array();
    foreach($table_desc as $item){
        $addontablestruct[] = $item['Field'];
    }

    $insertTemporaryResult = true;
    if(!$is_archiveid_reset){
        // 插入临时数据 枚举类型 ’0‘ 字符处理
        $insertTemporaryResult = insertTemporaryData($fromResults, $model, $addontablestruct, $db);
    }




    // TODO 开始组合数据
    if(1 == $channel['model_id']){

    }
    else{

    }

    // 默认数据处理
    $fromResults['keywords'] = '';
    $fromResults['description'] = $fromResults['post_excerpt'];

    $fromResults['weight'] = $fromResults['ID'];
    $fromResults['click'] = $fromResults['views'];
    $fromResults['senddate'] = strtotime($fromResults['post_date']);
    $fromResults['pubdate'] = strtotime($fromResults['post_modified']);
    $fromResults['writer'] = '农村青年';

    // 标志处理
    $flag = '';

    // 跳转处理
    $outlink = '';

    $diyname = 'archive-' . $fromResults['ID'];
    $fromResults['title'] = htmlspecialchars_decode($fromResults['post_title']);

    // 详情处理
    $fromResults['post_content'] = preg_replace('/<!-- \/?wp:.*? -->/', '', $fromResults['post_content']);
    $fromResults['post_content'] = preg_replace('/^\s+$/', '', $fromResults['post_content']);
    $fromResults['post_content'] = preg_replace('/<img .*?src="".*?>/i', '', $fromResults['post_content']);
    $fromResults['post_content'] = str_replace('<figure', '<p', $fromResults['post_content']);
    $fromResults['post_content'] = str_replace('</figure>', '</p>', $fromResults['post_content']);
    $fromResults['post_content'] = str_replace('<figcaption>', '<strong>', $fromResults['post_content']);
    $fromResults['post_content'] = str_replace('</figcaption>', '</strong>', $fromResults['post_content']);


    // 描述为空处理
    if(empty($fromResults['post_excerpt']) && isset($fromResults['post_content']) && 10 < strlen($fromResults['post_content'])){
        $fromResults['description'] = addslashes(mb_substr(strip_tags($fromResults['post_content']), 0, 200));
    }



    // 插入id比表中最大id大或者是重新排序插入
    if($insertTemporaryResult){
        // 插入文章主表
        $iquery = " INSERT INTO `#@__cms_archives` (`user_id`, `channel_id`, `channel_ids`, `model_id`, `special_ids`, `admin_id`, `title`, `flag`, `style`, `image`, `images`, `seotitle`, `keywords`, `description`, `tags`, `price`, `outlink`, `weigh`, `views`, `comments`, `likes`, `dislikes`, `diyname`, `isguest`, `iscomment`, `createtime`, `updatetime`, `publishtime`, `deletetime`, `memo`, `status`) VALUES 
 ( 0, '{$totype}', '', '{$channel['model_id']}', '', 1, '{$fromResults['title']}', '{$flag}', '', '{$fromResults['image']}', '', '', '{$fromResults['keywords']}', '{$fromResults['description']}', '', 0.00, '{$outlink}', '{$fromResults['weight']}', '{$fromResults['click']}', 0, 0, 0, '{$diyname}', 10, 10, '{$fromResults['senddate']}', '{$fromResults['senddate']}', '{$fromResults['pubdate']}', NULL, '', 'normal');";
// echo $iquery;
        $db->query($iquery);
        $arcID = $db->insert_id();
    }
    else{
        $iquery = "
UPDATE `#@__cms_archives` SET `user_id` = 0, `channel_id` = '{$totype}', `channel_ids` = '', `model_id` = '{$channel['model_id']}', `special_ids` = '', `admin_id` = 1, `title` = '{$fromResults['title']}', `flag` = '{$flag}', `style` = '', `image` = '{$fromResults['image']}', `images` = '', `seotitle` = '', `keywords` = '{$fromResults['keywords']}', `description` = '{$fromResults['description']}', `tags` = '', `price` = 0.00, `outlink` = '{$outlink}', `weigh` = '{$fromResults['weight']}', `views` = '{$fromResults['click']}', `comments` = 0, `likes` = 0, `dislikes` = 0, `diyname` = '{$diyname}', `isguest` = 10, `iscomment` = 10, `createtime` = '{$fromResults['senddate']}', `updatetime` = '{$fromResults['senddate']}', `publishtime` = '{$fromResults['pubdate']}', `deletetime` = NULL, `memo` = '', `status` = 'normal' WHERE `id` = '{$fromResults['ID']}';
";
// echo $iquery;
        $db->query($iquery);
        $arcID = $fromResults['ID'];
    }


// var_dump($arcID);

// 插入文章附加表
    $fromResults['content'] = $fromResults['post_content']; // 附加表固定字段赋值
    $fromResults['author'] = $fromResults['writer']; // 附加表固定字段赋值

//    组合插入数据表值
    $addontablevalue = '';
    $addontableArray = array_slice($addontablestruct,1);
    foreach($addontableArray as $item) {
        //TODO 1、字符串转义； 2、编码转换
        if(false !== strpos($fromResults[$item], "'")){
            $fromResults[$item] = addslashes($fromResults[$item]);
        }

        $addontablevalue .= ",'{$fromResults[$item]}'";
    }


    $query = " INSERT INTO `#@__" . $model['table'] . "` (" . implode(',', $addontablestruct) . ") VALUES ('$arcID'{$addontablevalue})";

//    echo "<hr>" . $query;
    $intiny = $db->query($query);
//var_dump($intiny);
    if(!$intiny)
    {
     // TODO 附加表出错处理
        file_put_contents('addon_insert.log', $query . "\r\n\r\n", FILE_APPEND);
    }

    return true;
}


/**
 * 插入无用的数据，递增文章id
 * @param $fromResults
 * @param $model
 * @param $addontablestruct
 * @param $db
 */
function insertTemporaryData($fromResults, $model, $addontablestruct, $db){

    $default_values = array(
        'title' => 'useless',
        'keywords' => 'can be delete',
    );

    $sql = " SELECT MAX(id) AS max_num FROM `#@__cms_archives` ";
    $db->query($sql);
    $res = $db->fetch_array();
    $max_num = (int)$res[0]['max_num'];
//    var_dump($count);
//    var_dump($addontablestruct);
    // 如果插入id大于当前最大自增id才插入
    if($fromResults['ID'] > $max_num){

        $sql_insert = " INSERT INTO `#@__cms_archives` (`user_id`, `channel_id`, `channel_ids`, `model_id`, `special_ids`, `admin_id`, `title`, `flag`, `style`, `image`, `images`, `seotitle`, `keywords`, `description`, `tags`, `price`, `outlink`, `weigh`, `views`, `comments`, `likes`, `dislikes`, `diyname`, `isguest`, `iscomment`, `createtime`, `updatetime`, `publishtime`, `deletetime`, `memo`, `status`) 
 VALUES ('0', '0', '0', '0', '0', '0', '{$default_values['title']}', '0', '0', '0', '0', '0', '{$default_values['keywords']}', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', 'normal');
 ";
//echo $sql_insert;
//    var_dump($fromResults['id']);
        // 循环插入无用的站位数据
        while($fromResults['ID'] > ++$max_num){
            $db->query($sql_insert);
            $insert_id = $db->insert_id();
        }

        return true;
    }

    return false;
}


//2021年9月28日14:24:10 xslooi V1 添加函数
/**
 * 递归 转换类别栏目数据
 * @param $from_db
 * @param $to_db
 * @param int $reid
 */
function convert_category($from_db, $to_db, $reid=0){
    $category_ids_relate = config('category_ids_relate');
    if(true === $category_ids_relate){
        $category_ids_relate = array();
    }

    // 递归获取织梦栏目数据并分步骤插入fastadmin-cms栏目表
    $sql = " SELECT * FROM `#@__arctype` WHERE reid = '{$reid}' ORDER BY sortrank ASC ";
    $from_db->query($sql);
    $res = $from_db->fetch_array();

    foreach($res as $item){
        $temp_id = convert_category_one($to_db, $item);
        $category_ids_relate[] = array('old'=>array('id'=>$item['id'], 'reid'=>$item['reid']), 'new'=>$temp_id);
    }

    if(0 < count($category_ids_relate)){
        config('category_ids_relate', $category_ids_relate);
    }

    foreach($res as $item){
        convert_category($from_db, $to_db, $item['id']);
    }
}

/**
 * 转换一条类别栏目数据
 * @param $to_db
 * @param $row
 * @return mixed
 */
function convert_category_one($to_db, $row){

    $default_data = array (
        'id' => '1',
        'type' => 'list',
        'model_id' => '1',
        'parent_id' => '0',
        'name' => '栏目名称',
        'image' => '',
        'flag' => '',
        'seotitle' => '',
        'keywords' => '',
        'description' => '',
        'diyname' => 'default',
        'outlink' => '',
        'items' => '0',
        'weigh' => '1',
        'channeltpl' => 'channel_news.html',
        'listtpl' => 'list_news.html',
        'showtpl' => 'show_news.html',
        'pagesize' => '12',
        'vip' => '0',
        'listtype' => '0',
        'iscontribute' => '0',
        'isnav' => '1',
        'createtime' => time(),
        'updatetime' => time(),
        'status' => 'normal',
        'content' => '',
        'title' => '',
    );

    // TODO 转换赋值数据
    // 栏目模型id
    $default_data['model_id'] = 1;

    // 栏目列表类型
    if(0 == $row['ispart']){
        $default_data['type'] = 'list';
    }
    elseif(1 == $row['ispart']){
        $default_data['type'] = 'channel';
    }
    elseif(2 == $row['ispart']){
        $default_data['type'] = 'link';
        $default_data['outlink'] = $row['typedir'];
    }


    $default_data['name'] = $row['typename'];

    if(!empty($row['seotitle'])){
        $default_data['seotitle'] = $row['seotitle'];
    }

    if(!empty($row['keywords'])){
        $default_data['keywords'] = $row['keywords'];
    }

    if(!empty($row['description'])){
        $default_data['description'] = $row['description'];
    }

    // 默认diyname不能重复
    $default_data['diyname'] = 'diyname' . mt_rand(12345678, 99999999);

    $default_data['channeltpl'] = convert_category_templets_file_name($row['tempindex'], 'channel');
    $default_data['listtpl'] = convert_category_templets_file_name($row['templist'], 'list');
    $default_data['showtpl'] = convert_category_templets_file_name($row['temparticle'], 'show');

    // todo 名称发生变更，写入变更配置文件
    $templets_filenames = array();
    $filename = substr($row['tempindex'], strrpos($row['tempindex'], '/') + 1);

    if($default_data['channeltpl'] != $filename){
        $templets_filenames[] = array(
            'old_name' => $filename,
            'new_name' => $default_data['channeltpl'],
        );
    }

    $filename = substr($row['templist'], strrpos($row['templist'], '/') + 1);

    if($default_data['listtpl'] != $filename){
        $templets_filenames[] = array(
            'old_name' => $filename,
            'new_name' => $default_data['listtpl'],
        );
    }

    $filename = substr($row['temparticle'], strrpos($row['temparticle'], '/') + 1);

    if($default_data['showtpl'] != $filename){
        $templets_filenames[] = array(
            'old_name' => $filename,
            'new_name' => $default_data['showtpl'],
        );
    }

    if(!empty($templets_filenames)){
        config_templets_filenames($templets_filenames);
    }

    if(!empty($row['ishidden'])){
        $default_data['isnav'] = 0;
    }

    if(isset($row['enname']) && !empty($row['enname'])){
        $default_data['title'] = $row['enname'];
    }

    if(isset($row['content']) && !empty($row['content'])){
        $default_data['content'] = addslashes($row['content']);
    }


//    var_dump($default_data);
    $sql_insert = "INSERT INTO `#@__cms_channel` (`type`, `model_id`, `name`, `image`, `flag`, `seotitle`, `keywords`, `description`, `diyname`, `outlink`, `channeltpl`, `listtpl`, `showtpl`, `pagesize`, `vip`, `listtype`, `iscontribute`, `isnav`, `createtime`, `updatetime`, `status`, `content`, `title`) 
VALUES ('{$default_data['type']}', {$default_data['model_id']}, '{$default_data['name']}', '{$default_data['image']}', '{$default_data['flag']}', '{$default_data['seotitle']}', '{$default_data['keywords']}', '{$default_data['description']}', '{$default_data['diyname']}', '{$default_data['outlink']}', '{$default_data['channeltpl']}', '{$default_data['listtpl']}', '{$default_data['showtpl']}', 12, '{$default_data['vip']}', '{$default_data['listtype']}', '{$default_data['iscontribute']}', '{$default_data['isnav']}', '{$default_data['createtime']}', '{$default_data['updatetime']}' ,'{$default_data['status']}', '{$default_data['content']}' ,'{$default_data['title']}')";

//    echo $sql_insert;
//    echo PHP_EOL;
//    exit;

    $to_db->query($sql_insert);
    $channel_id = $to_db->insert_id();

    // TODO 插入数据库后更新内容  weigh、diyname
    $sql_update = " UPDATE `#@__cms_channel` SET `weigh` = '{$channel_id}', `diyname` = '" . $default_data['type'] . $channel_id . "' WHERE `id` = {$channel_id} ";
    $to_db->query($sql_update);

    $diyname = convert_category_typedir($row);
    $sql_update = " UPDATE `#@__cms_channel` SET `diyname` = '" . $diyname . "' WHERE `id` = {$channel_id} ";

    if(!empty($diyname)){
        $to_db->query($sql_update);
    }


    return $channel_id;
}

/**
 *
 * @param $total
 * @param $channel_id
 * @param $to_db
 */
function setFastadminChannelItems($total, $channel_id, $to_db){
    $sql_update = " UPDATE `#@__cms_channel` SET `items` = '{$total}' WHERE `id` = {$channel_id} ";
    $to_db->query($sql_update);
}

/**
 * 转换模板文件名称风格 如：index_archive.htm => channel_archive.html
 * @param $templet_name
 * @param $type
 * @return string
 */
function convert_category_templets_file_name($templet_name, $type){

    $templet_name = strtolower($templet_name);
    $paths = explode('/', $templet_name);

    $filename = $origin_name = $paths[count($paths)-1];

    if('channel' == $type){
        if(false !== stripos($filename, '_index.htm')){
            $filename = 'channel_' . str_ireplace('_index.htm', '.htm', $filename);
        }
        elseif(0 === stripos($filename, 'index_')){
            $filename = 'channel_' . str_ireplace('index_', '', $filename);
        }
        elseif(false !== stripos($filename, '_article.htm')){
            $filename = 'channel_' . str_ireplace('_article.htm', '.htm', $filename);
        }
        elseif(0 === stripos($filename, 'channel_')){

        }
        else{
            $filename = 'channel_' . $filename;
        }
    }
    elseif('list' == $type){
        if(false !== stripos($filename, '_list.htm')){
            $filename = 'list_' . str_ireplace('_list.htm', '.htm', $filename);
        }
        elseif(0 === stripos($filename, 'list_')){

        }
        else{
            $filename = 'list_' . $filename;
        }
    }
    elseif('show' == $type){
        if(false !== stripos($filename, '_show.htm')){
            $filename = 'show_' . str_ireplace('_show.htm', '.htm', $filename);
        }
        elseif(0 === stripos($filename, 'article_')){
            $filename = 'show_' . str_ireplace('article_', '', $filename);
        }
        elseif(0 === stripos($filename, 'show_')){

        }
        else{
            $filename = 'show_' . $filename;
        }
    }

    $result = $filename . 'l';

    return $result;
}

/**
 * 转换的模板文件名称写入配置文件: config/templets_filenames.php
 * @param $old_name
 * @param $new_name
 */
/**
 * @param $change_names
 */
function config_templets_filenames($change_names){
   $templets_filenames = config('templets_filenames');
   if(true === $templets_filenames){
       $templets_filenames = array();
   }

   foreach($change_names as $item){
       $templets_filenames[] = $item;
   }

   config('templets_filenames', $templets_filenames);
}

/**
 * 根据导入的栏目id关系，恢复转换的栏目上下级关系
 * @param $to_db
 */
function convert_category_reid($to_db){
    $category_ids_relate = config('category_ids_relate');

    foreach($category_ids_relate as $item){
        if(0 != $item['old']['reid']){
            foreach($category_ids_relate as $value){
                if($value['old']['id'] == $item['old']['reid']){
                    $sql = " UPDATE `#@__cms_channel` SET `parent_id` = '{$value['new']}' WHERE `id` = '{$item['new']}' ";
                    $to_db->query($sql);

                    break;
                }
            }
        }
    }
}

/**
 * 根据文件保存目录转换为首字母大写的字符串 如：{cmspath}/cases/brand => CasesBrand
 * @param $row
 * @return string
 */
function convert_category_typedir($row){
    $diyname = '';
    $dir = explode('/', $row['typedir']);
    if(false !== strpos($dir[0], '{')){
        unset($dir[0]);
    }

    foreach($dir as $item){
        $diyname .= ucfirst($item);
    }

    // 默认不是index.html的栏目处理
    if('index.html' != $row['defaultname']){
        $defaultname = str_replace('.html', '', $row['defaultname']);
        $diyname .= ucfirst($defaultname);
    }

    return $diyname;
}

/**
 * 将多个栏目重复的模板文件名去重变唯一
 */
function unique_templets_filenames(){
    $templets_filenames = config('templets_filenames');
    if(true === $templets_filenames){
        $templets_filenames = array();
    }

    $result = array();
    $old_names = array();

    foreach($templets_filenames as $item){
        if(!in_array($item['old_name'], $old_names)){
            $result[] = $item;
        }

        $old_names[] = $item['old_name'];
    }

    config('templets_filenames', $result);
}

/**
 * 重置（删除）需要重新配置的文件
 */
function reset_config(){
    // 重置类别关联关系配置
    $path = ROOT_DIR . '/config/category_ids_relate.php';
    if(file_exists($path)) {
        unlink($path);
    }

    // 重置模板文件关系配置
    $path = ROOT_DIR . '/config/templets_filenames.php';
    if(file_exists($path)) {
        unlink($path);
    }
}


/**
 * 转换一个dedecms模板文件
 * @param $dedecms_file
 * @param $dedecms_path
 * @param $fastadmincms_path
 */
function convert_templets_one($dedecms_file, $dedecms_path, $fastadmincms_path){

    $templets_type = detect_templets_type($dedecms_file);

    $html_content = get_file_content($dedecms_file);

    multi_replace_head($html_content);

    multi_replace_dede_include($html_content);

    multi_replace_dede_miscellaneous($html_content, $templets_type);

    multi_replace_dede_singleclosure($html_content, $templets_type);

    multi_replace_dede_doubleclosure($html_content, $templets_type);

    // 处理模板名称变更情况
    $templets_fileames = config('templets_filenames');

    $out_path = '';
    $dedecms_path_info = explode('/', $dedecms_file);
    $dedecms_path_name = strtolower($dedecms_path_info[count($dedecms_path_info)-1]);

    // 处理手机_m模板后缀
    $dedecms_name = str_replace('_m.htm', '.htm', $dedecms_path_name);

    foreach ($templets_fileames as $fileame){
        if($dedecms_name == strtolower($fileame['old_name'])){
            $out_path = $fastadmincms_path . $fileame['new_name'];

            break;
        }
    }

    // 模板文件名未变更
    if(empty($out_path)){
        $out_path = str_replace($dedecms_path, $fastadmincms_path, $dedecms_file) . 'l';
    }

    // 处理手机版模板
    if('_m.htm' == substr($dedecms_path_name, -6)){
        $out_path = str_replace('_m.htm', '.htm', $out_path);
        $out_path = str_replace($fastadmincms_path, $fastadmincms_path . 'mobile/', $out_path);
    }

//        var_dump($out_path);

    put_file_content($out_path, $html_content);

}


//======================================================================================================================
/**
 * 递归删除一个目录包含子目录和文件 (不包括自身)
 * @param $path
 */
function deldir($path){
    //如果是目录则继续
    if(is_dir($path)){
        //扫描一个文件夹内的所有文件夹和文件并返回数组
        $p = scandir($path);
        foreach($p as $val){
            //排除目录中的.和..
            if($val !="." && $val !=".."){
                //如果是目录则递归子目录，继续操作
                if(is_dir($path.$val)){
                    //子目录中操作删除文件夹和文件
                    deldir($path.$val.'/');
                    //目录清空后删除空文件夹
                    @rmdir($path.$val.'/');
                }else{
                    //如果是文件直接删除
                    unlink($path.$val);
                }
            }
        }
    }
}

/**
 * 得到某个目录的文件列表
 * @param string $path_pattern
 * @return array|false
 */
function get_file_list($path_pattern){
    return glob($path_pattern);
}

/**
 * 得到文件内容
 * @param $file_path
 * @return false|string
 */
function get_file_content($file_path){
    return file_get_contents($file_path);
}

/**
 * 输出文件内容
 * @param $file_path
 * @param $html_body
 * @return bool|int
 */
function put_file_content($file_path, &$html_body){
    return file_put_contents($file_path, $html_body);
}

/**
 * 根据 HTML 开始标签 返回该标签的整段闭合HTML代码
 * TODO 注意此函数未处理 注释中的代码 <!-- --> 脚本代码 样式代码
 * !可能有多字节字符问题
 * 不匹配 </div > 闭合标签中有空格问题
 * @param $tag_start
 * @param $html
 * @return bool|string
 */
function get_closing_tag_html($tag_start, $html){
    if(empty($tag_start) || empty($html)){
        exit(__LINE__ . __FUNCTION__ . ' Parameters Error!');
    }

    //HTML 单闭合标签
    $html_single_tag = array('br', 'hr', 'area', 'base', 'img', 'input', 'link', 'meta', 'basefont', 'param', 'col', 'frame', 'embed');

    $html_fragment = ''; //HTML闭合标签整段代码

    //直接付给body 可能用于 body 内部代码段
    $html_body = $html;

    if(false !== stripos($html, '<body')){
        $html_body = substr($html, stripos($html, '<body'));
    }

    if(false !== stripos($html_body, '</body>')){
        $html_body = substr($html_body, 0, stripos($html_body, '</body>') + 7);
    }

    //如果没有找到开始代码段
    if(stripos($html_body, $tag_start) !== false){
        $tag_name_temp = explode(' ', $tag_start);
        $tag_name = substr($tag_name_temp[0], 1);
        $tag_name = str_replace(array('<', '>'), '', $tag_name);


        $html_start = substr($html_body, strpos($html_body, $tag_start));
        if(in_array($tag_name, $html_single_tag)){
            $html_fragment = substr($html_start, 0, strpos($html_start, '>') + 1);
        }
        else{

            $html_tag_end = '</' . $tag_name . '>';
            $html_tag_end_count = substr_count($html_body, $html_tag_end);

            $html_fragment = substr($html_start, 0, strpos($html_start, $html_tag_end) + strlen($html_tag_end));
            $html_fragment_length = strlen($html_fragment);
            $html_tag_start_count = substr_count($html_fragment, '<' . $tag_name . ' ') + substr_count($html_fragment, '<' . $tag_name . '>');
            $end_count = 1; //标签结束标志

            //遍历HTML 闭合标签代码 找到闭合位置
            for($i=1; $i<$html_tag_end_count; $i++){

                if($html_tag_start_count > $end_count){

                    $html_fragment = substr($html_start, $html_fragment_length);
                    $html_fragment = substr($html_fragment, 0, strpos($html_fragment, $html_tag_end) + strlen($html_tag_end));
                    $html_fragment = substr($html_start, 0, $html_fragment_length + strlen($html_fragment));
                    $html_fragment_length = strlen($html_fragment);
                    $html_tag_start_count = substr_count($html_fragment, '<' . $tag_name . ' ') + substr_count($html_fragment, '<' . $tag_name . '>');
                    $end_count++;
                }
                else{
                    break;
                }
            }
        }

    }

    return $html_fragment;
}


/**
 * 替换页面 标题、描述、关键字
 * TODO 此函数有bug 如果源网页中没有以上属性则不能替换成功
 * TODO 升级算法：
 * 1、把文档中 title、keywords、description、author、copyright 等属性直接替换为空
 * 2、然后直接都替换到 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 标签后边 则可以解决不存在某个属性的情况
 * @param $html_body
 */
function multi_replace_head(&$html_body){
    //此处正则替换多数标签
    $html_body = preg_replace("/<title>.*?<\/title>/i", '<title>{cms:config name="cms.title"/}-{cms:config name="cms.sitename"/}</title>', $html_body);

    $html_body = preg_replace("/<meta[\s]+name=\"keywords\"[\s]+content=\".*/i", '<meta name="keywords" content="{cms:config name=\'cms.keywords\'/}"/>', $html_body);
    $html_body = preg_replace("/<meta[\s]+name=\"description\"[\s]+content=\".*/i", '<meta name="description" content="{cms:config name=\'cms.description\'/}"/>', $html_body);
    $html_body = preg_replace("/<meta[\s]+name=\"author\"[\s]+content=\".*/i", "<meta name=\"author\" content=\"xslooi\"/>", $html_body);
    $html_body = preg_replace("/<meta[\s]+name=\"copyright\"[\s]+content=\".*/i", "<meta name=\"copyright\" content=\"xslooi\"/>", $html_body);
    $html_body = preg_replace("/<meta[\s]+name=\"generator\"[\s]+content=\".*/i", "<meta name=\"generator\" content=\"xslooi\"/>", $html_body);

    //内容在前标签
    $html_body = preg_replace("/<meta[\s]+content=\".*[\s]+name=\"keywords.*/i", '<meta name="keywords" content="{cms:config name=\'cms.keywords\'/}"/>', $html_body);
    $html_body = preg_replace("/<meta[\s]+content=\".*[\s]+name=\"description.*/i", '<meta name="description" content="{cms:config name=\'cms.description\'/}"/>', $html_body);
    $html_body = preg_replace("/<meta[\s]+content=\".*[\s]+name=\"author.*/i", "<meta name=\"author\" content=\"xslooi\"/>", $html_body);
    $html_body = preg_replace("/<meta[\s]+content=\".*[\s]+name=\"copyright.*/i", "<meta name=\"copyright\" content=\"xslooi\"/>", $html_body);
    $html_body = preg_replace("/<meta[\s]+content=\".*[\s]+name=\"generator.*/i", "<meta name=\"generator\" content=\"xslooi\"/>", $html_body);

}

/**
 * 替换dedecms模板中的include标签
 * @param $html_content
 */
function multi_replace_dede_include(&$html_content){
    $templets_tag = '{include file=\'common/%s\' /}';
    $pattern_include = '/\{dede:include[\s]+filename[\s]?=[\s]?[\'"](.*?)[\'"]\/\}/i';
    $matches = array();
    preg_match_all($pattern_include, $html_content, $matches);

    // todo 保存include的文件名称 以备复制文件目录时候使用 可以直接改名
//    var_dump($matches);
    $templets_filenames = config('templets_filenames');

    if(isset($matches[0][0]) && isset($matches[1][0])){
        foreach($matches[0] as $key=>$item){
            $file_name = strtolower($matches[1][$key]);
            $file_name = str_ireplace('_m.htm', '.htm', $file_name); // 处理手机模板
            $templets_filenames[] = array(
                'old_name' => $matches[1][$key],
                'new_name' => 'common/' . $file_name . 'l'
            );
            $file_name = str_replace('.htm', '', $file_name);
            $templets_replace = sprintf($templets_tag, $file_name);
//            var_dump($html_tag);

            $html_content = str_replace($item, $templets_replace, $html_content);
        }
    }

    config('templets_filenames', $templets_filenames);
}

/**
 * 替换dedecms模板标签中的杂项如固定的代码段
 * @param $html_content
 * @param $type
 */
function multi_replace_dede_miscellaneous(&$html_content, $type){
    // 特殊的调用链接
    $html_content = str_replace('<script type="text/javascript">
    (function () {var current_nav = document.getElementById("nav_{dede:type}[field:id function=\'GetTopid(@me)\'/]{/dede:type}");current_nav && current_nav.setAttribute("class", current_nav.getAttribute(\'class\') ? (current_nav.getAttribute(\'class\') + " on") : " on");})();
</script>', '', $html_content);
    $html_content = str_replace('/m/list.php?tid={dede:type}[field:id /]{/dede:type}', '{cms:channellist typeid=\'$__CHANNEL__.id\' id=\'channel\'}{$channel.url}{/cms:channellist}', $html_content);
    $html_content = str_replace('{dede:type}[field:typelink /]{/dede:type}', '{cms:channellist typeid=\'$__CHANNEL__.id\' id=\'channel\'}{$channel.url}{/cms:channellist}', $html_content);
    $html_content = str_replace('{dede:type}[field:typelink/]{/dede:type}', '{cms:channellist typeid=\'$__CHANNEL__.id\' id=\'channel\'}{$channel.url}{/cms:channellist}', $html_content);
    $html_content = str_ireplace('/m"', '/"', $html_content);
    $html_content = str_ireplace('/m/"', '/"', $html_content);
    $html_content = str_ireplace('[field:global.cfg_basehost /]', '', $html_content);
    $html_content = str_ireplace('[field:defaultname/]', '', $html_content);
    $html_content = str_ireplace('{dede:adminname/}', 'admin', $html_content);
    $html_content = str_ireplace('{dede:global.cfg_indexurl/}', '/', $html_content);
    $html_content = str_ireplace('/m/index.php', '/', $html_content);
    $html_content = str_ireplace('/index.php', '/', $html_content);
    $html_content = str_ireplace('/index.html', '/', $html_content);
    $html_content = str_ireplace('<div id="Paging"><div class="Pagination">{dede:pagelist listitem="info,index,end,pre,next,pageno" listsize="3"/}</div></div>', '<div id="Paging">
    <div class="pager">
        {cms:pageinfo type="full" /}
    </div>

    {if $__PAGELIST__->isEmpty()}
    <div class="loadmore loadmore-line loadmore-nodata"><span class="loadmore-tips">暂无数据</span></div>
    {/if}
</div>
    ', $html_content);

    $html_content = str_ireplace('<div id="Paging"><div class="Pagination">{dede:pagelist listitem="index,end,pre,next,pageno" listsize="1"/}</div></div>', '<div id="Paging">
    <div class="pager">
        {cms:pageinfo type="full" /}
    </div>

    {if $__PAGELIST__->isEmpty()}
    <div class="loadmore loadmore-line loadmore-nodata"><span class="loadmore-tips">暂无数据</span></div>
    {/if}
</div>
    ', $html_content);

    $html_content = str_ireplace('{dede:type}[field:id function=\'GetTopType(@me)\' /]{/dede:type}', '{php} if(0==$__CHANNEL__->parent_id){ $parent_id=$__CHANNEL__->id;}else{ $parent_id=$__CHANNEL__->parent_id;} {/php}
{cms:channellist typeid=\'$parent_id\' id=\'channel\'}{$channel.name}{/cms:channellist}
    ', $html_content);

    // 常用联系方式
    $html_content = str_replace('{dede:global.cfg_powerby/}', '{cms:config name=\'cms.sitecopyright\'/}', $html_content);
    $html_content = str_replace('{dede:global.cfg_beian/}', '{cms:config name=\'cms.icp_license\'/}', $html_content);
    $html_content = str_replace('{dede:global.cfg_tel/}', '{cms:config name=\'cms.contact_tel\'/}', $html_content);
    $html_content = str_replace('{dede:global.cfg_phone/}', '{cms:config name=\'cms.contact_phone\'/}', $html_content);
    $html_content = str_replace('{dede:global.cfg_qq/}', '{cms:config name=\'cms.contactqq\'/}', $html_content);
    $html_content = str_replace('{dede:global.cfg_email/}', '{cms:config name=\'cms.contact_email\'/}', $html_content);
    $html_content = str_replace('{dede:global.cfg_address/}', '{cms:config name=\'cms.contact_address\'/}', $html_content);
    $html_content = str_replace('{dede:global.cfg_contact_str/}', '{cms:config name=\'cms.contact_third\'/}', $html_content);

    // 系统常用配置
    $html_content = str_replace('{dede:global.cfg_webname/}', '{cms:config name=\'cms.sitename\'/}', $html_content);


    // 手动填写的动态链接 如: /plus/list.php?tid= ; /m/list.php?tid=
    $templets_tag = 'href="{cms:channellist typeid=\'%s\' id=\'channel\'}{$channel.url}{/cms:channellist}"';

    $pattern_field = '/href[\s]*=[\s]*[\'"]\/[m|plus]\/list\.php\?tid=([\d]+)[\'"]/i';
    $matches = array();
    preg_match_all($pattern_field, $html_content, $matches);

//    var_dump($matches);

    if(isset($matches[0][0]) && isset($matches[1][0])){
        foreach($matches[0] as $key=>$item){
            $typeid = intval($matches[1][$key]);
            $channel_id = get_related_typeid($typeid);

            $templets_replace = sprintf($templets_tag, $channel_id);
            $html_content = str_replace($item, $templets_replace, $html_content);
        }
    }


    // 模板分类
    if('show' == $type){
        $html_content = str_replace('<script src="{dede:field name=\'phpurl\'/}/count.php?view=yes&aid={dede:field name=\'id\'/}&mid={dede:field name=\'mid\'/}" type=\'text/javascript\'></script>', '{cms:archives name=\'views\' /}', $html_content);
        $html_content = str_replace('<script src="{dede:field name=\'phpurl\'/}/count.php?view=yes&aid={dede:field name=\'id\'/}" type=\'text/javascript\'></script>', '{cms:archives name=\'views\' /}', $html_content);

    }
    elseif('list' == $type){
        $html_segment = get_closing_tag_html('<div id="Paging">', $html_content);
        $html_page = '<div id="Paging">
    <div class="pager">
        {cms:pageinfo type="full" /}
    </div>

    {if $__PAGELIST__->isEmpty()}
    <div class="loadmore loadmore-line loadmore-nodata"><span class="loadmore-tips">暂无数据</span></div>
    {/if}
</div>
    ';
        $html_content = str_replace($html_segment, $html_page, $html_content);
    }
    elseif('channel' == $type){

    }
    else{ //other

    }
}

/**
 * 替换dedecms标签中的单闭合标签 如：{dede:field ...../}
 * @param $html_content
 * @param $type
 */
function multi_replace_dede_singleclosure(&$html_content, $type){
    // 匹配 field name="" 语法
    $templets_tag = '';

    $pattern_field = '/\{dede:field[\s]+name[\s]?=[\s]?[\'"](.*?)[\'"]\/\}/i';
    $matches = array();
    preg_match_all($pattern_field, $html_content, $matches);

//    var_dump($matches);

    if(isset($matches[0][0]) && isset($matches[1][0])){
        foreach($matches[0] as $key=>$item){
            $tag_name = strtolower($matches[1][$key]);

            if('position' == $tag_name){
                $templets_tag = '{cms:breadcrumb id="item"}
                <a href="{$item.url}" title="{$item.name}" >{$item.name}</a> »
                {/cms:breadcrumb}';

                $html_content = str_replace($item, $templets_tag, $html_content);
            }

            if('typename' == $tag_name){
                $templets_tag = '{cms:channel name=\'name\' /}';
                $html_content = str_replace($item, $templets_tag, $html_content);
            }


            // todo 其他field标签
            if('show' == $type){
                $templets_tag = '{cms:archives name=\'%s\' /}';


                if('arcurl' == $tag_name){
                    $templets_tag = sprintf($templets_tag, 'fullurl');
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }
                elseif('id' == $tag_name){
                    $templets_tag = sprintf($templets_tag, 'id');
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }
                elseif('mid' == $tag_name){
                    $templets_tag = sprintf($templets_tag, 'admin_id');
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }

            }
            elseif('list' == $type){

            }
            elseif('channel' == $type){

            }
            else{ //other

            }

        }
    }

    // 匹配field .点 content 语法
    $pattern_field = '/\{dede:field\.([a-z]+)[\s]*\/\}/i';
    $matches = array();
    preg_match_all($pattern_field, $html_content, $matches);
//    var_dump($matches);

    if(isset($matches[0][0]) && isset($matches[1][0])){
        foreach($matches[0] as $key=>$item){
            $tag_name = strtolower($matches[1][$key]);

            if('typename' == $tag_name){
                $html_content = str_replace($item, '{cms:channel name=\'name\' /}', $html_content);
            }
            elseif('content' == $tag_name){
                $html_content = str_replace($item, '{cms:channel name=\'content\' /}', $html_content);
            }


            if('show' == $type){
                $templets_tag = '{cms:archives name=\'%s\' /}';

                if('title' == $tag_name){
                    $templets_tag = sprintf($templets_tag, 'title');
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }
                elseif('writer' == $tag_name){
                    $templets_tag = sprintf($templets_tag, 'author');
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }
                elseif('litpic' == $tag_name){
                    $templets_tag = sprintf($templets_tag, 'image');
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }
                elseif('source' == $tag_name){
                    $html_content = str_replace($item, '网络', $html_content);
                }
                elseif('body' == $tag_name){
                    $templets_tag = sprintf($templets_tag, 'content');
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }
                else{
                    $templets_tag = sprintf($templets_tag, $tag_name);
                    $html_content = str_replace($item, $templets_tag, $html_content);
                }
            }
            elseif('list' == $type){

            }
            elseif('channel' == $type){

            }
            else{ //other

            }
        }
    }


    // 匹配field .点 content 语法 带function的
    $pattern_field = '/\{dede:field\.([a-z]+)[\s]*function[\s]*=[\s]*[\'"](.*?)[\'"][\s]*\/\}/i';
    $matches = array();
    preg_match_all($pattern_field, $html_content, $matches);
//    var_dump($matches);

    if(isset($matches[0][0]) && isset($matches[1][0])){
        foreach($matches[0] as $key=>$item){
            $tag_name = strtolower($matches[1][$key]);

            if('pubdate' == $tag_name){

            }


            // 模板分类
            if('show' == $type){
                if('pubdate' == $tag_name){
                    $html_content = str_replace($item, '{cms:archives name=\'publishtime|datetime\' /}', $html_content);
                }
            }
            elseif('list' == $type){

            }
            elseif('channel' == $type){

            }
            else{ //other

            }
        }
    }

    // 匹配{dede:prenext 上一页、下一页单闭合标签
    $pattern_field = '/\{dede:prenext[\s]+([\w\W]+?)\/\}/i';
    $matches = array();
    preg_match_all($pattern_field, $html_content, $matches);
//    var_dump($matches);

    if(isset($matches[0][0]) && isset($matches[1][0])){
        foreach($matches[0] as $key=>$item){
            $params = $matches[1][$key];

            if(false !== strpos($params, 'next')){
                $convert_html = '
{cms:prevnext id="prev" type="prev" archives="__ARCHIVES__.id" channel="__CHANNEL__.id"}
    <a href="{$prev.url}">{$prev.title}</a>
    {/cms:prevnext}
    ';
            }
            elseif(false !== strpos($params, 'pre')){
                $convert_html = '
{cms:prevnext id="next" type="next" archives="__ARCHIVES__.id" channel="__CHANNEL__.id"}
    <a href="{$next.url}">{$next.title}</a>
{/cms:prevnext}
    ';
            }

            $html_content = str_replace($item, $convert_html, $html_content);
        }
    }
}

/**
 * 替换dedecms标签中的闭合标签 如：{dede:arclist typeid=''} {/dede:arclist}
 * @param $html_content
 * @param $type
 */
function multi_replace_dede_doubleclosure(&$html_content, $type){
    // todo 仅列出常用的标签
    $dede_doubleclosure_tags = array('arclist', 'channel', 'flink', 'likearticle', 'php', 'sql', 'type', 'prenext', 'list');

    foreach ($dede_doubleclosure_tags as $tag){
        $test_tag = '{dede:' . $tag . ' ';
        if(false === strpos($html_content, $test_tag)){
           continue;
        }

        $pattern = '/\{dede:' . $tag . '[\s]+([\w\W]+?)\}([\w\W]+?)\{\/dede:' . $tag . '\}/';
        $matches = array();
        preg_match_all($pattern, $html_content, $matches);

//        var_dump($matches);
        if(isset($matches[0][0]) && isset($matches[1][0]) && isset($matches[2][0])){
            foreach($matches[0] as $key=>$match){
                $convert_html = convert_dede_doubleclosure_tag($matches[1][$key], $matches[2][$key], $tag);

                $html_content = str_replace($match, $convert_html, $html_content);
            }
        }

    }
}

/**
 * 侦测模板的类别 如：channel/list/show
 * TODO 此函数根据命名侦测（命名不规范会有bug）可以在转换模板文件名时候根据程序数据指定模板类型
 * @param $templets_path
 * @return string
 */
function detect_templets_type($templets_path){
    $type = 'other';
    $filename = explode('/', $templets_path);
    $filename = $filename[count($filename)-1];

    if(false !== stripos($filename, '_index.htm')){
        $type = 'channel';
    }
    elseif(false !== stripos($filename, '_index_m.htm')){
        $type = 'channel';
    }
    elseif(0 === stripos($filename, 'index_')){
        $type = 'channel';
    }
    elseif(false !== stripos($filename, '_list.htm')){
        $type = 'list';
    }
    elseif(false !== stripos($filename, '_list_m.htm')){
        $type = 'list';
    }
    elseif(false !== stripos($filename, '_show.htm')){
        $type = 'show';
    }
    elseif(false !== stripos($filename, '_show_m.htm')){
        $type = 'show';
    }
    elseif(0 === stripos($filename, 'article_')){
        $type = 'show';
    }

    return $type;
}

/**
 * 转换dedecms模板闭合标签为Fastadmin-cms标签
 * @param $params
 * @param $templets
 * @param $tag
 * @return string
 */
function convert_dede_doubleclosure_tag($params, $templets, $tag){

//    var_dump($html);
//    var_dump($params);
//    var_dump($templets);
//    var_dump($tag);

    $convert_html = '';
    $convert_html_head = '';
    $convert_html_content = '';
    $convert_html_foot = '';


    if('arclist' == $tag){
        // 处理参数
        $params = format_templets_html($params);
        $params_array = explode(' ', $params);
//        var_dump($params_array);
        $params_default = array('channel'=>3, 'row'=>8, 'flag'=>'', 'extend'=>'');

        foreach($params_array as $item){
            $item = str_replace(array('"', "'"), '', $item); // 去除引号

            if(0 === strpos($item, 'flag=')){
                if(false !== strpos($item, 'c')){
                    $params_default['flag'] = 'recommend';
                }

                if(false !== strpos($item, 'h')){
                    $params_default['flag'] = 'top';
                }

                if(false !== strpos($item, 'p')){
                    $params_default['extend'] = ' condition="(\'\' != a.image)"';
                }
            }
            elseif(0 === strpos($item, 'typeid=')){
                $params_default['channel'] = get_related_typeid(intval(str_replace('typeid=', '', $item)));
            }
            elseif(0 === strpos($item, 'row=')){
                $params_default['row'] = intval(str_replace('row=', '', $item));
            }
        }

        // 调用标签
        $convert_html_head = '{cms:arclist channel="' . $params_default['channel'] . '" row="' . $params_default['row'] . '" flag="' . $params_default['flag'] . '"' . $params_default['extend'] . ' type="sons" id="item" orderby="weigh" orderway="desc" addon="true"}';
        $convert_html_foot = '{/cms:arclist}';

        // 标签内容解析
        $convert_html_content = format_templets_html($templets);
//        var_dump($convert_html_content);
//        echo PHP_EOL . '#######################################################################' . PHP_EOL;
        $search_tags = array(
            '[field:global.autoindex/]',
            '[field:arcurl/]',
            '/m/view.php?aid=[field:id/]',
            '/plus/view.php?aid=[field:id/]',
            '[field:title/]',
            '[field:fulltitle/]',
            '[field:litpic/]',
            '[field:description/]',
            '[field:info/]',
            '[field:infos/]',
            '[field:pubdate function="MyDate(\'Y-m-d\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y-m\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y\',@me)"/]',
            '[field:pubdate function="MyDate(\'m-d\',@me)"/]',
            '[field:pubdate function="MyDate(\'d\',@me)"/]',

            '[field:pubdate function="MyDate(\'Y/m/d\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y/m\',@me)"/]',
            '[field:pubdate function="MyDate(\'m/d\',@me)"/]',

            '[field:pubdate function=\'strftime("%Y",@me)\'/]',
        );

        $replace_tags = array(
            '{$i}',
            '{$item.url}',
            '{$item.url}',
            '{$item.url}',
            '{$item.title}',
            '{$item.title}',
            '{$item.image}',
            '{$item.description}',
            '{$item.description}',
            '{$item.description}',
            '{:date("Y-m-d", $item[\'publishtime\'])}',
            '{:date("Y-m", $item[\'publishtime\'])}',
            '{:date("Y", $item[\'publishtime\'])}',
            '{:date("m-d", $item[\'publishtime\'])}',
            '{:date("d", $item[\'publishtime\'])}',

            '{:date("Y/m/d", $item[\'publishtime\'])}',
            '{:date("Y/m", $item[\'publishtime\'])}',
            '{:date("m/d", $item[\'publishtime\'])}',

            '{:date("Y", $item[\'publishtime\'])}',
        );

        $convert_html_content = str_ireplace($search_tags, $replace_tags, $convert_html_content);
//        var_dump($convert_html_content);

    }
    elseif('channel' == $tag){
//        var_dump($html);
        // 处理参数
        $params = format_templets_html($params);
        $params_array = explode(' ', $params);
//        var_dump($params_array);
        $params_default = array('typeid'=>0, 'row'=>20, 'type'=>'son', 'extend'=>'');

        foreach($params_array as $item){
            $item = str_replace(array('"', "'"), '', $item); // 去除引号

            if(0 === strpos($item, 'typeid=')){
                $params_default['typeid'] = get_related_typeid(intval(str_replace('typeid=', '', $item)));
            }
            elseif(0 === strpos($item, 'row=')){
                $params_default['row'] = intval(str_replace('row=', '', $item));
            }
            elseif(0 === strpos($item, 'type=')){
                $params_default['type'] = str_replace('type=', '', $item);
            }
        }

//        var_dump($params_default);
        // 调用标签
        if('son' == $params_default['type']){
            $convert_html_head = '{php} if(0==$__CHANNEL__->parent_id){ $channellist_type=\'son\';}else{ $channellist_type=\'brother\';} {/php}
{cms:channellist typeid="$__CHANNEL__.id" row="20" type="$channellist_type" id="channel" condition="1=isnav"}';

            if(!empty($params_default['typeid'])){
                $convert_html_head = '{cms:channellist typeid="' . $params_default['typeid'] . '" row="' . $params_default['row'] . '" type="son" id="channel"}';
            }
        }
        else{  //top
            $convert_html_head = '{cms:channellist type="top" row="20" id="nav" condition="1=isnav"}';
        }

        $convert_html_foot = '{/cms:channellist}';

        // 标签内容解析
        $convert_html_content = format_templets_html($templets);
//        var_dump($convert_html_content);
//        echo PHP_EOL . '#######################################################################' . PHP_EOL;
        $search_tags = array(
            '/m/list.php?tid=[field:id/]',
            '/plus/list.php?tid=[field:id/]',
            '[field:typelink/]',
            '[field:typename/]',
            '[field:typelitpic/]',
            '[field:enname/]',
            '[field:typeurl/]',
            '[field:rel/]',
        );

        $replace_tags = array(
            '{$channel.url}',
            '{$channel.url}',
            '{$channel.url}',
            '{$channel.name}',
            '{$channel.image}',
            '{$channel.title}',
            '{$channel.url}',
            '',
        );

        // 顶部导航变量名称变更
        if('top' == $params_default['type']){
            $replace_tags = array(
                '{$nav.url}',
                '{$nav.url}',
                '{$nav.url}',
                '{$nav.name}',
                '{$nav.image}',
                '{$nav.title}',
                '{$nav.url}',
                '',
            );
        }

        $convert_html_content = str_ireplace($search_tags, $replace_tags, $convert_html_content);
//        var_dump($convert_html_content);
    }
    elseif('flink' == $tag){
        $convert_html_content = '{cms:block name=\'friendlinks\' /}';
    }
    elseif('likearticle' == $tag){
        // 处理参数
        $params = format_templets_html($params);
        $params_array = explode(' ', $params);
//        var_dump($params_array);
        $params_default = array('channel'=>'', 'row'=>4, 'flag'=>'', 'extend'=>'');

        foreach($params_array as $item){
            $item = str_replace(array('"', "'"), '', $item); // 去除引号

            if(0 === strpos($item, 'typeid=')){
                $params_default['channel'] = get_related_typeid(intval(str_replace('typeid=', '', $item)));
            }
            elseif(0 === strpos($item, 'row=')){
                $params_default['row'] = intval(str_replace('row=', '', $item));
            }
        }

        // 调用标签
        $convert_html_head = '{cms:arclist channel="' . $params_default['channel'] . '" row="' . $params_default['row'] . '" id="relate" tags="__ARCHIVES__.tags" model="__ARCHIVES__.model_id" addon="true"}';
        $convert_html_foot = '{/cms:arclist}';

        // 标签内容解析
        $convert_html_content = format_templets_html($templets);
//        var_dump($convert_html_content);
//        echo PHP_EOL . '#######################################################################' . PHP_EOL;
        $search_tags = array(
            '[field:arcurl/]',
            '/m/view.php?aid=[field:id/]',
            '[field:title/]',
            '[field:fulltitle/]',
            '[field:litpic/]',
            '[field:description/]',
            '[field:info/]',
            '[field:pubdate function="MyDate(\'Y-m-d\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y-m\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y\',@me)"/]',
            '[field:pubdate function="MyDate(\'m-d\',@me)"/]',
            '[field:pubdate function="MyDate(\'d\',@me)"/]',
            '[field:global.autoindex/]',
        );

        $replace_tags = array(
            '{$relate.url}',
            '{$relate.url}',
            '{$relate.title}',
            '{$relate.title}',
            '{$relate.image}',
            '{$relate.description}',
            '{$relate.description}',
            '{:date("Y-m-d", $relate[\'publishtime\'])}',
            '{:date("Y-m", $relate[\'publishtime\'])}',
            '{:date("Y", $relate[\'publishtime\'])}',
            '{:date("m-d", $relate[\'publishtime\'])}',
            '{:date("d", $relate[\'publishtime\'])}',
            '{$i}',
        );

        $convert_html_content = str_replace($search_tags, $replace_tags, $convert_html_content);
    }
    elseif('php' == $tag){
        // 调用标签
        $convert_html_head = '{php}';
        $convert_html_foot = '{/php}';

        $convert_html_content = $templets;
    }
    elseif('sql' == $tag){
        // 处理参数
        $params = format_templets_html($params);
        $params = str_replace(array('"', "'"), '', $params); // 去除引号
        $params_array = explode('id=', $params);
//        var_dump($params_array);
        $params_default = array('channel'=> 2);

        if(isset($params_array[1])){
            $params_default['channel'] = get_related_typeid(trim($params_array[1]));
        }

        // 调用标签
        $convert_html_head = '{cms:channellist typeid="' . $params_default['channel'] . '" id="channel"}';
        $convert_html_foot = '{/cms:channellist}';

        $convert_html_content = '{:mb_substr(strip_tags($channel.content), 0, 800)}';

    }
    elseif('type' == $tag){
        // 处理参数
        $params = format_templets_html($params);
        $params = str_replace(array('"', "'"), '', $params); // 去除引号
        $params_array = explode('id=', $params);
//        var_dump($params_array);
        $params_default = array('channel'=> 2);

        if(isset($params_array[1])){
            $params_default['channel'] = get_related_typeid(trim($params_array[1]));
        }

        // 调用标签
        $convert_html_head = '{cms:channellist typeid="' . $params_default['channel'] . '" id="channel"}';
        $convert_html_foot = '{/cms:channellist}';

        // 标签内容解析
        $convert_html_content = format_templets_html($templets);
//        var_dump($convert_html_content);
//        echo PHP_EOL . '#######################################################################' . PHP_EOL;
        $search_tags = array(
            '/m/list.php?tid=[field:id/]',
            '/plus/list.php?tid=[field:id/]',
            '[field:typeurl/]',
            '[field:typelink/]',
            '[field:typename/]',
            '[field:typelitpic/]',
            '[field:enname/]',
        );

        $replace_tags = array(
            '{$channel.url}',
            '{$channel.url}',
            '{$channel.url}',
            '{$channel.url}',
            '{$channel.name}',
            '{$channel.image}',
            '{$channel.title}',
        );

        $convert_html_content = str_ireplace($search_tags, $replace_tags, $convert_html_content);

    }
    elseif('prenext' == $tag){

        if(false !== strpos($params, 'next')){
            $convert_html_content = '
{cms:prevnext id="prev" empty="<a>没有了</a>" type="prev" archives="__ARCHIVES__.id" channel="__CHANNEL__.id"}
    <a href="{$prev.url}">{$prev.title}</a>
{/cms:prevnext}
    ';
        }
        elseif(false !== strpos($params, 'pre')){
            $convert_html_content = '
{cms:prevnext id="next" empty="<a>没有了</a>" type="next" archives="__ARCHIVES__.id" channel="__CHANNEL__.id"}
    <a href="{$next.url}">{$next.title}</a>
{/cms:prevnext}
    ';
        }


    }
    elseif('list' == $tag){

        // 调用标签
        $convert_html_head = '{cms:pagelist id=\'item\'}';
        $convert_html_foot = '{/cms:pagelist}';

        // 标签内容解析
        $convert_html_content = format_templets_html($templets);

        $search_tags = array(
            '[field:global.autoindex/]',
            '[field:arcurl/]',
            '/m/view.php?aid=[field:id/]',
            '/plus/view.php?aid=[field:id/]',
            '[field:title/]',
            '[field:fulltitle/]',
            '[field:litpic/]',
            '[field:description/]',
            '[field:info/]',
            '[field:infos/]',
            '[field:pubdate function="MyDate(\'Y-m-d\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y-m\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y\',@me)"/]',
            '[field:pubdate function="MyDate(\'m-d\',@me)"/]',
            '[field:pubdate function="MyDate(\'d\',@me)"/]',
            '[field:click/]',
            '/m/list.php?tid=[field:id/]',
            '/plus/list.php?tid=[field:id/]',
            '[field:typelink/]',
            '[field:typename/]',

            '[field:pubdate function="MyDate(\'Y/m/d\',@me)"/]',
            '[field:pubdate function="MyDate(\'Y/m\',@me)"/]',
            '[field:pubdate function="MyDate(\'m/d\',@me)"/]',

            '[field:pubdate function=\'strftime("%Y",@me)\'/]',
        );

        $replace_tags = array(
            '{$i}',
            '{$item.url}',
            '{$item.url}',
            '{$item.url}',
            '{$item.title}',
            '{$item.title}',
            '{$item.image}',
            '{$item.description}',
            '{$item.description}',
            '{$item.description}',
            '{:date("Y-m-d", $item[\'publishtime\'])}',
            '{:date("Y-m", $item[\'publishtime\'])}',
            '{:date("Y", $item[\'publishtime\'])}',
            '{:date("m-d", $item[\'publishtime\'])}',
            '{:date("d", $item[\'publishtime\'])}',
            '{$item.views}',
            '{$item.channel.url}',
            '{$item.channel.url}',
            '{$item.channel.url}',
            '{$item.channel.name}',

            '{:date("Y/m/d", $item[\'publishtime\'])}',
            '{:date("Y/m", $item[\'publishtime\'])}',
            '{:date("m/d", $item[\'publishtime\'])}',

            '{:date("Y", $item[\'publishtime\'])}',
        );

        $convert_html_content = str_ireplace($search_tags, $replace_tags, $convert_html_content);

        // todo 兼容性更好的字段匹配方法
        $field_start = '[field:';
        $field_over = '/]';
        $field_tag = 'description';
        if(false !== strpos($convert_html_content, $field_start . $field_tag)){
            $field_tag_segment = substr($convert_html_content, strpos($convert_html_content, $field_start . $field_tag));
            $field_tag_segment = substr($field_tag_segment, 0, strpos($field_tag_segment, $field_over) + 2);

            $convert_html_content = str_replace($field_tag_segment, '{:mb_substr($item.description, 0, 150)} ', $convert_html_content);
        }

//        var_dump($convert_html_content);
    }

    // 组合
    $convert_html = $convert_html_head . $convert_html_content . $convert_html_foot;

    return $convert_html;
}

/**
 * 格式化模板HTML 主要是把连续多个不可见字符替换成一个空格
 * @param $templets
 * @return string|string[]|null
 */
function format_templets_html($templets){
    $templets = preg_replace('/[\r\t ]{2,}/', " ", $templets);
    $templets = str_replace(" /]", "/]", $templets);

    return $templets;
}

/**
 * 根据导入数据时候的关联id信息获取Fastadmin-cms的栏目id
 * @param $typeid
 * @return int|mixed
 */
function get_related_typeid($typeid){
    $channel_id = 0;
    $category_ids_relate = config('category_ids_relate');

    foreach($category_ids_relate as $item){
        if($typeid == $item['old']['id']){
            $channel_id = $item['new'];
        }
    }

    return $channel_id;
}

/**
 * 替换完成后审查模板查看还有未替换的标签并标记出来
 */
function inspect_dede_miss_tags(){
    $inspect_elements = array(
        '{dede:',
        '[field:',
        '/m/list.php',
        '/m/view.php',
        '/plus/list.php',
        '/plus/view.php',
        '/plus/search.php',
        '<?php ',
        'diy.php',
        '{cms:channellist typeid="0"',
    );

    $path_log = ROOT_DIR . '/__inspect_dede_miss_tags.log';
    $miss_log = array();

    $fastadmincms_path = ROOT_DIR . '/templets_fastadmincms/';
    $fastadmincms_common_path = ROOT_DIR . '/templets_fastadmincms/common/';

    $fastadmincms_mobile_path = ROOT_DIR . '/templets_fastadmincms/mobile/';
    $fastadmincms_mobile_common_path = ROOT_DIR . '/templets_fastadmincms/mobile/common/';

    $fastadmincms_files = get_file_list($fastadmincms_path . '*');
    $fastadmincms_common_files = get_file_list($fastadmincms_common_path . '*');

    $fastadmincms_mobile_files = get_file_list($fastadmincms_mobile_path . '*');
    $fastadmincms_mobile_common_files = get_file_list($fastadmincms_mobile_common_path . '*');

    foreach($fastadmincms_files as $path){

        $html_content = get_file_content($path);

        foreach($inspect_elements as $item){
            $pos = stripos($html_content, $item);

            if(false !== $pos){
                $miss_log[$path][] = '【' . $pos . '】' . $item;
            }
        }
    }

    foreach($fastadmincms_common_files as $path){

        $html_content = get_file_content($path);

        foreach($inspect_elements as $item){
            $pos = stripos($html_content, $item);

            if(false !== $pos){
                $miss_log[$path][] = '【' . $pos . '】' . $item;
            }
        }
    }

    foreach($fastadmincms_mobile_files as $path){

        $html_content = get_file_content($path);

        foreach($inspect_elements as $item){
            $pos = stripos($html_content, $item);

            if(false !== $pos){
                $miss_log[$path][] = '【' . $pos . '】' . $item;
            }
        }
    }

    foreach($fastadmincms_mobile_common_files as $path){

        $html_content = get_file_content($path);

        foreach($inspect_elements as $item){
            $pos = stripos($html_content, $item);

            if(false !== $pos){
                $miss_log[$path][] = '【' . $pos . '】' . $item;
            }
        }
    }

    file_put_contents($path_log, var_export($miss_log, true) . PHP_EOL);

    return $miss_log;
}


/**
 * 删除临时添加的无用数据
 * @return bool
 */
function fastadmincms_remove_useless_data(){
    //目标数据库
    $to_config = config('to_db');
    $TO_DB = new DataBase($to_config);

    $sql = " SELECT COUNT(id) AS num FROM `#@__cms_archives` WHERE title = 'useless' AND admin_id = 0 ";
    $TO_DB->query($sql);
    $result = $TO_DB->fetch_array();

    if($result){

        $sql_delete = " DELETE FROM `#@__cms_archives` WHERE title = 'useless' AND admin_id = 0 ";
        $TO_DB->query($sql_delete);

        return true;
    }

    return false;
}


//======================================================================================================================

//数据库操作类 统一类 todo 注意本地链接要写127.0.0.1 否则会有一秒“回环延迟”
class DataBase
{
    protected $conTypeArray = array('pdo', 'mysqli_connect', 'mysql_connect');
    protected $conType = '';
    protected $db = null;
    public $db_config = array();


    function __construct($config)
    {
        $this->db_config = $config;

        foreach($this->conTypeArray as $item)
        {
            $this->conType = 'DataBase_' . $item;

            if(class_exists($this->conType))
            {
                if(!in_array('mysql', PDO::getAvailableDrivers()))
                {
                    continue;
                }
                $this->db = new $this->conType($config['dbhost'], $config['dbuser'], $config['dbpwd'], $config['dbname']);
                return $this->db;
            }
            elseif(function_exists($item))
            {

                $this->conType = str_ireplace('_connect', '', $this->conType);
                if(!class_exists($this->conType))
                {
                    exit('class ' . $this->conType . ' Not Found!');
                }

                $this->db = new $this->conType($config['dbhost'], $config['dbuser'], $config['dbpwd'], $config['dbname']);
                return $this->db;
            }
        }

        exit(json_encode(array(
            'id' => 0,
            'state' => -1,
            'msg' => 'fail',
            'data' => ' All DataBase Driver Not Found!'
        )));
    }

    function testconnect()
    {
        return $this->db->testconnect();
    }

    function connect()
    {

    }

    function select_db($dbname)
    {

    }

    function connect_error()
    {

    }

    function query($sql)
    {
        $sql = str_replace('#@__', $this->db_config['dbprefix'], $sql);
        return $this->db->query($sql);
    }

    function insert_id()
    {
        return $this->db->insert_id();
    }

    function fetch_array()
    {
        return $this->db->fetch_array();
    }

    function fetch_row()
    {
        return $this->db->fetch_row();
    }

    function close()
    {

    }

}


class DataBase_pdo
{
    protected $conn = null;
    protected $resource = null;

    function __construct($host, $user, $pwd, $dbname='')
    {
        $dns = "mysql:dbname={$dbname};host=" . $host;
        try
        {
            $this->conn = new PDO($dns, $user, $pwd);
        }
        catch (Exception $e)
        {
            exit(json_encode(array(
                'id' => 0,
                'state' => -1,
                'msg' => 'PDO connect fail',
                'data' => $e->getMessage()
            )));
        }
    }

    function testconnect()
    {
        if($this->conn){
            return true;
        }else{
            return false;
        }
    }

    function query($sql)
    {
        if($this->conn){
            return $this->resource = $this->conn->query($sql);
        }else{
            return false;
        }
    }

    function fetch_array()
    {
        if($this->resource){
            return $this->resource->fetchAll(PDO::FETCH_ASSOC);
        }else{
            return false;
        }
    }

    function fetch_row()
    {
        if($this->resource){
            return $this->resource->fetch(PDO::FETCH_ASSOC);
        }else{
            return false;
        }
    }

    function insert_id()
    {
        if($this->conn){
            return $this->conn->lastInsertId();
        }else{
            return false;
        }
    }

    function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->conn = null;
    }
}


class DataBase_mysqli
{
    protected $conn = null;
    protected $resource = null;


    function __construct($host, $user, $pwd, $dbname='')
    {
        $this->conn = mysqli_connect($host, $user, $pwd, $dbname);
        if (!$this->conn) {
            exit(json_encode(array(
                'id' => 0,
                'state' => -1,
                'msg' => 'Mysqli connect fail',
                'data' => 'To Database Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error()
            )));
        }
        mysqli_query($this->conn, "SET NAMES utf8");

    }

    function testconnect()
    {
        if($this->conn){
            return true;
        }else{
            return false;
        }
    }

    function query($sql)
    {
        return $this->resource = mysqli_query($this->conn, $sql);
    }

    function fetch_array()
    {
        $result = $row = array();
        while($row = mysqli_fetch_array($this->resource, MYSQLI_ASSOC))
        {
            $result[] = $row;
        }
        return $result;
    }

    function fetch_row()
    {
        return mysqli_fetch_row($this->resource);
    }

    function insert_id()
    {
        return mysqli_insert_id($this->conn);
    }

    function __destruct()
    {
        // TODO: Implement __destruct() method.
        if($this->conn){
            mysqli_close($this->conn);
        }
    }
}


class DataBase_mysql
{
    protected $conn = null;
    protected $resource = null;
    protected $dbname = '';

    function __construct($host, $user, $pwd, $dbname='')
    {
        $this->dbname = $dbname;
        $this->conn = mysql_connect($host, $user, $pwd);
        if (!$this->conn) {
            exit(json_encode(array(
                'id' => 0,
                'state' => 0,
                'msg' => 'Mysql connect fail',
                'data' => 'To Database Connect Error (' . mysql_error($this->conn) . ') '
            )));
        }
        mysql_select_db($this->dbname, $this->conn);
        mysql_query("SET NAMES utf8", $this->conn);
    }

    function testconnect()
    {
        if($this->conn){
            return true;
        }else{
            return false;
        }
    }

    function query($sql)
    {
        mysql_select_db($this->dbname, $this->conn);
        $this->resource = mysql_query($sql, $this->conn);
        if(!$this->resource)
        {
            exit(json_encode(array(
                'id' => 0,
                'state' => 0,
                'msg' => 'mysql query error (' . mysql_error($this->conn) . ') ',
                'data' => $sql
            )));
        }
        return $this->resource;
    }

    function fetch_array()
    {
        $result = $row = array();
        while($row = mysql_fetch_array($this->resource, MYSQL_ASSOC))
        {
            $result[] = $row;
        }
        return $result;
    }

    function fetch_row()
    {
        return mysql_fetch_row($this->resource);
    }

    function insert_id()
    {
        return mysql_insert_id($this->conn);
    }

    function __destruct()
    {
        // TODO: Implement __destruct() method.
    }
}