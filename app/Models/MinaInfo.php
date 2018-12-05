<?php

namespace App\Models;

use App\Http\Models\Status;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as frequest;

/**
 * Class MinaInfo
 *
 * @package App\Models
 * @property integer $id
 * @property integer $uid
 * @property string  $name
 * @property string  $img
 * @property integer $cid
 * @property integer $con_min
 * @property integer $con_max
 * @property integer $success_count
 * @property integer $exc_condition
 * @property string  $mina_remark
 * @property string  $region
 * @property string  $code_img
 */
class MinaInfo extends Model
{
    protected $fillable = ['mina_code_image'];

    public function minaCrowdRatio()
    {
        return $this->belongsTo(MinaCrowdRatio::class, 'mid');
    }

    public function user(){
        return $this->belongsTo(User::class,'uid')->select('id','user_name','nick_name','wechat','phone','company');
    }

    /**
     * 小程序列表
     *
     * @param string $position
     * @param int    $limit
     * @return mixed
     */
    public static function getMinaList($page, $pageSize, $uid = '', $request, $type = '')
    {
        $invite = [];           //用户邀请信息列表
        $inviteArray = [];      //用户邀请成功信息列表
        $remove_invite = false; //移除已邀请小程序并置于最后一页
        $indexList = false;     //是否为首页的筛选
        if($type == 'minaBlackList'){       //换量小程序黑名单
            $query = ComplainList::select([
                    'm.id','m.uid','m.name','m.img','m.cid','m.con_min','m.con_max','m.exc_condition','m.label','m.complain_count','complain_lists.com_type'])
                    ->join('mina_infos as m', 'complain_lists.mina_id', 'm.id')
                    ->where('complain_lists.handle_type',Status::HANDLE_TRUE)
                    ->orderBy('m.complain_count', 'desc')->orderBy('complain_lists.id','desc');

        }else if($uid) {
            $query = MinaInfo::where('uid', $uid)
                             ->where('audit_type', Status::MINA_AUDIT_TRUE)
                             ->select(['id', 'uid', 'name', 'img', 'cid', 'con_min', 'con_max', 'exc_condition', 'label'])
                             ->orderBy('updated_at', 'desc');
        }else {
            //查出该用户邀请换量信息列表
            $authorization = frequest::header('authorization');
            if($authorization){
                $auth_uid = User::where('access_token',$authorization)->value('id');
                if(empty($auth_uid)){//传token又查不出来的为异常请求
                    $list['page'] = $page;
                    $list['total'] = 0;
                    $list['total_page'] = 0;
                    $list['list'] = [];
                    $list['invite'] = $invite;
                    return $list;
                }
                $invite = InviteInfo::select('mina_id','status')
                                    ->where(['uid'=>$auth_uid])
                                    ->wherein('status',[Status::INVITATIONS,Status::INVITATIONS_AGREE])
                                    ->get()->toArray();
                if(!empty($invite)){
                    foreach ($invite as $k => $v){
                        if($v['status'] == Status::INVITATIONS_AGREE){
                            $remove_invite = true;
                            $inviteArray[] = $v['mina_id'];
                        }
                    }
                }
            }
            //需求为在最新列表中，已邀请的要放在最后
            $orderBy = empty($request['sort']) && $request['sort'] != 'desc' && $request['sort'] != 'asc' ? 'desc' : $request['sort'];
            $orderByName = empty($request['sort']) && $request['sort'] != 'desc' && $request['sort'] != 'asc' ? 'updated_at' : 'con_max';
            $mina_ids = $request['mina_ids'];
            $mina_crowd_ids = [];
            if ( !empty($request['mina_crowd_ids']) && !empty($request['mobile_types'])) {
                foreach ($request['mina_crowd_ids'] as $k => $v) {
                    $mina_crowd_ids[] = Status::$crowd_question_info_for_sql[$v];
                }
                foreach ($request['mobile_types'] as $k => $v) {
                    $mina_crowd_ids[] = Status::$mobile_type_for_sql[ $v ];
                }
            } elseif ( !empty($request['mina_crowd_ids'])) {
                foreach ($request['mina_crowd_ids'] as $k => $v) {
                    $mina_crowd_ids[] = Status::$crowd_question_info_for_sql[$v];
                }
            } elseif ( !empty($request['mobile_types'])) {
                foreach ($request['mobile_types'] as $k => $v) {
                    $mina_crowd_ids[] = Status::$mobile_type_for_sql[ $v ];
                }
            }
            $region = $request['region'];
            //导流量筛选和精准筛选的已邀请不用沉底
            if(!empty($mina_ids) || !empty($region) || !empty($mina_crowd_ids) || !empty($request['sort'])) {
                $remove_invite = false;
            }
            $indexList = true;
            $query = self::minaListSearchQuery($mina_ids, $region, $mina_crowd_ids, $inviteArray, $remove_invite);
            $query->select(['id', 'uid', 'name', 'img', 'cid', 'con_min', 'con_max', 'exc_condition', 'label'])
                ->orderBy('sort','asc')->orderBy($orderByName, $orderBy);
        }
        $list['page'] = $page;
        $list['total'] = $query->count();
        $list['total_page'] = ceil($list['total'] / $pageSize);
        $list['list'] = $query->offset(($page - 1) * $pageSize)
                              ->limit($pageSize)
                              ->get()
                              ->toArray();
        //如果在首页，将id的列全部取出，方便进行上一个下一个换量详情的给值($authorization是排除旧版本)
        if($page == 1 && $indexList && $authorization){
            $catchKey = CacheService::userSearchMinaIdList($auth_uid);
            $query = self::minaListSearchQuery($mina_ids, $region, $mina_crowd_ids, $inviteArray, $remove_invite);
            $ids = $query->orderBy('sort','asc')->orderBy($orderByName, $orderBy)
                         ->pluck('id')->toArray();
            //有已邀请的取出已邀请的换量信息id拼在后面
            if(!empty($inviteArray)){
                $inviteMinaIds = MinaInfo::wherein('id', $inviteArray)->orderBy('updated_at', 'desc')->pluck('id');
                $ids = empty($ids) ? $inviteMinaIds : array_merge_recursive($ids,$inviteArray);
            }
            CacheService::setCache($catchKey,$ids);
        }
        //查询出来如果在最后一页并且用户有邀请，那么将这些数据拼在最后,暂时没想到更好的方法$remove_invite = true;
        //                            $inviteArray[] = $v['mina_id'];
        if(!empty($inviteArray)&&$remove_invite&&count($list['list'])<$pageSize){
            $inviteList = MinaInfo::select(['id', 'uid', 'name', 'img', 'cid', 'con_min', 'con_max', 'exc_condition', 'label'])
                             ->wherein('id', $inviteArray)
                             ->orderBy('updated_at', 'desc')->get()->toArray();
            $list['list'] = empty($list['list']) ? $inviteList : array_merge_recursive($list['list'],$inviteList);

        }
        $list['invite'] = $invite;
        return $list;
    }

    public static function minaListSearchQuery($mina_ids = '', $region = '', $mina_crowd_ids = '', $inviteArray = '', $remove_invite = false){
        $query = MinaInfo::where('audit_type', Status::MINA_AUDIT_TRUE)
                         ->when(!empty($mina_ids), function ($query) use ($mina_ids) {
                             $query->wherein('cid', $mina_ids);
                         })
                         ->when(!empty($region), function ($query) use ($region) {
                             switch (count($region)) {
                                 case 1:
                                     $query->where('region', 'like', '%' . $region[0] . '%');
                                     break;
                                 case 2:
                                     $query->where(function ($query) use ($region) {
                                         $query->where('region', 'like', '%' . $region[0] . '%')
                                               ->orwhere('region', 'like', '%' . $region[1] . '%');
                                     });
                                     break;
                                 case 3:
                                     $query->where(function ($query) use ($region) {
                                         $query->where('region', 'like', '%' . $region[0] . '%')
                                               ->orwhere('region', 'like', '%' . $region[1] . '%')
                                               ->orwhere('region', 'like', '%' . $region[2] . '%');
                                     });
                                     break;
                                 case 4:
                                     $query->where(function ($query) use ($region) {
                                         $query->where('region', 'like', '%' . $region[0] . '%')
                                               ->orwhere('region', 'like', '%' . $region[1] . '%')
                                               ->orwhere('region', 'like', '%' . $region[2] . '%')
                                               ->orwhere('region', 'like', '%' . $region[3] . '%');
                                     });
                                     break;
                                 default:
                                     break;
                             }
                         })
                         ->when(!empty($mina_crowd_ids), function ($query) use ($mina_crowd_ids) {
                             $query->where(function ($query) use ($mina_crowd_ids) {
                                 $query->wherein('top_age_ratio_name', $mina_crowd_ids)
                                       ->orwherein('top_sex_ratio_name', $mina_crowd_ids)
                                       ->orwherein('top_mobile_ratio_name', $mina_crowd_ids);
                             });
                         })
                         ->when($remove_invite, function ($query) use ($inviteArray) {
                             $query->wherenotin('id', $inviteArray);
                         });
        return $query;
    }

    public static function getMinaInfo($id)
    {
        return MinaInfo::select(['id', 'uid', 'name', 'cid', 'con_min', 'con_max', 'exc_condition', 'mina_remark'])
                       ->first()
                       ->toArray();
    }

}
