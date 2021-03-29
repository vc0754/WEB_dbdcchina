<?php
namespace app\admin\controller;

use think\Db;
use \think\Session;
use \think\Controller;
use app\admin\controller\Permissions;
use app\admin\model\Page as pageModel;

class Page extends Permissions
{
    # 单页 列表
    public function index()
    {
        $post = $this->request->param();
        $this->assign('post',$post);
        
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['pp.title'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['pid']) and $post['pid'] > 0) {
            $where['pp.pid'] = $post['pid'];
        }
        #菜单类型 0不是菜单，1顶部菜单，2底部菜单
        if (isset($post['is_menu']) and ($post['is_menu'] == 2 or $post['is_menu'] == 1 or $post['is_menu'] === '0')) {
            $where['pp.is_menu'] = $post['is_menu'];
        }
        if (isset($post['status']) and ($post['status'] == 1 or $post['status'] === '0')) {
            $where['pp.status'] = $post['status'];
        }
 
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['pp.create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        
        $where = empty($where) ? '' : $where;//条件
        
        $model = new pageModel();
        $lists = $model->alias('pp')
                ->field('pp.*, p.title as p_title, a.nickname')
                ->join('__ADMIN__ a', 'a.id=pp.admin_id')
                ->join('__PAGE__ p', 'p.id=pp.pid and p.id>0', 'left')
                ->where($where)
                ->order('pp.id')
                ->paginate(20,false,['query'=>$this->request->param()]);
        $this->assign('lists',$lists);
        #dump( Db::getLastSql()); dump($lists);
        
        $parents = $model->field('id,title,pid')->select();//父级列表
        $info['parents'] = treelist($parents); //递归方法 转为 树型结构
        $this->assign('info',$info);
        return $this->fetch();
    }
    
    # 页面新增
    public function add()
    {
        $model = new pageModel();
        
        //是新增 提交操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['title', 'require', '标题不能为空'],
                ['mark', 'require|alphaDash|unique:page', '别名不能为空|别名只能为字母和数字及下划线|别名不能重复'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            //设置操作人
            $post['admin_id'] = Session::get('admin');
            //设置不是菜单
            if(!isset($post['is_menu'])) $post['is_menu']=0;
            
            //页面关键词：将 换行符 斜线/ 中文逗号 顿号 转为 英文逗号 
            if(!empty($post['keywords'])) $post['keywords'] = preg_replace("/(，)|(、)|(\/)|(\r\n)/", ',' ,$post['keywords']);
            
            if(false == $model->allowField(true)->save($post)) {
                return $this->error('添加失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('添加成功','admin/page/index');
            }
        } else {
            //非提交操作
            $parents = $model->field('id,title,pid')->order('orders, id')->select();//父级列表
            $parentlists  = treelist($parents); //递归方法 转为 树型结构
            $this->assign('parentlists',$parentlists);
            return $this->fetch();
        }
    }

    # 页面修改
    public function edit()
    {
        //获取id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        //修改操作
        if($id <= 0) return $this->error('id不正确');
        
    	$model = new pageModel();

		//是修改 提交操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['title', 'require', '标题不能为空'],
                //['mark', 'require|alphaDash|unique:page', '别名不能为空|别名只能为字母和数字及下划线|别名不能重复'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            //验证是否存在
            $result = $model->where('id',$id)->find();
            if(empty($result)) {
                return $this->error('id不正确');
            }
            //设置最后修改人
            $post['admin_id'] = Session::get('admin');
            //设置不是菜单
            if(!isset($post['is_menu'])) $post['is_menu']=0;
            
            //页面关键词：将 换行符 斜线/ 中文逗号 顿号 转为 英文逗号 
            if(!empty($post['keywords'])) $post['keywords'] = preg_replace("/(，)|(、)|(\/)|(\r\n)/", ',' ,$post['keywords']);
            
            #更新时，删除ueditor编辑器中被废弃的图片
            if(isset($post['content']) && !empty($result['content'])) delete_imgs($result['content'], $post['content'], true);
            if(isset($post['en_content']) && !empty($result['en_content']))delete_imgs($result['en_content'], $post['en_content'], true);
            
            //重新上传图片时，删除原来关联的图片
            if(isset($post['thumb']) && $post['thumb']>0 && $result['thumb']>0){
                $thumb_url = Db::name('attachment')->where('id', $result['thumb'])->value('filepath');
                if(!empty($thumb_url) && file_exists(ROOT_PATH . 'public' . $thumb_url)) {
                    if(unlink(ROOT_PATH . 'public' . $thumb_url)) {
                        Db::name('attachment')->where('id', $result['thumb'])->delete();
                    }
                }
            }
            if(isset($post['banner']) && $post['banner']>0 && $result['banner']>0){
                $banner_url = Db::name('attachment')->where('id', $result['banner'])->value('filepath');
                if(!empty($banner_url) && file_exists(ROOT_PATH . 'public' . $banner_url)) {
                    if(unlink(ROOT_PATH . 'public' . $banner_url)) {
                        Db::name('attachment')->where('id', $result['banner'])->delete();
                    }
                }
            }
            
            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                return $this->error('修改失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('修改成功','admin/page/index');
            }
        } else {
            //非提交操作
            $result = $model->where('id',$id)->find();
            if(!empty($result)) {
                $this->assign('result',$result);
                
                
                $template = ''; //模板名称
                # 首页推荐 材料与工艺
                if(18 == $result['pid'] OR 21 == $result['pid']){
                    $template = 'top_edit';
                    //父级分类
                    $parentlists[] = db('page')->field('id, title')->where('id', $result['pid'])->find();
                    
                }else{
                    //父级列表
                    $parents = db('page')->where('id','NEQ',$id)->field('id,title,pid')->order('orders, id')->select();
                    $parentlists  = treelist($parents); //递归方法 转为 树型结构 
                } 
                $this->assign('parentlists',$parentlists);
                
                return $this->fetch($template);
            } else {
                return $this->error('id不正确');
            }
        }
    }

    # 单笔删除
    public function delete()
    {
    	if($this->request->isAjax()) {
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            # 存在子页面，则不允许删除
            if(0 < Db::name('page')->where('pid',$id)->count()){
                return $this->error('删除失败：当前页面存在子页面');
            } 
            
            //获取相关数据
            $result = Db::name('page')
                    ->field('id, content, en_content, thumb, banner')
                    ->where('id',$id)
                    ->find();
            if(!$result) return $this->error('当前记录已不存在，删除失败');
            #dump( Db::getLastSql()); dump($result);exit;
            
            //删除ueditor编辑器中被废弃的图片
            delete_imgs($result['content']);
            delete_imgs($result['en_content']);
            
            //删除关联的图片文件 及 附件表图片记录
            if($result['thumb']>0){
                $thumb_url = Db::name('attachment')->where('id', $result['thumb'])->value('filepath');
                if(!empty($thumb_url) && file_exists(ROOT_PATH . 'public' . $thumb_url)) {
                    if(unlink(ROOT_PATH . 'public' . $thumb_url)) {
                        Db::name('attachment')->where('id', $result['thumb'])->delete();
                    }
                }
            }
            if($result['banner']>0){
                $banner_url = Db::name('attachment')->where('id', $result['banner'])->value('filepath');
                if(!empty($banner_url) && file_exists(ROOT_PATH . 'public' . $banner_url)) {
                    if(unlink(ROOT_PATH . 'public' . $banner_url)) {
                        Db::name('attachment')->where('id', $result['banner'])->delete();
                    }
                }
            }
             
            if(false == Db::name('page')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                addlog($id);//写入日志
                return $this->success('删除成功','admin/page/index');
            }
    	}
    }

    # 列表 设置是否菜单
    public function is_menu()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('page')->where('id',$post['id'])->update(['is_menu'=>$post['is_menu']])) {
                return $this->error('设置失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('设置成功','admin/page/index');
            }
        }
    }
    
    # 列表 发布状态切换
    public function status()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('page')->where('id',$post['id'])->update(['status'=>$post['status']])) {
                # dump( Db::table('page')->getLastSql());
                return $this->error('设置失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('设置成功','admin/page/index');
            }
        }
    }
}
