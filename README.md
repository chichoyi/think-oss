# think-oss
thinkphp (>5.0) aliyun-oss 阿里云对象存储  腾讯云对象存储 支持切换只存本地

# 安装

    composer require chichoyi/think-oss

# 配置

    配置方式一：手动复制 vendor/chichoyi/think-oss/src/oss.php 到 application/extra/
    
    配置方式二: 在 application/extra/ 目录下创建文件 oss.php , 然后复制下一步的配置参数到该文件
    
    
## 配置参数
    return [
    
        //支持驱动：阿里云（oss）, 腾讯云(cos)
        'driver' => 'oss',
    
        //驱动连接参数
        'connection' => [
    
            //阿里云-oss
            'oss' => [
                'access_id' => '',
                'access_secret' => '',
                'endpoint' => '',
            ],
    
            //腾讯云-cos
            'cos' => [
                'access_id' => '',
                'access_secret' => '',
                'region' => '',
            ],
    
        ],
    
        //文件目录
        'directory' => [
            'DEFAULT' => [ 'dir' => 'default/', 'type' => 'default'],
            'PRIVATE' => [ 'dir' => 'private/default/', 'type' => 'private_default'],
            //...
        ],
    
        //生产桶
        'buckets' => [
            'default' => 'your bucket',
            'private_bucket' => 'your private bucket',
            //...
        ],
    
        //自动切换测试桶的标志, 0 生产环境  1 测试环境
        'test_sign' => 0,
    
        //测试桶
        'buckets_for_test' => [
            'default' => 'your bucket',
            'private_bucket' => 'your private bucket',
            //...
        ],
        
        //默认false；若为true，使用upload方法，将保存一份到oss，同时保存到本地
        'is_save_to_local' => 'false',
        
        //true - 不使用对象存储  false - 使用对象存储
        'un_oss' => false,
        //域名，上传成功返回可访问路径需要用到
        'domain' => 'http://localhost'
    ];
    
### 配置说明

    文件目录配置和生产桶或测试桶的关系是：文件目录子项的type参数就是生产桶或测试桶的索引（对应关系）,
    使用者可以根据规则灵活配置，文件目录的type 默认是default类型，如果有(private_)前缀的话将会自动识
    别该桶是私有桶(属性为私有)，即访问该图片需要授权签名，上传图片将会自动返回可访问的路径；
    公共桶(属性为公共读)不需添加此前缀
    
# 使用
    namespace app\index\controller;
    
    use Chichoyi\ThinkOss\Facade\Oss;
    use \think\Controller;
    
    class Index extend Controller
    {
        //上传图片
        $oss = Oss::upload('img'); 
        //第二个参数不传将自动使用default/文件夹
        
        //单图片上传返回格式如下
        //['path' => '', 'visit_path' => '']
        
        //多图片上传返回格式如下
        //[
        //  ['path' => '', 'visit_path' => '', 'faild_reason' => '', 'type' => 'success'],
        //  ['path' => '', 'visit_path' => '', 'faild_reason' => '', 'type' => 'error'],
        //]
    }
 
 # 文档 
 
 ### 1. 上传图片
      upload($input_name, $dir = 'DEFAULT', $rule = ['ext' => ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']])      //$input_name 表单的name,比如前端上传图片是 <input type='file' name='img'> 那么填的就是img了
      
      $dir 目录名称 对应的是application/extra/oss.php的directory的选项, 默认是DEFAUL
      
      $rule 验证规则 默认验证后缀'gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'
      [
          'ext' => [],  //检查文件后缀 
          'size' => '',  //检查文件大小
          'type' => ''//检查文件 Mime 类型
      ]
      
      单图片上传返回格式如下
      ['path' => '', 'visit_path' => '']
              
      多图片上传返回格式如下
      [
        ['path' => '', 'visit_path' => '', 'faild_reason' => '', 'type' => 'success'],
        ['path' => '', 'visit_path' => '', 'faild_reason' => '', 'type' => 'error'],
      ]
      
### 2. 删除图片
      delete($path) 
      
      $path 图片路径（相对路径或全路径，注：相对路径字符串第一个字符不能加斜杆/）
      
      返回格式如下
      ['code' => 20000, 'msg' => '操作成功', 'data' => '']
### 3. 获取图片访问路径
      getImgPath($path, $timeout = 3600)
      
      $path 图片相对路径
      
      $timeout 图片访问过期时间，默认3600s
      
      返回格式如下
      传空path将返回：‘图片路径不能为空’
      正常返回是：'https://'开头的全路径
      
      注意：不支持自定义域名的路径，需要cdn加速的自己重新获取图片访问路径方法
      
### 4. 将绝对路径转成相对路径
      handleUrl（$url）
      
      $url 图片绝对路径
      
      返回格式如下
      相对路径
     
### 5. 底层调用
      baseCall（$method, $arguments, $is_ret_original = false）
      
      $method 方法名 支持aliyun-oss-sdk包OssClient类的所有方法调用，使用者可根据需求自行调用sdk包的其他方法
      
      $arguments 参数，需要传数组格式
      
      $is_ret_original 是否返回源响应，调用成功默认返回true
      
# 作者言

    由于thinkphp官方不支持第三方对象存储，所以才有了这个拓展包出现。
    
    此版支持阿里云的对象存储和腾讯云对象存储，未来可能会支持七牛云，看反应吧。
    
    桶需要全部在同一个地区，不支持多个地区。
    
    若发现bug，还请多多指正，可以将Bug反馈到作者邮箱（chichoyi@163.com）。
    
   