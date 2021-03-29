<?php
namespace app\index\controller;

use \think\Controller;
use \think\Cookie;
use think\Db;
use think\Request;

# 基础控制器
class Base extends Controller
{
    protected $is_en = false; //是否英文版本
    protected $en = ''; //英文版时可作为相关字段前缀 en_ 
    protected $mailbox = '7799446@qq.com'; //收件邮箱

/*    
    public function __construct()
    {
        #中英文切换
        # $_SESSION['lang'] = empty(input('lang')) ? '' : input('lang');
        $_SESSION["lang"]="cn";
        $this->is_en = $_SESSION['lang'] == 'en' ? true : false;
        $this->en = $this->is_en ? 'en_' : '';
        $path = $this->is_en ? config('template.en_view_path') : config('template.view_path');
        #设置当前主题模板路径
        config('template.view_path', $path); 
        #dump(config('template.view_path'));exit;
        parent::__construct();
    }
 */    
    public function _initialize()
    {
        header("Content-type:text/html;charset=utf-8");
/* 	   
        //Mobile端访问时，自动切换跳转
		if(Request::instance()->isMobile()){
			$this->redirect(url('mb/index/index'));//跳转到手机模块
		}
 */        
        
        #网站配置
		$_options = [];
		$options = Db::name('options')->field('name, value')->select();
		if($options){
			foreach($options as $option){
				$_options[$option['name']] = $option['value'];
			}
		}
        $_options['mailbox'] = trim($_options['mailbox']);
		$this->assign('options', $_options);
        #dump($_options);exit;
        
        #导航菜单栏目
        $nav = get_nav(); 
        $this->assign('nav', $nav);
        # dump($nav);exit;
    }
    
    #获取页面数据（关键词，图片）
    function get_page($mark, $img=false){
        $field = "id, mark, {$this->en}title as page_title, {$this->en}description as description, {$this->en}content as content, imgs, {$this->en}keywords as keywords, pid, thumb, banner";
        
        $result = db('page')->field($field)->where('mark', $mark)->find();
        if(!$result) { $this->redirect(url('index/error/404')); exit; } //跳转到404页面        
/*       
        #页面关键词中将 换行符 斜线/ 中文逗号 顿号 转为 英文逗号 =>（后面 页面管理中已处理）  
        $result['keywords'] = empty($result['keywords']) ? '' : preg_replace("/(，)|(、)|(\/)|(\\n)/", ',' ,$result['keywords']);

*/      
        #子页面关键字不存在，则获取父级的
        if($result['pid'] > 0 && !$result['keywords']){
            $parent = db('page')->field("mark, {$this->en}keywords as keywords")->where('id', $result['pid'])->find();
            $result['keywords'] = $parent['keywords'];  //父级关键词
            //$result['pmark'] = $parent['mark'];       //父级别名
        }
        
        if(true === $img){
            $banner = '';
            
            #获取本身的 banner图片url
            if($result['banner']>0){
                $result['banner'] = db('attachment')->where('id', $result['banner'])->value('filepath');
            }
            
            if($result['thumb']>0){
                $result['thumb'] = db('attachment')->where('id', $result['thumb'])->value('filepath');
            }
            
            #本身不存在图片时，获取父级的
            if($result['pid']>0){
                if(empty($result['banner'])){
                    $result['banner'] = db('page')->alias('pp')
                        ->join('__ATTACHMENT__ att', 'att.id = pp.banner')
                        ->where('pp.id', $result['pid'])
                        ->where('pp.banner>0')
                        ->value('att.filepath');    
                }
                if(empty($result['thumb'])){
                    $result['thumb'] = db('page')->alias('pp')
                        ->join('__ATTACHMENT__ att', 'att.id = pp.thumb')
                        ->where('pp.id', $result['pid'])
                        ->where('pp.thumb>0')
                        ->value('att.filepath');  
                }   
            }
        }
        
        return $result;
    }
        
    # 空操作，用于输出404页面
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
        return $this->fetch('public/404');
        
        /* echo request()->url()."<br/>"; //当前地址
        echo request()->module()."-".request()->controller()."-".request()->action(); //模型、控制器、方法 */
	}
    
}
