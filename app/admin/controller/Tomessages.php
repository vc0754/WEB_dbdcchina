<?php
namespace app\admin\controller;

use \think\Controller;
use think\Db;
use app\admin\controller\Permissions;
use app\admin\model\Messages;
class Tomessages extends Permissions
{
    public function index()
    {
        $post = $this->request->param();
        $this->assign('post',$post);
        
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['title|recommend'] = ['like', '%' . $post['keywords'] . '%'];//或条件
        }
        
        if (isset($post['is_look']) and ($post['is_look'] == 1 or $post['is_look'] === '0')) {
            $where['is_look'] = $post['is_look'];
        }
 
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        
        $where = empty($where) ? [] : $where; //条件        
        $model = new Messages();
        /* $message = empty($where) ? $model->order('create_time desc')->paginate(20) : $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]); */
        $message = $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
        $this->assign('message',$message);
        return $this->fetch();
    }


    public function publish()
    {
    	$model = new Messages();
		
		//是新增操作
		if($this->request->isPost()) {
			//是提交操作
			$post = $this->request->post();
			//验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['recommend', 'require|length:20,200', '留言不能为空|请输入10-100个字的建议'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            //设置创建人
            $post['ip'] = $this->request->ip();
            if(false == $model->allowField(true)->save($post)) {
            	return $this->error('提交失败');
            } else {
                addlog($model->id);//写入日志
            	return $this->success('提交成功','admin/tomessages/index');
            }
		} else {
			//非提交操作
			return $this->fetch();
		}
    }

    #状态审核
    public function mark()
    {
        //获取id
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $model = new Messages();
        //是正常添加操作
        if($id > 0) {
            //是修改操作
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                //验证菜单是否存在
                $message = $model->where('id',$id)->find();
                if(empty($message)) {
                    return $this->error('id不正确');
                }
                if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                    return $this->error('提交失败');
                } else {
                    addlog($model->id);//写入日志
                    return $this->success('提交成功','admin/tomessages/index');
                }
            }
        }
    }

    #单笔记录删除
    public function delete()
    {
    	if($this->request->isAjax()) {
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('messages')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                addlog($id);//写入日志
                return $this->success('删除成功','admin/tomessages/index');
            }
    	}
    }
    
    //批量删除 一并删除文件
    public function deleteAll()
    {
    	if($this->request->isAjax()) {
            $post = $this->request->post();
            $data = $post['ids'];
            
            //批量删除的 条数
            $rows = Db::name('messages')->delete($data);
            if(false == $rows){
                return $this->error('批量删除失败');
            }else{
                $num = count($data) - $rows; //提交的条数与实现删除的条数
                if(0 == $num) {
                    addlog(implode(",",$data));//写入日志
                    return $this->success('成功删除'.$rows.'笔数据','admin/tomessages/index');
                }else{
                    return $this->error('有'.$num.'笔记录删除失败');
                }
            }
    	}
    }
}
