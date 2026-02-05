<?php
// 应用公共文件
use think\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;
use Mpdf\Mpdf;
use OSS\OssClient;
use OSS\Core\OssException;
use think\facade\Filesystem;

// 获取列表链接地址
function getUrl($v)
{
    // 判断是否外部链接
    if (trim($v['url']) == '') {
        // 判断是否跳转到下级栏目
        if ($v['is_next'] == 1) {
            $is_next = \app\common\model\Cate::with(['module'])
                ->where('parent_id', $v['id'])
                ->order('sort asc,id desc')
                ->find();
            if ($is_next) {
                $v['url'] = getUrl($is_next);
            }
        } else {
            if ($v['cate_folder']) {
                $v['url'] = (string)\think\facade\Route::buildUrl($v['cate_folder'] . '/index')->domain('');
            } else {
                if (isset($v['module']['model_name']) && !empty($v['module']['model_name'])) {
                    $moduleName = $v['module']['model_name'];
                } else {
                    $moduleId   = $v['module']['id'] ?? $v['module_id'];
                    $moduleName = \app\common\model\Module::where('id', $moduleId)
                        ->value('model_name');
                }
                $v['url'] = (string)\think\facade\Route::buildUrl($moduleName . '/index', ['cate' => $v['id']])->domain('');
            }
        }
    }
    return $v['url'];
}

// 获取详情URL
function getShowUrl($v)
{
    if ($v) {
        if (isset($v['url']) && !empty($v['url'])) {
            return $v['url'];
        }
        if (isset($v['cate_id']) && !empty($v['cate_id'])) {
            if (isset($v['cate'])) {
                $cate = $v['cate'];
            } else {
                $cate = \app\common\model\Cate::field('id,cate_folder,module_id')
                    ->where('id', $v['cate_id'])
                    ->find();
            }
            if ($cate['cate_folder']) {
                $url = (string)\think\facade\Route::buildUrl($cate['cate_folder'] . '/info', ['id' => $v['id']])->domain('');
            } else {
                if (isset($v['cate']['module'])) {
                    $modelName = $v['cate']['module']['model_name'];
                } else {
                    $modelName = \app\common\model\Module::where('id', $cate['module_id'])
                        ->value('model_name');
                }
                $url = (string)\think\facade\Route::buildUrl($modelName . '/info', ['cate' => $cate['id'], 'id' => $v['id']])->domain('');
            }
        }
    }
    return $url ?? '';
}

/***
 * 处理数据（把列表中需要处理的字段转换成数组和对应的值,用于自定义标签文件中）
 * @param $list      列表
 * @param $moduleId  模型ID
 * @return array
 */
function changeFields($list, $moduleId)
{
    // 根据模型ID查询字段信息
    $fields     = \app\common\model\Field::with(['module', 'dictionaryType'])->where('module_id', '=', $moduleId)
        ->select()
        ->toArray();
    $optionsArr = [];
    foreach ($fields as $k => $v) {
        $options                 = \app\common\facade\MakeBuilder::getFieldOptions($v);
        $optionsArr[$v['field']] = $options;
    }

    foreach ($list as $k => $v) {
        $url             = getShowUrl($v);
        $list[$k]        = changeField($v, $moduleId, $optionsArr);
        $info[$k]        = $list[$k]; // 定义中间变量防止报错
        $info[$k]['url'] = $url;
    }
    return $info ?? [];
}

/***
 * 处理数据（用于详情页中数据转换）
 * @param $info        内容详情
 * @param $moduleid    模型ID
 * @param $optionsArr  选项信息
 * @return array
 */
function changeField($info, $moduleId, $optionsArr)
{
    $fields = \app\common\model\Field::with(['module', 'dictionaryType'])->where('module_id', '=', $moduleId)
        ->select()
        ->toArray();
    foreach ($fields as $k => $v) {
        // select等需要获取数据的字段
        if ($optionsArr) {
            $options = $optionsArr[$v['field']];
        } else {
            $options = \app\common\facade\MakeBuilder::getFieldOptions($v);
        }
        if (isset($info[$v['field']])) {
            if ($v['type'] == 'text') {
                // 忽略
            } elseif ($v['type'] == 'textarea' || $v['type'] == 'password') {
                // 忽略
            } elseif ($v['type'] == 'radio' || $v['type'] == 'checkbox') {

                $info[$v['field'] . '_array'] = \app\common\facade\Cms::changeOptionsValue($options, $info[$v['field']], true);
                $info[$v['field']]            = \app\common\facade\Cms::changeOptionsValue($options, $info[$v['field']], false);
            } elseif ($v['type'] == 'select' || $v['type'] == 'select2') {
                if ($v['field'] !== 'cate_id') {
                    $info[$v['field'] . '_array'] = \app\common\facade\Cms::changeOptionsValue($options, $info[$v['field']], true);
                    $info[$v['field']]            = \app\common\facade\Cms::changeOptionsValue($options, $info[$v['field']], false);
                }
            } elseif ($v['type'] == 'number') {
            } elseif ($v['type'] == 'hidden') {
            } elseif ($v['type'] == 'date' || $v['type'] == 'time' || $v['type'] == 'datetime') {

            } elseif ($v['type'] == 'daterange') {
            } elseif ($v['type'] == 'tag') {
                if (!empty($info[$v['field']])) {
                    $tags = explode(',', $info[$v['field']]);
                    foreach ($tags as $k => $tag) {
                        $tags[$k] = [
                            'name' => $tag,
                            'url'  => \think\facade\Route::buildUrl('index/tag', ['module' => $moduleId, 't' => $tag])->__toString(),
                        ];
                    }
                    $info[$v['field']] = $tags;
                }
            } elseif ($v['type'] == 'images' || $v['type'] == 'files') {
                $info[$v['field']] = json_decode($info[$v['field']], true);
            } elseif ($v['type'] == 'editor') {

            } elseif ($v['type'] == 'color') {

            }
        }
    }
    return $info;
}

/**
 * 邮件发送
 * @param        $to      接收人
 * @param string $subject 邮件标题
 * @param string $content 邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to, $subject = '', $content = '')
{
    $mail   = new PHPMailer\PHPMailer\PHPMailer();
    $arr    = \think\facade\Db::name('config')
        ->where('inc_type', 'smtp')
        ->select();
    $config = convert_arr_kv($arr, 'name', 'value');

    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    //调试输出格式
    //$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp_server'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];

    if ($mail->Port == '465') {
        $mail->SMTPSecure = 'ssl';
    }// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //发送邮箱
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['smtp_user'], $config['email_id']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if (is_array($to)) {
        foreach ($to as $v) {
            $mail->addAddress($v);
        }
    } else {
        $mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    return $mail->send();
}

/**
 * 验证输入的邮件地址是否合法
 * @param $user_email 邮箱
 * @return bool
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 验证输入的手机号码是否合法
 * @param $mobile_phone 手机号
 * @return bool
 */
function is_mobile_phone($mobile_phone)
{
    $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
    if (preg_match($chars, $mobile_phone)) {
        return true;
    }
    return false;
}

/**
 * 过滤数组元素前后空格 (支持多维数组)
 * @param $array 要过滤的数组
 * @return array|string
 */
function trim_array_element($array)
{
    if (!is_array($array))
        return trim($array);
    return array_map('trim_array_element', $array);
}

/**
 * 将数据库中查出的列表以指定的 值作为数组的键名，并以另一个值作为键值
 * @param $arr
 * @param $key_name
 * @return array
 */
function convert_arr_kv($arr, $key_name, $value)
{
    $arr2 = array();
    foreach ($arr as $key => $val) {
        $arr2[$val[$key_name]] = $val[$value];
    }
    return $arr2;
}

function string2array($info)
{
    if ($info == '') return array();
    eval("\$r = $info;");
    return $r;
}

function array2string($info)
{
    //删除空格，某些情况下字段的设置会出现换行和空格的情况
    if (is_array($info)) {
        if (array_key_exists('options', $info)) {
            $info['options'] = trim($info['options']);
        }
    }
    if ($info == '') return '';
    if (!is_array($info)) {
        //删除反斜杠
        $string = stripslashes($info);
    }
    foreach ($info as $key => $val) {
        $string[$key] = stripslashes($val);
    }
    $setup = var_export($string, TRUE);
    return $setup;
}

/**
 * 文本域中换行标签输出
 * @param $info 内容
 * @return mixed
 */
function textareaBr($info)
{
    $info = str_replace("\r\n", "<br />", $info);
    $info = str_replace("\n", "<br />", $info);
    $info = str_replace("\r", "<br />", $info);
    return $info;
}

/**
 * 无限分类-栏目
 * @param        $cate
 * @param string $lefthtml
 * @param int    $pid
 * @param int    $lvl
 * @return array
 */
function tree_cate($cate, $leftHtml = '|— ', $pid = 0, $lvl = 0)
{
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parent_id'] == $pid) {
            $v['lvl']         = $lvl + 1;
            $v['left_html']   = str_repeat($leftHtml, $lvl);
            $v['l_cate_name'] = $v['left_html'] . $v['cate_name'];
            $arr[]            = $v;
            $arr              = array_merge($arr, tree_cate($cate, $leftHtml, $v['id'], $lvl + 1));
        }
    }
    return $arr;
}

/**
 * 组合多维数组
 * @param        $cate
 * @param string $name
 * @param int    $pid
 * @return array
 */
function unlimitedForLayer($cate, $name = 'sub', $pid = 0)
{
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parent_id'] == $pid) {
            $v[$name] = unlimitedForLayer($cate, $name, $v['id']);
            $v['url'] = getUrl($v);
            $arr[]    = $v;
        }
    }
    return $arr;
}

/**
 * 传递一个父级分类ID返回当前子分类
 * @param $cate
 * @param $pid
 * @return array
 */
function getChildsOn($cate, $pid)
{
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parent_id'] == $pid) {
            $v['sub'] = getChilds($cate, $v['id']);
            $v['url'] = getUrl($v);
            $arr[]    = $v;
        }
    }
    return $arr;
}

/**
 * 传递一个父级分类ID返回所有子分类
 * @param $cate
 * @param $pid
 * @return array
 */
function getChilds($cate, $pid)
{
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parent_id'] == $pid) {
            $v['url'] = getUrl($v);
            $arr[]    = $v;
            $arr      = array_merge($arr, getChilds($cate, $v['id']));
        }
    }
    return $arr;
}

/**
 * 传递一个父级分类ID返回所有子分类ID
 * @param $cate
 * @param $pid
 * @return array
 */
function getChildsId($cate, $pid)
{
    $arr = [];
    foreach ($cate as $v) {
        if ($v['parent_id'] == $pid) {
            $arr[] = $v;
            $arr   = array_merge($arr, getChildsId($cate, $v['id']));
        }
    }
    return $arr;
}

/**
 * 格式化分类数组为字符串
 * @param        $ids
 * @param string $pid
 * @return string
 */
function getChildsIdStr($ids, $pid = '')
{
    $result = '';
    foreach ($ids as $k => $v) {
        $result .= $v['id'] . ',';
    }
    if ($pid) {
        $result = $pid . ',' . $result;
    }
    $result = rtrim($result, ',');
    return $result;
}

/**
 * 传递一个子分类ID返回所有的父级分类[前台栏目]
 * @param $cate
 * @param $id
 * @return array
 */
function getParents($cate, $id)
{
    $arr = array();
    foreach ($cate as $v) {
        if ($v['id'] == $id) {
            $arr[] = $v;
            $arr   = array_merge(getParents($cate, $v['parent_id']), $arr);
        }
    }
    return $arr;
}

/**
 * 查找一个分类id的顶级分类id
 * @param $id
 * @return string
 */
function getTopId($id)
{
    $cate    = \app\common\model\Cate::field('id,parent_id')->select()->toArray();
    $cateArr = [];
    if ($cate) {
        foreach ($cate as $k => $v) {
            $cateArr[$v['id']] = $v['parent_id'] ?: "0";
        }
    }
    while ($cateArr[$id]) {
        $id = $cateArr[$id];
    }
    return $id;
}

/**
 * 获取文件目录列表
 * @param string  $pathname 路径
 * @param integer $fileFlag 文件列表 0所有文件列表,1只读文件夹,2是只读文件(不包含文件夹)
 * @param string  $pathname 路径
 * @return array
 */
function get_file_folder_List($pathname, $fileFlag = 0, $pattern = '*')
{
    $fileArray = array();
    $pathname  = rtrim($pathname, '/') . '/';
    $list      = glob($pathname . $pattern);
    foreach ($list as $i => $file) {
        switch ($fileFlag) {
            case 0:
                $fileArray[] = basename($file);
                break;
            case 1:
                if (is_dir($file)) {
                    $fileArray[] = basename($file);
                }
                break;

            case 2:
                if (is_file($file)) {
                    $fileArray[] = basename($file);
                }
                break;

            default:
                break;
        }
    }

    if (empty($fileArray)) $fileArray = NULL;
    return $fileArray;
}

/**
 * 获取所有模版
 * @return mixed
 */
function getTemplate()
{
    // 查找所有系统设置表数据
    $system = \app\common\model\System::find(1);

    $path        = './template/' . $system['template'] . '/index/' . $system['html'] . '/';
    $tpl['list'] = get_file_folder_List($path, 2, '*_list*');
    $tpl['show'] = get_file_folder_List($path, 2, '*_show*');
    return $tpl;
}

/**
 * 传递一个父级分类ID返回所有子分类
 * @param $cate
 * @param $pid
 * @return array
 */
function getChildsRule($rules, $pid)
{
    $arr = [];
    foreach ($rules as $v) {
        if ($v['pid'] == $pid) {
            $arr[] = $v;
            $arr   = array_merge($arr, getChildsRule($rules, $v['id']));
        }
    }
    return $arr;
}

/***
 * 对象转数组
 * @param $object
 * @return array
 */
function object2array($object)
{
    $array = array();
    if (is_object($object)) {
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }
    } else {
        $array = $object;
    }
    return $array;
}

/***
 * 获取当前栏目ID
 * @return mixed
 */
function getCateId()
{
    if (\think\facade\Request::has('cate')) {
        $result = (int)\think\facade\Request::param('cate');
    } else {
        $cateFolder = get_cate_folder();
        if ($cateFolder) {
            $result = \app\common\model\Cate::where('cate_folder', '=', $cateFolder)->value('id');
        }
    }
    return $result ?? '';
}

/**
 * 改变前台字典数据标签取得的数据
 * @param array $list
 * @return array
 */
function changeDict(array $list, string $field, string $all = "全部")
{
    $get = \think\facade\Request::except(['page'], 'get');
    foreach ($list as $k => $v) {
        $url             = $get;
        $url[$field]     = $v['dict_value'];
        $list[$k]['url'] = (string)url(get_cate_folder() . '/' . \think\facade\Request::action(), $url);
        $param           = \think\facade\Request::param('', '', 'htmlspecialchars');
        // 高亮显示
        $list[$k]['current'] = 0;
        if (!empty($param)) {
            foreach ($param as $kk => $vv) {
                if ($kk == $field) {
                    if (strpos($vv, '|') !== false) {
                        // 多选
                        $paramArr = explode("|", $vv);
                        foreach ($paramArr as $kkk => $vvv) {
                            if ($vvv == $v['dict_value']) {
                                $list[$k]['current'] = 1;
                                break;
                            }
                        }
                    } else {
                        // 单选
                        if ($vv == $v['dict_value']) {
                            $list[$k]['current'] = 1;
                        }
                    }
                }
            }
        }
        $list[$k]['param'] = $param;
    }

    // 添加[全部]字段在第一位
    if (isset($get[$field])) {
        unset($get[$field]);
    } else {
        $hover = 1;
    }
    $url = (string)url(get_cate_folder() . '/' . \think\facade\Request::action(), $get);

    $all = [
        'dict_label' => $all,
        'dict_value' => 0,
        'url'        => $url,
        'current'    => $hover ?? 0,
    ];
    array_unshift($list, $all);

    return $list;
}

/**
 * 改变模版标签中分类字段传递
 * @param string $field 需要分类查询的字段，通过,分割或|分割
 * @return string
 */
function getSearchField(string $field)
{
    $sql = '';
    if ($field) {
        $field    = str_replace('|', ',', $field);
        $fieldArr = explode(',', $field);
        foreach ($fieldArr as $k => $v) {
            if (!empty($v)) {
                // 查询浏览器参数是否包含此参数
                if (\think\facade\Request::has($v, 'get')) {
                    $str = \think\facade\Request::get($v, '', ['strip_tags', 'htmlspecialchars']);
                    if (strpos($str, '|') !== false) {
                        $sql    = ' AND (';
                        $strArr = explode("|", $str);
                        foreach ($strArr as &$strAr) {
                            // 检测是否存在
                            $dictCount = \app\common\model\Dictionary::where('dict_value', $strAr)->count();
                            if ($dictCount) {
                                $sql .= ' FIND_IN_SET(\'' . $strAr . '\', ' . $v . ') OR';
                            }
                        }
                        // 去除最后一个or
                        $sql = substr($sql, 0, strlen($sql) - 2);
                        $sql .= ') ';
                    } else {
                        // 检测是否存在
                        $dictCount = \app\common\model\Dictionary::where('dict_value', $str)->count();
                        if ($dictCount) {
                            $sql .= ' AND FIND_IN_SET(\'' . $str . '\', ' . $v . ') ';
                        } else {
                            // 常规搜索
                            $sql .= ' AND ' . $v . ' LIKE "%' . $str . '%" ';
                        }
                    }
                }
            }
        }
    }
    return $sql;
}

/**
 * 无限分类-权限
 * @param        $cate            栏目
 * @param string $lefthtml        分隔符
 * @param int    $pid             父ID
 * @param int    $lvl             层级
 * @return array
 */
function tree($cate, $lefthtml = '|— ', $pid = 0, $lvl = 0)
{
    $arr = array();
    foreach ($cate as $v) {
        if ($v['pid'] == $pid) {
            $v['lvl']      = $lvl + 1;
            $v['lefthtml'] = str_repeat($lefthtml, $lvl);
            $v['ltitle']   = $v['lefthtml'] . $v['title'];
            $arr[]         = $v;
            $arr           = array_merge($arr, tree($cate, $lefthtml, $v['id'], $lvl + 1));
        }
    }
    return $arr;
}

/**
 * 无限分类-权限
 * @param        $cate            栏目
 * @param string $lefthtml        分隔符
 * @param int    $pid             父ID
 * @param int    $lvl             层级
 * @return array
 */
function tree_three($cate, $lefthtml = '|— ', $pid = 0, $lvl = 0)
{
    $arr = array();
    foreach ($cate as $v) {
        $keys = array_keys($v);
        if (end($v) == $pid) {
            $v['lvl']      = $lvl + 1;
            $v['lefthtml'] = str_repeat($lefthtml, $lvl);
            $v[$keys[1]]   = $v['lefthtml'] . $v[$keys[1]];
            $arr[]         = $v;
            $arr           = array_merge($arr, tree_three($cate, $lefthtml, $v[$keys[0]], $lvl + 1));
        }
    }
    return $arr;
}

/**
 * 标签云数据处理
 * @param $list
 * @return array
 */
function get_tagcloud($list, $moduleId, $limit = 10)
{
    $result = [];
    if ($list) {
        foreach ($list as $k => $v) {
            if ($v['tags']) {
                $arr = explode(',', $v['tags']);
                foreach ($arr as $ar) {
                    if (!empty($ar)) {
                        $result[] = $ar;
                    }
                }
            }
        }
    }
    if ($result) {
        $arr = array_count_values($result); // 统计数组中所有的值出现的次数
        // 降序排序
        arsort($arr);
        $arr    = array_slice($arr, 0, $limit);// 截取前N条数据
        $result = [];
        foreach ($arr as $k => $v) {
            $result[] = [
                'name'  => $k,
                'count' => $v,
                'url'   => \think\facade\Route::buildUrl('index/tag', ['module' => $moduleId, 't' => $k])->__toString(),
            ];
        }
    }
    return $result;
}

/**
 * 获取前一页地址中设置的返回url
 * @return array
 */
function get_back_url()
{
    if (isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"])) {
        $queryStr = explode('?', $_SERVER["HTTP_REFERER"]);
        if (count($queryStr) == 2) {
            parse_str($queryStr[1], $queryArr);
            if (isset($queryArr['back_url']) && !empty($queryArr['back_url'])) {
                $backUrl = explode("&", urldecode($queryArr['back_url']));
                foreach ($backUrl as $k => $v) {
                    $v = explode("=", $v);
                    if (isset($v[1]) && !empty($v[1])) {
                        $backArr[$v[0]] = $v[1];
                    }
                }
            }
        }
    }
    return $backArr ?? [];
}

/**
 * 转换moment格式为php可用格式[废弃]
 * @param $format
 * @return string
 */
function convert_moment_format_to_php(string $format = '')
{
    $replacements = [
        'DD'   => 'd',
        'ddd'  => 'D',
        'D'    => 'j',
        'dddd' => 'l',
        'E'    => 'N',
        'o'    => 'S',
        'e'    => 'w',
        'DDD'  => 'z',
        'W'    => 'W',
        'MMMM' => 'F',
        'MM'   => 'm',
        'MMM'  => 'M',
        'M'    => 'n',
        'YYYY' => 'Y',
        'YY'   => 'y',
        'a'    => 'a',
        'A'    => 'A',
        'h'    => 'g',
        'H'    => 'G',
        'hh'   => 'h',
        'HH'   => 'H',
        'mm'   => 'i',
        'ss'   => 's',
        'SSS'  => 'u',
        'zz'   => 'e',
        'X'    => 'U',
    ];
    $phpFormat    = strtr($format, $replacements);
    return $phpFormat;
}

/**
 * 转换php格式为moment可用格式
 * @param $format
 * @return string
 */
function convert_php_to_moment_format(string $format = '')
{
    $replacements = [
        'd' => 'DD',
        'D' => 'ddd',
        'j' => 'D',
        'l' => 'dddd',
        'N' => 'E',
        'S' => 'o',
        'w' => 'e',
        'z' => 'DDD',
        'W' => 'W',
        'F' => 'MMMM',
        'm' => 'MM',
        'M' => 'MMM',
        'n' => 'M',
        't' => '', // no equivalent
        'L' => '', // no equivalent
        'o' => 'YYYY',
        'Y' => 'YYYY',
        'y' => 'YY',
        'a' => 'a',
        'A' => 'A',
        'B' => '', // no equivalent
        'g' => 'h',
        'G' => 'H',
        'h' => 'hh',
        'H' => 'HH',
        'i' => 'mm',
        's' => 'ss',
        'u' => 'SSS',
        'e' => 'zz', // deprecated since version 1.6.0 of moment.js
        'I' => '',   // no equivalent
        'O' => '',   // no equivalent
        'P' => '',   // no equivalent
        'T' => '',   // no equivalent
        'Z' => '',   // no equivalent
        'c' => '',   // no equivalent
        'r' => '',   // no equivalent
        'U' => 'X',
    ];
    $momentFormat = strtr($format, $replacements);
    return $momentFormat;
}

/**
 * 根据url获取栏目目录
 */
function get_cate_folder()
{
    //return \think\facade\Request::controller();                    // 控制器的方式无法使用'-','_','/'连接符
    $careFolder = \think\facade\Request::rule()->getRule();          // 获取当前路由规则
    $careFolder = str_replace('<id>', '', $careFolder);              // 移除路由规则多余的字符
    $careFolder = trim($careFolder, '/');                            // 移除两侧的/
    return $careFolder;
}

/**
 * 根据父ID获取下级联动数据
 * @param string $modelName    模型名称
 * @param int    $pid          父ID
 * @param string $pidFieldName 父ID字段名
 * @return array
 */
function getLinkageData(string $modelName, int $pid = 0, string $pidFieldName = 'pid')
{
    $model = '\app\common\model\\' . $modelName;
    if (class_exists($model)) {
        return $model:: where($pidFieldName, $pid)->select()->toArray();
    }
    return [];
}

/**
 * 根据末级ID获取父级联动数据
 * @param string $modelName     模型名称
 * @param string $id            主键值
 * @param string $idFieldName   主键字段名
 * @param string $nameFieldName name字段名
 * @param string $pidFieldName  pid字段名
 * @param int    $level         级别
 */
function getLinkageAllData(string $modelName, $id = '', $idFieldName = 'id', $nameFieldName = 'name', $pidFieldName = 'pid', $level = 1)
{
    $model = '\app\common\model\\' . $modelName;
    // 获取当前数据的父ID
    $pidFielValue = $model:: where($idFieldName, $id)->value($pidFieldName);

    // 当前级别的数据
    $resultKey[$level]  = $pidFielValue;
    $resultData[$level] = getLinkageData($modelName, (int)$pidFielValue, $pidFieldName);

    if ($pidFielValue != 0) {
        $data       = getLinkageAllData($modelName, $pidFielValue, $idFieldName, $nameFieldName, $pidFieldName, $level + 1);
        $resultKey  = $resultKey + $data['key']; // 后面一个数组，加入到前面一个数组中，键名相同时，不会被覆盖
        $resultData = $resultData + $data['data'];
    }

    $result['key']  = $resultKey;
    $result['data'] = $resultData;
    return $result;
}

/**
 * 根据末级ID获取每级的联动数据
 * @param string $modelName     模型名称
 * @param string $id            主键值
 * @param string $idFieldName   主键字段名
 * @param string $nameFieldName name字段名
 * @param string $pidFieldName  pid字段名
 * @param array  $result        数据
 */
function getLinkageListData(string $modelName, $id = '', $idFieldName = 'id', $nameFieldName = 'name', $pidFieldName = 'pid', $result = [])
{
    $model = '\app\common\model\\' . $modelName;
    // 查找当前层的数据
    $data = $model:: where($idFieldName, $id)->field([$idFieldName, $nameFieldName, $pidFieldName])->find();
    if ($data) {
        $result[] = $data->toArray();
        if ($data->{$pidFieldName}) {
            return getLinkageListData($modelName, $data->{$pidFieldName}, $idFieldName, $nameFieldName, $pidFieldName, $result);
        }
    }
    return $result;
}

/**
 * 获取小程序accesstoken
 * @param string $modelName     模型名称
 * @param string $id            主键值
 * @param string $idFieldName   主键字段名
 * @param string $nameFieldName name字段名
 * @param string $pidFieldName  pid字段名
 * @param
 */
function accesstoken()
{
    // 查找所有系统设置表数据
    $system = \app\common\model\System::find(1);
    if (!$system || !$system['appid'] || !$system['appsecret']) {
        return ['code'=>1,'msg'=>'没有找到微信配置！'];
    }
    $info = [];
    if ($system['expire_time'] < time()) {
        $tokenurl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$system['appid']."&secret=".$system['appsecret'];
        $tokenData = http_curl_get($tokenurl, true);
        $tokenData = json_decode($tokenData, true);
        if(!empty($tokenData['errcode'])){
            return ['code'=>2,'msg'=>"获取access_token错误，错误码：".$tokenData['errcode']];
        }else{
            $access_token = $tokenData['access_token'];
            $ticketurl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$access_token";
            $tickeData = http_curl_get($ticketurl, true);
            $tickeData = json_decode($tickeData, true);
            $ticket = $tickeData['ticket'];

            // 重新赋值
            $newConfig['app_create_time']   = time();
            $newConfig['expire_time']       = time()+7200;
            $newConfig['ticket']            = $ticket;
            $newConfig['accesstoken']       = $access_token;
            $newConfig['id']                = 1;
            $newConfig['update_time']       = time();
            // 修改
            Db::name('System')->strict(false)->field(true)->update($newConfig);
            // 返回
            $info['access_token']= $access_token;
            $info['ticket']      = $ticket;
            $info['appid']       = $system['appid'];
            $info['appsecret']   = $system['appsecret'];
            $info['create_time'] = $newConfig['app_create_time'];
            $info['expire_time'] = $newConfig['expire_time'];
            return ['code'=>0,'msg'=>'ok','data'=>$info];
        }
    }else{
        $info['access_token']= $system['accesstoken'];
        $info['ticket']      = $system['ticket'];
        $info['appid']       = $system['appid'];
        $info['appsecret']   = $system['appsecret'];
        $info['create_time'] = $system['app_create_time'];
        $info['expire_time'] = $system['expire_time'];
        return ['code'=>0,'msg'=>'ok','data'=>$info];
    }
}

// 更新token
function updateAccesstoken()
{
    // 查找所有系统设置表数据
    $system = \app\common\model\System::find(1);
    if (!$system || !$system['appid'] || !$system['appsecret']) {
        return ['code'=>1,'msg'=>'没有找到微信配置！'];
    }
    $info = [];
    $tokenurl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$system['appid']."&secret=".$system['appsecret'];
    $tokenData = http_curl_get($tokenurl, true);
    $tokenData = json_decode($tokenData, true);
    if(!empty($tokenData['errcode'])){
        return ['code'=>2,'msg'=>"获取access_token错误，错误码：".$tokenData['errcode']];
    }else{
        $access_token = $tokenData['access_token'];
        $ticketurl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$access_token";
        $tickeData = http_curl_get($ticketurl, true);
        $tickeData = json_decode($tickeData, true);
        $ticket = $tickeData['ticket'];

        // 重新赋值
        $newConfig['app_create_time']   = time();
        $newConfig['expire_time']       = time()+7200;
        $newConfig['ticket']            = $ticket;
        $newConfig['accesstoken']       = $access_token;
        $newConfig['id']                = 1;
        $newConfig['update_time']       = time();
        // 修改
        Db::name('System')->strict(false)->field(true)->update($newConfig);
        // 返回
        /*$info['access_token']= $access_token;
        $info['ticket']      = $ticket;
        $info['appid']       = $system['appid'];
        $info['appsecret']   = $system['appsecret'];
        $info['create_time'] = $newConfig['app_create_time'];
        $info['expire_time'] = $newConfig['expire_time'];
        return ['code'=>0,'msg'=>'ok','data'=>$info];*/
    }
}

//curl get获取
function http_curl_get($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_TIMEOUT, 5000);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt ($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}
//curl post提交
function http_curl_post($url, $data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

// 微信调用
function https_request($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $output = curl_exec($curl);
    curl_close($curl);
    
    return $output;
    exit();
    
}

//获取url参数
function get_params($key = "")
{
    return Request::instance()->param($key);
}

function gen_uuid() {
    $uuid = array(
        'time_low'  => 0,
        'time_mid'  => 0,
        'time_hi'  => 0,
        'clock_seq_hi' => 0,
        'clock_seq_low' => 0,
        'node'   => array()
    );

    $uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
    $uuid['time_mid'] = mt_rand(0, 0xffff);
    $uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
    $uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
    $uuid['clock_seq_low'] = mt_rand(0, 255);

    for ($i = 0; $i < 6; $i++) {
        $uuid['node'][$i] = mt_rand(0, 255);
    }

    $uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
        $uuid['time_low'],
        $uuid['time_mid'],
        $uuid['time_hi'],
        $uuid['clock_seq_hi'],
        $uuid['clock_seq_low'],
        $uuid['node'][0],
        $uuid['node'][1],
        $uuid['node'][2],
        $uuid['node'][3],
        $uuid['node'][4],
        $uuid['node'][5]
    );

    return $uuid;
}

//随机字符串，默认长度10
function set_salt($num = 10)
{
    $str = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    $salt = substr(str_shuffle($str), 10, $num);
    return $salt;
}

/**
 * [GetNumberCode 随机数生成生成]
 * @param integer $length [生成长度]
 */
function GetNumberCode($length = 6)
{
    $code = '';
    for($i=0; $i<intval($length); $i++) $code .= rand(0, 9);
    return $code;
}

/**
 * 对称加密
 * @param string $str      需要加密的字符串
 * @param string $key      加密key
 * @return string data     加密之后的字符串
 */
function symencryption($str,$key)
{
    $str = $str.$key;
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $rand = rand(0,64);
    $ch = $chars[$rand];
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$rand%8, $rand%8+7);
    $str = base64_encode($str);
    $tmp = '';
    $i=0;$j=0;$k = 0;
    for ($i=0; $i<strlen($str); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($rand+strpos($chars,$str[$i])+ord($mdKey[$k++]))%64;
        $tmp .= $chars[$j];
    }
    return urlencode(base64_encode($ch.$tmp));
}

/**
 * 对称解密
 * @param string $str      需要解密的字符串
 * @param string $key      加密key
 * @return string data     解密之后的字符串
 */
function symdecrypt($str,$key)
{
    $str = base64_decode(urldecode($str));
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $ch = $str[0];
    $rand = strpos($chars,$ch);
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$rand%8, $rand%8+7);
    $str = substr($str,1);
    $tmp = '';
    $i=0;$j=0; $k = 0;
    for ($i=0; $i<strlen($str); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars,$str[$i])-$rand - ord($mdKey[$k++]);
        while ($j<0) $j+=64;
        $tmp .= $chars[$j];
    }
    return trim(base64_decode($tmp),$key);
}

if (!function_exists('filterText')) {
    /**
     * 过滤评论内容
     * @param string $text 评论内容
     * @return string
     */
    function filterText($text)
    {
        $text = str_replace("\r", '', trim($text));
        $text = preg_replace("/\n{2,}/", "\n\n", $text);
        return $text;
    }
}

/**
 * 处理XSS跨站攻击的过滤函数
 *
 * @author kallahar@kallahar.com
 * @link http://kallahar.com/smallprojects/php_xss_filter_function.php
 * @access public
 * @param string $val 需要处理的字符串
 * @return string
 */
function removeXSS($val)
{
   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
   // this prevents some character re-spacing such as <java\0script>
   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
   $val = preg_replace('/([\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19])/', '', $val);

   // straight replacements, the user should never need these since they're normal characters
   // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
   $search = 'abcdefghijklmnopqrstuvwxyz';
   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $search .= '1234567890!@#$%^&*()';
   $search .= '~`";:?+/={}[]-_|\'\\';

   for ($i = 0; $i < strlen($search); $i++) {
      // ;? matches the ;, which is optional
      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

      // &#x0040 @ search for the hex values
      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
      // &#00064 @ 0{0,7} matches '0' zero to seven times
      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
   }

   // now the only remaining whitespace attacks are \t, \n, and \r
   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
   $ra = array_merge($ra1, $ra2);

   $found = true; // keep replacing as long as the previous round replaced something
   while ($found == true) {
      $val_before = $val;
      for ($i = 0; $i < sizeof($ra); $i++) {
         $pattern = '/';
         for ($j = 0; $j < strlen($ra[$i]); $j++) {
            if ($j > 0) {
               $pattern .= '(';
               $pattern .= '(&#[xX]0{0,8}([9ab]);)';
               $pattern .= '|';
               $pattern .= '|(&#0{0,8}([9|10|13]);)';
               $pattern .= ')*';
            }
            $pattern .= $ra[$i][$j];
         }
         $pattern .= '/i';
         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags

         if ($val_before == $val) {
            // no replacements were made, so exit the loop
            $found = false;
         }
      }
   }

   return $val;
}

/**
 * 返回json数据，用于接口
 * @param    integer    $code
 * @param    string     $msg
 * @param    array      $data
 * @param    string     $url
 * @param    integer    $httpCode
 * @param    array      $header
 * @param    array      $options
 * @return   json
 */
function to_assign($code = 0, $msg = "操作成功", $data = [], $url = '', $httpCode = 200, $header = [], $options = [])
{
    $res = ['code' => $code];
    $res['msg'] = $msg;
    $res['url'] = $url;
    if (is_object($data)) {
        $data = $data->toArray();
    }
    $res['data'] = $data;
    $response = \think\Response::create($res, "json", $httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}

/**
 * 生成二维码图片
 * @param $content 二维码内容
 * @param $logo logo图片
 * @return string
 */
function Qrcode($content,$logo = ""){//static/admin/images/nopic.png
    try {
        include_once('../extend/org/phpqrcode.php');
        $date = date('Ymd');
        $dir = 'uploads/ercode/'.$date.'/';
        $url =  http_type();
        if(!file_exists($dir)){
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($dir, 0700,true);
        }
        $name = md5($content).".png";
        $value = "$content";             //二维码内容
        $errorCorrectionLevel = 'M';    //容错级别L(QR_ECLEVEL_L，7%)、M(QR_ECLEVEL_M，15%)、Q(QR_ECLEVEL_Q，25%)、H(QR_ECLEVEL_H，30%)
        $matrixPointSize = 6;           //生成图片大小
        //组装图片路径
        $filepath= $dir.$name;
        /*if(file_exists($filepath)==true){
            return $url."/uploads/ercode/".$name;
        }*/
        \QRcode::png($value,$filepath , $errorCorrectionLevel, $matrixPointSize, 2);
        $QR = $filepath;              //已经生成的原始二维码图片文件
        if ($logo && file_exists($logo)) {
            $QR = imagecreatefromstring(file_get_contents($QR));        //目标图象连接资源。
            $logo = imagecreatefromstring(file_get_contents($logo));    //源图象连接资源。
            $QR_width = imagesx($QR);           //二维码图片宽度
            $QR_height = imagesy($QR);          //二维码图片高度
            $logo_width = imagesx($logo);       //logo图片宽度
            $logo_height = imagesy($logo);      //logo图片高度
            $logo_qr_width = $QR_width / 4;     //组合之后logo的宽度(占二维码的1/5)
            $scale = $logo_width/$logo_qr_width;    //logo的宽度缩放比(本身宽度/组合后的宽度)
            $logo_qr_height = $logo_height/$scale;  //组合之后logo的高度
            $from_width = ($QR_width - $logo_qr_width) / 2;   //组合之后logo左上角所在坐标点

            //重新组合图片并调整大小
            /*
             *  imagecopyresampled() 将一幅图像(源图象)中的一块正方形区域拷贝到另一个图像中
             */
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
            $newname = 'uploads/ercode/'.$date.'/log_'.$name;
            imagepng($QR, $newname);
            //输出图片
            unlink($filepath);
            imagedestroy($QR);
            imagedestroy($logo);
            return $url.$newname;
        }else{
            $QR = imagecreatefromstring(file_get_contents($QR));
            //输出图片
            imagedestroy($QR);
            return "/uploads/ercode/".$date."/".$name;
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/*
 * 判断当前域名http或https,组装域名
 */
function http_type(){
$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
return  $http_type . $_SERVER['HTTP_HOST'];
}

if (!function_exists('check_phone')) {
    /**
     * 手机号验证
     * @param $phone
     * @return false|int
     */
    function check_phone($phone)
    {
        return preg_match("/^1[3456789]\d{9}$/", $phone);
    }
}

/**
 * 验证身份证号
 * @param $vStr
 * @return bool
 */
function isCreditNo($vStr)
{
    $vCity = array(
        '11','12','13','14','15','21','22',
        '23','31','32','33','34','35','36',
        '37','41','42','43','44','45','46',
        '50','51','52','53','54','61','62',
        '63','64','65','71','83','81','82','91'
    );
 
    if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;
    if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
    $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
    $vLength = strlen($vStr);
    if ($vLength == 18){
        $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
    } else {
        $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
    }
 
    if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
    if ($vLength == 18){
        $vSum = 0;
        for ($i = 17 ; $i >= 0 ; $i--)
        {
            $vSubStr = substr($vStr, 17 - $i, 1);
            $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
        }
        if($vSum % 11 != 1) return false;
    }
    return true;
}

//15位身份证号转18位
function CardId15To18($card){
    $len = strlen($card);
    if($len == 18){
        return $card;
    }else if($len != 15){
        return false;
    }
    $result = array();
 
    for($i=0;$i<$len;$i++){
        if($i<=5){
            $result[$i] = intval($card[$i]);
        }else{
            //15位的年份是两位数，18位的是4位数，留出2位
            $result[$i+2] = intval($card[$i]);
        }
    }
    //留出的2位，补充为年份，年份最后两位小于17,年份为20XX，否则为19XX
    if(intval(substr($card,6,2)) <= 17){
        $result[6] = 2;
        $result[7] = 0;
    }else{
        $result[6] = 1;
        $result[7] = 9;
    }
    ksort($result);
    //计算最后一位
    //前十七位乘以系数[7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2],
    $arrInt = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
 
    $sum = 0;
    for($i=0;$i<17;$i++){
        $sum += $result[$i] * $arrInt[$i];
    }
    //对11求余，的余数 0 - 10
    $rod = $sum % 11;
    //所得余数映射到对应数字即可
    $arrCh = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
    $result[17] = $arrCh[$rod];
    return implode('',$result);
}


/**
 *  根据身份证号码获取性别
 *  author:xiaochuan
 *  @param string $idcard    身份证号码
 *  @return int $sex 性别 1男 2女 0未知
 */
function get_sex($idcard) {
    if(empty($idcard)) return '未知'; 
    if(strlen($idcard)!=18){
        $sexint = (int)substr($idcard,14,1);
    }else{
        $sexint = (int)substr($idcard,16,1);
    }
    return $sexint % 2 === 0 ? '女' : '男';
}
 
/**
 *  根据身份证号码获取生日
 *  author:xiaochuan
 *  @param string $idcard    身份证号码
 *  @return $birthday
 */
function get_birthday($idcard) {
    if(empty($idcard)) return null; 
    $bir = substr($idcard, 6, 8);
    $year = (int) substr($bir, 0, 4);
    $month = (int) substr($bir, 4, 2);
    $day = (int) substr($bir, 6, 2);
    return $year . "-" . $month . "-" . $day;
}
 
/**
 *  根据身份证号码计算年龄
 *  author:xiaochuan
 *  @param string $idcard    身份证号码
 *  @return int $age
 */
function get_age($idcard){  
    if(empty($idcard)) return null; 
    #  获得出生年月日的时间戳 
    $date = strtotime(substr($idcard,6,8));
    #  获得今日的时间戳 
    $today = strtotime('today');
    #  得到两个日期相差的大体年数 
    $diff = floor(($today-$date)/86400/365);
    #  strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比 
    $age = strtotime(substr($idcard,6,8).' +'.$diff.'years')>$today?($diff+1):$diff; 
    return $age; 
}
 
/**
 *  根据身份证号，返回对应的生肖
 *  author:xiaochuan
 *  @param string $idcard    身份证号码
 */
function get_zodiac($idcard){ //
    if(empty($idcard)) return null;
    $start = 1901;
    $end = (int)substr($idcard, 6, 4);
    $x = ($start - $end) % 12;
    $val = '';
    if ($x == 1 || $x == -11) $val = '鼠';
    if ($x == 0)              $val = '牛';
    if ($x == 11 || $x == -1) $val = '虎';
    if ($x == 10 || $x == -2) $val = '兔';
    if ($x == 9 || $x == -3)  $val = '龙';
    if ($x == 8 || $x == -4)  $val = '蛇';
    if ($x == 7 || $x == -5)  $val = '马';
    if ($x == 6 || $x == -6)  $val = '羊';
    if ($x == 5 || $x == -7)  $val = '猴';
    if ($x == 4 || $x == -8)  $val = '鸡';
    if ($x == 3 || $x == -9)  $val = '狗';
    if ($x == 2 || $x == -10) $val = '猪';
    return $val;
}
 
/**
 *  根据身份证号，返回对应的星座
 *  author:xiaochuan
 *  @param string $idcard    身份证号码
 */
function get_starsign($idcard){
    if(empty($idcard)) return null;
    $b = substr($idcard, 10, 4);
    $m = (int)substr($b, 0, 2);
    $d = (int)substr($b, 2);
    $val = '';
    if(($m == 1 && $d <= 21) || ($m == 2 && $d <= 19)){
        $val = "水瓶座";
    }else if (($m == 2 && $d > 20) || ($m == 3 && $d <= 20)){
        $val = "双鱼座";
    }else if (($m == 3 && $d > 20) || ($m == 4 && $d <= 20)){
        $val = "白羊座";
    }else if (($m == 4 && $d > 20) || ($m == 5 && $d <= 21)){
        $val = "金牛座";
    }else if (($m == 5 && $d > 21) || ($m == 6 && $d <= 21)){
        $val = "双子座";
    }else if (($m == 6 && $d > 21) || ($m == 7 && $d <= 22)){
        $val = "巨蟹座";
    }else if (($m == 7 && $d > 22) || ($m == 8 && $d <= 23)){
        $val = "狮子座";
    }else if (($m == 8 && $d > 23) || ($m == 9 && $d <= 23)){
        $val = "处女座";
    }else if (($m == 9 && $d > 23) || ($m == 10 && $d <= 23)){
        $val = "天秤座";
    }else if (($m == 10 && $d > 23) || ($m == 11 && $d <= 22)){
        $val = "天蝎座";
    }else if (($m == 11 && $d > 22) || ($m == 12 && $d <= 21)){
        $val = "射手座";
    }else if (($m == 12 && $d > 21) || ($m == 1 && $d <= 20)){
        $val = "魔羯座";
    }
    return $val;
}

/*
 * 腾讯ip定位接口：根据ip获取所在城市名称
 */
function get_ip_area($key,$ip=''){
    if(!$ip) $ip = get_client_ip();

    if($ip == '127.0.0.1'){
        return '内网IP';
    }
    $url = "https://apis.map.qq.com/ws/location/v1/ip?ip={$ip}&key=".$key;
    $ret = https_request($url);
    $arr = json_decode($ret,true);
    if($arr['status'] == 0){
        $dist = $arr['result']['ad_info']['district'] ? $arr['result']['ad_info']['district'] : $arr['result']['ad_info']['adcode'];
        return [
            'province'=> $arr['result']['ad_info']['province'],
            'city'    => $arr['result']['ad_info']['city'],
            'district'=> $dist,
        ];
        //return $arr['result']['ad_info']['nation'].'-'.$arr['result']['ad_info']['province'].'-'.$arr['result']['ad_info']['city'].'-'.$dist;
    }else{
        return '';
    }
}

if (!function_exists('geocoder')) {
    /**
     * 腾讯地图：逆地址解析->坐标转位置
     * @param $longitude    经度
     * @param $latitude     纬度
     * @return false|int
     */
    function geocoder($longitude,$latitude)
    {
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?location=".$latitude.','.$longitude.'&key=OYABZ-TUDOW-3ENR7-RX4YQ-IGXUJ-QKFCP&get_poi=0';
        $data = http_request($url);
        $data = json_decode($data, true);
        return $data;
    }
}

function http_request($url, $data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}


// PDF文件生成
function createPdf($html,$filename,$header="",$footer=""){
    //为了防止文件下载的时候出现跨域问题
    //header("Content-type: text/html; charset=utf-8");
    //文件地址
    // 2023-11-08 根据不同的IP向不同路径写入文件
    // 获取服务器IP地址
    $serverIP = $_SERVER['SERVER_ADDR'];
    // 根据IP地址进行判断
    if ($serverIP == '127.0.0.1') {
        $tempDir = 'D:/wwwroot/xfq.dianfengcms.com/public/pdf';
    } elseif ($serverIP == '172.29.109.120') {
        $tempDir = '/www/wwwroot/xfq.ylbigdata.com/public/pdf';
    } else {
        $tempDir = '/www/wwwroot/xfq.ylbigdata.com/public/pdf';
    }

    $mpdf = new Mpdf(['mode'=>'utf-8','format' => 'A4','sys_get_temp_dir' => $tempDir]);
    $mpdf->SetDisplayMode('fullpage');
    // 水印
    $mpdf->SetWatermarkImage('/static/common/images/water.jpg',0.05,'',[0,0]);//图片路径 透明度 水印大小 水印位置

    // 开启图片水印  每页都添加
    $mpdf->showWatermarkImage = true;

    //$w = 'WWW.WENLV.COM';
    //$mpdf->SetWatermarkText($w,0.1);
    
    // 文字水印
    //$mpdf->showWatermarkText = true;

    //自动分析录入内容字体
    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont = true;
    //文章pdf文件存储路径
    $fileUrl = $tempDir.'/'.$filename; // "tour_accounting_id_".$data['id'].".pdf";
    //添加页眉和页脚到pdf中
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    // 关闭自动
    //$mpdf->cacheCleanupInterval = false;
    //以html为标准分析写入内容
    $mpdf->WriteHTML($html);
    // 加密和设置权限
    // $mpdf->SetProtection(array(), '100200','');
    //添加分页
    //$mpdf->AddPage();
    //生成文件
    $mpdf->Output($fileUrl); // "D" 下载  "I"在线浏览
    //判断是否生成文件成功
    if (is_file($fileUrl)){
        return ['code'=>1,'msg'=>'文件生成成功','url'=>'/pdf/'.$filename];
    }
    return ['code'=>0,'msg'=>'文件生成失败'];
    //记得使用exit,防止乱码
    //exit();
}


// PDF文件生成直接下载
function downloadPdf($html,$filename,$header="",$footer=""){
    //为了防止文件下载的时候出现跨域问题
    header("Content-type: text/html; charset=utf-8");
    //文件地址
    $tempDir = $_SERVER['SERVER_ADDR'] === '127.0.0.1' ? 'D:/phpstudy_pro/WWW/xfq.bak/xfq.dianfengcms.com/public/pdf' : '/www/wwwroot/xfq.ylbigdata.com/public/pdf';

    $mpdf = new Mpdf(['mode'=>'utf-8','format' => 'A4','tempDir' => $tempDir]);
    $mpdf->SetDisplayMode('fullpage');
    // 水印
    $mpdf->SetWatermarkImage('/static/common/images/water.jpg',0.05,'',[0,0]);//图片路径 透明度 水印大小 水印位置

    // 开启图片水印  每页都添加
    $mpdf->showWatermarkImage = true;

    //$w = 'WWW.WENLV.COM';
    //$mpdf->SetWatermarkText($w,0.1);
    
    // 文字水印
    //$mpdf->showWatermarkText = true;

    //自动分析录入内容字体
    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont = true;
    //文章pdf文件存储路径
    $fileUrl = $tempDir.'/'.$filename; // "tour_accounting_id_".$data['id'].".pdf";
    //添加页眉和页脚到pdf中
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    // 关闭自动
    //$mpdf->cacheCleanupInterval = false;
    //以html为标准分析写入内容
    $mpdf->WriteHTML($html);
    // 加密和设置权限
    // $mpdf->SetProtection(array(), '100200','');
    //添加分页
    //$mpdf->AddPage();
    //生成文件
    $mpdf->Output($filename,'D'); // "D" 下载  "I"在线浏览
    //判断是否生成文件成功
    /*if (is_file($fileUrl)){
        return ['code'=>1,'msg'=>'文件生成成功','url'=>'/pdf/'.$filename];
    }
    return ['code'=>0,'msg'=>'文件生成失败'];*/
    //记得使用exit,防止乱码
    exit();
}
 
//阿里云OSS
if (!function_exists('alioss')) {
    function alioss($savePath,$category='',$isunlink=false,$bucket=""){

        // 访问信息 
        // Content-Disposition:inline：直接预览文件内容。
        // Content-Disposition:attachment：以原文件名的形式下载到浏览器指定路径。
        // Content-Disposition:attachment; filename="yourFileName"：以自定义文件名的形式下载到浏览器指定路径。
        $options = array(
            OssClient::OSS_HEADERS => array(
                'Content-Disposition' => 'inline;',
                'x-oss-meta-self-define-title' => 'SLOMOO',
            )
        );
        $bucket             = Config::get('app.aliyun_oss.bucket');
        $accessKeyId        = Config::get('app.aliyun_oss.accessKeyId');//去阿里云后台获取秘钥
        $accessKeySecret    = Config::get('app.aliyun_oss.accessKeySecret');//去阿里云后台获取秘钥
        $endpoint   = Config::get('app.aliyun_oss.endpoint');//阿里云OSS地址
        $ossClient  = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        // 判断bucketname是否存在，不存在就去创建
        if( !$ossClient->doesBucketExist($bucket)){
            $ossClient->createBucket($bucket);
        }
        $category = empty($category) ? $bucket : $category;
 
        $savePath = str_replace("\\","/",$savePath);
        
        $object = $category.'/'.substr(strrchr($savePath, "/"), 1);//想要保存文件的名称

        $file = '.'.$savePath;//文件路径，必须是本地的。

        try{
            $ossClient->uploadFile($bucket,$object,$file,$options); 
            if ($isunlink==true){
                unlink($file);
            }
        }catch (OssException $e){
            $e->getErrorMessage();
        }
        $oss = Config::get('app.aliyun_oss.url');

        //$web = "";//阿里云oss外网访问的Bucket域名
        return $oss."/".$object;
    }
}
 

if (!function_exists('uplodeOss')) {
    function uplodeOss($file){
        //$path = Filesystem::disk('public')->putFile('upload', $file);
        //$path = Filesystem::disk('public')->putFile('uploads', $file);
        //图片路径，Filesystem::getDiskConfig('public','url')功能是获取public目录下的storage，
        //$ossPath = $path.'/'.substr(strrchr($file, "/"), 1);
        //上传OSS 并获取阿里云OSS地址
        $image = alioss($file, '', true, 'wlxfq');
        if($image){
            return $image;
        }
    }
}

function sm2($res){
    // 针对密钥是 Asn.1 格式的
    /*$key = base64_decode('MFkwEwYHKoZIzj0CAQYIKoEcz1UBgi0DQgAEShQSLl+hSiiJVcUjn6kVmhpCClh0
    9RmCEUaKNMOXglHs0BTw1JITOZckfahMn/KHeop+9ubzPEB8fqdehWzzuQ==');
    $pubKey = new \Lat\Ecc\PublicKey();
    $pubKey->parse($key);
    $sm2 = new \Lat\Ecc\Sm2();
    $res = $sm2->pubEncrypt($pubKey, $data);

    $key = base64_decode('MIGTAgEAMBMGByqGSM49AgEGCCqBHM9VAYItBHkwdwIBAQQgmKp8uBbpJhZCXliV
    xksD3oM5H1oyDt84MNxiwVN6BAigCgYIKoEcz1UBgi2hRANCAARKFBIuX6FKKIlV
    xSOfqRWaGkIKWHT1GYIRRoo0w5eCUezQFPDUkhM5lyR9qEyf8od6in725vM8QHx+
    p16FbPO5');
    $privKey = new \Lat\Ecc\PrivateKey();
    $privKey->parse($key);
    $a = $sm2->decrypt($privKey, $res);
    print_r($a);die;*/
    

    // 针对密钥是解出来的16进制数据
    // sm2 加密
    /*$key = '042DBA45E7B03394F603CADAFCDDEC854D3E01A4E9C52CD799B85B1A14BDB970137AE58BA553D79F058604DC1CD4B77DE5408BA3308E767584100C2B663510C819';
    $sm2 = new \Lat\Ecc\Sm2();
    $pubKey = new \Lat\Ecc\PublicKey();
    $pubKey->parseUncompressedPoint($key);
    $res = $sm2->pubEncrypt($pubKey, $data);*/

    try {
        $sm2 = new \Lat\Ecc\Sm2();
        // sm2 解密
        $privKey = new \Lat\Ecc\PrivateKey();
        $privKey->setKey('BF1F907B4E0487F798DC80AFD7BC2A6201E8514233002272EA3BE2FC6F797843');
        $a = $sm2->decrypt($privKey, $res);
        $a = base64_decode($a);
        return json_decode($a,true);
    } catch (Exception $e) {
        return $e->getMessage();  
    }
}

/**
* 检查密码复杂度
*/
function checkPassword($pwd) {
   if ($pwd == null) {
        return ['code' => 0, 'data' => '', 'msg' => '密码不能为空'];
    }
    $pwd = trim($pwd);
    $strlen = strlen($pwd);
    if ($strlen < 8) { //必须大于6个字符
        return ['code' => 0, 'data' => '', 'msg' => '密码必须大于8个字符'];
    }
    if ($strlen > 16) { //必须大于6个字符
        return ['code' => 0, 'data' => '', 'msg' => '密码必须小于16个字符'];
    }
    if (preg_match('/^[0-9]+$/', $pwd)) { //必须含有特殊字符
        return ['code' => 0, 'data' => '', 'msg' => '密码不能全是数字，请包含数字，字母大小写或者特殊字符'];
    }
    if (preg_match('/^[a-zA-Z]+$/', $pwd)) {
        return ['code' => 0, 'data' => '', 'msg' => '密码不能全是字母，请包含数字，字母大小写或者特殊字符'];
    }
    if (preg_match('/^[0-9A-Z]+$/', $pwd)) {
        return ['code' => 0, 'data' => '', 'msg' => '请包含数字，字母大小写或者特殊字符'];
    }
    if (preg_match('/^[0-9a-z]+$/', $pwd)) {
        return ['code' => 0, 'data' => '', 'msg' => '请包含数字，字母大小写或者特殊字符'];
    }
    return ['code' => 1, 'data' => '', 'msg' => '密码复杂度通过验证'];
}

function SafeFilter (&$str) 
{
    $ra = Array('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/','/script/','/javascript/','/vbscript/','/expression/','/applet/','/meta/','/xml/','/blink/','/link/','/style/','/embed/','/object/','/frame/','/layer/','/title/','/bgsound/','/base/','/onload/','/onunload/','/onchange/','/onsubmit/','/onreset/','/onselect/','/onblur/','/onfocus/','/onabort/','/onkeydown/','/onkeypress/','/onkeyup/','/onclick/','/ondblclick/','/onmousedown/','/onmousemove/','/onmouseout/','/onmouseover/','/onmouseup/','/onunload/');
    $str     = preg_replace($ra,'',$str);     //删除非打印字符，粗暴式过滤xss可疑字符串
    $str     = strip_tags($str); //去除 HTML 和 PHP 标记并转换为HTML实体
    // htmlentities(strip_tags($str));
    return purXss($str);
}

// 避免xss攻击
function purXss ($string) 
{
    require_once '../extend/htmlpurifier/library/HTMLPurifier.auto.php';
    // 生成配置对象
    $_clean_xss_config = HTMLPurifier_Config::createDefault();
    // 以下就是配置：
    $_clean_xss_config->set('Core.Encoding', 'UTF-8');
    // 设置允许使用的HTML标签
    $_clean_xss_config->set('HTML.Allowed','div,b,strong,i,em,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]');
    // 设置允许出现的CSS样式属性
    $_clean_xss_config->set('CSS.AllowedProperties', 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align');
    // 设置a标签上是否允许使用target="_blank"
    $_clean_xss_config->set('HTML.TargetBlank', FALSE);
    // 清除空标签
    //$_clean_xss_config->set('AutoFormat.RemoveEmpty', true);
    // 使用配置生成过滤用的对象
    $obj = new HTMLPurifier($_clean_xss_config);

    // 过滤字符串
    return $obj->purify($string);
}

// 根据身份证前6位返回用户省份、城市、县区
if (!function_exists('get_area_code_info')) {
    /**
     * 根据身份证前6位返回用户省份、城市、县区
     * @param $idcard    完整身份证号
     */
    function get_area_code_info($idcard)
    {
        $code = substr(substr($idcard, 0, 6) . '0000', 0, 6);
        return \think\facade\Db::name('area_code')
        ->field('province,city,district')
        ->where('code', $code)
        ->find();
    }
}

// 缓存管理
function cache($name, $value = '', $options = null)
{
    static $cache = '';
    if (empty($cache)) {
        $cache = \app\common\libs\Cache_factory::instance();
    }   // 获取缓存
    if ('' === $value) {
        if (false !== strpos($name, '.')) {
            $vars = explode('.', $name);
            $data = $cache->get($vars[0]);
            return is_array($data) ? $data[$vars[1]] : $data;
        } else {
            return $cache->get($name);
        }
    } elseif (is_null($value)) {    //删除缓存
        return $cache->remove($name);
    } else {    //缓存数据
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : null;
        } else {
            $expire = is_numeric($options) ? $options : null;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
* 安全IP检测，支持IP段检测
* @param string $ip 要检测的IP
* @param string|array $ips 白名单IP或者黑名单IP
* @return boolean true 在白名单或者黑名单中，否则不在
*/
function is_safe_ip($ip="",$system){
    if(!$ip) $ip = get_client_ip(); //获取客户端IP
    $ips = explode(",", $system['is_safe_ip']);

    if(in_array($ip, $ips)){
        return true;
    }

    $ipregexp = implode('|', str_replace( array('*','.'), array('\d+','\.') ,$ips));
    $rs = preg_match("/^(".$ipregexp.")$/", $ip);
    if($rs) return true;

    return false;
}

/**
* 获取客户端IP地址
* @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
* @param boolean $adv 是否进行高级模式获取(有可能被伪装)
* @return mixed
*/
function get_client_ip($type = 0,$adv=false) {
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) return $ip[$type];

    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
* 比较当前时间是否在某个时段内
*/
function is_effective_time($start,$end){
    $now = time();
    // 获取当前日期
    $nowDate = date('Y-m-d',$now);
    // 获取当天开始时间
    $nowStart = strtotime($nowDate.date('H:i:s',$start));
    // 获取当天结束时间
    $nowEnd = strtotime($nowDate.date('H:i:s',$end));
    if($now >= $nowStart && $now <= $nowEnd){
       return true; 
    }
    return false;
}

/**
* 概率算法
*/
function get_rand_msg($proArr) {
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($proArr);
    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset ($proArr);
    return $result;
}

/**
 * 国内护照
 * 1.G|E+8位数字：如：G12345678
 * 2.D|S|P+7位数字：如：D1234567
 */
function passportVerify($content)
{
    /*$pattern = "/^[\w]{5,17}$/";
    if(!preg_match($pattern, $content)){
        return false;
    }
    return true;*/
    $reg = "/^1[45][0-9]{7}|([P|p|S|s]\d{7})|([S|s|G|g]\d{8})|([Gg|Tt|Ss|Ll|Qq|Dd|Aa|Ff]\d{8})|([H|h|M|m]\d{8，10})$/";
    if(!preg_match($reg, $content))
    {
        return false;
    }
    return true;
}


/**
 * 来往港澳通行证:
 * 1.W，C+8位数字
 * 2.7位数字
 */
function gapassportVerify($content)
{
    $pattern = "/^\d{7}$|^[W|C]\d{8}$/";
    if (!preg_match($pattern, $content)) {
        return false;
    }
    return true;
}

/**
 * 回乡证:
 * 1.W，C+8位数字
 * 2.7位数字
 */
function _checkReturnHome($value='')
{
    $reg = "/(H|M)(\d{8})$|^(H|M)(\d{10})$/";
    if(!preg_match($reg, $value))
    {
        return false;
    }
    return true;
}

/**
 * 台胞证：
 * 1、8位数字，如：12345678
 * 2、10位数字+(1位英文字母)，如：1234567890(T)
 */
function taibaoVerify($content)
{
    $pattern_one = "/^[\d]{8}$/";
    $pattern_two = "/^[\d]{10}[(|（][a-zA-z][)|）]$/iu";
    if((preg_match($pattern_one, $content) || preg_match($pattern_two, $content))){
        return true;
    }
    return false;
}

/**
 * 脱敏
 *
 * @authors: Msy
 * @Created-Time: 2022/10/17 17:54
 * @param $string 需要脱敏的字符
 * @param $start  开始位置
 * @param $length 脱敏长度
 * @param $re     替换字符
 * @return string
 */
function desensitize($string, $start = 0, $length = 0, $re = '*'){
    if(empty($string) || empty($length) || empty($re)) return $string;
    $end = $start + $length;
    $strlen = mb_strlen($string);
    $str_arr = array();
    for($i=0; $i<$strlen; $i++) {
        if($i>=$start && $i<$end){
             $str_arr[] = $re;
        }
        else{
            $str_arr[] = mb_substr($string, $i, 1);
        }

    }
    return implode('',$str_arr);
}

/**
 * 姓名正则过滤
 * 只能是汉字，可以包含·，长度不能超过30个字节
 */
/*function checkNameFilter($value='')
{   
    $reg = "/^(?=.{4,30}$)[\u4e00-\u9fa5]+(?:·[\u4e00-\u9fa5]+)*$/";
    if(!preg_match($reg, $value))
    {
        return false;
    }
    return true;
}*/
/**
 * 姓名正则过滤
 * @param $name
 * @return bool
 * 只能是汉字，可以包含·，长度不能超过30个字节
 */
function checkNameFilter($name){
    if (!preg_match('/^[\x{4e00}-\x{9fa5}]+([?:·•]?[\x{4e00}-\x{9fa5}])+$/u', $name)) {
        return false;
    }
    $strLen = mb_strlen($name);
    if ($strLen < 2 || $strLen > 20) {
        return false;
    }
    return true;
}

function uid_to_name($uid){
    return Db::name('users')->field('name,mobile,idcard')->where('id',$uid)->find();
}



if (!function_exists('clockConvertSecond')) {
    function clockConvertSecond($clock)
    {
        list($hours, $minutes) = explode(':', $clock);
        $seconds = $hours * 3600 + $minutes * 60;

        return $seconds;
    }
}

if (!function_exists('secondConvertClock')) {
    function secondConvertClock($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $time = sprintf("%02d:%02d", $hours, $minutes);

        return $time;
    }
}

if (!function_exists('weeksToEn')) {
    function weeksToEn($weeks)
    {
        $weekArray = explode(",", $weeks);

        $weekEns = array(
            '1'=>"Monday",
            '2'=>"Tuesday",
            '3'=>"Wednesday",
            '4'=>"Thursday",
            '5'=>"Friday",
            '6'=>"Saturday",
            '7'=>"Sunday"
        );

        $resultArray = array();
        foreach ($weekArray as $i) {
            $resultArray[] = $weekEns[$i];
        }
        return $resultArray;
    }
}
//生成唯一日期编码
if (!function_exists('uniqidDate')) {
    function uniqidDate(int $size = 16, string $prefix = ''): string
    {
        if ($size < 14) $size = 14;
        $code = $prefix . date('Ymd') . (date('H') + date('i')) . date('s');
        while (strlen($code) < $size) $code .= rand(0, 9);
        return $code;
    }
}
//2023-08-24 生成唯一数字编码
if (!function_exists('uniqidNumber')) {
    function uniqidNumber(int $size = 12, string $prefix = ''): string
    {
        $time = strval(time());
        if ($size < 10) $size = 10;
        $code = $prefix . (intval($time[0]) + intval($time[1])) . substr($time, 2) . rand(0, 9);
        while (strlen($code) < $size) $code .= rand(0, 9);
        return $code;
    }
}
//2023-09-04 生辰随机数
if(!function_exists('buildRandom')){
    function buildRandom(int $size = 10, int $type = 1, string $prefix = ''): string
    {
        $numbs = '0123456789';
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        if ($type === 1) $chars = $numbs;
        if ($type === 3) $chars = "{$numbs}{$chars}";
        $code = $prefix . $chars[rand(1, strlen($chars) - 1)];
        while (strlen($code) < $size) $code .= $chars[rand(0, strlen($chars) - 1)];
        return $code;
    }
}
// 2023-06-30 用户核销时与商户的距离计算
// 2023-07-10 去除用户与商户点核销、增加商户核销点、核验人与用户的位置校验
function calculateDistance($userLat, $userLng, $merchantLat, $merchantLng) {
    $earthRadius = 6371; // 地球半径，单位为千米

    $deltaLat = deg2rad($merchantLat - $userLat);
    $deltaLng = deg2rad($merchantLng - $userLng);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) + cos(deg2rad($userLat)) * cos(deg2rad($merchantLat)) * sin($deltaLng / 2) * sin($deltaLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    $distance = $earthRadius * $c * 1000; // 返回米
    return $distance;
}

// 检测字符串是否有效的json格式
function isJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}


/**
 * 对称加密
 * @param string $str      需要加密的字符串
 * @param string $key      加密key
 * @return string data     加密之后的字符串
 */
function sys_encryption($str, $key)
{
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
    $encrypted = openssl_encrypt($str, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * 对称解密
 * @param string $str      需要解密的字符串
 * @param string $key      加密key
 * @return string data     解密之后的字符串
 */
function sys_decryption($str, $key)
{
    $data = base64_decode($str);
    $iv = substr($data, 0, openssl_cipher_iv_length('AES-256-CBC'));
    $encrypted = substr($data, openssl_cipher_iv_length('AES-256-CBC'));
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

function getDateRange()
{
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');

    $dateRange = [];
    $currentDate = $startDate;

    while ($currentDate <= $endDate) {
        $dateRange[] = $currentDate;
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }
    return $dateRange;
}

//curl post提交
function http_curl_post_header($url, $data = null,$header){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

/**
 * 上传文件到远程服务器FTP上传方式
 * @param string $localFilePath 本地文件路径
 * @param string $remoteServerPath 远程服务器路径
 * @return bool 上传成功返回地址，上传失败返回异常信息
 */
if (!function_exists('slomoooss')) {
    function slomoooss($file, $localFilePath, $remoteServerPath) {
        $config  = Config::get('app.slomoo_oss');
        $ftpHost = $config['remoteHost'];
        $ftpPort = $config['remotePort'];
        $ftpUsername = $config['remoteUser'];
        $ftpPassword = $config['remotePass'];
        $remoteUrl  = $config['url'];
        $fptTimeout = $config['timeOut'];
        
        try {
            // 连接FTP服务器
            $ftpConn = ftp_connect($ftpHost, $ftpPort, $fptTimeout);
            if (!$ftpConn) {
                throw new Exception("无法连接到FTP服务器");
            }

            // 登录FTP服务器
            $ftpLogin = ftp_login($ftpConn, $ftpUsername, $ftpPassword);
            if (!$ftpLogin) {
                ftp_close($ftpConn);
                throw new Exception("无法登录FTP服务器");
            }

            $fullRemotePath = $remoteServerPath . $file;

            // 检查目录是否存在，如果不存在则创建
            $parts = explode('/', $remoteServerPath);
            $dirStr = '';

            // 逐级创建目录
            foreach ($parts as $part) {
                $dirStr .= $part . '/';
                // 检查目录是否存在
                if (!@ftp_chdir($ftpConn, $dirStr)) {
                    // 如果目录不存在，则创建目录
                    if (!ftp_mkdir($ftpConn, $dirStr)) {
                        throw new Exception("无法创建目录: " . $dirStr);
                    }
                    // 设置目录权限
                    if (!ftp_chmod($ftpConn, 0755, $dirStr)) {
                        throw new Exception("无法设置目录权限: " . $dirStr);
                    }
                }
            }

            // 切换到被动模式
            ftp_set_option($ftpConn, FTP_USEPASVADDRESS, false); // 必须添加，否则报错 这个函数的目的是关闭被动模式的地址设置。
            ftp_pasv($ftpConn, true); // 注：切换到被动模式,否则linux服务器下上传图片文件大小为0

            // 打开上传目录
            $dir = ftp_pwd($ftpConn);
            if (empty($dir)) {
                throw new Exception("无法获取当前目录");
            }

            // 上传文件
            $upload = ftp_put($ftpConn, $fullRemotePath, $localFilePath, FTP_BINARY);
            if (!$upload) {
                throw new Exception("文件上传失败: " . ftp_error($ftpConn));
            }

            // 删除本地文件
            if (!unlink($localFilePath)) {
                throw new Exception("无法删除本地文件");
            }
        } catch (Exception $e) {
            // 处理或抛出异常
            throw new Exception("文件上传失败: " . $e->getMessage());
        } finally {
            // 关闭FTP连接
            ftp_close($ftpConn);
        }

        return $remoteUrl . $fullRemotePath;
    }
}

/**
 * 获取字符串两个符号之间的字符串
 * @param string $s 原字符串
 * @param string $start 开始位置符号
 * @param string $end 结束位置符号
 * @return string 上传成功返回地址，上传失败返回异常信息
 */
if (!function_exists('extractString')) {
    function extractString($s,$start,$end) {
        // 找到最后一个斜杠出现的位置
        $lastSlashIndex = strrpos($s, $start);
        
        // 如果没有找到斜杠，则返回整个字符串
        if ($lastSlashIndex === false) {
            return $s;
        }
        
        // 找到第一个句号出现的位置
        $firstDotIndex = strpos($s, $end, $lastSlashIndex);
        
        // 如果没有找到句号，则返回从最后一个斜杠到字符串末尾的字符串
        if ($firstDotIndex === false) {
            return substr($s, $lastSlashIndex + 1);
        }
        
        // 返回这两者之间的字符串
        return substr($s, $lastSlashIndex + 1, $firstDotIndex - $lastSlashIndex - 1);
    }
}