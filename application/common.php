<?php

// 公共助手函数

use Symfony\Component\VarExporter\VarExporter;
use think\exception\HttpResponseException;
use think\Response;

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @param int    $precision 小数位数
     * @return string
     */
    function format_bytes($size, $delimiter = '', $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $cdnurl = \think\Config::get('upload.cdnurl');
        $url = preg_match($regex, $url) || ($cdnurl && stripos($url, $cdnurl) === 0) ? $url : $cdnurl . $url;
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = isset($v['field']) ? $v['field'] : $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = isset($v['display']) ? $v['display'] : str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = isset($v['primary']) ? $v['primary'] : '';
            $v['column'] = isset($v['column']) ? $v['column'] : 'name';
            $v['model'] = isset($v['model']) ? $v['model'] : '';
            $v['table'] = isset($v['table']) ? $v['table'] : '';
            $v['name'] = isset($v['name']) ? $v['name'] : str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = isset($ids[$v['field']]) ? $model->where($primary, 'in', $ids[$v['field']])->column($v['column'], $primary) : [];
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $linedata = array_intersect_key($result[$n], $curr);
                    $v[$fieldsArr[$n]['display']] = $fieldsArr[$n]['column'] == '*' ? $linedata : implode(',', $linedata);
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 使用短标签打印或返回数组结构
     * @param mixed   $data
     * @param boolean $return 是否返回数据
     * @return string
     */
    function var_export_short($data, $return = true)
    {
        return var_export($data, $return);
        $replaced = [];
        $count = 0;

        //判断是否是对象
        if (is_resource($data) || is_object($data)) {
            return var_export($data, $return);
        }

        //判断是否有特殊的键名
        $specialKey = false;
        array_walk_recursive($data, function (&$value, &$key) use (&$specialKey) {
            if (is_string($key) && (stripos($key, "\n") !== false || stripos($key, "array (") !== false)) {
                $specialKey = true;
            }
        });
        if ($specialKey) {
            return var_export($data, $return);
        }
        array_walk_recursive($data, function (&$value, &$key) use (&$replaced, &$count, &$stringcheck) {
            if (is_object($value) || is_resource($value)) {
                $replaced[$count] = var_export($value, true);
                $value = "##<{$count}>##";
            } else {
                if (is_string($value) && (stripos($value, "\n") !== false || stripos($value, "array (") !== false)) {
                    $index = array_search($value, $replaced);
                    if ($index === false) {
                        $replaced[$count] = var_export($value, true);
                        $value = "##<{$count}>##";
                    } else {
                        $value = "##<{$index}>##";
                    }
                }
            }
            $count++;
        });

        $dump = var_export($data, true);

        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', '[', $dump); // Starts
        $dump = preg_replace('#\n([ ]*)\),#', "\n$1],", $dump); // Ends
        $dump = preg_replace('#=> \[\n\s+\],\n#', "=> [],\n", $dump); // Empties
        $dump = preg_replace('#\)$#', "]", $dump); //End

        if ($replaced) {
            $dump = preg_replace_callback("/'##<(\d+)>##'/", function ($matches) use ($replaced) {
                return isset($replaced[$matches[1]]) ? $replaced[$matches[1]] : "''";
            }, $dump);
        }

        if ($return === true) {
            return $dump;
        } else {
            echo $dump;
        }
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" dominant-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                $response = Response::create('跨域检测无效', 'html', 403);
                throw new HttpResponseException($response);
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                $response = Response::create('', 'html');
                throw new HttpResponseException($response);
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('check_ip_allowed')) {
    /**
     * 检测IP是否允许
     * @param string $ip IP地址
     */
    function check_ip_allowed($ip = null)
    {
        $ip = is_null($ip) ? request()->ip() : $ip;
        $forbiddenipArr = config('site.forbiddenip');
        $forbiddenipArr = !$forbiddenipArr ? [] : $forbiddenipArr;
        $forbiddenipArr = is_array($forbiddenipArr) ? $forbiddenipArr : array_filter(explode("\n", str_replace("\r\n", "\n", $forbiddenipArr)));
        if ($forbiddenipArr && \Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $forbiddenipArr)) {
            $response = Response::create('请求无权访问', 'html', 403);
            throw new HttpResponseException($response);
        }
    }
}

if (!function_exists('build_suffix_image')) {
    /**
     * 生成文件后缀图片
     * @param string $suffix 后缀
     * @param null   $background
     * @return string
     */
    function build_suffix_image($suffix, $background = null)
    {
        $suffix = mb_substr(strtoupper($suffix), 0, 4);
        $total = unpack('L', hash('adler32', $suffix, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $background = $background ? $background : "rgb({$r},{$g},{$b})";

        $icon = <<<EOT
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
            <path style="fill:#E2E5E7;" d="M128,0c-17.6,0-32,14.4-32,32v448c0,17.6,14.4,32,32,32h320c17.6,0,32-14.4,32-32V128L352,0H128z"/>
            <path style="fill:#B0B7BD;" d="M384,128h96L352,0v96C352,113.6,366.4,128,384,128z"/>
            <polygon style="fill:#CAD1D8;" points="480,224 384,128 480,128 "/>
            <path style="fill:{$background};" d="M416,416c0,8.8-7.2,16-16,16H48c-8.8,0-16-7.2-16-16V256c0-8.8,7.2-16,16-16h352c8.8,0,16,7.2,16,16 V416z"/>
            <path style="fill:#CAD1D8;" d="M400,432H96v16h304c8.8,0,16-7.2,16-16v-16C416,424.8,408.8,432,400,432z"/>
            <g><text><tspan x="220" y="380" font-size="124" font-family="Verdana, Helvetica, Arial, sans-serif" fill="white" text-anchor="middle">{$suffix}</tspan></text></g>
        </svg>
EOT;
        return $icon;
    }
}

// 密码盐
if (!function_exists('build_ranstr')) {
    /**
     * 获得随机字符串
     * @param $len             需要的长度
     * @param $special        是否需要特殊符号
     * @return string       返回随机字符串
     */
    function build_ranstr($len = 10, $special = false)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
    
        if($special){
            $chars = array_merge($chars, array(
                "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
                "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
                "}", "<", ">", "~", "+", "=", ",", "."
            ));
        }
    
        $charsLen = count($chars) - 1;
        shuffle($chars);                            //打乱数组顺序
        $str = '';
        for($i=0; $i<$len; $i++)
        {
            $str .= $chars[mt_rand(0, $charsLen)];    //随机取出一位
        }
        return $str;
    }
}

// 订单号生成方法
if (!function_exists('build_code')) {

    /**
     * 生成唯一订单号
     *
     * @param string $path 指定的path
     * @return string
     */
    function build_code($prefix = "")
    {
        @date_default_timezone_set("PRC");
        $order_id_main = date('YmdHis') . rand(10000, 99999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $osn = $prefix.$order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT); //生成唯一订单号
        return $osn;
    }
}

// 判断某函数是否被定义
// 返回值: 返回上传成功或失败的数组
if(!function_exists('build_upload')) {
    function build_upload($name = 'images') {
        // 返回上传的信息
        $success = [
            'result' => false,
            'msg' => '', // 返回的文本(默认为空)
            'data' => '' // 返回的数据(默认为空)
        ];

        // 获取上传的文件
        $file = request() -> file($name);

        // 将路径存在变量中(ROOT_PATH为根路径)
        $path = ROOT_PATH . 'public' . DS .'uploads';

        // 判断文件是否有获取到
        if($file) {
            // 将获取到的文件移动到指定的目录中
            $info = $file -> move($path);

            // 如果移动成功说明上传成功
            if($info) {
                $success['result'] = true;
                $success['msg'] = '文件上传成功';
                $success['data'] = str_replace("\\", "/", '/uploads/' . $info->getSaveName()); // 跳转并获取上传的文件名
                return $success;
            } else {
                // 移动失败(上传失败)就调用getError方法报错
                $success['result'] = false;
                $success['msg'] = $file -> getError();
                return $success;
            }
        } else {
            // 没有获取到文件
            $success['result'] = false;
            $success['msg'] = '无文件上传';
            return $success;
        }
        
    }
}

// 新建邮箱发送
if(!function_exists('send_email')) {
    // 引入邮箱php文件
    include_once(APP_PATH . 'phpmail/class.phpmailer.php');

    // 邮箱的发送(email是页面提交的用户邮箱地址，code是验证码)
    function send_email($email = '' , $code = '') {
        $success = [
            'res' => false,
            'msg' => '',
        ];

        // 1、判断邮箱是否为空
        if(empty($email)) {
            $success['res'] =  false;
            $success['msg'] =  '邮箱为空, 无法发送验证码';
            return $success; // 结果
        }

        // 2、判断验证码是否为空
        if(empty($code)) {
            $success['res'] =  false;
            $success['msg'] =  '验证码为空, 无法发送验证码';
            return $success; // 结果
        }

        // 要发送的内容
        $temp = "
            <h1>
                欢迎新用户注册该平台
            </h1>
            您的验证码是: <b>$code</b>
        ";

        // 创建一个邮件发送的对象
        $mailer = new PHPMailer(true);

        // 开启这项服务
        $mailer->IsSMTP();

        // 开启账号验证服务
        $mailer -> SMTPAuth = true;

        // 设置邮件发送的端口
        $mailer -> Port = 25;

        // 你想要使用的是哪个第三方 qq sina 163 162 
        $mailer -> Host = 'smtp.qq.com';

        // 用户名(发送方邮箱地址)和密码
        $mailer -> Username = '2645280647@qq.com';

        // 密码不是登录密码  是 授权码
        $mailer -> Password = 'idwhrkjylwlvecai';

        // 使用第三方来发送（该方法不知什么原因会导致报错）
        // $mailer -> IsSendmail();

        // 回复人地址（用户回复邮箱给我）
        $mailer -> AddReplyTo("2645280647@qq.com","回复人的地址");

        // 发送方
        $mailer -> From       = "2645280647@qq.com";
        $mailer -> FromName   = "测试客服";

        // 添加收件人(页面发送请求的天湖的邮箱地址)
        $mailer -> AddAddress($email);

        // 邮件标题
        $mailer -> Subject  = "测试邮箱信息";

        // 要发送的内容是什么
        $mailer -> MsgHTML($temp);

        // 发送给客服的内容是否带有html标签
        $mailer -> IsHTML(true);

        $res = $mailer -> Send();

        if($res)
        {
            $success['res'] = true;
            $success['msg'] = '邮件发送成功';
        }else
        {
            $success['res'] = false;
            $success['msg'] = '邮件发送失败';
        }

        return $success;
    }
}

// 物流查询接口
if(!function_exists('query_express')) {
    /**
     * $no 快递单号
     * $type 快递公司
    */

    function query_express($no = '', $type = '') {
        $success = [
            'result' => false,
            'msg' => '',
            'data' => null
        ];

        // 判断快递单号是否存在
        if(empty($no)) {
            $success['result'] = false;
            $success['msg'] = '快递单号不能为空';
            return $success;
        }

        $data = [
            'no' => $no
        ];

        if(!empty($type)) {
            $data['type'] = $type;
        }

        // url参数结构
        $querys = http_build_query($data); //参数写在这里

        // 头信息验证
        $appcode = "fe71f9ee4a7049a79386c3cdee4aa24e"; //开通服务后 买家中心-查看AppCode
        $headers = [
            "Authorization:APPCODE " . $appcode
        ];

        // 接口地址
        $url = "https://wuliu.market.alicloudapi.com/kdi?$querys";//api访问链接

        // 初始化cul对象
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET"); // 设置GET请求
        curl_setopt($curl, CURLOPT_URL, $url); // 设置请求地址
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); // 设置头信息
        curl_setopt($curl, CURLOPT_FAILONERROR, false); // 不显示错误信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 将结果return回来
        curl_setopt($curl, CURLOPT_HEADER, false); // 返回头信息

        // 发送请求, 并返回结果
        $output = curl_exec($curl);

        // 将json转换为php数组
        $output = json_decode($output, true);

        // 判断物流信息是否查询成功
        if($output['status'] != 0) {
            $success['result'] = false;
            $success['msg'] = $output['msg'];
            return $success;
        } else {
            $success['result'] = true;
            $success['msg'] = '查询物流信息成功';
            $success['data'] = $output['result']['list'];
        }
        return $success;

    }
}