<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Models\Status;
use App\Models\MinaInfo;
use App\Services\MinaInfoService;
use App\Services\UserService;
use App\Models\MinaCategory;

class MinaInfoController extends Controller
{
    use ModelForm;
    public function index()
    {
        return Admin::content(function (Content $content){
            $content->header('header');
            $content->description('description');
            $content->body($this->grid());
        });
    }
    

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id){
            $content->header('header');
            $content->description('description');
            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content){
            $content->header('header');
            $content->description('description');
            $content->body($this->form());
        });
    }
    
    public function form()
    {
        return Admin::form(MinaInfo::class, function (Form $form){
            $form->tab('换量信息', function ($form){
                //用户列表
                $user = User::select('id','nick_name')->where('status','!=',Status::USER_DISABLED)
                            ->get()
                            ->toArray();
                $userList = [];
                $c_list = [];
                //类目列表
                $c_info = MinaCategory::select('id','name')->get()->toArray();
                foreach ($user as $k => $v){
                    $userList[$v['id']] = $v['nick_name'];
                }
                foreach ($c_info as $k => $v){
                    $c_list[$v['id']] = $v['name'];
                }
                //换量类型
                $radios = Status::$exc_condition;
                //标签
                $labelArray = Status::$mina_label;
                $label = [];
                foreach($labelArray as $key => $v){
                    $label[$key] = $v;
                }
                $type = Status::$mina_audit_type;
                $type_radios = [];
                foreach ($type as $k => $value){
                    $type_radios[$k] = $value;
                }
                $form->text('name', '名称')->rules("required");//required为名称规则，具体信息的定义在recourse=>lang=>zh_CN=>validation.php文件中
                $form->select('uid', '发布者')->options($userList)->rules('required');
                $form->select('cid', '所属类目')->options($c_list)->rules('required');
                $form->image('img', '图片')->rules("required");
                $form->radio('exc_condition', '换量条件')->options($radios)->rules("required");
                $form->radio('label','标签')->options($label);
                $form->radio('audit_type','审核状态')->options($type_radios)->rules('required');
                $form->text('con_min', '换量最小')->rules("required");
                $form->text('con_max', '换量最大')->rules("required");
                $form->text('sort','排序(默认99)')->rules("required");
                $form->text('mina_remark', '小程序备注');
                /*$form->hasMany('minaCrowdRatio', function (Form\NestedForm $form) {
                    $form->text('ratio_num');
                });*/
            });


        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(MinaInfo::class, function (Grid $grid){
            $grid->model()->orderBy('sort', 'asc')->orderBy('id','desc');
            $grid->id('ID')->sortable();
            $grid->sort('排序(默认99)')->sortable();
            $grid->user()->nick_name('发布者昵称');
            $grid->user()->user_name('发布者姓名');
            $grid->user()->wechat('发布者微信');
            $grid->user()->phone('发布者手机号');
            $grid->user()->company('所在公司');
            /*$grid->uid('发布者')->display(function ($uid) {
                $user = User::find($uid);
                if ($user) {
                    return $user->nick_name;
                } else {
                    return "用户不存在";
                }
            });*/
            $grid->column("name", '名称');
            $grid->column("img", "图片")->display(function ($value){
                $str = empty($value) ? User::DEFAULT_AVATAR : $value;
                return "<img width='50px' src='{$str}' >";
            });
            $grid->cid('所在类目')->display(function ($cid) {
                $exc_name = MinaInfoService::getMinaType($cid,'name');
                return $exc_name['name'];
            });
            $grid->column("con_min", '导量最小值');
            $grid->column("con_max", '导量最大值');
            $grid->column("success_count", '成功合作次数');

            $grid->column("exc_condition", '合作条件')->display(function ($value){
                $str = '';
                if(strstr($value,',')){
                    foreach (explode(',',$value) as $k => $v){
                        $str .= Status::$exc_condition[$v].',';
                    }
                }
                return !empty($str) ? $str : Status::$exc_condition[$value];
            })->sortable();
            $grid->column("label", '标签')->display(function ($value){
                if(empty($value)){
                    return '';
                }
                $str = '';
                if(strstr($value,',')){
                    foreach (explode(',',$value) as $k => $v){
                        $str .= Status::$mina_label[$v].',';
                    }
                }
                return !empty($str) ? $str : config('minainfo.mina_label')[$value];
            })->sortable();
            $grid->column("mina_remark", "备注");
            $grid->column("created_at", "创建时间");
            $grid->column("updated_at", "更新时间")->sortable();
            $grid->column("mina_code_image", "小程序二维码")->display(function ($value){
                $str = empty($value) ? User::DEFAULT_AVATAR : $value;
                return "<img width='50px' src='{$str}' >";
            });
            $grid->column('audit_type','审核状态')->display(function ($value){
                return Status::$mina_audit_type[$value];
            })->sortable();
            $grid->disableRowSelector();
            //$grid->disableCreateButton();
            $grid->disableExport();
            $grid->actions(function ($actions) {
                $key = $actions->getKey();
                $actions->append("<a href='/admin/invite_info?page=1&mina_id=$key'><i class='fa fa-eye'></i>查看合作信息</a>");
                $actions->disableDelete();
            });
            $grid->filter(function ($filter){
                $filter->like('name', '名称');
                $filter->equal('uid', '发布者')->select('/api/userNickName');
                $filter->equal('cid', '所在类目')->select('/api/minaCategoryName');
                $filter->disableIdFilter();
                $radios = Status::$exc_condition;
                $filter->in('status', '合作条件')->radio($radios);
                $filter->in('audit_type','审核状态')->radio(['0'=>'未审核','1'=>'已审核','2'=>'审核不通过']);
            });

        });
    }
}
