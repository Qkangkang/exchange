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
use App\Models\ComplainList;

class ComplainController extends Controller
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
            $edit = $this->form()->edit($id);
            $content->body($edit);
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
        return Admin::form(ComplainList::class, function (Form $form){
            $form->tab('处理投诉信息', function ($form){
                $isExamines = Status::$handle_type;
                $radios = [];
                foreach($isExamines as $key => $isExamine){
                    $radios[$key] = $isExamine;
                }
                $form->radio('handle_type', '处理')->options($radios)->rules("required");
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
        return Admin::grid(ComplainList::class, function (Grid $grid){
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->mina_id('投诉换量信息')->display(function ($mina_id) {
                $mina = MinaInfo::find($mina_id);
                if ($mina) {
                    return $mina->name;
                } else {
                    return "用户不存在";
                }
            });
            $grid->uid('投诉人')->display(function ($uid) {
                $userInfo = User::find($uid);
                if ($userInfo) {
                    return $userInfo->nick_name;
                } else {
                    return "用户不存在";
                }
            });
            $grid->com_type('投诉类型')->display(function ($com_type) {
                return Status::$complaint_type[$com_type];
            });
            $grid->column("complain_remark", '描述');
            $grid->column("img1", "图片1")->display(function ($value){
                $str = empty($value) ? User::DEFAULT_AVATAR : $value;
                return "<img width='50px' src='{$str}' >";
            });
            $grid->column("img2", "图片2")->display(function ($value){
                $str = empty($value) ? User::DEFAULT_AVATAR : $value;
                return "<img width='50px' src='{$str}' >";
            });
            $grid->column("img3", "图片3")->display(function ($value){
                $str = empty($value) ? User::DEFAULT_AVATAR : $value;
                return "<img width='50px' src='{$str}' >";
            });
            $grid->handle_type('受理状态')->display(function ($handle_type) {
                return Status::$handle_type[$handle_type];
            });
            $grid->column("created_at", "创建时间");
            $grid->column("updated_at", "更新时间")->sortable();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
            $grid->filter(function ($filter){
                $filter->equal('mina_id', '投诉换量信息')->select('/api/minaInfoName');
            });

        });
    }
}
