<?php
namespace app\index\controller;

use think\Db;
use \think\Cookie;
use \think\Controller;
use app\admin\model\Member as Model;
#用户注册 登录 退出 忘记密码
class Common extends Base
{   
    #阿里云发送短信验证码
    public function sendsms()
    {
        if($this->request->isAjax()){
            $mobile = input('phone'); 
            $type = input('type'); //模板类型
            if(!$mobile) return false;
            if($type!=='register' and $type!=='updatepsd') return false;
            
            $code = mt_rand(10000, 99999);  
            $result = sendMsg($mobile, $code, $type);  
            if ($result['Code'] == 'OK') {  
                //存到缓存当中,并且返回json数据给前端  300s=5分钟
                cache('tel' . $mobile, $code, 300);  
                return json(['success' => 'ok', 'tel' => $mobile]);  
            }else{
                return json(['success' => 'error', 'tel' => $mobile]);  
            }
        }
    }
    
    #用户注册
    public function register()
    {
        //提交注册
        if($this->request->isPost()){
            $post = $this->request->param();
            $rt = [];
            
            //检测手机是否已被注册过
            $has = db('member')->where('phone', $post['phone'])->value('id');
            if(!empty($has)) {
                $rt['code'] = -1;
                $rt['msg'] = '该手机号码已被注册了！';
                return json($rt); #返回json数据   
            }
            
            //检测手机短信验证码是否正确
            $code = cache('tel'.$post['phone']);
            if(!$code){
                $rt['code'] = -2;
                $rt['msg'] = '验证码已过期！';
                return json($rt); #返回json数据 
            }elseif((int)$post['code'] !== $code){
                $rt['code'] = -2;
                $rt['msg'] = '验证码不正确！';
                return json($rt); #返回json数据 
            }
            
            //密码加密
            $post['password'] = password($post['password']); 
            //cookie 随机码加密
            $cookie_token = get_rand_str(); //随机码
            $post['cookie_token'] = password($post['phone'].$cookie_token);
            
            $model = new Model();
            if(false == $model->allowField(true)->save($post)) {
                $this->error('注册失败，请稍候再操作！');
            }else{                
                //记录用户登录信息 cmf
                cookie('sxyb_uid', $model->id); //有效期 0
                cookie('sxyb_phone', $post['phone']); //手机账号
                cookie('sxyb_token', $post['cookie_token']);  //随机码加密

                $this->success('恭喜您，注册成功！', 'index/member/index'); //注册成功，跳转至个人信息界面
            }
        }else{            
            #页面数据
            $result = $this->get_page('register', true); 
            $this->assign('post',$result);
            
            return $this->fetch();
        } 
    }

    #会员登录
	public function login()
    {
        #是否已登陆
        if(true === $this->is_login){ //已登陆
            $this->redirect(url('index/member/index')); //已登陆跳转至会员中心  
        }else{
            //提交登陆
            if($this->request->isAjax()){
                $post = $this->request->param();
                
                //手机号 是否已注册
                $result = db('member')->where('phone', $post['phone'])->find();
                if(!$result) $this->error('账户不存在！');
                
                if($result['password'] != password($post['password'])){
                    return $this->error('账户密码错误');
                }

                //cookie 随机码加密
                $cookie_token = get_rand_str(); //随机码
                $cookie_token = password($post['phone'].$cookie_token);//手机号+随机码 加密
                
                //更新数据库及cookie中的 随机码
                if(false === Db::name('member')->where('id', $result['id'])->update(['cookie_token'=>$cookie_token])){
                    return $this->error('登陆失败，请稍候再试！');
                }else{
                    //记录用户登录信息
                    $time = empty($post['remember']) ? 0 : 3600*24*7; //浏览器关闭则失效 或 7天有效期
                    cookie('sxyb_uid', $result['id'], $time);  //7天有效期
                    cookie('sxyb_phone', $result['phone'], $time);
                    cookie('sxyb_token', $cookie_token, $time); //cookie 随机码加密  
                }
                
                $this->success('登陆成功', url('index/member/index')); //已登陆跳转至用户中心
            }  
        }
    }
    
    #用户登出
    public function logout()
    {
        cookie('sxyb_uid', null);
        cookie('sxyb_phone', null);
    	cookie('sxyb_token', null);
        
        $this->redirect(url('/')); //退出跳转至首页（无单独的登陆页面）
    }
    
    #用户 忘记密码
    public function forget()
    {
        if($this->request->isAjax()){ 
            $post = $this->request->post();
            
            #验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['captcha','captcha','验证码不正确'],
            ]);
            #验证部分数据合法性
            if (!$validate->check($post)) {
                $rt['code']  = -3; //状态码，错误为-3
                $rt['msg'] = $validate->getError();
                return json($rt);
            }
            
            #用户是否存在
            $id = db('member')->where('phone', $post['phone'])->where('status',1)->value('id');
            if($id>0){
                #短信验证码 检测是否正确
                $code = cache('tel'.$post['phone']);
                if(!$code){
                    $rt['code'] = -2;
                    $rt['msg'] = '短信验证码不在在或已过期！';
                    return json($rt); #返回json数据 
                }elseif((int)$post['code'] !== $code){
                    $rt['code'] = -2;
                    $rt['msg'] = '验证码不正确！';
                    return json($rt); #返回json数据 
                }
                
                $post['password'] =  password($post['password']); //加密
                
                $model = new Model();
                if(false == $model->allowField(true)->save($post, ['id'=>$id])) {
                    return $this->error('操作失败，请稍候再试！');
                }else{
                    return $this->success('密码重置成功，您现在可以登陆了！', 'index/index/index');
                }
            }else{
                $rt['code'] = -1;
                $rt['msg'] = '账户不存在或已被禁用！';
                return json($rt); #返回json数据 
            }
        }
    }
    
    
}
