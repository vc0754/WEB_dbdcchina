<?php

namespace app\index\controller;

use think\Db;
use \think\Controller;

class Product extends Base {
  protected $init_num = 12;   //默认初始化加载/图片列表显示记录数 
  protected $load_num = 12;   //每次异步加载的记录数

  #获取所有顶级分类
  protected function getCates() {
    $cates = db('product_cate')->alias('aa')
      ->where('aa.pid', 0)
      ->order('orders, id')
      ->column('aa.mark, aa.id as cid, aa.name');
    return $cates;
  }

  #案例列表
  public function index() {
    $cid = $this->request->get('cid');
    $this->assign('cid', $cid);
    
    $cates = db('product_cate')->alias('')->where('pid', 0)->order('orders, id')->column('id, name');
    $this->assign('cates', $cates);

    #轮播大小图 5张
    // $slides = db('slide')->alias('ss')
    //   ->join('__ATTACHMENT__ att', 'ss.banner>0 and att.id=ss.banner', 'left')
    //   ->join('__ATTACHMENT__ aa', 'ss.thumb>0 and aa.id=ss.thumb', 'left')
    //   ->where('ss.status=1 and type="pp"')
    //   ->order('ss.orders, ss.id')
    //   ->limit(5)
    //   ->column('ss.id, att.filepath as banner, aa.filepath as thumb');
    // $this->assign('slides', $slides);

    #初始化 显示部分案例
    $lists = [];

    if (!$cid) { # 全部案例
      $lists = db('product')->alias('pp')
        ->field('pp.id, pp.title, pp.description, att.filepath as thumb')
        ->join('__ATTACHMENT__ att', 'att.id=pp.thumb', 'left')
        ->where('pp.status=1')
        ->where('pp.admin_id=1 || pp.admin_id=14')
        ->order('pp.orders, pp.id desc')
        ->limit($this->init_num)
        ->select();
    } else { # 某类案例
      $lists = db('product')->alias('pp')
        ->field('pp.id, pp.title, pp.description, att.filepath as thumb')
        ->join('__PRODUCT_CATE__ cc', 'cc.id=pp.cid')
        ->join('__ATTACHMENT__ att', 'att.id=pp.thumb', 'left')
        ->where('cc.id', $cid)
        ->where('pp.admin_id=1 || pp.admin_id=14')
        ->where('pp.status=1 and pp.thumb>0')
        ->order('pp.orders, pp.id desc')
        ->limit($this->init_num)
        ->select();
    }
    $this->assign('lists', $lists);
    # dump( Db::getLastSql());dump($lists);exit;

    // #页面数据 带banner图
    $result = $this->get_page('case', true);
    // $result['cid'] = $cid; //当前分类id
    $result['is_load'] = count($lists) < $this->init_num ? false : true;
    $result['load'] = $this->load_num;
    $this->assign('post', $result);
    $this->assign('slides', [
      [
          'name'  => ucfirst($result['mark']),
          'banner' => $result['banner']
      ]
  ]);
    
    $this->assign('cls', 'case');

    return $this->fetch();
  }

  #异步获取 案例
  public function getlists() {
    if ($this->request->isAjax()) {
      $cid = input('cid'); //当前分类id
      $num = (int)input('num'); // 计数器
      $listRows = $this->load_num; // 每次加载数量
      $firstRow = $this->init_num + $num * $listRows; // 每次异步的开始位置

      $lists = [];
      if ($cid == 0) {
        //所有案例
        $lists = db('product')->alias('pp')
          ->field('pp.id, pp.title, att.filepath as thumb')
          ->join('__ATTACHMENT__ att', 'att.id=pp.thumb')
          ->where('pp.status=1 and pp.thumb>0')
          ->order('pp.orders, pp.id desc')
          ->limit($firstRow . ',' . $listRows)
          ->select();
      } else {
        //某类案例
        $cate = db('product_cate')->field('id')->where('id', $cid)->find();
        if (!$cate) return false;

        $lists = db('product')->alias('pp')
          ->field('pp.id, pp.title, att.filepath as thumb')
          ->join('__PRODUCT_CATE__ cc', 'cc.id=pp.cid')
          ->join('__ATTACHMENT__ att', 'att.id=pp.thumb')
          ->where('pp.cid', $cid)
          ->where('pp.status=1 and pp.thumb>0')
          ->order('pp.orders, pp.id desc')
          ->limit($firstRow . ',' . $listRows)
          ->select();
      }
      #dump( Db::getLastSql());dump($lists);exit;

      foreach ($lists as $k => $v) {
        $lists[$k]['url'] = url('index/product/detail', ['id' => $v['id']]); //产品详情url
      }

      return json($lists);
    }
  }

  #案例详情
  public function detail() {
    $id = input('id');
    $detail = db('product')->alias('pp')
      ->field('pp.id, pp.title, pp.subtitle, pp.description, pp.content, att.filepath as banner, aa.filepath as m_banner')
      ->join('__ATTACHMENT__ att', 'pp.banner>0 and att.id=pp.banner', 'left')
      ->join('__ATTACHMENT__ aa', 'pp.m_banner>0 and aa.id=pp.m_banner', 'left')
      ->where('pp.id', $id)
      ->where('pp.status=1')
      ->find();
    if (!$detail) $this->redirect(url('index/error/404'));

    $detail['description'] = nl2br($detail['description']); //替换换行符为 <br>
    $detail['content'] = getImgSrc($detail['content']); //正则匹配图片
    $this->assign('detail', $detail);

    #页面数据
    $result = $this->get_page('case');
    $result['banner'] = $detail['banner'];  //PC端Banner
    $result['thumb'] = $detail['m_banner']; //手机端Banner
    $this->assign('post', $result);

    $this->assign('slides', [
      [
          'name'  => '',
          'banner' => $detail['banner']
      ]
    ]);

    #增加阅读量
    Db::name('product')->where('id', $id)->setInc('views');

    return $this->fetch();
  }
}
