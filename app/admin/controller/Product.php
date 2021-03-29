<?php
namespace app\admin\controller;

use think\Db;
use \think\Session;
use \think\Controller;
use app\admin\controller\Permissions;
use app\admin\model\Product as ProductModel;
use app\admin\model\ProductCate as CateModel;
class Product extends Permissions
{
    public function index()
    {       
        $post = $this->request->param();
        $this->assign('post',$post);
        
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['code|title'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['cid']) and $post['cid'] > 0) {
            $where['cid'] = $post['cid'];
        }
        if (isset($post['status']) and ($post['status'] == 1 or $post['status'] === '0')) {
            $where['aa.status'] = $post['status'];
        }
        if (isset($post['is_top']) and ($post['is_top'] == 1 or $post['is_top'] === '0')) {
            $where['is_top'] = $post['is_top'];
        } 

        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['aa.create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        
        $where = empty($where) ? [] : $where;  //条件
        
        $admin_id = Session::get('admin');
        if ($admin_id !== 1 && $admin_id !== 14) {
            $where['aa.admin_id'] = $admin_id;
        }

        $model = new ProductModel();
        $lists = $model->alias('aa')
                ->field('aa.*, c.name as catename, a.nickname, att.filepath as thumb')
                ->join('__PRODUCT_CATE__ c', 'c.id=aa.cid', 'left')
                ->join('__ADMIN__ a', 'a.id=aa.admin_id', 'left')
                ->join('__ATTACHMENT__ att', 'att.id=aa.thumb', 'left')
                ->where($where)
                ->order('aa.orders, aa.id desc')
                ->paginate(20,false,['query'=>$this->request->param()]);
        // dump( Db::getLastSql());
        $this->assign('lists',$lists);
        
        $cateModel = new CateModel();
        $cates = $cateModel->field('id,name,pid')->order('orders, id')->select();
    	$info['cate'] = treelist($cates); //递归方法 转为 树型结构
        $this->assign('info',$info);
        
        return $this->fetch();
    }
    
    # 新增
    public function add()
    {
        $model = new ProductModel();
        $cateModel = new CateModel();
        
        //是新增 提交操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['title', 'require', '标题不能为空'],
                ['cid', 'require', '请选择分类'],
                ['orders', 'number', '排序必须为数字'],
                ['thumb', 'require', '请上传列表图'],
            //  ['content', 'require', '文章内容不能为空'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            
            //发布时间 日期转时间戳
            $post['create_time'] = strtotime($post['create_time']);
            //设置操作人
            $post['admin_id'] = Session::get('admin');
            //设置不推荐
            if(!isset($post['is_top'])) $post['is_top']=0;
            
            if(false == $model->allowField(true)->save($post)) {
                return $this->error('添加失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('添加成功','admin/product/index');
            }
        } else {
            //非提交操作
            $cates = $cateModel->field('id,name,pid')->order('orders, id')->select();
            $cates_tree = treelist($cates); //递归方法 转为 树型结构
            $this->assign('cates',$cates_tree);
            return $this->fetch();
        }        
    }
    
    # 修改
    public function edit()
    {
    	//获取菜单id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        
        $model = new ProductModel();
        $cateModel = new CateModel();
        
        if($this->request->isPost()) {
            $post = $this->request->post();
            
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['title', 'require', '标题不能为空'],
                ['cid', 'require', '请选择分类'],
                ['orders', 'number', '排序必须为数字'],
                //['thumb', 'require', '请上传列表图'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            //验证菜单是否存在
            $result = $model->where('id',$id)->find();
            if(empty($result)) {
                return $this->error('id不正确');
            }

            //日期转时间戳
            $post['create_time'] = strtotime($post['create_time']);
            //设置修改人
            $post['admin_id'] = Session::get('admin');
            
            //设置不推荐
            if(!isset($post['is_top'])) $post['is_top']=0;
            
            #更新时，删除ueditor编辑器中被废弃的图片
            if(isset($post['content']) && !empty($post['content'])) delete_imgs($result['content'], $post['content'], true);
            
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
                return $this->success('修改成功','admin/product/index');
            }
        } else {
            //非提交操作
            $result = $model->where('id',$id)->find();
            if(!empty($result)){
                $this->assign('result',$result);
                
                $cates = $cateModel->field('id,name,pid')->order('orders, id')->select();
                $cates_tree = treelist($cates); //递归方法 转为 树型结构
                $this->assign('cates',$cates_tree);
                return $this->fetch();
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
            
            //获取关联缩略图片和banner图片的id 及 图片路径
            $result = Db::name('product')
                    ->field('id, content, thumb, banner')
                    ->where('id',$id)
                    ->find();
            if(!$result) return $this->error('当前记录已不存在，删除失败');
            #dump( Db::getLastSql()); dump($result);exit;
            
            //删除ueditor编辑器中被废弃的图片
            if(!empty($result['content'])) delete_imgs($result['content']);
            
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
            
            //删除记录
            if(false == Db::name('product')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                addlog($id);//写入日志
                return $this->success('删除成功','admin/product/index');
            }
    	}
    }

    # 推荐
    public function is_top()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('product')->where('id',$post['id'])->update(['is_top'=>$post['is_top']])) {
                return $this->error('推荐设置失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('推荐设置成功','admin/product/index');
            }
        }
    }

    # 状态切换
    public function status()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('product')->where('id',$post['id'])->update(['status'=>$post['status']])) {
                return $this->error('状态切换失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('状态切换成功','admin/product/index');
            }
        }
    }
    
    # 列表 批量排序
    public function orders()
    {
        if($this->request->isPost()) {
            $post = $this->request->post();
            
            $i = 0;
            foreach ($post['orders'] as $k => $val) {
                $order = Db::name('product')->where('id',$k)->value('orders');
                if($order != $val) {
                    if(false == Db::name('product')->where('id',$k)->update(['orders'=>$val])) {
                        return $this->error('更新失败');
                    } else {
                        $i++;
                    }
                }
            }
            addlog();//写入日志
            return $this->success('成功更新'.$i.'个数据','admin/product/index');
        }
    }
    
    # 列表 批量删除
    public function deleteAll()
    {
        if($this->request->isAjax()) {           
            $post = $this->request->post();
            $data = $post['ids'];
            
            //获取图片id 及 图片路径                
            $result = Db::name('product')->alias('pp')
                    ->field('id, content, thumb, banner')
                    ->where('id','in',$data)
                    ->select();
            if(!$result) return $this->error('记录已不存在，删除失败');
            # dump( Db::getLastSql()); dump($result);exit;
            
            $image_ids = []; //存放图片所在的附件表ID
            foreach($result as $k => $v){
                //删除ueditor编辑器中被废弃的图片
                if(!empty($v['content'])) delete_imgs($v['content']);
                //if(!empty($v['en_content'])) delete_imgs($v['en_content']);
                
                //附件文件存在时，先删除文件，并记录对应的附件表中的id
                if($v['thumb']>0){
                    $thumb_url = Db::name('attachment')->where('id', $v['thumb'])->value('filepath');
                    if(!empty($thumb_url) && file_exists(ROOT_PATH . 'public' . $thumb_url)) {
                        unlink(ROOT_PATH . 'public' . $thumb_url);
                    }
                    $image_ids[] = $v['thumb']; //所有图片ID
                }
                if($v['banner']>0){
                    $banner_url = Db::name('attachment')->where('id', $v['banner'])->value('filepath');
                    if(!empty($banner_url) && file_exists(ROOT_PATH . 'public' . $banner_url)) {
                        unlink(ROOT_PATH . 'public' . $banner_url);
                    }
                    $image_ids[] = $v['banner']; //所有图片ID
                }  
            }
            
            //删除附件表图片记录
            if(!empty($image_ids)) Db::name('attachment')->delete($image_ids);  
            
            //批量删除的 条数
            $rows = Db::name('product')->delete($data);
            #dump( Db::getLastSql());
            $num = count($data) - $rows; //提交的条数与实现删除的条数
            if(0 == $num) {
                addlog(implode(",",$data));//写入日志
                return $this->success('成功删除'.$rows.'笔数据','admin/product/index');
            } else {
                return $this->error('有'.$num.'笔记录删除失败');
            } 
    	}
    }
}
