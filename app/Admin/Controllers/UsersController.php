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

class UsersController extends Controller
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
        return Admin::form(User::class, function (Form $form){
            $form->tab('用户基本信息', function ($form){
                $form->text('nick_name', '用户昵称')->rules("required");//required为名称规则，具体信息的定义在recourse=>lang=>zh_CN=>validation.php文件中
                $form->text('user_name', '姓名');
                $form->text('phone', '手机号');
                $form->text('company', '公司名');
                $form->text('remain_apply_count', '当天剩余申请次数');
                $form->text('openid', '用户openid');
                $form->text('unionid', '用户union_id');
                $form->radio('status', '是否封禁')->options([0 => '否', 1 => '是']);
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
        return Admin::grid(User::class, function (Grid $grid){
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->column("nick_name", '昵称');
            $grid->column("user_name", '姓名');
            $grid->column("avatar", "头像")->display(function ($value){
                $str = empty($value) ? User::DEFAULT_AVATAR : $value;
                return "<img width='50px' src='{$str}' >";
            });
            $grid->column("phone", '手机号码');
            $grid->column("wechat", '微信号');
            $grid->column("company", '公司名称');
            $grid->column("status", '用户状态')->display(function ($value){
                return Status::$isForbid[$value]['title'];
            })->sortable();
            $grid->column("apply_count", "累计申请次数");
            $grid->column("remain_apply_count", "剩余申请次数");
            $grid->column("login_at", '最后一次登录时间');
            $grid->column("created_at", "创建时间")->sortable();
            $grid->column("updated_at", "更新时间")->sortable();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableExport();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
            $grid->filter(function ($filter){
                //3.字段equal 筛选
                $filter->like('nick_name', '昵称');
                $filter->like('phone', '手机号码');
                $filter->like('company', '公司名');
                $filter->disableIdFilter();
                $isExamines = Status::$isForbid;
                $radios = [];
                foreach($isExamines as $key => $isExamine){
                    $radios[$key] = $isExamine['title'];
                }
                $filter->in('status', '用户状态')->radio($radios);
            });

        });
    }
}
