<?php
namespace app\admin\controller;

use think\Db;
use \think\Session;
use \think\Controller;
use app\admin\controller\Permissions;
use app\admin\model\NewsCate as CateModel;
class Newscate extends Permissions
{
    //列表
    public function index()
    {
        $model = new cateModel();
        $cate = $model->select();
        
        $cates = treelist($cate);
        $this->assign('cates',$cates);
        
        return $this->fetch();
    }

    //新增
    public function add()
    {        
        $model = new CateModel();
        //是提交操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['name', 'require', '分类名称不能为空'],
                ['pid', 'require', '请选择上级分类'],
                ['mark', 'alphaDash|unique:news_cate', '别名只能为字母和数字及下划线|别名不能重复'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            if(false == $model->allowField(true)->save($post)) {
                return $this->error('添加失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('添加成功', 'admin/newscate/index');
            }
        } else {
            //非提交操作
            $pid = $this->request->has('pid') ? $this->request->param('pid', null, 'intval') : null;
            if(!empty($pid)) {
                $this->assign('pid',$pid); //添加子分类的父级ID
            }
            //只获取当前类别的分类
            $cates = $model->select();
            $cates_tree = treelist($cates); //递归方法 转为 树型结构
            $this->assign('cates',$cates_tree);
            return $this->fetch();
        }
    }
    
    //修改
    public function edit()
    {       
    	//获取菜单id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    	$model = new CateModel();

		if($id > 0) {
    		//是修改操作
    		if($this->request->isPost()) {
    			//是提交操作
    			$post = $this->request->post();
                
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['name', 'require', '分类名称不能为空'],
	                ['pid', 'require', '请选择上级分类'],
                    ['mark', 'alphaDash|unique:news_cate', '别名只能为字母和数字及下划线|别名不能重复'],
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
	            //验证是否存在
	            $cate = $model->where('id',$id)->find();
	            if(empty($cate)) {
	            	return $this->error('id不正确');
	            }
               
	            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
	            	return $this->error('修改失败');
	            } else {
                    addlog($model->id);//写入日志
	            	return $this->success('修改分类成功', 'admin/newscate/index');
	            }
    		} else {
    			//非提交操作
    			$cate = $model->where('id',$id)->find();
    			if(!empty($cate)) {
    				$this->assign('cate',$cate);
                    
                    $where['id'] = ['neq', $id]; 
                    $where['pid'] = ['neq', $id]; 
                    $cates = $model->where($where)->select();
                    #dump( Db::getLastSql());dump($cates);
                    $cates_tree = treelist($cates); //递归方法 转为 树型结构
                    $this->assign('cates',$cates_tree);
                
    				return $this->fetch();
    			} else {
    				return $this->error('id不正确');
    			}
    		}
    	}
    }

    //删除 单笔
    public function delete()
    {
    	if($this->request->isAjax()) {            
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(Db::name('news_cate')->where('pid',$id)->select() == null) {
                if(false == Db::name('news_cate')->where(['id'=>$id])->delete()) {
                    return $this->error('删除失败');
                } else {
                    addlog($id);//写入日志
                    return $this->success('删除成功', 'admin/newscate/index');
                }
            } else {
                return $this->error('该分类下还有子分类，不能删除');
            }
    	}
    }
    
    # 推荐
    public function is_top()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('news_cate')->where('id',$post['id'])->update(['is_top'=>$post['is_top']])) {
                return $this->error('推荐设置失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('推荐设置成功','admin/newscate/index');
            }
        }
    }
    
    //列表 排序
    public function orders()
    {
        if($this->request->isPost()) {
            $post = $this->request->post();
            
            $i = 0;
            foreach ($post['id'] as $k => $val) {
                $order = Db::name('news_cate')->where('id',$val)->value('orders');
                if($order != $post['orders'][$k]) {
                    if(false == Db::name('news_cate')->where('id',$val)->update(['orders'=>$post['orders'][$k]])) {
                        return $this->error('更新失败');
                    } else {
                        $i++;
                    }
                }
            }
            addlog();//写入日志
            return $this->success('成功更新'.$i.'个数据', 'admin/newscate/index');
        }
    }
}
