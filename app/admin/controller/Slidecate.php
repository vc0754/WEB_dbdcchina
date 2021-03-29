<?php
namespace app\admin\controller;

use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\controller\Permissions;
use app\admin\model\SlideCate as cateModel;
class Slidecate extends Permissions
{
    public function index()
    {
        $model = new cateModel();
        $cates = $model->select();
        $this->assign('cates',$cates);
        return $this->fetch();
    }


    public function publish()
    {
    	//获取菜单id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    	$model = new cateModel();
		//是正常添加操作
		if($id > 0) {
    		//是修改操作
    		if($this->request->isPost()) {
    			//是提交操作
    			$post = $this->request->post();
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['name', 'require', '分类名称不能为空']
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
	            //验证菜单是否存在
	            $cate = $model->where('id',$id)->find();
	            if(empty($cate)) {
	            	return $this->error('id不正确');
	            }
	            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
	            	return $this->error('修改失败');
	            } else {
                    addlog($operation_id=$id);//写入日志
	            	return $this->success('修改分类成功','admin/slidecate/index');
	            }
    		} else {
    			//非提交操作
    			$cate = $model->where('id',$id)->find();
    			$cates_all = $model->select();
    			$this->assign('cates',$cates_all);
    			if(!empty($cate)) {
    				$this->assign('cate',$cate);
    				return $this->fetch();
    			} else {
    				return $this->error('id不正确');
    			}
    		}
    	} else {
    		//是新增操作
    		if($this->request->isPost()) {
    			//是提交操作
    			$post = $this->request->post();
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['name', 'require', '分类名称不能为空'],
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
	            if(false == $model->allowField(true)->save($post)) {
	            	return $this->error('添加失败');
	            } else {
                    addlog($model->id);//写入日志
	            	return $this->success('添加成功','admin/slidecate/index');
	            }
    		} else {
    			$cates = $model->select();
    			$this->assign('cates',$cates);
    			return $this->fetch();
    		}
    	}
    	
    }


    public function delete()
    {
    	if($this->request->isAjax()) {
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('slide_cate')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                addlog($id);//写入日志
                return $this->success('删除成功','admin/slidecate/index');
            }
    	}
    }
}
