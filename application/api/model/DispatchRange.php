<?php

namespace app\api\model;

use think\Model;
use think\Db;

class DispatchRange extends BaseModel
{
    protected $hidden = ['delete_time', 'update_time'];
    protected $autoWriteTimestamp = true;
    public function setPointsAttr($value)
    {
        return json_encode($value);
    }
    public function getPointsAttr($value)
    {
        return json_decode($value,true);
    }
    public static function getAll($where)
    {
        $model = new self();
        return $model->where($where)->select();
    }
}
