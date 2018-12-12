<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Advert;
use App\Models\User;
use App\Utils\QiniuUtil;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Models\Status;
use App\Models\MinaInfo;
use App\Models\InviteInfo;
use Overtrue\LaravelFilesystem\Qiniu\QiniuStorageServiceProvider;
use zgldh\QiniuStorage\QiniuStorage;

class AdvertController extends Controller
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
        return Admin::form(Advert::class, function (Form $form){
            $form->tab('广告', function ($form){
                $form->text('name', '广告名称')->rules("required");
                $form->text('mark', '描述');
                $form->image('image', '广告图片')->rules("required");
                $form->text('app_id', 'appid')->rules("required");
                $form->text('app_path', '跳转路径')->rules("required");
                $form->radio('status', '审核状态')->options(Status::$advert_type)->rules('required');
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
        return Admin::grid(Advert::class, function (Grid $grid){
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->name('广告名称');
            $grid->mark('广告描述');
            $grid->column("image", "图片")->display(function ($value){
                return !empty($value) ? "<img src=\"" . config('minainfo.DEFAULT_QINIU_PATH') . $value . "\" style=\"width: 50px; height: 50px; \"/>" : '';
            });
            $grid->app_id('appid');
            $grid->app_path('跳转路径');
            $grid->column("status", '上架状态')->display(function ($value){
                return Status::$advert_type[$value];
            })->sortable();
            $grid->column("created_at", "创建时间");
            $grid->column("updated_at", "更新时间")->sortable();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
            $grid->disableRowSelector();
            //$grid->disableCreateButton();
            $grid->disableExport();
            $grid->filter(function ($filter){
                $filter->like('name', '广告名称');
                $filter->disableIdFilter();
                $filter->in('status', '上架状态')->radio(Status::$advert_type);
            });

        });
    }
}
