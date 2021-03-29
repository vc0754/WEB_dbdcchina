<?php
// 应用公共文件

# 递归 转 树状结构 （分类，单页父级） 
function treelist($data,$id=0,$level=0, $has_str=true){
    static $trees = array();
    foreach ($data as $k => $value) {
        if ($value['pid']==$id) {
            if(true === $has_str){ //是否要 前置空格
                if($level> 0){
                    $value['str'] = str_repeat('&emsp;&emsp;', $level).'└ ';
                }else{
                    $value['str'] = '';
                }
            }
            
            $value['level'] = $level+1;
            $trees[] = $value;
            unset($data[$k]); //注销当前节点数据，减少已无用的遍历
            
            treelist($data,$value['id'],$value['level'],$has_str);
        }
    }
    return $trees;
}

# 递归 转 树状结构 （所有分类） 
function treelist2($data,$id=0,$level=0){
    static $trees_all = array(); //避免与上面的 静态变量 $trees 一样
    foreach ($data as $k => $value) {
        if ($value['pid']==$id) {
            if($level> 0){
                $value['str'] = str_repeat('&emsp;&emsp;', $level).'└ ';
            }else{
                $value['str'] = '';
            }
            
            $value['level'] = $level+1;
            $trees_all[] = $value;
            unset($data[$k]); //注销当前节点数据，减少已无用的遍历
            
            treelist2($data,$value['id'],$value['level']);
        }
    }
    return $trees_all;
}

/**
     * 找出当前类的顶级分类 接口
     *
     * @param $categoryInfo 所有分类 数组
     * @param $nowCategory 当前分类 数组
     *
     * @return 顶级分类 所有数据
     */
function getTopCategory ($categoryInfo, $nowCategory)
{
    if ( $nowCategory['pid'] != 0 ) {
        foreach ( $categoryInfo as $cate ) {
            if ( $cate['id'] == $nowCategory['pid'] ) {
                if ( $cate['pid'] == 0) {
                    return $cate; //$cate['id']
                } else {
                    $parentCate = [
                        'id' => $cate['id'],
                        'pid'=> $cate['pid']
                    ];
                    return getTopCategory($categoryInfo, $parentCate);
                }
            }
        }
    } else {
        return $nowCategory; //$nowCategory['id']
    }
}

# 正则匹配百度编辑器 文章中的图片，只获取图片的地址
function getImgSrc($str){
	$pattern = '/<img[^>]*src\s?=[\'|"]([^\'|"]*)[\'|"]/is';
	# 正则表达式全局匹配，成功返回整个模式匹配的次数；并把匹配结果存入到 $getPicPath数组中
	$flag = preg_match_all($pattern,$str,$getPicPath);
	
	$imgSrc = array();
	if($flag){
		for($i=0; $i<count($getPicPath[1]); $i++){
			$imgSrc[] = $getPicPath[1][$i];			
		}
	}
	return $imgSrc;
}


/**
 * 根据附件表的id返回url地址
 * @param  [type] $id [description]
 * @return [type]     [description]
 */
function geturl($id)
{
	if ($id) {
		$geturl = \think\Db::name("attachment")->where(['id' => $id])->find();
		if($geturl['status'] == 1) {
			//审核通过
			return $geturl['filepath'];
		} elseif($geturl['status'] == 0) {
			//待审核
			return '/uploads/xitong/beiyong1.jpg';
		} else {
			//不通过
			return '/uploads/xitong/beiyong2.jpg';
		} 
    }
    return false;
}


/**
 * [SendMail 邮件发送]
 * @param [type] $address  [description]
 * @param [type] $title    [description]
 * @param [type] $content  [description]
 * @param [type] $from     [description]
 * @param [type] $fromname [description]
 * @param [type] $smtp     [description]
 * @param [type] $username [description]
 * @param [type] $password [description]
 */
function SendMail($to, $title, $content)
{
    vendor('phpmailer.PHPMailerAutoload');
    //vendor('PHPMailer.class#PHPMailer');
    $mail = new \PHPMailer(); 
    
    // 设置PHPMailer使用SMTP服务器发送Email
    $mail->IsSMTP();                
    // 设置邮件的字符编码，若不指定，则为'UTF-8'
    $mail->CharSet='UTF-8'; 
    
    // 设置SMTP服务器。
    $mail->Host=config('MAIL.HOST');
    // 端口号
    $mail->Port=config('MAIL.PORT');
    // 发送方式: 'tls' 或 'ssl'
    $mail->SMTPSecure=config('MAIL.SECURE');
    // 设置为"需要验证" ThinkPHP 的config方法读取配置文件
    $mail->SMTPAuth=config('MAIL.SMTPAUTH');
    
    // 设置用户名和密码。
    $mail->Username=config('MAIL.FROM'); 
    $mail->Password=config('MAIL.PASSWORD'); 
    // 设置邮件头的From字段。
    $mail->From=config('MAIL.FROM'); 
    // 设置发件人名字
    $mail->FromName=config('MAIL.FROMNAME'); 
    // 添加收件人地址，可以多次使用来添加多个收件人
    $mail->AddAddress($to); 
    // 设置html发送格式
    $mail->isHTML(true); 
    
    // 设置邮件标题
    $mail->Subject=$title;
    // 设置邮件正文
    $mail->Body=$content; 
    // 邮件正文不支持HTML的备用显示
    $mail->AltBody = "This is the body in plain text for non-HTML mail clients";	
    
    // 发送邮件
    return($mail->Send());
}


/**
 * 阿里大鱼短信发送
 * @param [type] $appkey    [description]
 * @param [type] $secretKey [description]
 * @param [type] $type      [description]
 * @param [type] $name      [description]
 * @param [type] $param     [description]
 * @param [type] $phone     [description]
 * @param [type] $code      [description]
 * @param [type] $data      [description]
 */
function SendSms($param,$phone)
{
    // 配置信息
    import('dayu.top.TopClient');
    import('dayu.top.TopLogger');
    import('dayu.top.request.AlibabaAliqinFcSmsNumSendRequest');
    import('dayu.top.ResultSet');
    import('dayu.top.RequestCheckUtil');

    //获取短信配置
    $data = \think\Db::name('smsconfig')->where('sms','sms')->find();
            $appkey = $data['appkey'];
            $secretkey = $data['secretkey'];
            $type = $data['type'];
            $name = $data['name'];
            $code = $data['code'];
    
    $c = new \TopClient();
    $c ->appkey = $appkey;
    $c ->secretKey = $secretkey;
    
    $req = new \AlibabaAliqinFcSmsNumSendRequest();
    //公共回传参数，在“消息返回”中会透传回该参数。非必须
    $req ->setExtend("");
    //短信类型，传入值请填写normal
    $req ->setSmsType($type);
    //短信签名，传入的短信签名必须是在阿里大于“管理中心-验证码/短信通知/推广短信-配置短信签名”中的可用签名。
    $req ->setSmsFreeSignName($name);
    //短信模板变量，传参规则{"key":"value"}，key的名字须和申请模板中的变量名一致，多个变量之间以逗号隔开。
    $req ->setSmsParam($param);
    //短信接收号码。支持单个或多个手机号码，传入号码为11位手机号码，不能加0或+86。群发短信需传入多个号码，以英文逗号分隔，一次调用最多传入200个号码。
    $req ->setRecNum($phone);
    //短信模板ID，传入的模板必须是在阿里大于“管理中心-短信模板管理”中的可用模板。
    $req ->setSmsTemplateCode($code);
    //发送
    

    $resp = $c ->execute($req);
}

/**
 * 管理员密码加密方式
 * @param $password  密码
 * @param $password_code 密码额外加密字符
 * @return string
 */
function password($password, $password_code='lshi4AsSUrUOwWV')
{
    return md5(md5($password) . md5($password_code));
}

//随机字符串  
function get_rand_str($len=10){  
    $str = "1234567890asdfghjklqwertyuiopzxcvbnmASDFGHJKLZXCVBNMPOIUYTREWQ";  
    return substr(str_shuffle($str),0,$len);  
} 

/**
 * 替换手机号码中间四位数字
 * @param  [type] $str [description]
 * @return [type]      [description]
 */
function hide_phone($str){
    $resstr = substr_replace($str,'****',3,4);  
    return $resstr;  
}
