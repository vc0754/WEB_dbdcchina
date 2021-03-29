<?php

namespace app\index\controller;

use think\Db;
use \think\Controller;

class News extends Base {
  protected $init_num = 12;   //默认初始化加载/图片列表显示记录数 
  protected $load_num = 12;   //每次异步加载的记录数

  #获取所有顶级分类
  protected function getCates() {
    $cates = db('news_cate')->alias('aa')
      ->where('aa.pid', 0)
      ->order('orders, id')
      ->column('aa.mark, aa.id as cid, aa.name');
    return $cates;
  }

  #新闻列表 news
  public function index() {

    $mark = input('mark', 'all');   #当前分类别名，默认为 全部all
    $cates = $this->getCates();     #获取所有顶级分类
    if ('all' != $mark && empty($cates[$mark])) $this->redirect('index/news/index'); #当前分类不存在，则跳转

    #合并数组，把"全部分类"加到开头处
    $all = ['all' => ['mark' => 'all', 'cid' => 0, 'name' => '全部']];
    $cates = $all + $cates;
    $this->assign('cates', $cates);

    #初始化 显示部分案例
    $lists = [];
    $cid = $cates[$mark]['cid']; //当前分类id

    if ('all' == $mark) { # 全部案例
      $lists = db('news')->alias('aa')
        ->field('aa.id, aa.title, aa.description, aa.create_time, att.filepath as thumb')
        ->join('__NEWS_CATE__ cc', 'cc.id=aa.cid', 'left')
        ->join('__ATTACHMENT__ att', 'att.id=aa.thumb', 'left')
        ->where('aa.status=1')
        ->where('aa.admin_id=1 || aa.admin_id=14')
        ->order('aa.orders, aa.id desc')
        ->limit($this->init_num)
        ->select();
    } else { # 某类案例
      $lists = db('news')->alias('aa')
        ->field('aa.id, aa.title, aa.description, aa.create_time, att.filepath as thumb')
        ->join('__NEWS_CATE__ cc', 'cc.id=aa.cid', 'left')
        ->join('__ATTACHMENT__ att', 'att.id=aa.thumb', 'left')
        ->where('aa.cid', $cid)
        ->where('aa.status=1')
        ->where('aa.admin_id=1 || aa.admin_id=14')
        ->order('aa.orders, aa.id desc')
        ->limit($this->init_num)
      ->select();
    }
    // dump( Db::getLastSql());
    
    #初始化 显示部分
    // $lists = db('news')->alias('aa')
    //   ->field('aa.id, aa.title, aa.description, aa.create_time, att.filepath as thumb')
      
    //   ->join('__PRODUCT_CATE__ cc', 'cc.id=pp.cid')
    //   ->join('__ATTACHMENT__ att', 'att.id=aa.thumb')
    //   ->where('aa.status=1 and aa.thumb>0')
    //   ->order('aa.orders, aa.id desc')
    //   ->limit($this->init_num)
    //   ->select();

    #链接        
    // foreach ($lists as $k => $v) {
    //   if ($v['is_link'] == 0) {
    //     $lists[$k]['class'] = 'view';
    //     $lists[$k]['url'] = url('index/news/detail', ['id' => $v['id']]); //本地url
    //   } else {
    //     $lists[$k]['class'] = 'link';
    //     $lists[$k]['url'] = empty($v['url']) ? '#' : $v['url'];     //外链url
    //   }
    //   $lists[$k]['create_time'] = date('Y-m-d', $lists[$k]['create_time']);
    // }
    
    $this->assign('lists', $lists);
    # dump( Db::getLastSql());dump($lists);exit;

    #页面数据 带banner图
    $result = $this->get_page('news', true);
    $result['cid'] = $cid; //当前分类id
    $result['is_load'] = count($lists) < $this->init_num ? false : true;
    $result['load'] = $this->load_num;
    $this->assign('post', $result);
    
    $this->assign('slides', [
      [
        'name'  => ucfirst($result['mark']),
        'banner' => $result['banner']
      ]
    ]);
    $this->assign('cls', 'news');

    return $this->fetch();
  }

  #异步获取 案例
  public function getlists() {
    if ($this->request->isAjax()) {
      $num = (int)input('num'); // 计数器
      $listRows = $this->load_num; // 每次加载数量
      $firstRow = $this->init_num + $num * $listRows; // 每次异步的开始位置

      $lists = db('news')->alias('aa')
        ->field('aa.id, aa.title, aa.description, aa.create_time, aa.is_link, aa.url, att.filepath as thumb')
        ->join('__ATTACHMENT__ att', 'att.id=aa.thumb')
        ->where('aa.status=1 and aa.thumb>0')
        ->order('aa.orders, aa.id desc')
        ->limit($firstRow . ',' . $listRows)
        ->select();
      #dump( Db::getLastSql());dump($lists);exit;

      foreach ($lists as $k => $v) {
        if ($v['is_link'] == 0) {
          $lists[$k]['class'] = 'view';
          $lists[$k]['url'] = url('index/news/detail', ['id' => $v['id']]); //本地url
        } else {
          $lists[$k]['class'] = 'link';
          $lists[$k]['url'] = empty($v['url']) ? '#' : $v['url'];     //外链url
        }
        $lists[$k]['create_time'] = date('Y-m-d', $lists[$k]['create_time']);
      }

      return json($lists);
    }
  }

  #新闻详情
  public function detail() {
    $id = input('id');
    $detail = db('news')->alias('aa')
      ->field('aa.*, att.filepath as banner')
      ->join('__ATTACHMENT__ att', 'att.id=aa.banner', 'LEFT')
      ->where('aa.id', $id)
      ->where('aa.status=1')
      ->find();
    if (!$detail) $this->redirect(url('index/error/404'));
    
    // dump($detail);
    // die();

    #页面数据
    $result = $this->get_page('news', true);
    $this->assign('post', $result);

    $this->assign('slides', [
      [
          'name'  => '',
          'banner' => $detail['banner']
      ]
    ]);

    #增加阅读量
    Db::name('news')->where('id', $id)->setInc('views');

    //上一篇
    $detail['prev_id'] = db('news')
      ->where("id>" . $id)
      ->where('status=1')
      ->order('id asc')
      ->limit('1')
      ->value('id');
    //下一篇
    $detail['next_id'] = db('news')
      ->where("id<" . $id)
      ->where('status=1')
      ->order('id desc')
      ->limit('1')
      ->value('id');
    $this->assign('detail', $detail);

    $this->assign('cls', 'newsdetail');

    return $this->fetch();
  }
}
