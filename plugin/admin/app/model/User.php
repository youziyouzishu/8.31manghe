<?php

namespace plugin\admin\app\model;

use Illuminate\Support\Facades\DB;
use plugin\admin\app\model\Base;

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
 * @property-read mixed $official_text
 * @mixin \Eloquent
 */
class User extends Base
{




    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['username', 'nickname', 'password', 'sex', 'avatar', 'email', 'mobile', 'level', 'birthday', 'money', 'score', 'last_time', 'last_ip', 'join_time', 'join_ip', 'token', 'created_at', 'updated_at', 'role', 'status', 'openid', 'official', 'invitecode'];

    protected $appends = ['official_text'];
    /**
     * 变更会员余额
     * @param int $money 余额
     * @param int $user_id 会员ID
     * @param string $memo 备注
     * @throws \Throwable
     */
    public static function money($money, $user_id, $memo)
    {
        DB::beginTransaction();
        try {
            $user = self::lockForUpdate()->find($user_id);
            if ($user && $money != 0) {
                $before = $user->money;
                //$after = $user->money + $money;
                $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
                //更新会员信息
                $user->save(['money' => $after]);
                //写入日志
                UsersMoneyLog::create(['user_id' => $user_id, 'money' => $money, 'before' => $before, 'after' => $after, 'memo' => $memo]);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 变更会员积分
     * @param int $score 积分
     * @param int $user_id 会员ID
     * @param string $memo 备注
     */
    public static function score($score, $user_id, $memo)
    {
        Db::beginTransaction();
        try {
            $user = self::lock(true)->find($user_id);
            if ($user && $score != 0) {
                $before = $user->score;
                $after = $user->score + $score;
                $level = self::nextlevel($after);
                //更新会员信息
                $user->save(['score' => $after, 'level' => $level]);
                //写入日志
                ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    function userDisburse()
    {
        return $this->hasMany(UsersDisburse::class);
    }

    function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    function boxPrize()
    {
        return $this->hasManyThrough(BoxPrize::class, UsersPrize::class, 'user_id', 'id', 'id', 'box_prize_id');
    }

    function userPrize()
    {
        return $this->hasMany(UsersPrize::class);
    }

    function getOfficialTextAttribute($value)
    {
        $value = $value ?: ($this->official ?? '');
        $list = $this->getOfficialList();
        return $list[$value] ?? '';
    }


    public function getOfficialList()
    {
        return ['1' => '是', '2' => '否'];
    }

}
