<?php
namespace app\index\controller;

use think\Db;
use \think\Controller;
#优势
class Advantage extends Base
{   
    # protected $init_num = 10;   //默认初始化加载/图片列表显示记录数 
    # protected $load_num = 10;   //每次异步加载的记录数
    
    #获取所有顶级分类
    protected function getCates()
    {
        $cates = db('news_cate')->alias('aa')
            ->join('__ATTACHMENT__ att', 'att.id = aa.banner', 'left')
            ->where('aa.pid', 0)
            ->order('aa.orders, aa.id')
            ->column('aa.mark, aa.id as cid, aa.name, aa.en_name, att.filepath as banner');
        return $cates;
    }
    
    #优势列表
    public function index()
    {
        $mark = input('mark', 'painting'); //默认为 绘画 painting
        $cates = $this->getCates();     #获取所有顶级分类
        if(empty($cates[$mark])) $this->redirect('index/page/advantage'); //跳转
        $this->assign('cates',$cates); 
        
        #页面数据 带banner图
        $result = $this->get_page($mark, true); 
        $result['cid'] = $cates[$mark]['cid']; //当前分类cid
        $result['mark'] = 'advantage';  //父级别名，菜单高亮
        $result['curr_mark'] = $mark;   //当前别名
        $result['curr_banner'] = $cates[$mark]['banner'];  //当前分类图片
        #dump($result);exit;
        $this->assign('post',$result);

        $lists = db('news')->alias('aa')
            ->field('aa.id, aa.title, aa.num, att.filepath as thumb')
            ->join('__NEWS_CATE__ cc', 'cc.id=aa.cid')
            ->join('__ATTACHMENT__ att', 'att.id=aa.thumb')
            ->where('cc.mark', $mark)
            ->where('aa.status=1 and aa.thumb>0')
            ->order('aa.orders, aa.id desc')
            ->select();
        $this->assign('lists',$lists); 
        # dump( Db::getLastSql()); dump($lists);exit;
        
        $tpl = ''; //模板名称
        if($mark == 'video') $tpl = 'index_video';
        
        return $this->fetch($tpl);
    } 
    
/*    
    #异步获取 
    public function getlists()
    {
        if($this->request->isAjax()){             
            $num = (int)input('num');// 计数器
            $listRows = $this->load_num;// 取9条记录
            $firstRow = $this->init_num + $num*$listRows; // 每次异步的开始位置
            
            $lists = db('news')->alias('aa')
                ->field('aa.id, aa.title, aa.create_time, att.filepath as thumb')
                ->join('__ATTACHMENT__ att', 'att.id=aa.thumb')
                ->where('aa.status=1 and aa.thumb>0')
                ->order('aa.orders, aa.id desc')
                ->limit($firstRow.','.$listRows)
                ->select();
            #dump( Db::getLastSql());dump($lists);exit;
            
            foreach($lists as $k => $v){
                $lists[$k]['url'] = url('index/advantage/detail', ['id'=>$v['id']]);
                $lists[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            }
            
            return json($lists);
        }
    }
 */    
 
    #优势详情
    public function detail(){
        $id = input('id'); 
        $detail = db('news')->alias('aa')
                ->field("aa.title, aa.author as subtitle, aa.description, aa.video_url, att.filepath as banner")
                ->join('__ATTACHMENT__ att', 'aa.banner>0 and att.id=aa.banner', 'left')
                ->where('aa.id',$id)
                ->where('aa.status=1')
                ->find();
        #dump( Db::getLastSql()); dump($detail);exit;
        if(empty($detail)){  $this->redirect(url('index/error/404')); exit; } //跳转到404页面
        $detail['description'] = nl2br($detail['description']); //替换换行符为 <br>
        $this->assign('detail', $detail);
        
        #页面数据
        $result = $this->get_page('advantage'); 
        $this->assign('post', $result);
        
        #增加阅读量
        Db::name('news')->where('id', $id)->setInc('views');
        
        return $this->fetch();
    }
}
