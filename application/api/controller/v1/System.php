<?php
/**
 * Created by 七月.
 * Author: 七月
 * 微信公号：小楼昨夜又秋风
 * 知乎ID: 七月在夏天
 * Date: 2017/2/23
 * Time: 2:56
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Order as OrderModel;
use app\api\model\User;

use app\api\service\Token as TokenService;
use app\api\validate\AddressNew;
use app\api\model\System as SystemModel;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessReturn;
use app\lib\exception\UserException;
use think\Db;

class System extends BaseController
{

    /**
     * 获取系统商户地址
     */
    public function getStorePos()
    {
        $res = Db::table('system')->where('id', 1)->field('latitude,longitude')->find();
        return $res;
    }

    /**
     * 更新或者创建用户收获地址
     */
    public function createOrUpdateAddress()
    {
        $validate = new AddressNew();
        $validate->goCheck();

        $uid = TokenService::getCurrentUid();
        $user = User::get($uid);
        if (!$user) {
            throw new UserException([
                'code' => 404,
                'msg' => '用户收获地址不存在',
                'errorCode' => 60001
            ]);
        }
        $userAddress = $user->address;
        // 根据规则取字段是很有必要的，防止恶意更新非客户端字段
        $data = $validate->getDataByRule(input('post.'));
        if (!$userAddress) {
            // 关联属性不存在，则新建
            $user->address()
                ->save($data);
        } else {
            // 存在则更新
//            fromArrayToModel($user->address, $data);
            // 新增的save方法和更新的save方法并不一样
            // 新增的save来自于关联关系
            // 更新的save来自于模型
            $user->address->save($data);
        }
        return new SuccessMessage();
    }

    /**
     * 判断店铺是否在营业
     * @return bool
     */
    public function checkOpen()
    {
        $model = new SystemModel();
        $res = $model->where('id', 1)->field('is_open,business_hours,close_notice')->find();
        $now = time();
        if(!$res['is_open']) return ['status'=>false,'close_notice'=>$res['close_notice']];
        $hours = $res['business_hours'];
        if (!$hours) return false;
        foreach ($hours as $k => $v) {
            if ($now > strtotime($v[0]) && $now < strtotime($v[1])) {
                return ['status'=>true];
            }
        }
        return ['status'=>false,'close_notice'=>null];
    }

    public static function autoClose(){
        $system_model = new SystemModel();
        $data = $system_model->find(1);
        $data = $data->toArray();
        if(!$data['limit_close'] && !$data['order_limit'] && !$data['amount_limit']){
            return ;
        }
        $where = ['date'=>date('Y-n-j'),'pay_status'=>2];
        $total_price = OrderModel::where($where)->sum('total_price');
        $count = OrderModel::where($where)->count();
        echo $data['order_limit'];
        //到达极限，自动关停店铺
        if($total_price>=$data['amount_limit'] || $count>=$data['order_limit']){
            $res = $system_model->save(['is_open'=>0],['id'=>1]);
            if($res){
                //推送消息给手机端通知店铺已经关停
            }else{
                //自动关停失败，同样推送消息给手机端
            }
        }
    }

}