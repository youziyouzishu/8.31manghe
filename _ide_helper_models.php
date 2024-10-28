<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property int $default 默认
 * @property string $detail 详细地址
 * @property string $province 省
 * @property string $city 市
 * @property string $region 区
 * @property string $mobile 手机号
 * @property string $name 姓名
 * @method static \Illuminate\Database\Eloquent\Builder|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Address query()
 * @mixin \Eloquent
 */
	class Address extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id ID(主键)
 * @property string $username 用户名
 * @property string $nickname 昵称
 * @property string $password 密码
 * @property string $avatar 头像
 * @property string $email 邮箱
 * @property string $mobile 手机
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $login_at 登录时间
 * @property string $roles 角色
 * @property integer $status 状态 0正常 1禁用
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin query()
 * @mixin \Eloquent
 */
	class Admin extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id ID(主键)
 * @property string $admin_id 管理员id
 * @property string $role_id 角色id
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole query()
 * @mixin \Eloquent
 */
	class AdminRole extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id ID
 * @property int|null $pid 父id
 * @property string|null $shortname 简称
 * @property string|null $name 名称
 * @property string|null $mergename 全称
 * @property int|null $level 层级:1=省,2=市,3=区/县
 * @property string|null $pinyin 拼音
 * @property string|null $code 长途区号
 * @property string|null $zip 邮编
 * @property string|null $first 首字母
 * @property string|null $lng 经度
 * @property string|null $lat 纬度
 * @property string|null $city_code 城市编码
 * @property int|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|Area newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Area newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Area query()
 * @property int $pass 采集过
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @mixin \Eloquent
 */
	class Area extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $image 图片
 * @method static \Illuminate\Database\Eloquent\Builder|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner query()
 * @property string $leng_image 纵向图
 * @mixin \Eloquent
 */
	class Banner extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Base newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Base newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Base query()
 * @mixin \Eloquent
 */
	class Base extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $type 分类
 * @property string $images 图片
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 名称
 * @property string $price 单价
 * @method static \Illuminate\Database\Eloquent\Builder|Box newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Box newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Box query()
 * @property-read mixed $images_text
 * @property-read mixed $type_text
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrize
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxLevel> $level
 * @property string $image 封面
 * @property int $status 状态
 * @mixin \Eloquent
 */
	class Box extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $box_id 盲盒
 * @property string $image 图片
 * @property integer $name 关卡
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrize
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property-read mixed $name_text
 * @mixin \Eloquent
 */
	class BoxLevel extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户
 * @property integer $status 状态
 * @property integer $box_id 所属盲盒
 * @property string $amount 订单金额
 * @property string $pay_amount 支付金额
 * @property string $coupon_amount 优惠金额
 * @property string $ordersn 订单编号
 * @property string $pay_at 支付时间
 * @property int $user_coupon_id 优惠券
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder query()
 * @property-read \plugin\admin\app\model\UsersCoupon|null $userCoupon
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property int $times 抽奖次数
 * @property int $level_id 所属关卡
 * @property-read \plugin\admin\app\model\User|null $user
 * @property int $pay_type 支付类型
 * @mixin \Eloquent
 */
	class BoxOrder extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $grade 评级
 * @property integer $box_id 所属盲盒
 * @property float $chance 概率
 * @property integer $num 数量
 * @property string $image 图片
 * @property string $name 名称
 * @property-read \plugin\admin\app\model\Box|null $box
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize query()
 * @property-read mixed $grade_text
 * @property int $total 总数量
 * @property int $level_id 所属关卡
 * @property-read \plugin\admin\app\model\BoxLevel|null $level
 * @property string $price 市场价
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrize> $userPrizes
 * @mixin \Eloquent
 */
	class BoxPrize extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $address 地址
 * @property string $name 名称
 * @property string $qid 唯一标识
 * @property string $tel 手机号
 * @method static \Illuminate\Database\Eloquent\Builder|Caiji newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Caiji newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Caiji query()
 * @mixin \Eloquent
 */
	class Caiji extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 券名称
 * @property integer $type 券类型
 * @property string $amount 优惠金额
 * @property string $with_amount 满足金额
 * @property integer $num 券数量
 * @property string $mark 备注
 * @property string $expire_at 失效日期
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon query()
 * @property-read mixed $type_text
 * @mixin \Eloquent
 */
	class Coupon extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property int $status 状态:0=待支付,1=待发货,2=待收货,3=已完成
 * @property string $freight 运费
 * @property string $ordersn 订单编号
 * @property string $waybill 快递单号
 * @property string $express 快递公司
 * @method static \Illuminate\Database\Eloquent\Builder|Deliver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deliver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deliver query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\DeliverDetail> $detail
 * @property int $address_id 收货地址
 * @property-read \plugin\admin\app\model\Address|null $address
 * @property-read mixed $status_text
 * @property string $mark 备注
 * @mixin \Eloquent
 */
	class Deliver extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $deliver_id 发货
 * @property int $box_prize_id 奖品
 * @method static \Illuminate\Database\Eloquent\Builder|DeliverDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliverDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliverDetail query()
 * @property int $user_prize_id 所属用户奖品
 * @property-read \plugin\admin\app\model\Deliver|null $deliver
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\UsersPrize|null $userPrize
 * @mixin \Eloquent
 */
	class DeliverDetail extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property int $box_prize_id 奖品
 * @property int $type 类型:1=梦想大奖,2=基础奖
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|Dream newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dream newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dream query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @mixin \Eloquent
 */
	class Dream extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property string $ordersn 订单编号
 * @property int $big_prize_id 大奖
 * @property int $small_prize_id 小奖
 * @property int $status 状态:1=未支付,2=已支付
 * @property string|null $pay_at 支付时间
 * @property string $profit 盈亏
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $times 次数
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrders newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrders newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrders query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $bigPrize
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\DreamOrdersPrize> $orderPrize
 * @property-read \plugin\admin\app\model\BoxPrize|null $smallPrize
 * @property string $probability 概率
 * @property string $pay_amount 支付金额
 * @property-read \plugin\admin\app\model\User|null $user
 * @mixin \Eloquent
 * @property int $pay_type 支付类型
 */
	class DreamOrders extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $order_id 订单
 * @property int $box_prize_id 奖品
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrdersPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrdersPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrdersPrize query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\DreamOrders|null $orders
 * @mixin \Eloquent
 */
	class DreamOrdersPrize extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $prize_id 奖品
 * @property integer $class_id 分类
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods query()
 * @mixin \Eloquent
 */
	class Goods extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 名称
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsClass newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsClass newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsClass query()
 * @mixin \Eloquent
 */
	class GoodsClass extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int|null $user_id 用户
 * @property int|null $goods_id 商品
 * @property string $ordersn 订单编号
 * @property-read \plugin\admin\app\model\Goods|null $goods
 * @property-read \plugin\admin\app\model\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsOrder query()
 * @property string $pay_amount 支付金额
 * @property int $status 订单状态:1=未支付,2=已支付
 * @property string $amount 订单金额
 * @property string $pay_at 支付时间
 * @property int $pay_type 支付类型
 * @mixin \Eloquent
 */
	class GoodsOrder extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id (主键)
 * @property string $name 键
 * @property mixed $value 值
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|Option newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Option newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Option query()
 * @mixin \Eloquent
 */
	class Option extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $name 角色名
 * @property string $rules 权限
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $pid 上级id
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @mixin \Eloquent
 */
	class Role extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property int|null $user_id 用户
 * @property string $name 房间名称
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property string|null $start_at 开始时间
 * @property string|null $end_at 结束时间
 * @property string $content 活动介绍
 * @property int $type 房间类型:1=密码,2=流水
 * @property string $password
 * @property int $status 房间状态:1=进行中,2=未开始,3=已结束
 * @property int $num 参与人数
 * @method static \Illuminate\Database\Eloquent\Builder|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Room query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\RoomPrize> $roomPrize
 * @property-read mixed $status_text
 * @property-read \plugin\admin\app\model\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\RoomUsers> $roomUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrizes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\User> $roomUserUser
 * @mixin \Eloquent
 */
	class Room extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int|null $room_id 房间
 * @property int|null $user_prize_id 赏品
 * @method static \Illuminate\Database\Eloquent\Builder|RoomPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomPrize query()
 * @property-read \plugin\admin\app\model\UsersPrize|null $userPrize
 * @property-read \plugin\admin\app\model\Room|null $room
 * @property int $box_prize_id 奖品
 * @mixin \Eloquent
 */
	class RoomPrize extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|RoomUsers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomUsers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomUsers query()
 * @property int|null $user_id 用户
 * @property int|null $room_id 房间
 * @property-read \plugin\admin\app\model\User|null $user
 * @mixin \Eloquent
 */
	class RoomUsers extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id
 * @property int $room_id
 * @property int $box_prize_id
 * @method static \Illuminate\Database\Eloquent\Builder|RoomWinprize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomWinprize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomWinprize query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\Room|null $room
 * @mixin \Eloquent
 */
	class RoomWinprize extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $title 标题
 * @property string $icon 图标
 * @property string $key 标识
 * @property integer $pid 上级菜单
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $href url
 * @property integer $type 类型
 * @property integer $weight 排序
 * @method static \Illuminate\Database\Eloquent\Builder|Rule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rule query()
 * @mixin \Eloquent
 */
	class Rule extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property string $code 验证码
 * @method static \Illuminate\Database\Eloquent\Builder|Sms newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sms newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sms query()
 * @property string $mobile 手机号
 * @mixin \Eloquent
 */
	class Sms extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $name 名称
 * @property string $url url
 * @property integer $admin_id 管理员
 * @property integer $user_id 用户
 * @property integer $file_size 文件大小
 * @property string $mime_type mime类型
 * @property integer $image_width 图片宽度
 * @property integer $image_height 图片高度
 * @property string $ext 扩展名
 * @property string $storage 存储位置
 * @property string $created_at 上传时间
 * @property string $category 类别
 * @property string $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|Upload newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Upload newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Upload query()
 * @mixin \Eloquent
 */
	class Upload extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $username 用户名
 * @property string $nickname 昵称
 * @property string $password 密码
 * @property string $sex 性别
 * @property string $avatar 头像
 * @property string $email 邮箱
 * @property string $mobile 手机
 * @property integer $level 等级
 * @property string $birthday 生日
 * @property string $money 余额(元)
 * @property integer $score 积分
 * @property string $last_time 登录时间
 * @property string $last_ip 登录ip
 * @property string $join_time 注册时间
 * @property string $join_ip 注册ip
 * @property string $token token
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $role 角色
 * @property integer $status 禁用
 * @property string $openid 微信公众标识
 * @property string $invitecode 邀请码
 * @property integer $official 官方
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @property int $coupon_num 优惠券展示次数
 * @property int $kol 达人
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersDisburse> $userDisburse
 * @property float $chance 额外中奖率
 * @property int $parent_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrize
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrize> $userPrize
 * @property int $new 新用户
 * @mixin \Eloquent
 */
	class User extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int|null $user_id 用户
 * @property int|null $coupon_id 优惠券
 * @property int $status 状态:1=未使用,2=已使用,3=已过期
 * @method static \Illuminate\Database\Eloquent\Builder|UsersCoupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersCoupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersCoupon query()
 * @property-read \plugin\admin\app\model\Coupon $coupon
 * @property-read mixed $status_text
 * @mixin \Eloquent
 */
	class UsersCoupon extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property string $amount 金额
 * @property string $mark 备注
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDisburse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDisburse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDisburse query()
 * @property int $type 类型:1=微信,2=水晶
 * @mixin \Eloquent
 */
	class UsersDisburse extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property int $times 次数
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDrawLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDrawLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDrawLog query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrizeLog> $prizeLog
 * @property int $box_id 所属盲盒
 * @property int $level_id 所属关卡
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property string $ordersn 订单编号
 * @mixin \Eloquent
 */
	class UsersDrawLog extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int|null $user_id
 * @property int|null $pid
 * @property int|null $layer
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLayer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLayer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLayer query()
 * @property int|null $parent_id
 * @mixin \Eloquent
 */
	class UsersLayer extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property int $level_id 所在关卡
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevel query()
 * @property int $box_id 所属盲盒
 * @mixin \Eloquent
 */
	class UsersLevel extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property int $level_id 所在关卡
 * @property int $box_id 所属盲盒
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevelLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevelLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevelLog query()
 * @mixin \Eloquent
 */
	class UsersLevelLog extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 会员ID
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property string $money 变更余额
 * @property string $before 变更前余额
 * @property string $after 变更后余额
 * @property string|null $memo 备注
 * @method static \Illuminate\Database\Eloquent\Builder|UsersMoneyLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersMoneyLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersMoneyLog query()
 * @mixin \Eloquent
 */
	class UsersMoneyLog extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户
 * @property integer $box_prize_id 奖品
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property int $safe 保险箱
 * @property string $mark 备注
 * @property-read \plugin\admin\app\model\User|null $user
 * @property \Illuminate\Support\Carbon $deleted_at 删除时间
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize withoutTrashed()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\RoomPrize> $roomPrizes
 * @property string $price 参考价
 * @mixin \Eloquent
 */
	class UsersPrize extends \Eloquent {}
}

namespace plugin\admin\app\model{
/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户
 * @property integer $source_user_id 来源对象
 * @property integer $box_prize_id 奖品
 * @property integer $type 类型
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrizeLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrizeLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrizeLog query()
 * @property string $mark 备注
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property int $draw_id 抽奖
 * @property-read \plugin\admin\app\model\User|null $sourceUser
 * @property string $price 参考价
 * @mixin \Eloquent
 */
	class UsersPrizeLog extends \Eloquent {}
}

