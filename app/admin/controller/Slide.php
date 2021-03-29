<?php
namespace app\admin\controller;

use think\Db;
use \think\Session;
use \think\Controller;
use app\admin\controller\Permissions;
use app\admin\model\Slide as slideModel;
use app\admin\model\SlideCate as cateModel;

class Slide extends Permissions
{
    public function index()
    {
        $model = new slideModel();
        $post = $this->request->param(); 
        $this->assign('post',$post);
        
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['title'] = ['like', '%' . $post['keywords'] . '%'];
        }
/*      if (isset($post['cid']) and $post['cid'] > 0) {
            $where['cid'] = $post['cid'];
        } 
        if (isset($post['is_video']) and ($post['is_video'] == 1 or $post['is_video'] === '0')) {
            $where['is_video'] = $post['is_video'];
        }*/
        if (isset($post['status']) and ($post['status'] == 1 or $post['status'] === '0')) {
            $where['status'] = $post['status'];
        }
 
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['s.create_time'] = [['>=',$min_time],['<=',$max_time]];
        } 
        
        $where = empty($where) ? '' : $where;//条件
        $lists = $model->alias('s')
                ->field('s.*, c.name as catename, a.nickname, att.filepath as thumb')
                ->join('__SLIDE_CATE__ c', 'c.id=s.cid','left')
                ->join('__ADMIN__ a', 'a.id=s.admin_id','left')
                ->join('__ATTACHMENT__ att', 'att.id=s.thumb', 'left')
                ->where($where)
                ->order('s.orders, s.id desc')
                ->paginate(20,false,['query'=>$this->request->param()]);
        #dump( Db::getLastSql()); dump($lists);
        $this->assign('lists',$lists);
        
        $info['cates'] = Db::name('slide_cate')->select();
        $this->assign('info',$info);
        return $this->fetch();
    }
    
    // 新增
    public function add()
    {
    	$model = new slideModel();
        $cateModel = new cateModel();
        
        //是新增提交操作
        if($this->request->isPost()) {
            //是提交操作
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                // ['title', 'require', '标题不能为空'],
                ['banner', 'require', '请上传大屏轮播图'], 
                /* ['thumb', 'require', '请上传小屏轮播图'],*/
                /* ['cid', 'require', '请选择分类'], */
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            //设置创建人，操作人
            $post['admin_id'] = Session::get('admin');
            //设置修改人
            #$post['edit_admin_id'] = $post['admin_id'];
            
            if(false == $model->allowField(true)->save($post)) {
                return $this->error('添加失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('添加成功','admin/slide/index');
            }
        } else {
            //非提交操作
            /* $cates = $cateModel->field('id,name')->select(); //分类
            $this->assign('cates',$cates); */

            return $this->fetch();
        }
    }
    
    //修改
    public function edit()
    {
        //获取id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if($id <= 0) return $this->error('id不正确');
        
    	$model = new slideModel();
        $cateModel = new cateModel();
        
        //是提交操作
        if($this->request->isPost()) {
            $post = $this->request->post();
            
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                // ['title', 'require', '标题不能为空'],
                /* ['cid', 'require', '请选择分类'], */
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
            
            //设置操作人
            $post['admin_id'] = Session::get('admin');
            
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
            //重新上传图片时，删除原来关联的 banner图片
            if(isset($post['banner']) && $post['banner']>0 && $result['banner']>0){
                $att_result = Db::name('attachment')
                        ->field('id, filepath as url')
                        ->where('id',$result['banner'])
                        ->find();
                //存在记录则删除
                if(!empty($att_result)){
                    //存在图片则删除图片
                    if(!empty($att_result['url']) && file_exists(ROOT_PATH . 'public' . $att_result['url'])){
                        unlink(ROOT_PATH . 'public' . $att_result['url']);
                    }
                    Db::name('attachment')->delete($result['banner']);
                }
            }
            
            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                return $this->error('修改失败');
            } else {
                addlog($model->id);//写入日志
                return $this->success('修改成功','admin/slide/index');
            }
        } else {
            //非提交操作
            $result = $model->where('id',$id)->find();
            if(!empty($result)) {
                $this->assign('result',$result);
                
                return $this->fetch();
            } else {
                return $this->error('id不正确');
            }
        }    	
    }

    // 单笔删除
    public function delete()
    {
    	if($this->request->isAjax()) {
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            
            //获取关联缩略图片和banner图片的id 及 图片路径
            $result = Db::name('slide')->alias('s')
                ->field('s.thumb, a.filepath as thumb_url, s.banner, att.filepath as banner_url')
                ->join(' __ATTACHMENT__ a', 's.thumb>0 and a.id=s.thumb', 'left')
                ->join(' __ATTACHMENT__ att', 's.banner>0 and att.id=s.banner', 'left')
                ->where('s.id',$id)
                ->find();
            #dump( Db::getLastSql()); dump($result);exit;
            
            //删除关联的图片文件 及 附件表图片记录
            if(!empty($result['thumb_url']) && file_exists(ROOT_PATH . 'public' . $result['thumb_url'])) {
                if(unlink(ROOT_PATH . 'public' . $result['thumb_url'])) {
                    Db::name('attachment')->where('id', $result['thumb'])->delete();
                }
            }
            if(!empty($result['banner_url']) && file_exists(ROOT_PATH . 'public' . $result['banner_url'])) {
                if(unlink(ROOT_PATH . 'public' . $result['banner_url'])) {
                    Db::name('attachment')->where('id', $result['banner'])->delete();
                }
            }
            
            if(false == Db::name('slide')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                addlog($id);//写入日志
                return $this->success('删除成功','admin/slide/index');
            }
    	}
    }

    // 切换状态
    public function status()
    {
        if($this->request->isPost()){
            $post = $this->request->post();
            if(false == Db::name('slide')->where('id',$post['id'])->update(['status'=>$post['status']])) {
                return $this->error('设置失败');
            } else {
                addlog($post['id']);//写入日志
                return $this->success('设置成功','admin/slide/index');
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
                $order = Db::name('slide')->where('id',$k)->value('orders');
                if($order != $val) {
                    if(false == Db::name('slide')->where('id',$k)->update(['orders'=>$val])) {
                        return $this->error('更新失败');
                    } else {
                        $i++;
                    }
                }
            }
            addlog();//写入日志
            return $this->success('成功更新'.$i.'个数据','admin/slide/index');
        }
    }

    # 列表 批量删除
    public function deleteAll()
    {
        if($this->request->isAjax()) {
            $post = $this->request->post();
            $data = $post['ids'];
            
            //获取图片id 及 图片路径               
            $result = Db::name('slide')->alias('s')
                ->field('s.thumb, a.filepath as thumb_url, s.banner, att.filepath as banner_url')
                ->join(' __ATTACHMENT__ a', 's.thumb>0 and a.id=s.thumb', 'left')
                ->join(' __ATTACHMENT__ att', 's.banner>0 and att.id=s.banner', 'left')
                ->where('s.id','in',$data)
                ->select();
            # dump( Db::getLastSql()); dump($result);exit;
            
            //删除图片文件 及 附件表图片记录
            if(!empty($result)){
                //存放图片所在的附件表ID
                $image_ids = []; 
                //附件文件存在时，先删除文件
                foreach($result as $k => $v){
                    if(!empty($v['thumb_url']) && file_exists(ROOT_PATH . 'public' . $v['thumb_url'])) {
                        if(unlink(ROOT_PATH . 'public' . $v['thumb_url'])) {
                            $image_ids[] = $v['thumb']; //所有thumb图片ID
                        }
                    }
                    
                    if(!empty($v['banner_url']) && file_exists(ROOT_PATH . 'public' . $v['banner_url'])) {
                        if(unlink(ROOT_PATH . 'public' . $v['banner_url'])) {
                            $image_ids[] = $v['banner']; //所有banner图片ID
                        }
                    }
                }
                //删除附件表图片记录
                Db::name('attachment')->delete($image_ids);  
            }

            //批量删除的 条数
            $rows = Db::name('slide')->delete($data); 
            $num = count($data) - $rows; //提交的条数与实现删除的条数
            if(0 == $num) {
                addlog(implode(",",$data));//写入日志
                return $this->success('成功删除'.$rows.'笔数据','admin/slide/index');
            } else {
                return $this->error('有'.$num.'笔记录删除失败');
            } 
    	}
    }
}
