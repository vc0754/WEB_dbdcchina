<?php
namespace app\admin\model;

use \think\Model;
class ProductCate extends Model
{
    # 别名 写入前 先转小写
    public function setMarkAttr($value)
    {
        return strtolower($value);
    }
    
/* 	# 分类列表 树状
    public function catelist($cate,$id=0,$level=0){
		static $cates = array();
		foreach ($cate as $value) {
			if ($value['pid']==$id) {
                if($level> 0)
				{
					$value['str'] = str_repeat('&emsp;&emsp;', $level).'└ ';
				}else{
                    $value['str'] = '';
                }
                $value['level'] = $level+1;
				$cates[] = $value;
                
				$this->catelist($cate,$value['id'],$value['level']);
			}
		}
		return $cates;
	} */
    
}
