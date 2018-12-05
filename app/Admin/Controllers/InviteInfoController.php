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
use App\Models\InviteInfo;

class InviteInfoController extends Controller
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
        return Admin::form(InviteInfo::class, function (Form $form){
            $form->tab('合作信息', function ($form){
                $form->radio('status', '邀请状态')->options(Status::$inv_type);
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
        return Admin::grid(InviteInfo::class, function (Grid $grid){
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->uid('邀请人')->display(function ($uid) {
                $user = User::find($uid);
                if ($user) {
                    return $user->nick_name;
                } else {
                    return "用户不存在";
                }
            });
            $grid->b_uid('被邀请人')->display(function ($b_uid) {
                $b_user = User::find($b_uid);
                if ($b_user) {
                    return $b_user->nick_name;
                } else {
                    return "用户不存在";
                }
            });
            $grid->mina_id('换量信息')->display(function ($mina_id) {
                $mina_info = MinaInfo::find($mina_id);
                if ($mina_info) {
                    return $mina_info->name;
                } else {
                    return "用户不存在";
                }
            });
            $grid->column("status", '邀请状态')->display(function ($value){
                return Status::$inv_type[$value];
            })->sortable();
            $grid->column("created_at", "创建时间");
            $grid->column("updated_at", "更新时间")->sortable();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->filter(function ($filter){
                $filter->equal('uid', '邀请人')->select('/api/userNickName');
                $filter->equal('b_uid', '被邀请人')->select('/api/userNickName');
                $filter->equal('mina_id', '小程序')->select('/api/minaInfoName');
                $filter->disableIdFilter();
                $isExamines = Status::$inv_type;
                $radios = [];
                foreach($isExamines as $key => $isExamine){
                    $radios[$key] = $isExamine;
                }
                $filter->in('status', '合作条件')->radio($radios);
            });

        });
    }
}
