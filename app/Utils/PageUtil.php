<?php
/**
 * Created by PhpStorm.
 * User: rain
 * Date: 2018/4/7
 * Time: 下午1:40
 */

namespace App\Utils;

class PageUtil
{
    public static function page($list = [], $page = 1, $pageSize = 10, $totalRecord = 0)
    {
        $pageCount = $totalRecord == 0 ? 0 : ceil($totalRecord / $pageSize);

        return ['list' => $list, 'page' => $page, 'page_size' => $pageSize, 'page_count' => $pageCount, 'total_record' => $totalRecord];
    }

}