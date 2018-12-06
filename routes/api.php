<?php
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/', function (){
    return view('welcome');
})->name('register');

Route::namespace("Api")->group(function () {
    //登录
    Route::post("login", "UserController@login")->name("login");
    //轮播列表
    Route::get("mina/top_line","MinaInfoController@topLine");
    //换量列表
    Route::get("mina/show_list","MinaInfoController@showList");
    Route::post("mina/show_list","MinaInfoController@showList");
    //拉取通知
    Route::get("user/pull_notice","UserController@pullNotice");
    //微信消息推送
    Route::get("user/message","UserController@message");
    Route::post("user/message","UserController@message");
    Route::get("user/get_media","UserController@getMedia");
    Route::get("mina/push_excel_info","MinaInfoController@pushExcelInfo");
    //Route::get("user/test","UserController@test");
    //拉取通知
    Route::get("user/pull_notice","UserController@pullNotice");
    Route::get("user/pull_notice_count","UserController@pullInviteAcceptCount");
    Route::get("mina/get_screening_conditions","MinaInfoController@getScreeningConditions");
    //黑名单列表
    Route::get("invite/mina_black_list","InviteInfoController@minaBlackList");
    //小程序流量变现minaRealize
    Route::get("user/mina_realize","UserController@minaRealize");
    //登录权限
    Route:: group(['middleware' => 'auth.api'], function (){
        //广告列表advertList
        Route::get("user/advert_list","UserController@advertList");
        //展示换量详情
        Route::get("mina/show","MinaInfoController@show");
        //获取用户信息
        Route::get("user/get_user_info","UserController@getUserInfo");
        //存储formid信息
        Route::post("user/formid","UserController@formid");
        //授权更新用户信息
        Route::post("user/user_info","UserController@userInfo");
        //获取七牛上传token
        Route::get("mina/push_token","MinaInfoController@pushToken");
        //点击发布按钮信息界面
        Route::get("mina/click_release","MinaInfoController@clickRelease");
        //查询小程序重名
        Route::post("mina/check_mina_name","MinaInfoController@checkMinaName");
        //提交换量信息
        Route::post("mina/store","MinaInfoController@store");
        //换量信息投诉
        Route::post("mina/complain","MinaInfoController@complain");
        //修改用户信息
        Route::post("user/change","UserController@change");
        //邀请合作
        Route::post("invite/create","InviteInfoController@create");
        //分享增加邀请次数
        Route::post("invite/share","InviteInfoController@share");
        //同意或拒绝邀请
        Route::post("invite/agree_or_not","InviteInfoController@agreeOrNot");
        //发布记录
        Route::get("invite/release_list","InviteInfoController@releaseList");
        //合作记录
        Route::get("invite/invite_list","InviteInfoController@inviteList");
        //点赞与取消点赞
        Route::post("mina/like_or_not","MinaInfoController@likeOrNot");
        //更新时间
        Route::post("invite/update_release","InviteInfoController@updateRelease");
        //complainEnquiry
        Route::get("mina/complain_enquiry","MinaInfoController@complainEnquiry");
    });
    
    
});

    
