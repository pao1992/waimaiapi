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
use app\api\model\DispatchRange AS DispatchRangeModel;
use app\api\model\User;
use app\api\model\UserAddress;
use app\api\service\Token;
use app\api\service\Token as TokenService;
use app\api\validate\AddressNew;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;

class Address extends BaseController
{
    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'createOrUpdateAddress,getUserAddress']
    ];

    /**
     * 获取用户地址信息
     * @return UserAddress
     * @throws UserException
     */
    public function getUserAddress()
    {
        $uid = Token::getCurrentUid();
        $userAddress = UserAddress::where('user_id', $uid)
            ->find();
        if (!$userAddress) {
            throw new UserException([
                'msg' => '用户地址不存在',
                'errorCode' => 60001
            ]);
        }
        //计算配送价格
        $position = ['lat' => $userAddress['lat'], 'lng' => $userAddress['lng']];
        $ranges = DispatchRangeModel::getAll(['status' => 1]);

        if (!$ranges) {
            $userAddress['starting_price'] = null;
        } else {
            $starting_price = null;
            foreach ($ranges->toArray() as $k => $v) {
                //一一检测是否在圈内
                $polygon = [];
                foreach ($v['points'] as $kk => $vv) {
                    $polygon[] = ['lat' => $vv['latLng']['lat'], 'lng' => $vv['latLng']['lng']];
                }
                $bool = isPointInPolygon($polygon, $position);
                if ($bool) {
                    //在该区域内
                    if ($starting_price) {
                        $starting_price = $starting_price > $v['starting_price'] ? $v['starting_price'] : $starting_price;
                    } else {
                        $starting_price = $v['starting_price'];
                    }
                }
            }
            $userAddress['starting_price'] = $starting_price;
            $userAddress['out_of_range'] = $starting_price === null ? true : false;
        }
        return $userAddress;
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
}