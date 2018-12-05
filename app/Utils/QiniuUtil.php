<?php
/**
 * Created by PhpStorm.
 * User: rain
 * Date: 2018/4/7
 * Time: 下午1:40
 */

namespace App\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class QiniuUtil
{
    public static function getDisk($name = 'qiniu')
    {
        return Storage::disk($name);
    }

    public static function getUploadToken()
    {
        $disk = self::getDisk();

        return $disk->getUploadToken();
        //return $disk->getUploadToken('folder/token.txt', 3600);
    }


    /**
     * 格式化URL地址
     */
    public static function buildUrl($path, $isQiniu = false)
    {
        $url = $isQiniu ? self::getQiniuCdnUrl() : self::getCdnUrl();

        return $url . $path;
    }

    /**
     * 生成文件扩展路径
     */
    public static function getFilePath($type, $fileName, $extension)
    {
        $directoryLevel = 4;
        $directoryNameLength = 7;

        $fileName = date('YmdHisu') . md5(json_encode([
                'file' => $fileName,
                'rand' => rand(10000000000, 99999999999)
            ]));

        $path = '';
        for($i = 1; $i <= $directoryLevel; $i++){
            $path .= substr($fileName, ($i - 1) * $directoryNameLength, $directoryNameLength) . '/';
        }
        return '/' . $type . '/' . $path . substr($fileName, $directoryLevel * $directoryNameLength) . '.'
               . $extension;
    }

    private static function getPath($type, $fileName)
    {
        $directoryLevel = 4;
        $directoryNameLength = 7;

        $fileName = date('YmdHisu') . md5(json_encode([
                'file' => $fileName,
                'rand' => rand(10000000000, 99999999999)
            ]));

        $path = '';
        for($i = 1; $i <= $directoryLevel; $i++){
            $path .= substr($fileName, ($i - 1) * $directoryNameLength, $directoryNameLength);
            if ($i < $directoryLevel){
                $path .= DIRECTORY_SEPARATOR;
            }
        }

        $path = DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $path;

        return $path;
    }

    /**
     * 生成文件的绝对路径
     */
    public static function getTruePath($path)
    {
        $file = self::getCdnPath() . $path;
        $dirName = dirname($file);

        File::isDirectory($dirName) or File::makeDirectory($dirName, 0777, true, true);

        return $file;
    }

    public static function getCdnPath()
    {
        return env('CDN_PATH', '/');
    }

    public static function getCdnUrl()
    {
        return env('CDN_URL', 'https://image.lingyiliebian.com');
    }

    public static function getQiniuCdnUrl()
    {
        return env('QINIU_CDN_URL', 'https://image.lingyiliebian.com');
    }

    public static function uploadBase64($content, $type = 'auction_qr_code', $extension = 'jpg', $fileName = '')
    {
        if (empty($fileName)){
            $fileName = Uuid::uuid1();
        }
        $filePath = QiniuUtil::getFilePath($type, $fileName, $extension);
        // $truePath = QiniuUtil::getTruePath($filePath);

        try{
            $disk = self::getDisk();
            $disk->put($filePath, $content);

            return self::buildUrl($filePath, true);
        }catch(\Exception $e){
            throw $e;
        }


    }

    public static function uploadFile($content, $type = 'res_auction', $fileName = '')
    {
        if (empty($fileName)){
            $fileName = Uuid::uuid1();
        }
        $path = QiniuUtil::getPath($type, $fileName);

        try{
            $disk = self::getDisk();
            $filePath = $disk->put($path, $content);

            return self::buildUrl('/' . $filePath, true);
        }catch(\Exception $e){
            throw $e;
        }
    }

    public static function demo()
    {
        //$disk = Storage::disk('qiniu');
        // create a file
        //$disk->put('avatars/1', $fileContents);

        // check if a file exists
        // $exists = $disk->has('file.jpg');

        // get timestamp
        // $time = $disk->lastModified('file1.jpg');
        // $time = $disk->getTimestamp('file1.jpg');

        // copy a file
        //$disk->copy('old/file1.jpg', 'new/file1.jpg');

        // move a file
        //$disk->move('old/file1.jpg', 'new/file1.jpg');

        // get file contents
        //$contents = $disk->read('folder/my_file.txt');

        // fetch url content
        //$file = $disk->fetch('folder/save_as.txt', 'http://www.baidu.com');

        // get file url
        //$url = $disk->getUrl('folder/my_file.txt');

        // get file upload token
        //        $token = $disk->getUploadToken('folder/my_file.txt');
        //        $token = $disk->getUploadToken('folder/my_file.txt', 3600);

        // get private url
        // $url = $disk->privateDownloadUrl('folder/my_file.txt');
    }
}