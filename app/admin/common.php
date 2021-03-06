<?php
//admin模块公共函数

/* #正则验证字符串是否为url地址
function validateURL($URL) {
  $pattern_1 = "/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i";
  $pattern_2 = "/^(www)((\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i";       
  if(preg_match($pattern_1, $URL) || preg_match($pattern_2, $URL)){
	return true;
  } else{
	return false;
  }
} */


/**
 * 修改、删除时，则百度编辑器中废弃掉的图片一并删除
 * @param $content  百度编辑器中的内容
 * @return true
 */
function delete_imgs($content, $content_new='', $is_update=false)
{
    $host = "http://".$_SERVER['HTTP_HOST'];//当前域名
    $url = $host.'/ueditor/php/upload/image'; //图片上传地址
    
    #去掉 编辑器中图片可能携带的 当前域名
    $content = str_ireplace($url, '/ueditor/php/upload/image', $content);
    #正则匹配返回内容中的图片
    $imgs = getImgSrc($content);
    
    $imgs_diff = [];
    if(true === $is_update){
        $imgs_new = [];
        if(!empty($content_new)){
            $content_new = str_ireplace($url, '/ueditor/php/upload/image', $content_new);
            $imgs_new = getImgSrc($content_new);
            
            if(!empty($imgs_new)){
                #数组的差集，即旧内容中被废弃的图片，并重新排列数组下标
                $imgs_diff = array_values(array_diff($imgs, $imgs_new));
            }else{
                $imgs_diff = $imgs;
            }
        }else{
            $imgs_diff = $imgs;
        }
    }else{
        $imgs_diff = $imgs;
    }
    
    #循环删除被废弃的图片
    foreach($imgs_diff as $v){
        if(file_exists(ROOT_PATH . 'public' . $v)){
            @unlink(ROOT_PATH . 'public' . $v);
        }
    }
    
    return true;
}


/**
 * 管理员操作日志
 * @param  [type] $data [description]
 * @return [type]       [description]
 */
function addlog($operation_id='')
{
    //获取配置文件中的参数 写入操作日志 是否开启 
    if(config('web_config.is_log') == 1) {
        $data['operation_id'] = $operation_id;
        $data['admin_id'] = \think\Session::get('admin');//管理员id
        $request = \think\Request::instance();
        $data['ip'] = $request->ip();//操作ip
        $data['create_time'] = time();//操作时间
        $url['module'] = $request->module();
        $url['controller'] = $request->controller();
        $url['function'] = $request->action();
        //获取url参数
        $parameter = $request->path() ? $request->path() : null;
        //将字符串转化为数组
        $parameter = explode('/',$parameter);
        //剔除url中的模块、控制器和方法
        foreach ($parameter as $key => $value) {
            if($value != $url['module'] and $value != $url['controller'] and $value != $url['function']) {
                $param[] = $value;
            }
        }

        if(isset($param) and !empty($param)) {
            //确定有参数
            $string = '';
            foreach ($param as $key => $value) {
                //奇数为参数的参数名，偶数为参数的值
                if($key%2 !== 0) {
                    //过滤只有一个参数和最后一个参数的情况
                    if(count($param) > 2 and $key < count($param)-1) {
                        $string.=$value.'&';
                    } else {
                        $string.=$value;
                    }
                } else {
                    $string.=$value.'=';
                }
            } 
        } else {
            //ajax请求方式，传递的参数path()接收不到，所以只能param()
            $string = [];
            $param = $request->param();
            foreach ($param as $key => $value) {
                if(!is_array($value)) {
                    //这里过滤掉值为数组的参数
                    $string[] = $key.'='.$value;
                }
            }
            $string = implode('&',$string);
        }
        $data['admin_menu_id'] = empty(\think\Db::name('admin_menu')->where($url)->where('parameter',$string)->value('id')) ? \think\Db::name('admin_menu')->where($url)->value('id') : \think\Db::name('admin_menu')->where($url)->where('parameter',$string)->value('id');
                    
        #dump( \think\Db::getLastSql()); dump($data);exit;
        //return $data;
        \think\Db::name('admin_log')->insert($data);
    } else {
        //关闭了日志
        return true;
    }
	
}


/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}
