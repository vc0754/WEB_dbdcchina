<?php
namespace app\admin\controller;

use think\Db;
use \think\Session;
use \think\Controller;
use app\admin\controller\Permissions;
use app\admin\model\ProductCate as CateModel;
class Productcate extends Permissions
{
    //列表
    public function index()
    {
        $post = $this->request->param();
        $this->assign('post',$post);
        
        $model = new CateModel();
        if(empty($post)){
            #不带搜索时
            $cates = $model->alias('aa')
                ->field('aa.*, a.nickname')
                ->join('__ADMIN__ a', 'a.id=aa.admin_id')
                ->order('aa.orders, aa.id')->select();
            
            #递归 所有分类
            $cates_tree = treelist($cates, 0); //默认显示    
        }else{
            #带搜索条件
            if (isset($post['cid']) and $post['cid'] > 0) {
                $where['pid'] = $post['cid']; //父级分类
            } 
            if (isset($post['keywords']) and !empty($post['keywords'])) {
                $where['aa.name'] = ['like', '%' . $post['keywords'] . '%'];
            }  
            
            $cates_tree = $model->alias('aa')
                ->field('aa.*, a.nickname')
                ->join('__ADMIN__ a', 'a.id=aa.admin_id')
                ->where($where)
                ->order('aa.orders, aa.id')->select();
        }
        $this->assign('cates',$cates_tree);
        
        #分类（只支持二级）
        $info['cate'] = $model->where('pid', 0)->select();
        $this->assign('info',$info);
        
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
                ['mark', 'alphaDash|unique:product_cate', '别名只能为字母和数字及下划线|别名不能重复'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            //设置操作人
            $post['admin_id'] = Session::get('admin');
            
            if(false == $model->allowField(true)->save($post)) {
                return $this->error('添加失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('添加成功', 'admin/productcate/index');
            }
        } else {
            //非提交操作
            $pid = $this->request->has('pid') ? $this->request->param('pid', null, 'intval') : null;
            if(!empty($pid)) {
                $this->assign('pid',$pid); //添加子分类的父级ID
            }
        
            //上级分类只能为顶级分类 （即只支持二级分类）
            $cates_tree = $model->where('pid', 0)->select();
            $this->assign('cates',$cates_tree);
            return $this->fetch();
        }
    }
    
    //修改
    public function edit()
    {
    	//获取菜单id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        
        //实例化
    	$model = new CateModel();
        
        //验证是否存在
        $cate = $model->where('id',$id)->find();
        if($id<=0 or empty($cate)) {
            return $this->error('id不正确');
        }
        
        //是修改操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['name', 'require', '分类名称不能为空'],
                ['pid', 'require', '请选择上级分类'],
                ['mark', 'alphaDash|unique:product_cate', '别名只能为字母和数字及下划线|别名不能重复'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            
            //设置操作人
            $post['admin_id'] = Session::get('admin');
            
            #更新时，删除ueditor编辑器中被废弃的图片
            if(!empty($cate['content'])) delete_imgs($cate['content'], $post['content'], true);
            if(!empty($cate['en_content'])) delete_imgs($cate['en_content'], $post['en_content'], true);
            
            //重新上传图片时，删除原来关联的图片
            if(isset($post['thumb']) && $post['thumb']>0 && $cate['thumb']>0){
                $thumb_url = Db::name('attachment')->where('id', $cate['thumb'])->value('filepath');
                if(!empty($thumb_url) && file_exists(ROOT_PATH . 'public' . $thumb_url)) {
                    if(unlink(ROOT_PATH . 'public' . $thumb_url)) {
                        Db::name('attachment')->where('id', $cate['thumb'])->delete();
                    }
                }
            }
            if(isset($post['banner']) && $post['banner']>0 && $cate['banner']>0){
                $banner_url = Db::name('attachment')->where('id', $cate['banner'])->value('filepath');
                if(!empty($banner_url) && file_exists(ROOT_PATH . 'public' . $banner_url)) {
                    if(unlink(ROOT_PATH . 'public' . $banner_url)) {
                        Db::name('attachment')->where('id', $cate['banner'])->delete();
                    }
                }
            }
/*             
            if(isset($post['thumb_home']) && $post['thumb_home']>0 && $cate['thumb_home']>0){
                $thumb_home_url = Db::name('attachment')->where('id', $cate['thumb_home'])->value('filepath');
                if(!empty($thumb_home_url) && file_exists(ROOT_PATH . 'public' . $thumb_home_url)) {
                    if(unlink(ROOT_PATH . 'public' . $thumb_home_url)) {
                        Db::name('attachment')->where('id', $cate['thumb_home'])->delete();
                    }
                }
            }
            if(isset($post['banner_home']) && $post['banner_home']>0 && $cate['banner_home']>0){
                $banner_home_url = Db::name('attachment')->where('id', $cate['banner_home'])->value('filepath');
                if(!empty($banner_home_url) && file_exists(ROOT_PATH . 'public' . $banner_home_url)) {
                    if(unlink(ROOT_PATH . 'public' . $banner_home_url)) {
                        Db::name('attachment')->where('id', $cate['banner_home'])->delete();
                    }
                }
            }
  */           
            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                return $this->error('修改失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('修改分类成功', 'admin/productcate/index');
            }
        } else {
            //非提交操作
            $cate = $model->where('id',$id)->find();
            if(!empty($cate)) {
                $this->assign('cate',$cate); 
/*                 
                //修改时上级分类 不能 为自身及其子分类
                $where['id'] = ['neq', $id]; 
                $where['pid'] = ['neq', $id]; 
                $cates = $model->where($where)->select();
                # dump( Db::getLastSql());dump($cates);exit;
                $cates_tree = treelist($cates); //递归方法 转为 树型结构 */
                
                //上级分类只能为顶级分类 （即只支持二级分类）
                $cates_tree = $model->where('pid', 0)->select();
                $this->assign('cates',$cates_tree);
                return $this->fetch();
            } else {
                return $this->error('id不正确');
            }
        }

    }

    //删除 单笔
    public function delete()
    {
    	if($this->request->isAjax()) {            
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            
            //获取关联缩略图片的id 及 图片路径
            $result = Db::name('product_cate')
                    ->field('id, content, en_content, thumb, banner, thumb_home, banner_home')
                    ->where('id',$id)
                    ->find();
            if(!$result) return $this->error('当前记录已不存在，删除失败');
            #dump( Db::getLastSql()); dump($result);exit;
            
            //是否存在子类
            if(Db::name('product_cate')->where('pid',$id)->select() == null) {

                //删除ueditor编辑器中被废弃的图片
                if(!empty($result['content'])) delete_imgs($result['content']);
                if(!empty($result['content'])) delete_imgs($result['en_content']);
                
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
/*                 
                if($result['thumb_home']>0){
                    $thumb_home_url = Db::name('attachment')->where('id', $result['thumb_home'])->value('filepath');
                    if(!empty($thumb_home_url) && file_exists(ROOT_PATH . 'public' . $thumb_home_url)) {
                        if(unlink(ROOT_PATH . 'public' . $thumb_home_url)) {
                            Db::name('attachment')->where('id', $result['thumb_home'])->delete();
                        }
                    }
                }
                if($result['banner_home']>0){
                    $banner_home_url = Db::name('attachment')->where('id', $result['banner_home'])->value('filepath');
                    if(!empty($banner_home_url) && file_exists(ROOT_PATH . 'public' . $banner_home_url)) {
                        if(unlink(ROOT_PATH . 'public' . $banner_home_url)) {
                            Db::name('attachment')->where('id', $result['banner_home'])->delete();
                        }
                    }
                }
 */                
                if(false == Db::name('product_cate')->where('id',$id)->delete()) {
                    return $this->error('删除失败');
                } else {
                    addlog($id);//写入日志
                    return $this->success('删除成功','admin/productcate/index');
                }
            } else {
                return $this->error('该分类下还有子分类，不能删除');
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
                $order = Db::name('product_cate')->where('id',$val)->value('orders');
                if($order != $post['orders'][$k]) {
                    if(false == Db::name('product_cate')->where('id',$val)->update(['orders'=>$post['orders'][$k]])) {
                        return $this->error('更新失败');
                    } else {
                        $i++;
                    }
                }
            }
            addlog();//写入日志
            return $this->success('成功更新'.$i.'个数据', 'admin/productcate/index');
        }
    }
}
