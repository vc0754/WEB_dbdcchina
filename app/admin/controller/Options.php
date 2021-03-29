<?php
namespace app\admin\controller;

use app\admin\controller\Permissions;
use \think\Db;
#网站设置
class Options extends Permissions
{
    public function index()
    {
        $result = Db::name('options')->field('name, value')->select();
        
        $options = [];
        foreach($result as $data){
            $options[$data['name']] = $data['value'];
        }

        $this->assign('options',$options);
        return $this->fetch();
    }

    public function publish()
    {
    	if($this->request->isPost()) {
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['title', 'require', '网站名称不能为空'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('更新失败：' . $validate->getError());
            }
            
            //存在 热门搜索关键词
            if(isset($post['hot_search'])){
                $post['hot_search'] = str_replace(PHP_EOL, ',', $post['hot_search']); //替换换行符
                $post['hot_search'] = empty($post['hot_search']) ? '' : preg_replace("/(，)|(、)|(\/)/" , ',' ,$post['hot_search']); //把换行 / 中文逗号 全部替换为 英文逗号
            }
            
            //参数不存在 就写入，存在则更新
            foreach($post as $key => $v){
                if($this->option_exist($key)){
                    $this->option_save($key, $v);
                }else{
                    $this->option_add($key, $v);
                }
            }

            addlog(); //写入操作日志
            return $this->success('更新成功','admin/options/index');
        }
    }
    
    # 设置是否存在
	private function option_exist($option_name){
        $result = Db::name('options')->where('name', $option_name)->value('name');
		if(is_null($result)) return false;
		return true;
	}
    
    # 设置写入
	private function option_add($option_name, $option_value){
        $result = Db::name('options')->data(['name'=>$option_name, 'value'=>$option_value])->insert();
		return $result;
	}
	
	# 设置更新
	private function option_save($option_name, $option_value){
		$result = Db::name('options')->where('name',$option_name)->setField('value', $option_value);
		/* if($result){
			# 缓存更新
			$options = S('DB_OPTION_DATA');
			$options[$option_name] = $option_value;
			S('DB_OPTION_DATA', $options);
			C($options);
		} */
		return $result;
	}
}
