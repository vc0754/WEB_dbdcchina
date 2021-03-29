<?php
namespace app\admin\controller;

use think\Db;
use \think\Session;
use \think\Controller;
use app\admin\controller\Permissions;
use app\admin\model\Links as LinksModel;
use app\admin\model\LinksCate as CateModel;

class Links extends Permissions
{   
    # 列表
    public function index()
    {       
        $type = input('type'); //url中的 分类类型
        $this->assign('type',$type);
        
        $post = $this->request->param();
        $this->assign('post',$post);
        
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['kk.title'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['is_top']) and ($post['is_top'] == 1 or $post['is_top'] === '0')) {
            $where['is_top'] = $post['is_top'];
        } 
/*      
        if (isset($post['cid']) and $post['cid'] > 0) {
            $where['cid'] = $post['cid'];
        } 
*/
        if (isset($post['status']) and ($post['status'] == 1 or $post['status'] === '0')) {
            $where['kk.status'] = $post['status'];
        }

        $where = empty($where) ? [] : $where; //条件
       
        $model = new LinksModel();
        /* #关联分类表
        $lists = $model->alias('kk')
                ->field('kk.*, c.name as catename, a.nickname, att.filepath as thumb')
                ->join('__LINKS_CATE__ c', "c.id=kk.cid and c.type='$type'")
                ->join('__ADMIN__ a', 'a.id=kk.admin_id')
                ->join('__ATTACHMENT__ att', 'att.id=kk.thumb', 'left')
                ->where($where)
                ->order('kk.orders, kk.id desc')
                ->paginate(20,false,['query'=>$this->request->param()]);
        */
        
        #不关联分类表
        $lists = $model->alias('kk')
                ->field('kk.*, a.nickname, att.filepath as thumb')
                ->join('__ADMIN__ a', 'a.id=kk.admin_id')
                ->join('__ATTACHMENT__ att', 'att.id=kk.thumb', 'left')
                ->where('type', $type)
                ->where($where)
                ->order('kk.orders, kk.id desc')
                ->paginate(20,false,['query'=>$this->request->param()]);
        # dump( Db::getLastSql());
        $this->assign('lists', $lists);
        
        $cateModel = new CateModel();
        $cates = $cateModel->field('id,name,pid')->where('type',$type)->order('orders, id')->select();
    	$info['cate'] = treelist($cates); //递归方法 转为 树型结构
        $this->assign('info',$info);

        return $this->fetch('index_'.$type);
    }

    # 新增
    public function add()
    {
        $model = new LinksModel();
        $cateModel = new CateModel();
        
        $type = input('type'); //url中的 分类类型
        $this->assign('type',$type);

        //是提交操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['title', 'require', '标题不能为空'],
                ['type', 'require', '请选择所属类别'],
                ['thumb', 'require', '请上传图片'],
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
                return $this->success('添加成功',url('admin/links/index',['type'=>$type]));
            }
        } else {
            //非提交操作
            /* 
            $cates = $cateModel->field('id,name,pid')->where('type',$type)->order('orders, id')->select();
            $cates_tree = treelist($cates); //递归方法 转为 树型结构
            $this->assign('cates',$cates_tree);
             */
            return $this->fetch('add_'.$type);
        }
    }
    
    # 修改
    public function edit()
    {       
        //获取id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
		//修改操作
        if($id <= 0) return $this->error('id不正确');
        
        $model = new LinksModel(); 
        $cateModel = new CateModel();
        
        $type = input('type'); //url中的 分类类型
        $this->assign('type',$type);
        
        //是提交操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['title', 'require', '标题不能为空'],
                //['type', 'require', '请选择所属类别'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            //验证记录是否存在
            $result = $model->where('id',$id)->find();
            if(empty($result)) {
                return $this->error('id不正确');
            }
            //设置修改人
            $post['admin_id'] = Session::get('admin');
            //设置不推荐
            if(!isset($post['is_top'])) $post['is_top']=0;
            
            //重新上传图片时，删除原来关联的图片
            if(isset($post['thumb']) && $post['thumb']>0 && $result['thumb']>0){
                $att_result = Db::name('attachment')
                        ->field('id, filepath as url')
                        ->where('id',$result['thumb'])
                        ->find();
                //存在记录则删除
                if(!empty($att_result)){
                    //存在图片则删除图片
                    if(!empty($att_result['url']) && file_exists(ROOT_PATH . 'public' . $att_result['url'])){
                        unlink(ROOT_PATH . 'public' . $att_result['url']);
                    }
                    Db::name('attachment')->delete($result['thumb']);
                }
            }
            
            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                return $this->error('修改失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('修改成功',url('admin/links/index',['type'=>$type]));
            }
        } else {
            //非提交操作
            $result = $model->where('id',$id)->find();
            if(!empty($result)) {
                $this->assign('result',$result);
                /* 
                $cates = $cateModel->field('id,name,pid')->where('type',$type)->order('orders, id')->select();
                $cates_tree = treelist($cates); //递归方法 转为 树型结构
                $this->assign('cates',$cates_tree); 
                */
                return $this->fetch('edit_'.$type);
            } else {
                return $this->error('id不正确');
            }
        }
    }

    # 删除 单笔
    public function delete()
    {
    	if($this->request->isAjax()) {
            if($this->request->has('type')){
                $type = $this->request->param('type');
            }else{
                return $this->error('缺少参数，删除失败');
            }
            
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            //获取图片id 及 图片路径
            $result = Db::name('links')->alias('p')
                ->field('a.id as img_id, a.filepath as url')
                ->join(' __ATTACHMENT__ a', 'a.id=p.thumb')
                ->where('p.id',$id)
                ->where('p.thumb','>',0)
                ->find();
            #dump( Db::getLastSql()); dump($result);exit;
            //删除图片文件 及 附件表图片记录
            if(!empty($result)){
                if(file_exists(ROOT_PATH . 'public' . $result['url'])) {
                    if(@unlink(ROOT_PATH . 'public' . $result['url'])) {
                        Db::name('attachment')->where('id', $result['img_id'])->delete();
                    } else {
                        return $this->error('关联图片删除失败');
                    }
                }
            }
            
            //删除记录
            if(false == Db::name('links')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                addlog($id);//写入日志
                return $this->success('删除成功',url('admin/links/index',['type'=>$type]));
            }
    	}
    }

    # 列表 置顶推荐
    public function is_top()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('links')->where('id',$post['id'])->update(['is_top'=>$post['is_top']])) {
                return $this->error('设置失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('设置成功');
            }
        }
    }

    # 列表 发布状态切换
    public function status()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('links')->where('id',$post['id'])->update(['status'=>$post['status']])) {
                return $this->error('设置失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('设置成功');
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
                $order = Db::name('links')->where('id',$k)->value('orders');
                if($order != $val) {
                    if(false == Db::name('links')->where('id',$k)->update(['orders'=>$val])) {
                        return $this->error('排序更新失败');
                    } else {
                        $i++;
                    }
                }
            }
            addlog();//写入日志
            return $this->success('成功更新'.$i.'个数据');
        }
    }   
    
    # 列表 批量删除
    public function deleteAll()
    {
        if($this->request->isAjax()) {
            $post = $this->request->post();            
            $data = $post['ids'];
            
            //获取图片id 及 图片路径
            $result = Db::name('links')->alias('s')
                ->field('a.id as img_id, a.filepath as url')
                ->join(' __ATTACHMENT__ a', 'a.id=s.thumb')
                ->where('s.id','in',$data)
                ->where('s.thumb','>',0)
                ->select();
            # dump( Db::getLastSql()); dump($result);exit;
            
            //删除图片文件 及 附件表图片记录
            if(!empty($result)){
                //存放图片所在的附件表ID
                $image_ids = []; 
                //附件文件存在时，先删除文件
                foreach($result as $k => $v){
                    if(file_exists(ROOT_PATH . 'public' . $v['url'])) {
                        unlink(ROOT_PATH . 'public' . $v['url']);
                    }
                    $image_ids[] = $v['img_id']; //所有图片ID
                }
                //删除附件表图片记录
                Db::name('attachment')->delete($image_ids);  
            }

            //批量删除的 条数
            $rows = Db::name('links')->delete($data); 
            #dump( Db::getLastSql());
            if(false == $rows){
                return $this->error('批量删除失败');
            }else{
                $num = count($data) - $rows; //提交的条数与实现删除的条数
                if(0 == $num) {
                    addlog(implode(",",$data));//写入日志
                    return $this->success('成功删除'.$rows.'笔数据');
                } else {
                    return $this->error('有'.$num.'笔记录删除失败');
                }
            }
            
    	}
    }
}
