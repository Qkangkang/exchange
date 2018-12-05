<?php
/**
 * Created by PhpStorm.
 * User: rain
 * Date: 2018/4/14
 * Time: 上午12:54
 */

namespace App\Utils;

class StringUtil
{

    public static function filter($str)
    {
        $value = json_encode($str);
        $value = preg_replace("/\\\u[ed][0-9a-f]{3}\\\u[ed][0-9a-f]{3}/", "*", $value);//替换成*
        $value = json_decode($value);

        return $value; //输出 中文字符
    }

    /**
     * 函数makeSemiangle,把全角字符转换成半角;
     */
    public static function semiangle($string, $to = 1)
    {
        $patterns = [
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            'Ａ' => 'A',
            'Ｂ' => 'B',
            'Ｃ' => 'C',
            'Ｄ' => 'D',
            'Ｅ' => 'E',
            'Ｆ' => 'F',
            'Ｇ' => 'G',
            'Ｈ' => 'H',
            'Ｉ' => 'I',
            'Ｊ' => 'J',
            'Ｋ' => 'K',
            'Ｌ' => 'L',
            'Ｍ' => 'M',
            'Ｎ' => 'N',
            'Ｏ' => 'O',
            'Ｐ' => 'P',
            'Ｑ' => 'Q',
            'Ｒ' => 'R',
            'Ｓ' => 'S',
            'Ｔ' => 'T',
            'Ｕ' => 'U',
            'Ｖ' => 'V',
            'Ｗ' => 'W',
            'Ｘ' => 'X',
            'Ｙ' => 'Y',
            'Ｚ' => 'Z',
            'ａ' => 'a',
            'ｂ' => 'b',
            'ｃ' => 'c',
            'ｄ' => 'd',
            'ｅ' => 'e',
            'ｆ' => 'f',
            'ｇ' => 'g',
            'ｈ' => 'h',
            'ｉ' => 'i',
            'ｊ' => 'j',
            'ｋ' => 'k',
            'ｌ' => 'l',
            'ｍ' => 'm',
            'ｎ' => 'n',
            'ｏ' => 'o',
            'ｐ' => 'p',
            'ｑ' => 'q',
            'ｒ' => 'r',
            'ｓ' => 's',
            'ｔ' => 't',
            'ｕ' => 'u',
            'ｖ' => 'v',
            'ｗ' => 'w',
            'ｘ' => 'x',
            'ｙ' => 'y',
            'ｚ' => 'z',
            '（' => '(',
            '）' => ')',
            '〔' => '[',
            '〕' => ']',
            '【' => '[',
            '】' => ']',
            '〖' => '[',
            '〗' => ']',
            '“' => '[',
            '”' => ']',
            '‘' => '[',
            '’' => ']',
            '｛' => '{',
            '｝' => '}',
            '《' => '<',
            '》' => '>',
            '％' => '%',
            '＋' => '+',
            '—' => '-',
            '－' => '-',
            '～' => '-',
            '：' => ':',
            '。' => '.',
            '、' => ',',
            '，' => '.',
            '、' => '.',
            '；' => ',',
            '？' => '?',
            '！' => '!',
            '…' => '-',
            '‖' => '|',
            '”' => '"',
            '’' => '`',
            '‘' => '`',
            '｜' => '|',
            '〃' => '"',
            '　' => ' '
        ];
        $patterns = 2 == $to ? array_flip($patterns) : $patterns;

        return strtr($string, $patterns);
    }

    /**
     * 函数changeAutoCharset,改变字符的编码;
     *
     * @param contents   string|array [必须] 需要转行的数据;
     * @param from enum('gbk','utf-8') [可选] 原始编码,默认是@gbk编码;
     * @param to enum('gbk','utf-8') [可选] 目标编码,默认是@utf-8编码;
     *
     * @return string;
     */
    public static function autoCharset($contents, $from = 'gbk', $to = 'utf-8', $changeKeyCharset = false)
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($contents)
            || (is_scalar($contents)
                && !is_string($contents))){
            return $contents;
        }

        if (is_string($contents)){
            return function_exists('mb_convert_encoding') ? mb_convert_encoding($contents, $to, $from) : (function_exists('iconv') ? iconv($from, $to, $contents) : $contents);
        }elseif (is_array($contents)){
            $_contents = [];
            foreach($contents as $key => $value){
                $_key = $changeKeyCharset ? call_user_func(__METHOD__, $key, $from, $to) : $key;
                $_contents[$_key] = call_user_func(__METHOD__, $value, $from, $to);
            }

            return $_contents;
        }else{
            return $contents;
        }
    }

    /**
     * 函数msubstr,实现中文截取字符串;
     *
     * @param   str string [必选] 需要截取的字符串;
     * @param   length int [必须] 截取字符的长度,按照一个汉字的长度算作一个字符;
     * @param   start string [可选] 从那里开始截取;
     * @param   suffix string [可选] 截取字符后加上的后缀,默认为@...;
     * @param   charset enum('gbk','utf-8') [可选] 字符的编码,默认为@utf-8;
     *
     * @return string;
     */
    public static function msubstr($str, $start = 0, $length = null, $suffix = '...', $charset = 'utf-8')
    {
        $length = null === $length ? strlen($length) : $length;

        switch($charset){
            case 'utf-8':
                $charLen = 3;
                break;
            case 'UTF8':
                $charLen = 3;
                break;
            default:
                $charLen = 2;
        }
        // 小于指定长度，直接返回
        if (strlen($str) <= ($length * $charLen)){
            return $str;
        }elseif (function_exists('mb_substr')){
            $slice = mb_substr($str, $start, $length, $charset);
        }elseif (function_exists('iconv_substr')){
            $slice = iconv_substr($str, $start, $length, $charset);
        }else{
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }

        return $slice . $suffix;
    }

    //去除全角空白符
    public static function trimCN($string)
    {
        return preg_replace('/(^[\s\x{3000}]*)|([\s\x{3000}]*$)/u', '', strval($string));
    }

    /**
     * 中英文混杂字符串截取
     *
     * @param string $string
     * 原字符串
     * @param integer $length
     * 截取的字符数
     * @param string $etc
     * 省略字符
     * @param string $charset
     * 原字符串的编码
     *
     * @return string
     */
    public static function substrCN($string, $length = 80, $charset = 'UTF-8', $etc = '...')
    {
        if (mb_strwidth($string, 'UTF-8') <= $length){
            return $string;
        }

        return mb_strimwidth($string, 0, $length, '', $charset) . $etc;
    }

    //    /**
    //     * 中英文混杂字符串截取
    //     *
    //     * @param string $string 原字符串
    //     * @param integer $length 截取字数(中英各代表一个字)
    //     * @param string $charset 编码
    //     */
    //    public static function substrCn($string, $length = 80, $charset = 'UTF-8')
    //    {
    //        $i = $k = $nextstart = 0;
    //
    //        $split_count = ceil(strlen($string) / ($length * 2));
    //        for ($ii = 0; $ii < $split_count; $ii++) {
    //            $en = $cn = 0;
    //            while ($k < $length) {
    //                if (preg_match("/[0-9a-zA-Z]/", $string[$i])) {
    //                    $en++; //纯英文
    //                } else {
    //                    $cn++; //非英文字节
    //                }
    //                $k = $cn / 3 + $en / 2;
    //                $i++;
    //            }
    //
    //            $split_len += $cn / 3 + $en; //最终截取长度
    //            $start = $nextstart;
    //            $nextstart += floor($split_len);
    //            $tmpstr = mb_substr($string, $start, $split_len, $charset);
    //            if (!empty($tmpstr)) {
    //                $split_array[] = mb_substr($string, $start, $split_len, $charset);
    //            }
    //        }
    //
    //        return $split_array;
    //    }

    /**
     * 函数_asciiHtmlEntityEncode,返回字符的html实体;
     *
     * @param str string    [必选]    需要转换的字符;
     *
     * @return string;
     */
    public static function htmlEntityEncode($str)
    {
        $len = strlen($str);
        $a = 0;
        $scill = '';
        while($a < $len){
            $ud = 0;
            if (ord($str{$a}) >= 0 && ord($str{$a}) <= 127){
                $ud = ord($str{$a});
                $a += 1;
            }elseif (ord($str{$a}) >= 192 && ord($str{$a}) <= 223){
                $ud = (ord($str{$a}) - 192) * 64 + (ord($str{$a + 1}) - 128);
                $a += 2;
            }elseif (ord($str{$a}) >= 224 && ord($str{$a}) <= 239){
                $ud = (ord($str{$a}) - 224) * 4096 + (ord($str{$a + 1}) - 128) * 64 + (ord($str{$a + 2}) - 128);
                $a += 3;
            }elseif (ord($str{$a}) >= 240 && ord($str{$a}) <= 247){
                $ud = (ord($str{$a}) - 240) * 262144 + (ord($str{$a + 1}) - 128) * 4096 + (ord($str{$a + 2}) - 128) * 64 + (ord($str{$a + 3}) - 128);
                $a += 4;
            }elseif (ord($str{$a}) >= 248 && ord($str{$a}) <= 251){
                $ud = (ord($str{$a}) - 248) * 16777216 + (ord($str{$a + 1}) - 128) * 262144 + (ord($str{$a + 2}) - 128) * 4096 + (ord($str{$a + 3})
                                                                                                                                  - 128) * 64
                      + (ord($str{$a + 4}) - 128);
                $a += 5;
            }elseif (ord($str{$a}) >= 252 && ord($str{$a}) <= 253){
                $ud = (ord($str{$a}) - 252) * 1073741824 + (ord($str{$a + 1}) - 128) * 16777216 + (ord($str{$a + 2}) - 128) * 262144 + (ord($str{$a
                                                                                                                                                 + 3})
                                                                                                                                        - 128) * 4096
                      + (ord($str{$a + 4}) - 128) * 64 + (ord($str{$a + 5}) - 128);
                $a += 6;
            }elseif (ord($str{$a}) >= 254 && ord($str{$a}) <= 255){
                $ud = false;
            }
            $scill .= "&#$ud;";
        }

        return $scill;
    }

    /**
     * 函数_asciiHtmlEntityDecode,把html实体转换为普通字符;
     *
     * @param  str string    [必选]    需要转换的字符;
     *
     * @return string;
     */
    public static function htmlEntityDecode($str)
    {
        preg_match_all('/(d{2,5})/', $str, $a);
        $a = $a[0];
        $utf = '';
        foreach($a as $dec){
            if ($dec < 128){
                $utf .= chr($dec);
            }elseif ($dec < 2048){
                $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            }else{
                $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096));
                $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            }
        }

        return $utf;
    }

    /**
     * 生成随机字符串
     *
     * @param $length integer
     * @param $type integer，1：字母(大小写)数字，2：字母(小写)数字，3：数字
     *
     * @return string;
     */
    public static function randomChar($length, $type = 1)
    {
        $strPol = [
            1 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz',
            2 => 'abcdefghijklmnopqrstuvwxyz',
            3 => '0123456789',
        ];

        $strPolLen = strlen($strPol[$type]);

        $string = '';
        for($i = 0; $i < $length; $i++){
            $string .= $strPol[$type][rand(0, $strPolLen - 1)];
        }

        return $string;
    }

    /**
     * 文本关键词高亮
     *
     * @param string $text 文本
     * @param array $keywords 关键词
     * @return string
     */
    public static function keywordHighlight($text, array $keywords, $template = '<font color="red">{{keyword}}</font>')
    {
        if (!is_string($text) || empty($text) || empty($keywords)){
            return $text;
        }

        $from = [];
        foreach($keywords as $keyword){
            $from[$keyword] = strtr($template, ['{{keyword}}' => $keyword]);
        }

        return strtr($text, $from);
    }

    public static function filterPartialUTF8Char($str)
    {
        $str = preg_replace("/[\\xC0-\\xDF](?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", "", $str);
        $str = preg_replace("/[\\xE0-\\xEF][\\x80-\\xBF]{0,1}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", "", $str);
        $str = preg_replace("/[\\xF0-\\xF7][\\x80-\\xBF]{0,2}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", "", $str);

        return $str;
    }
}
