<?php
/**
 * Created by PhpStorm.
 * User: chicho
 * Date: 2018/4/13
 * Time: 16:19
 */
namespace Chichoyi\ThinkOss;

use OSS\Core\OssException;
use OSS\OssClient;
use think\exception\ErrorException;
use Qcloud\Cos\Client;

class ThinkOss
{
    private $unOss;
    private $instance;
    private $driver;
    private $connection;
    private $directory;
    private $bucket;
    private $uploadInfo = [];

    public function __construct()
    {
        $checkConfig = $this->checkConfig();
        if ($checkConfig !== true)
            throw new ErrorException(0, $checkConfig,  __FILE__, __LINE__);

        if ($this->unOss !== true){
            switch ($this->driver){
                case 'oss':
                    $this->instance = new OssClient($this->connection['access_id'], $this->connection['access_secret'], $this->connection['endpoint']);
                    break;
                case 'cos':
                    $this->instance = new Client(
                        [
                            'region' => $this->connection['region'],
                            'credentials' => [
                                'secretId' => $this->connection['access_id'],
                                'secretKey' => $this->connection['access_secret']
                            ],
                        ]);
                    break;
                default:
                    throw new ErrorException(0, '驱动不存在',  __FILE__, __LINE__);
                    break;
            }
        }

        $this->directory = config('oss.directory');
    }

    /**
     * description 自定义文件路径上传文件
     * author chicho
     * @param $inputName
     * @param string $path
     * @param string $bucketKey
     * @param array $rule
     * @return array|bool|mixed
     * @throws ErrorException
     */
    public function uploadByPath($inputName, $path = '', $bucketKey = 'public', $rule = ['ext' => ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']])
    {
        if (empty($bucketKey)) return $this->ret(50000, '桶不能为空');
        $this->getBucket($bucketKey);
        $info = request()->file($inputName);
        if (empty($info)) return $this->ret(50000, '上传文件不能为空');

        $checkRule = $info->check($rule);
        if ($checkRule !== true) return $this->ret(50000, $info->getError());
        $content = file_get_contents($info->getInfo('tmp_name'));

        if (empty($path)) $path = $info->getInfo('name');
        if (!$this->unOss){
            $result = $this->putObject($path, $content);
            if ($result !== true) return $result;
            $this->uploadInfo = ['path' => $path, 'visit_path' => $this->getImgPath($path, 3600, $bucketKey)];
        }

        $result = $this->saveToLocal($info->getInfo('tmp_name'), $path);
        if ($this->unOss && $result > 0)
            $this->uploadInfo = ['path' => 'uploads/'.$path, 'visit_path' => $this->getImgPath('uploads/'.$path)];

        return $this->uploadInfo;
    }

    /**
     * description 上传图片
     * author chicho
     * @param $input_name
     * @param string $dir
     * @param array $rule
     * @return array|bool|mixed
     * @throws ErrorException
     */
    public function upload($input_name, $dir = 'DEFAULT', $rule = ['ext' => ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']]){
        if (!array_key_exists($dir, $this->directory))
            throw new ErrorException(0, '所选目录不存在',  __FILE__, __LINE__);
        $real_dir = $this->directory[$dir]['dir'];

        $this->getBucket($this->directory[$dir]['type']);

        $info = request()->file($input_name);
        if (empty($info)) return $this->ret(50000, '上传图片不能为空');

        if (is_array($info)){
            //多图上传
            foreach ($info as $file){
                $result = $file->check($rule);
                if ($result !== true){
                    $this->setUploadInfo('error', $file->getInfo('name'), $file->getError());
                    continue;
                }
                $path = $this->setFileName($file, $real_dir);
                $content = file_get_contents($file->getInfo('tmp_name'));

                if (!$this->unOss){
                    $result = $this->putObject($path, $content);
                    if ($result === true){
                        $this->setUploadInfo('success', $path, '', $this->getImgPath($path));
                    }else{
                        $this->setUploadInfo('error', $path, $result['msg']);
                    }
                }

                $result = $this->saveToLocal($file->getInfo('tmp_name'), $path);
                if ($this->unOss && $result > 0)
                    $this->setUploadInfo('success', 'uploads/'.$path, '', $this->getImgPath('uploads/'.$path));
            }
        }else{
            //单图片上传
            $check_rule = $info->check($rule);
            if ($check_rule !== true) return $this->ret(50000, $info->getError());
            $content = file_get_contents($info->getInfo('tmp_name'));
            $path = $this->setFileName($info, $real_dir);

            if (!$this->unOss){
                $result = $this->putObject($path, $content);
                if ($result !== true) return $result;
                $this->uploadInfo = ['path' => $path, 'visit_path' => $this->getImgPath($path)];
            }

            $result = $this->saveToLocal($info->getInfo('tmp_name'), $path);
            if ($this->unOss && $result > 0)
                $this->uploadInfo = ['path' => 'uploads/'.$path, 'visit_path' => $this->getImgPath('uploads/'.$path)];
        }

        return $this->uploadInfo;
    }

    /**
     * description 本地图片上传到云存储
     * author chicho
     * @param string $filePath
     * @param string $dir
     * @return array|bool|mixed
     * @throws ErrorException
     */
    public function localToOss($filePath = '', $dir = 'DEFAULT'){
        if (!is_file($filePath))
            return $this->ret(50000, '文件路径错误');

        $this->getBucket($this->directory[$dir]['type']);
        $path = $this->setFileName($filePath, $this->directory[$dir]['dir']);
        $content = file_get_contents($filePath);
        $result = $this->putObject($path, $content);
        if ($result !== true) return $result;
        $this->uploadInfo = ['path' => $path, 'visit_path' => $this->getImgPath($path)];

        return $this->uploadInfo;
    }

    /**
     * description 单或多图上传
     * author chicho
     * @param $path
     * @param $content
     * @return array|bool|mixed
     * @throws ErrorException
     */
    protected function putObject($path, $content){
        if ($this->driver == 'oss'){
            return $this->baseCall('putObject', [$this->bucket, $path, $content]);
        }elseif ($this->driver == 'cos'){
            return $this->baseCall('putObject', [['Bucket' => $this->bucket, 'Key' => $path, 'Body' => $content]]);
        }
    }

    /**
     * description 设置上传信息
     * author chicho
     * @param $type
     * @param $path
     * @param string $faildReason
     * @param string $visitPath
     */
    protected function setUploadInfo($type, $path, $faildReason = '', $visitPath = ''){
        array_push($this->uploadInfo, ['path' => $path, 'faild_reason' => $faildReason, 'type' => $type, 'visit_path' => $visitPath]);
    }

    /**
     * description 删除图片
     * author chicho
     * @param $path
     * @return array|bool|mixed
     * @throws ErrorException
     */
    public function delete($path){
        $path = $this->handleUrl($path);
        if ($path == '') return '图片路径不能为空';

        if ($this->unOss){
            @unlink($path);
            return $this->ret();
        }

        $bucket_info = $this->getBucketByPath($path);

        if ($this->driver == 'oss'){
            $result =  $this->baseCall('deleteObject', [$bucket_info['bucket'], $path]);
        }elseif ($this->driver == 'cos'){
            $result =  $this->baseCall('deleteObject', [['Bucket' => $bucket_info['bucket'], 'Key' => $path]]);
        }

        if ($result !== true) return $result;
        return $this->ret();
    }

    /**
     * description 获取图片访问路径
     * author chicho
     * @param $path
     * @param int $timeout
     * @return array|bool|mixed|string
     * @throws ErrorException
     */
    public function getImgPath($path, $timeout = 3600, $bucket = ''){
        if ($this->unOss){
            return config('oss.domain').$path;
        }

        $bucketInfo = [];
        if (empty($bucket)){
            $path = $this->handleUrl($path);
            if ($path == '') return '';
            $bucketInfo = $this->getBucketByPath($path);
            if (array_key_exists('code', $bucketInfo)){
                if ($bucketInfo['code'] == 50000){
                    return config('oss.domain').$path;
                }
            }
        }else{
            $this->getBucket($bucket);
            $bucketInfo['bucket'] = $this->bucket;
            $bucketInfo['type'] = $bucket;
        }

        $result = strpos($bucketInfo['type'], 'private_');
        if ($this->driver == 'oss'){
            if ($result !== false)
                return $this->baseCall('signUrl', [$bucketInfo['bucket'], $path, $timeout], true);
            return "https://".$bucketInfo['bucket'] . '.' . $this->connection['endpoint'] . '/' . $path;
        }elseif ($this->driver == 'cos'){
            if ($result !== false)
                return $this->baseCall('getObjectUrl', [$bucketInfo['bucket'], $path, '+'. $timeout / 60 .' minutes'], true);
            return "https://".$bucketInfo['bucket'] . '.cos.' . $this->connection['region'] . '.myqcloud.com/' . $path;
        }
    }

    /**
     * description 根据路径自动获取桶名称
     * author chicho
     * @param $path
     * @return array
     * @throws ErrorException
     */
    public function getBucketByPath($path){
        if (!count($this->directory)) throw new ErrorException(0, '配置目录不能为空',  __FILE__, __LINE__);
        $dirInfo = '';
        foreach ($this->directory as $key => $value){
            if (!is_array($value)) continue;
            if (!array_key_exists('dir', $value)) continue;
            if (strpos($path, $value['dir']) !== false){
                $dirInfo = $value;
                break;
            }
        }

        if ($dirInfo === '') return $this->ret(50000, '找不到图片对应的bucket');
        $this->getBucket($dirInfo['type']);
        return ['bucket' => $this->bucket, 'type' => $dirInfo['type']];
    }

    /**
     * description 处理url
     * author chicho
     * @param $url
     * @return bool|string
     */
    public function handleUrl($url){
        if (empty($url)) return '';
        if (!preg_match('/(http:\/\/)|(https:\/\/)/i', $url))
            return $url;
        $pathArr = parse_url($url);
        return substr($pathArr['path'], 1);
    }

    //下载文件
    public function download($path){

    }

    /**
     * description 本地保存文件
     * author chicho
     * @param $file
     * @param $path
     * @return bool
     */
    protected function saveToLocal($file, $path){
        if (!config('oss.is_save_to_local')) return false;
        $path = 'uploads/' . $path;
        $pathDir = str_replace(basename($path), '', $path);
        if (!file_exists($pathDir))
            mkdir ($pathDir, 0777, true );
        return file_put_contents($path, file_get_contents($file));
    }

    /**
     * description 定义返回格式
     * author chicho
     * @param int $code
     * @param string $msg
     * @param string $data
     * @return array
     */
    protected function ret($code = 20000, $msg = '操作成功', $data = ''){
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }

    /**
     * description 检查必须配置
     * author chicho
     * @return bool|string
     */
    protected function checkConfig(){
        $this->unOss = config('oss.un_oss');
        if ($this->unOss === true) return true;

        $driver = config('oss.driver');
        if (empty($driver)) return '请配置驱动';
        if (!in_array($driver, ['oss', 'cos'])){
            return '暂不支持该驱动';
        }

        $connection = config('oss.connection')[$driver];
        if ($driver == 'oss'){
            if ($connection['access_id'] == '' || $connection['access_secret'] == '' || $connection['endpoint'] == '')
                return '请配置连接参数';
        }
        if ($driver == 'cos'){
            if ($connection['access_id'] == '' || $connection['access_secret'] == '' || $connection['region'] == '')
                return '请配置连接参数';
        }

        $this->driver = $driver;
        $this->connection = $connection;
        return true;
    }


    /**
     * description 获取桶-配置
     * author chicho
     * @param $dir
     * @throws ErrorException
     */
    protected function getBucket($dir){
        //判断部署环境
        $key = 'buckets';
        if (config('oss.test_sign'))
            $key = 'buckets_for_test';
        $buckets = config('oss.'.$key);
        if (!array_key_exists($dir, $buckets))
            throw new ErrorException(0, 'bucket不存在',  __FILE__, __LINE__);
        $this->bucket = $buckets[$dir];
    }

    /**
     * description 设置文件名路径
     * author chicho
     * @param $info
     * @param $dir
     * @return string
     */
    protected function setFileName($info, $dir){
        if (is_string($info)){
            $path = $info;
            $filePath = $info;
        }else{
            $path = $info->getInfo('name');
            $filePath = $info->getInfo('tmp_name');
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $md5_name = md5_file($filePath).rand(1,999).'.'.$ext;
        return $dir . date('Ym/d', time()). '/'. $md5_name;
    }

    /**
     * description 自动调用其他方法
     * author chicho
     * @param $method
     * @param $arguments
     * @return array|bool|mixed
     * @throws ErrorException
     */
    public function __call($method, $arguments)
    {
        return $this->baseCall($method, $arguments, true);
    }

    /**
     * description 底层调用
     * author chicho
     * @param $method
     * @param $arguments
     * @param bool $isRetOriginal
     * @return array|bool|mixed
     * @throws ErrorException
     */
    public function baseCall($method, $arguments, $isRetOriginal = false){
        if ($this->driver == 'oss'){
            if (!in_array($method, get_class_methods($this->instance)))
                throw new ErrorException(0, '方法不存在',  __FILE__, __LINE__);
            try{
                $result = call_user_func_array(array($this->instance, $method), $arguments);
                if ($isRetOriginal) return $result;
                return true;
            }catch (OssException $e){
                return $this->ret(50000, $e->getMessage());
            }
        }elseif($this->driver == 'cos'){
            try{
                $result = call_user_func_array(array($this->instance, $method), $arguments);
                if ($isRetOriginal) return $result;
                return true;
            }catch (\Exception $e){
                return $this->ret(50000, $e->getMessage());
            }
        }

    }


}