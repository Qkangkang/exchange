<?php


namespace App\Http\Models;

//字段状态枚举类
class Status
{
    //全局的配置
    //每页最大请求数
    const MAX_PAGE_SIZE = 20;
    //头条轮播请求条数
    const TOP_LINE_SIZE = 20;
    //头条轮播最长展示字数
    const TOP_LINE_MAX_WORD = 29;
    //【前台】
    //用户是否封禁
    const USER_ENABLED = 0;
    const USER_DISABLED = 1;
    public static $isForbid = [
        '' => ['title' => '全部'],
        self::USER_ENABLED => ['title' => '正常用户'],
        self::USER_DISABLED => ['title' => '封禁用户'],
    ];
    const USER_DISABLED_NOTICE = '你发布的信息暂不符合换量，账户已经被封禁';
    const USER_OTHER_DISABLED_NOTICE = '该用户发布的信息暂不符合换量，账户已经被封禁';
    const MINA_UNSANCTION_NOTICE = '该换量信息已下架';
    //const OTHERS_MINA_UNSANCTION_NOTICE = '该用户的换量信息已下架';
    //置换条件(新增的话配置文件minainfo也要改)
    const EXC_APPEND = 1;
    const EXC_AUTHORIZE = 2;
    const EXC_CLICK = 3;
    public static $exc_condition = [
        self::EXC_APPEND => '新增UV 1:1',
        self::EXC_AUTHORIZE => '授权用户 1:1',
        self::EXC_CLICK => '点击UV 1:1'
    ];
    //邀请信息状态
    const INVITATIONS = 1;
    const INVITATIONS_AGREE = 2;
    const INVITATIONS_REFUSE = 3;
    public static $inv_type = [
        self::INVITATIONS => '邀请中',
        self::INVITATIONS_AGREE => '已同意',
        self::INVITATIONS_REFUSE => '已拒绝'
    ];
    //投诉受理状态
    const HANDLE_FALSE = 1;
    const HANDLE_TRUE = 2;
    public static $handle_type = [
        self::HANDLE_FALSE => '未受理',
        self::HANDLE_TRUE => '已受理',
    ];
    //换量信息审核状态
    const MINA_AUDIT_NOT = 0;
    const MINA_AUDIT_TRUE = 1;
    const MINA_AUDIT_FALSE = 2;
    public static $mina_audit_type = [
        self::MINA_AUDIT_NOT => '未审核',
        self::MINA_AUDIT_TRUE => '已通过',
        self::MINA_AUDIT_FALSE => '未通过',
    ];
    //模板消息类型
    //投诉类型
    const COMPLAINT_SHAM = 1;
    const COMPLAINT_INCONFORMITY = 2;
    const COMPLAINT_AD = 3;
    const COMPLAINT_OTHERS = 4;
    const COMPLAINT_CHEAT = 5;
    public static $complaint_type = [
        self::COMPLAINT_SHAM => '扣量',
        self::COMPLAINT_INCONFORMITY => '拖款严重',
        self::COMPLAINT_AD => '中介',
        self::COMPLAINT_OTHERS => '不结算',
        self::COMPLAINT_CHEAT => '骗子'
    ];
    //广告状态
    const ADVERT_PUT_ON = 1;
    const ADVERT_PUT_OFF = 0;
    public static $advert_type = [
        self::ADVERT_PUT_ON => '上架',
        self::ADVERT_PUT_OFF => '下架'
    ];
    //滚动消息显示状态
    const TOP_NEWS_SHOW = 1;
    const TOP_NEWS_HIDE = 0;
    //邀请状态
    const ACCEPT_INVITE_OR_NOT = 1;         //等待接受邀请窗口
    const ACCEPT_INVITE = 2;                //对方同意邀请窗口
    //缓存时间
    const CACHE_TEN_MINUTES = 10;
    const CACHE_THIRTY_MINUTES = 30;
    const CACHE_ONE_HOUR = 60;
    const CACHE_THREE_HOUR = 3 * 60;
    const CACHE_ONE_DAY = 60 * 24;
    const CACHE_ONE_WEEK = 60 * 24 * 7;
    //模块开关
    const SWITCH_OPEN = 1;
    const SWITCH_CLOSE = 0;
    //换量信息筛选条件
    public static $crowd_question_info = [
        '1' => '17岁以下',
        '2' => '18-24岁',
        '3' => '25-29岁',
        '4' => '30-39岁',
        '5' => '40-49岁',
        '6' => '50岁以上',
        '7' => '男性为主',
        '8' => '女性为主'
    ];
    public static $crowd_question_info_for_sql = [
        '1' => '17岁以下',
        '2' => '18-24岁',
        '3' => '25-29岁',
        '4' => '30-39岁',
        '5' => '40-49岁',
        '6' => '50岁以上',
        '7' => '男',
        '8' => '女'
    ];
    public static $crowd = [
        'age' => [
            '17岁以下'    => "",
            '18-24岁'    => "",
            '25-29岁'    => "",
            '30-39岁'    => "",
            '40-49岁'    => "",
            '50岁以上'    => "",
        ],
        'sex' => [
            '男'         => "",
            '女'         => "",
        ],
        'mobile' => [
            'iPhone' => "",
            'Android' => ""
        ]
    ];
    public static $crowd_type = [
        'age' => 1,
        'sex' => 2,
        'mobile' => 3
    ];
    public static $mobile_type = [
        '1' => 'iPhone为主',
        '2' => 'Android为主'
    ];
    public static $mobile_type_for_sql = [
        '1' => 'iPhone',
        '2' => 'Android'
    ];
    public static $region = [
        '1' => '一线城市',
        '2' => '二线省会城市',
        '3' => '三四线城市',
        '4' => '五六线乡镇'
    ];

    //小程序标签
    public static $mina_label = [
        '1' => "★平台担保 真实可靠"
    ];
    //流量变现文案
    const MINA_REALIZE_NOTICE = "小程序流量变现，换量合作可以添加小编微信号:";
    const MINA_REALIZE_WECHAT = "xiaoteng111";

    //特殊返回状态码约定
    const HAVE_THIS_MINA_IN_HALL_CODE = 300;
    const HAVE_THIS_MINA_IN_HALL_MSG = "换量大厅已有该小程序，是否前往查看";

    const RETURN_NOT_CREATE_MINA_CODE = 301;
    const RETURN_NOT_CREATE_MINA_IMAGE = "您未填写换量信息\n对方无法判断能否合作";

    const RETURN_INVITE_LIMIT_CODE = 302;
    const RETURN_INVITE_LIMIT_IMAGE = "邀请合作每天只有5次机会,今天您已经用完了哦";


}