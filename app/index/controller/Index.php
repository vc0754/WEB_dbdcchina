<?php

namespace app\index\controller;

use think\Db;
use \think\Controller;

class Index extends Base {

  #首页
  public function index() {
    #首页轮播大小图 5张
    $slides = db('slide')->alias('ss')
      ->join('__ATTACHMENT__ att', 'ss.banner>0 and att.id=ss.banner', 'left')
      ->join('__ATTACHMENT__ aa', 'ss.thumb>0 and aa.id=ss.thumb', 'left')
      ->where('ss.status=1 and type="home"')
      ->order('ss.orders, ss.id')
      ->limit(5)
      ->column('ss.id, ss.title, att.filepath as banner, aa.filepath as thumb');
    $this->assign('slides', $slides);

    $result = [];
    #推荐产品 6笔
    $products = db('product')->alias('pp')
      ->join('__ATTACHMENT__ att', 'att.id=pp.thumb')
      ->where('pp.is_top=1')
      ->where('pp.status=1')
      ->where('pp.admin_id=1 || pp.admin_id=14')
      ->order('pp.orders, pp.id desc')
      ->limit(30)
      ->column('pp.id, pp.title, pp.description, att.filepath as thumb');

    $result['products'] = array_chunk($products, 5);

    #推荐客户 20笔
    // $result['clients'] = db('links')->alias('ll')
    //   ->join('__ATTACHMENT__ att', 'att.id=ll.thumb')
    //   ->where('ll.is_top=1 and ll.thumb>0')
    //   ->where('ll.status=1 and ll.type="client"')
    //   ->order('ll.orders, ll.id')
    //   ->limit(20)
    //   ->column('att.filepath as logo');

    #优劣首页推荐 
    // $result['advantage'] = db('news_cate')->alias('aa')
    //   ->field('aa.id as cid, aa.home_title, aa.en_home_title, aa.mark, att.filepath as thumb')
    //   ->join('__ATTACHMENT__ att', 'att.id=aa.thumb')
    //   ->where('aa.is_top=1 and aa.thumb>0')
    //   ->order('aa.orders, aa.id')
    //   ->select();
    #dump($result);exit;

    $result['mark'] = 'home'; //首页高亮
    $this->assign('post', $result);

    $this->assign('cls', 'index');
    return $this->fetch();
  }
}
