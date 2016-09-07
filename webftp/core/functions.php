<?php


defined('ES_ROOT') or die( 'Access not allowed');
G('init');

function u2g($str){
    return iconv('UTF-8', 'GB2312//IGNORE', $str);
}
function g2u($str){
    return iconv('GB2312', 'UTF-8//IGNORE', $str);
}

function gpc($key, $type='g', $func=null){
    switch (strtoupper($type)){
        case 'G': $var = &$_GET;     break;
        case 'P': $var = &$_POST;    break;
        case 'R': $var = &$_REQUEST; break;
        case 'C': $var = &$_COOKIE;  break;
        case 'S': $var = &$_SESSION; break;
    }
    $data = isset($var[$key]) ? $var[$key] : NULL;
    if (!is_array($data))
    $data = trim($data);
    if ($func)
    $data = $func($data);
    return $data;
}





/**
 * 文件系统函数 兼容Win、Linux
 *
 * @param unknown_type $size
 * @return unknown
 */
function get_dirname($path){
    return dirname($path);
}

function get_basename($path){
    $path = rtrim($path, '/\\');
    $path = explode('/', $path);
    return end($path);
}

function get_filename($path){
    $path = get_basename($path);
    $path = explode('.', $path);
    return $path[0];
}

function get_fileext($path){
    $path = get_basename($path);
    $path = explode('.', $path);
    return isset($path[1]) ? strtolower(end($path)) : '';
}

function get_pathinfo($path){
    $info = array(
    'dirname'   => get_dirname($path),
    'basename'  => get_basename($path),
    'filename'  => get_filename($path),
    'extension' => get_fileext($path),
    );
    return $info;
}


//文件大小格式化
function get_deal_size($size){
    $dna = array('Byte','KB','MB','GB', 'TB', 'PB');
    $did = 0;
    while($size >= 900){
        $size = round($size*100/1024)/100;
        $did++;
    }
    return $size.' '.$dna[$did];
}

function get_deal_chmod($path, $format=false){
    $perms = fileperms($path);
    if(!$format){
        $mode = substr(sprintf('%o', $perms), -4);
    } else{
        // Owner
        $mode = '';
        $mode .= (($perms & 0x0100) ? 'r' : '-');
        $mode .= (($perms & 0x0080) ? 'w' : '-');
        $mode .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $mode .= (($perms & 0x0020) ? ' r' : ' -');
        $mode .= (($perms & 0x0010) ? 'w' : '-');
        $mode .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

        // World
        $mode .= (($perms & 0x0004) ? ' r' : ' -');
        $mode .= (($perms & 0x0002) ? 'w' : '-');
        $mode .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    }
    return $mode;
}


// 返回Ajax数据
function show($code, $message, $data=array(), $json=true) {
    header('Content-Type: text/html; charset=utf-8');
    if ($json) {
        $arr = array('code'=>$code, 'message'=>$message, 'data'=>$data, 'time'=>G('init', '_end', 3));
        echo json_encode($arr);
        exit();
    } else{
        echo $message;
        exit();
    }
}

// 浏览器友好的变量输出
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')){
            $output = print_r($var, true);
            $output = '<pre>'.$label.htmlspecialchars($output, ENT_QUOTES).'</pre>';
        } else{
            $output = $label.print_r($var, true);
        }
    } else{
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = '<pre>'.$label.htmlspecialchars($output, ENT_QUOTES).'</pre>';
        }
    }
    if ($echo){
        echo($output);
    } else{
        return $output;
    }
    return null;
}

// 页面重定向
function redirect($url){
    exit("<script type='text/javascript'>document.location.href = '{$url}';</script>");
}

//自定义错误处理
function error_handler_fun($errno, $errmsg, $errfile, $errline, $errvars){
    if (!C('LOG_EXCEPTION_RECORD')) return;
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
    $errortype = array (
    E_ERROR              => 'EMERG',
    E_WARNING            => 'WARNING',//非致命的 run-time 错误。不暂停脚本执行。
    E_PARSE              => 'EMERG',//语法错误
    E_NOTICE             => 'NOTICE',//Run-time 通知。
    E_CORE_ERROR         => 'EMERG',
    E_CORE_WARNING       => 'WARNING',
    E_COMPILE_ERROR      => 'EMERG',
    E_COMPILE_WARNING    => 'WARNING',
    E_USER_ERROR         => 'EMERG',//致命的用户生成的错误。
    E_USER_WARNING       => 'WARNING',//非致命的用户生成的警告。
    E_USER_NOTICE        => 'NOTICE',//用户生成的通知。
    E_STRICT             => 'NOTICE',
    E_RECOVERABLE_ERROR  => 'EMERG',//可捕获的致命错误。
    'INFO'               => 'INFO',//信息: 程序输出信息
    'DEBUG'              => 'DEBUG',// 调试: 调试信息
    'SQL'                => 'SQL',// SQL：SQL语句
    );
    if (isset($errortype[$errno])){
        $error['type'] = $errortype[$errno];
    } else{
        $error['type'] = $errno;
    }
    if (!in_array($error['type'], explode(',',C('LOG_EXCEPTION_TYPE')))){return;}

    $err  = date('[ Y-m-d H:i:s (T) ]').'  ';
    $err .= $error['type'].':  ';
    $err .= $errmsg.'  ';
    $err .= $errfile.'  ';
    $err .= '第'.$errline.'行  ';
    $err .= "\n";

    $destination = DATA_PATH.'logs/'.date('y_m_d').'.log';
    if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination) ){
        if (1 == C('LOG_SAVE_TYPE')){
            unlink($destination);
        } else{
            rename($destination,dirname($destination).'/'.time().'-'.basename($destination));
        }
    }
    error_log($err, 3, $destination);
}



// 获取配置值
function C($name=null, $value=null) {
    static $_config = array();
    // 无参数时获取所有
    if (empty($name)){
        return $_config;
    }

    // 优先执行设置获取或赋值
    if (is_string($name)){
        $name = strtolower($name);
        if (false === strpos($name, '.')) {
            if (is_null($value)){
                return isset($_config[$name]) ? $_config[$name] : null;
            } else{
                return $_config[$name] = $value;
            }
        }

        // 二、三维数组设置和获取支持
        $name = explode('.', $name);
        if (false === isset($name[2])){
            if (is_null($value)){
                return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
            } else{
                return $_config[$name[0]][$name[1]] = $value;
            }
        } else{
            if (is_null($value)){
                return isset($_config[$name[0]][$name[1]][$name[2]]) ? $_config[$name[0]][$name[1]][$name[2]] : null;
            } else{
                return $_config[$name[0]][$name[1]][$name[2]] = $value;
            }
        }
    }
    //批量设置
    if (is_array($name)){
        return $_config = array_merge($_config, array_change_key_case($name, CASE_LOWER));
    }
    //避免非法参数
    return null;
}

// 记录和统计时间（微秒）
function G($start, $end='', $dec=3){
    static $_info = array();
    if (!empty($end)){
        if (!isset($_info[$end])){
            $_info[$end] = microtime(true);
        }
        return number_format(($_info[$end]-$_info[$start]), $dec);
    } else{
        return $_info[$start]  =  microtime(true);
    }
}

// 设置和获取统计数据
function N($key, $step=0){
    static $_num = array();
    if (!isset($_num[$key])) $_num[$key] = 0;
    if (empty($step)){
        return $_num[$key];
    } else{
        return $_num[$key] = $_num[$key] + (int) $step;
    }
}
