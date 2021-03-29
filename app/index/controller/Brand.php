<?php

namespace app\index\controller;

use app\admin\model\Admin;
use think\Db;
use \think\Controller;
use app\admin\model\Article as TeamModel;
// use app\admin\model\Company;
use app\admin\model\News;
use app\admin\model\Product;

class Brand extends Base {
  
  public function detail() {
    $id = input('id');

    // 公司信息
    $company = Admin::alias('a')
    ->join('eco_attachment ea', 'ea.id = a.thumb', 'LEFT')
    ->join('eco_attachment ea2', 'ea2.id = a.brandimg', 'LEFT')
    ->field([
      'a.id', 'a.nickname', 'a.name', 'a.url',
      // 'ec.name', 'ec.about', 'ec.contact',
      'ea.filepath AS thumb',
      'ea2.filepath AS brandimg'
    ])->where([
      'a.id' => $id
    ])->find();

    // if ($company['url'] === null) delete $company['url'];
    // $company['url'] = $company['url'] !== null ? trim($company['url']) : '';
    // dump($company);
    $this->assign('company', $company);


    #页面数据 带banner图
    $result = $this->get_page('search', true);
    $result['is_load'] = false;
    $result['page_title']=$company['nickname'];
    $this->assign('post', $result);

    $type = input('type', 'case');
    $this->assign('type', $type);
    $this->assign('slides', []);
    
    // WORKS
    $works = Product::alias('p')->join('eco_attachment ea', 'p.thumb = ea.id', 'LEFT')->field([
      'p.*',
      'ea.filepath AS thumb'
    ])->where([
      'p.admin_id' => $id
    ])->order([
      'orders ASC'
    ])->paginate(9, false, [
      'query'=>$this->request->param()
    ]);
    $this->assign('works', $works);

    // 团队
    $teams = TeamModel::alias('t')->field([
      't.id', 't.title', 't.description', 
      'att.filepath AS thumb'
    ])->join('__ATTACHMENT__ att', 'att.id = t.thumb', 'left')->where([
      't.admin_id' => $id
    ])->order([
      'orders ASC'
    ])->select();
    $this->assign('teams', $teams);

    // HONOR
    $honor = News::alias('n')->join('eco_attachment ea', 'n.thumb = ea.id', 'LEFT')->field([
      'n.*',
      'ea.filepath AS thumb'
    ])->where([
      'n.admin_id' => $id
    ])->select();
    $this->assign('honor', []);
    

    // NEWS
    $news = News::alias('n')->join('eco_attachment ea', 'n.thumb = ea.id', 'LEFT')->field([
      'n.*',
      'ea.filepath AS thumb'
    ])->where([
      'n.admin_id' => $id
    ])->select();
    $this->assign('news', $news);

    // ABOUT
    $page_about = db('admin')->where([
      'id' => $id
    ])->find();
    $this->assign('about', $page_about['content2']);

    // CONTACT
    $page_contact = db('admin')->where([
      'id' => $id
    ])->find();
    $this->assign('contact', $page_contact['content3']);



    $this->assign('detail', [
      'title' => '',
      'author' => '',
      'content' => '',
      'create_time' => '1533548320'
    ]);
    
    //

    $this->assign('total', 10);
    $this->assign('cls', 'branddetail');
    return $this->fetch();
  }

  public function work(){
    $id = input('id');
    $works = Product::alias('p')->join('eco_attachment ea', 'p.thumb = ea.id', 'LEFT')->field([
      'p.*',
      'ea.filepath AS thumb'
    ])->where([
      'p.admin_id' => $id
    ])->order([
      'orders ASC'
    ])->paginate(9, false, [
      'query'=>$this->request->param()
    ]);
    
    return $works;
  }
}
