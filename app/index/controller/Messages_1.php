<?php
namespace app\index\controller;

use think\Db;
use \think\Controller;
use app\admin\model\Messages as MessagesModel;
class Messages extends Base
{
    # 留言反馈
    public function send(){
		if(!$this->request->isPost()) {$this->redirect(url('index/error/404')); exit;}
        
        #获取表单提交的数据
        $post = $this->request->post();
        
        #验证码提示 中英文
        $captcha = true == $this->is_en ? 'Please enter the correct captcha' : '验证码错误';
        #验证  唯一规则： 表名，字段名，排除主键值，主键名
        $validate = new \think\Validate([
            ['captcha','captcha',$captcha],
        ]);
        #验证部分数据合法性
        if (!$validate->check($post)) {
            $rt=[];
            $rt['code']  = -1;
            $rt['msg'] = $validate->getError();
            return json($rt);
        }
        
        #整合数据
        $data = [];
		foreach($post as $key => $val){
            if(is_array($val)){
                foreach($val as $k => $v){ //复选框提交是数组
                    $data[$key] = htmlspecialchars(trim($v)).', &nbsp;'.$data[$key];
                }
            }else{
                $data[$key]=htmlspecialchars(trim($val));//将与、单双引号、大于和小于号化成HTML编码格式
            }
		}

		$data['type'] = 'message';//留言类型
		$data['ip']	= request()->ip();	
        
        #发送邮件
        $to = $this->mailbox;	#收件邮箱的地址
		$title = "<长江设计官网> 有用户发提交了留言："; 
		$content = "姓名：".$data['username']." <br/>  
                    电话：".$data['phone']." <br/>
					留言内容：".$data['msg']." <br/> 
					提交时间：".date('Y-m-d H:i:s')." <br/>";
        $mailto = SendMail($to, $title, $content);
        
        if(true == $this->is_en){//英文版
            $ok_msg = 'Congrats ! Your information has been successfully submitted : )'; 
			$bad_msg= 'Oops ! Failed to submit your information. Please try again later : (';
        }else{
            $ok_msg = '提交成功，感谢您的留言！'; 
			$bad_msg= '提交失败！';
        }
        
        #添加数据到数据库
        $model = new MessagesModel();
        
        if(false == $model->allowField(true)->save($data)) {
			$this->error($bad_msg); //状态码 0
        } else {
            $this->success($ok_msg);
        } 
    }

}
