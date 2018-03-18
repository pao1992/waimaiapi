<?php

namespace app\api\model;

use think\Model;

class System extends BaseModel
{
    protected function getBusinessHoursAttr($value)
    {
        $res = json_decode($value,true);
        return $res?$res:[];
    }
    protected function setIsOpenAttr($value)
    {
        return $value?1:0;
    }
    protected function getlimitCloseAttr($value)
    {
        return $value?true:false;
    }
}
