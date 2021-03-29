<?php
namespace app\admin\model;

use \think\Model;
class NewsCate extends Model
{
	# 别名 写入前 先转小写
    public function setMarkAttr($value)
    {
        return strtolower($value);
    }
}
