<?php 
return [
    'crowd' => [
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
         ],
    'crowd_type' => [
        'age' => 1,
        'sex' => 2,
        'mobile' => 3
    ],
    //换量信息标签
    'mina_label' => [
        '1' => "★平台担保 真实可靠"
    ],
    'USER_DAY_SHARE_MAX_TIMES' => 5,
    //产品基本信息
    'CROWD_QUESTION' => [
       'age' => [
           '1' => '17岁以下',
           '2' => '18-24岁',
           '3' => '25-29岁',
           '4' => '30-39岁',
           '5' => '40-49岁',
           '6' => '50岁以上',
       ],
       'sex' => [
           '1' => '男',
           '2' => '女'
       ],
       'mobile' => [
           '1' => 'iPhone',
           '2' => 'Android'
       ]
    ],
    //邀请弹窗类型
    'INVITE_TYPE' => ['agree',
                      'agree_or_not'
                    ],
    //每日重置邀请次数
    'REMAIN_APPLY_COUNT' => 5,
    //列表最大请求数
    'MAX_PAGE_SIZE' => 20,

    'DEFAULT_QINIU_PATH' => env('QINIU_DOMAIN', 'https://image.lingyiliebian.com/'),
];