<?php
//index模块公共函数

#获取所有顶级菜单栏目
function get_nav(){
    $nav = db('page')->field('title, en_title, mark, link_url as url')
            ->where('is_menu=1 and status=1 and pid=0')
            ->order('orders, id')
            ->select();
    
    foreach($nav as $k => $v){
        $nav[$k]['url'] = url($v['url']); //生成URL
    }
    
    return $nav;
}

# 获取当前菜单 及其 子菜单
function get_curr_nav($mark){
    $pid = db('page')->where('mark', $mark)->where('is_menu=1 and status=1')->value('id');
    if(empty($pid)) return false;
    
    $nav = db('page')->alias('pp')
        ->where('pp.pid', $pid)
        ->where('pp.status=1')
        ->order('pp.orders, pp.id')
        ->column('pp.mark, pp.title, pp.en_title');
    
    return $nav;
}

# 获取产品的所有分类 （父级带子级）
function get_cates(){
    $rs = db('productCate')->order('orders, id')->column("id, pid, name, mark");
    $cates = [];
    foreach($rs as $k => $v){
        if($v['pid']>0){
            $v['url'] = url('index/product/index');
            $cates[$v['pid']]['son'][] = $v;
        }else{
            $cates[$k] = $v;
        }
    }

    return $cates;
}


/* #获取新闻轮播列表
function get_slide_news($limit=3){
    $slides = db('slide')->alias('ss')
            ->field("ss.nid, att.filepath as banner")
            ->join(' __ATTACHMENT__ att', 'ss.banner>0 and att.id=ss.banner')
            ->order('ss.orders, ss.id desc')
            ->limit($limit)
            ->select();
            
    return $slides;
} */