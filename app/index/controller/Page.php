<?php

namespace app\index\controller;

use app\admin\model\Admin;
use think\Db;
use \think\Controller;
use app\admin\model\Article as TeamModel;

#单页管理
class Page extends Base {

    public function brand() {
        #页面数据 带banner图
        $result = $this->get_page('brand', true);
        $result['cid'] = input('cid', '0');
        $this->assign('post', $result);
        
        $this->assign('slides', [
            [
                'name'  => ucfirst($result['mark']),
                'banner' => $result['banner']
            ]
        ]);
        
        $where = [
            'admin_cate_id' => 3
        ];

        $city = $this->request->get('city');
        $this->assign('city', $city);
        if ($city) $where['city'] = $city;
        
        $users = Admin::alias('a')
        ->join('eco_attachment ea', 'a.thumb = ea.id', 'left')
        ->join('eco_attachment ea2', 'a.brandimg = ea2.id', 'left')
        ->field([
            'a.id', 'a.nickname', 'a.subname', 'a.city',
            'ea.filepath AS logo',
            'ea2.filepath AS brandimg'
        ])->where($where)->select();

        $cities = Admin::alias('')->where([
            'admin_cate_id' => 3
        ])->column('city');
        $this->assign('cities', array_unique($cities));
        
        $this->assign('users', $users);

        $this->assign('cls', 'brand');
        return $this->fetch();
    }

    public function search(){
        #页面数据 带banner图
        $result = $this->get_page('search', true);
        $this->assign('post', $result);

        $type = input('type', 'case');
        $this->assign('type', $type);

        $keyword = input('keyword', 'get');
        $this->assign('keyword', $keyword);
        
        // 查找个种类数据数量
        $total = [];
        $total['case'] = db('product')->where('title', 'like', '%'.$keyword.'%')->count();
        $total['event'] = db('news')->where('title', 'like', '%'.$keyword.'%')->where('cid', '<>', 4)->count();
        $total['company'] = db('news')->where('title', 'like', '%'.$keyword.'%')->where('cid', '=', 4)->count();

        switch($type) {
            case 'case':
                $res = db('product')->where('title', 'like', '%'.$keyword.'%')->select();
                break;
            case 'event':
                $res = db('news')->where('title', 'like', '%'.$keyword.'%')->where('cid', '<>', 4)->select();
                break;
            case 'company':
                $res = db('news')->where('title', 'like', '%'.$keyword.'%')->where('cid', '=', 4)->select();
                break;
        }

        $this->assign('total', $total);
        $this->assign('totalsum', array_sum($total));
        $this->assign('res', $res);
        $this->assign('cls', 'search');

        return $this->fetch();
    }

    # 青桐中国
    public function about() {
        #页面数据 带banner图
        $result = $this->get_page('about', true);
        $this->assign('post', $result);
        
        $this->assign('slides', [
            [
                'name'  => ucfirst($result['mark']),
                'banner' => $result['banner']
            ]
        ]);

        #合作客户 logo列表
        $clients = db('links')->alias('ll')
            ->join('__ATTACHMENT__ att', 'att.id=ll.thumb')
            ->where('ll.thumb>0')
            ->where('ll.status=1 and ll.type="client"')
            ->order('ll.orders, ll.id')
            ->column('att.filepath as logo');
        $this->assign('clients', $clients);

        // 团队
        $teams = TeamModel::alias('t')->field([
            't.id', 't.title', 't.en_title', 't.description', 
            'att.filepath AS thumb'
        ])->join('__ATTACHMENT__ att', 'att.id = t.thumb', 'left')
        ->where('t.admin_id=1 || t.admin_id=14')->select();
        $this->assign('teams', $teams);

        $this->assign('cls', 'about');

        return $this->fetch();
    }

    # 联系
    public function contact() {
        #页面数据 带banner图
        $result = $this->get_page('contact', true);
        $this->assign('post', $result);
        
        $this->assign('slides', [
            [
                'name'  => ucfirst($result['mark']),
                'banner' => $result['banner']
            ]
        ]);

        $this->assign('cls', 'contact');

        return $this->fetch();
    }
}
