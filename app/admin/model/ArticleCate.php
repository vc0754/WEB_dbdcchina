<?php
namespace app\admin\model;

use \think\Model;
class ArticleCate extends Model
{
	# 别名 写入前 先转小写
    public function setMarkAttr($value)
    {
        return strtolower($value);
    }
    
    public function catelist($cate,$id=0,$level=0){
		static $cates = array();
		foreach ($cate as $value) {
			if ($value['pid']==$id) {
				$value['level'] = $level+1;
				if($level == 0)
				{
					$value['str'] = str_repeat('',$value['level']);
				}
				elseif($level == 2)
				{
					$value['str'] = '&emsp;&emsp;&emsp;&emsp;'.'└ ';
				}
				else
				{
					$value['str'] = '&emsp;&emsp;'.'└ ';
				}
				$cates[] = $value;
				$this->catelist($cate,$value['id'],$value['level']);
			}
		}
		return $cates;
	}


/* 	public function article()
    {
        //关联文章表
        return $this->hasOne('Article');
    } */
}
