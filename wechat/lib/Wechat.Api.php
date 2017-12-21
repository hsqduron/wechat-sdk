<?php
use Library\wechat\Wechat;
require_once "Wechat.Exception.php";
require_once "Wechat.Config.php";
require_once "Wechat.Data.php";
require_once 'Wechat.Crypt.php';
/**
 * @author wangcb	
 * @date: 2017年2月19日 下午2:35:49
 * 
 */

class WechatApi{
    /**
     * xml加解密响应
     * @author wangcb
     */
    public function xmlCryptResponse (&$result){
        $Crypt  = new WechatCrypt(
            WechatConfig::COMPONENT_TOKEN, 
            WechatConfig::COMPONENT_ENCODING_ASE_KEY, 
            WechatConfig::COMPONENT_APPID);
        $post_data  = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data       = $_GET;
        $from_xml   = '';
        $re         = $Crypt->decryptMsg($data['msg_signature'], $data['timestamp'], $data['nonce'], $post_data, $from_xml);
        if($re == 0){
            $result     = WechatResult::Init($from_xml);
            if(isset($result['ComponentVerifyTicket'])){
                self::config('VerifyTicket', $result['ComponentVerifyTicket']);
            }
        }
    }
    
    /**
     * 获取公众号的接口调用凭据和授权信息
     * @author wangcb
     * @param unknown $input
     */
    public function componentLoginPage($input){
        $input->SetAppid(WechatConfig::COMPONENT_APPID);
        $input->SetAppsecret(WechatConfig::COMPONENT_APPSECRET);
        $preAuthCode                = self::preAuthCode($input);
        $data['component_appid']    = $input->GetAppid();
        $data['pre_auth_code']      = $preAuthCode;
        $data['redirect_uri']       = $input->GetRedirectUri();
        return WechatConfig::COMPONENT_LOGIN . WechatConfig::COMPONENT_LOGIN_PAGE . http_build_query($data);
    }
    /**
     * 使用授权码换取公众号的接口调用凭据和授权信息
     * @author wangcb
     */
    public function componentQueryAuth($input){
        $input->SetAppid(WechatConfig::COMPONENT_APPID);
        $params = json_encode($input->GetValues());
        $token  = self::compomentAccessToken($input); 
        $result = self::postCurl($params, WechatConfig::API_URL_PREFIX . WechatConfig::API_QUERY_AUTH.'?component_access_token='.$token);
        $data   = json_decode($result,true);
        return $data['authorization_info'];
    }
    
    /**
     * 获取授权方的公众号帐号基本信息
     * @author wangcb
     * @param unknown $input
     */
    public function getAuthInfo($input){
        $token  = self::compomentAccessToken($input);
        $data   = $input->GetValues();
        $param['component_access_token'] = $token;
        $result = self::postCurl(json_encode($data), WechatConfig::API_URL_PREFIX . WechatConfig::API_AUTHORIZER_INFO . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取授权方的选项设置信息
     * @author wangcb
     * @param unknown $input
     */
    public function getAuthOption($input){
        $token  = self::compomentAccessToken($input);
        $data   = $input->GetValues();
        $param['component_access_token'] = $token;
        $result = self::postCurl(json_encode($data), WechatConfig::API_URL_PREFIX . WechatConfig::GET_AUTHORIZER_OPTION . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 设置授权方的选项信息
     * @author wangcb
     * @param unknown $input
     */
    public function setAuthOption($input){
        $token  = self::compomentAccessToken($input);
        $data   = $input->GetValues();
        $param['component_access_token'] = $token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::SET_AUTHORIZER_OPTION . http_build_query($param));
        return json_decode($result, true);
    }
    
    
    /**
     * 获取第三方平台接口调用凭据compoment_access_token
     * 对象包含component_appid,component_appsecret两个属性
     * @author wangcb
     */
    private function compomentAccessToken($input) {
        $token  =   self::config('ComponentAccessToken');
        if (time() > $token['time'] + $token['expires_in'] - 60){
            $input->SetTicket(self::config('VerifyTicket'));
            if(!$input->GetAppid()) $input->SetAppid(WechatConfig::COMPONENT_ACCESS_TOKEN);
            if(!$input->GetAppsecret()) $input->SetAppsecret(WechatConfig::COMPONENT_APPSECRET);
            $params = json_encode($input->GetValues());
            $result = self::postCurl($params, WechatConfig::API_URL_PREFIX . WechatConfig::COMPONENT_ACCESS_TOKEN);
            $data   = json_decode($result,true);
            if(isset($data['component_access_token'])){
                $data['time'] = time();
                self::config('ComponentAccessToken', $data);
                return $data['component_access_token'];
            }
        }else{
            return $token['component_access_token'];
        }
    }
    
    /**
     * 获取预授权码pre_auth_code
     * @author wangcb
     * @param unknown $input
     * @return mixed|unknown
     */
    private function preAuthCode($input){
        $token  =   self::config('PreAuthCode');
        if(time() > $token['time'] + $token['expires_in'] - 60){
            $params['component_appid']  = $input->GetAppid();
            $component_access_token     = self::compomentAccessToken($input);
            $result = self::postCurl(json_encode($params), WechatConfig::API_URL_PREFIX . WechatConfig::PRE_AUTH_CODE . 'component_access_token='.$component_access_token);
            $data   = json_decode($result, true);
            if(isset($data['pre_auth_code'])){
                $data['time'] = time();
                self::config('PreAuthCode', $data);
                return $data['pre_auth_code'];
            }
        }else{
            return $token['pre_auth_code'];
        }
    }
    public function clearPreAuthCode(){
        self::config('PreAuthCode', array());
    }
    /**
     * 授权公众号的接口调用凭
     * 1.判断授权公众号接口调用凭证是否过期
     * 2.获取（刷新）授权公众号的接口调用凭据（令牌）
     * @author wangcb
     */
    public function authAccessToken($appid, $refresh_token){
        $input = new WechatOpenAuth();
        $input->SetAppid(WechatConfig::COMPONENT_APPID);
        $input->SetAppsecret(WechatConfig::COMPONENT_APPSECRET);
        $params['component_appid'] = $input->GetAppid();
        $params['authorizer_appid'] = $appid;
        $params['authorizer_refresh_token'] = $refresh_token;
        $token = self::compomentAccessToken($input);
        $result = self::postCurl(json_encode($params), WechatConfig::API_URL_PREFIX . WechatConfig::API_AUTHORIZER_TOKEN . 'component_access_token=' . $token);
        return json_decode($result, true);
    }
    
    /**
     * 临时配置文件
     * @author wangcb
     * @param unknown $name
     * @param string $value
     */
    private function config($name, $value=null){
        $data = require __DIR__.'/config.php';
        if(func_num_args() == 2){
            $data = array_merge((array)$data,array($name=>$value));
            file_put_contents(__DIR__.'/config.php', "<?php \nreturn ".var_export($data,true)."; \n?>");
        }else{
            return $data[$name];
        }
    }
    
    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WechatException
     * @author wangcb
     */
    private static function postCurl($params, $url, $useCert = false, $second = 30, &$header)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //如果有配置代理这里就设置代理
        if(WechatConfig::CURL_PROXY_HOST != "0.0.0.0" && WechatConfig::CURL_PROXY_PORT != 0){
            curl_setopt($ch,CURLOPT_PROXY, WechatConfig::CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, WechatConfig::CURL_PROXY_PORT);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, WechatConfig::SSLCERT_PATH);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, WechatConfig::SSLKEY_PATH);
        }else{
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        }
        //post提交方式
        if(!empty($params)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        //运行curl
        $data = curl_exec($ch);
        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        // 根据头大小去获取头信息内容
        $header     = substr($data, 0, $headerSize);
        $data       = substr($data, $headerSize, strlen($data));
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new WechatException("curl出错，错误码:$error");
        }
        
    }
    
    /**
     * 获取毫秒级别的时间戳
     * @author wangcb
     * @return unknown
     */
    private static function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }
    

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    
    /**
     * 自定义菜单创建
     * @author zhangzj
     * @param string    $access_token [公众平台授权令牌]
     * @param json      $data               [自定义菜单参数]
     */
    public function menuCreate($access_token, $data){
        $menu                  = new WechatMenu();
        $data                  = $menu->menuDataBuild($data);
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MENU_CREATE_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 自定义菜单删除
     * @author zhangzj
     * @param string $access_token
     */
    public function menuDelete($access_token) {
        $param['access_token'] = $access_token;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::MENU_DELETE_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 自定义菜单修改
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function menuModify($access_token, $data) {
        return self::menuCreate($access_token, $data);
    }

    /**
     * 自定义菜单查询
     * @author zhangzj
     * @param string $access_token
     */
    public function menuQuery($access_token) {
        $param['access_token'] = $access_token;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::MENU_GET_URL . http_build_query($param));
        $result = json_decode($result, true);
        $menu   = new WechatMenu();
        $data   = $menu->menuDataParse($result);
        return $data;
    }

    /**
     * 获取自定义菜单配置
     * @author zhangzj
     * @param string $access_token
     */
    public function menuQueryAll($access_token) {
        $param['access_token'] = $access_token;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::GET_CURRENT_SELFMENU_INFO_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 创建标签
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userTagCreate($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->creatUserTagDataBuild($data, 'add');
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAG_CREATE_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 获取标签
     * @author zhangzj
     * @param string $access_token
     */
    public function userTagQuery($access_token){
        $param['access_token'] = $access_token;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::TAG_GET_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 编辑标签
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userTagUpdate($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->creatUserTagDataBuild($data, 'modify');
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAG_UPDATE_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 删除标签
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userTagDelete($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->creatUserTagDataBuild($data, 'del');
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAG_DELETE_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 获取标签下粉丝列表
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userListTag($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->getUserTagDataBuild($data);
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::USER_TAG_GET_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 批量为用户打标签
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userAddTags($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->addUserTagDataBuild($data);
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAGS_MEMBERS_BATCHTAGGING_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 批量为用户取消标签
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userDelTags($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->addUserTagDataBuild($data);
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAGS_MEMBERS_BATCHUNTAGGING_URL . http_build_query($param));
        return json_decode($result, true);
    }

    /**
     * 获取用户身上的标签列表
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userGetByTags($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->getUserByTagDataBuild($data);
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAGS_GETIDLIST_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
     /**
     * 设置用户备注名
     * @author zhangzj
     * @param string $access_token
     * @param json   $data
     */
    public function userUpdateRemark($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->userMarkDataBuild($data);
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::USER_INFO_UPDATEREMARK_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取用户基本信息
     * @author zhangzj
     * @param string   $access_token
     * @param string   $openid
     * @param string  $lang                     [语言版本][zh_CN 简体，zh_TW 繁体，en 英语]
     */
    public function userInfoQuery($access_token, $openid, $lang='zh_CN'){
        $param['access_token'] = $access_token;
        $param['openid']       = $openid;
        $param['lang']         = $lang;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::USER_INFO_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 批量获取用户基本信息
     * @author zhangzj
     * @param string    $access_token
     * @param json      $data
     */
    public function userInfoBatchQuery($access_token, $data){
        $userTags              = new WechatUserTags();
        $data                  = $userTags->userInfoBatchGetDataBuild($data);
        $param['access_token'] = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::USER_INFO_BATCHGET_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取用户列表
     * @author zhangzj
     * @param string   $access_token
     * @param string  $next_openid          [第一个拉取的OPENID，默认从头开始拉取]
     */
    public function userListQuery($access_token, $next_openid = ''){
        $param['access_token']  = $access_token;
        if(!empty($next_openid))  $param['next_openid']   = $next_openid;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::USER_GET_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取公众号的黑名单列表
     * @author zhangzj
     * @param string   $access_token
     * @param string  $begin_openid         [开始拉取的OPENID，默认从头开始拉取]
     */
    public function memberGetBlackList($access_token, $begin_openid = ''){
        $param['access_token']  = $access_token;
        $data = '{"begin_openid": %s}';
        $data = sprintf($data, $begin_openid);
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAG_MEMBERS_GETBLACKLIST_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 拉黑用户
     * @author zhangzj
     * @param string   $access_token
     * @param json   $data  
     */
    public function memberBatchBlackList($access_token, $data){
        $userTags = new WechatUserTags();
        $data = $userTags->memberBatchBlackDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAG_MEMBERS_BATCHBLACKLIST_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 取消拉黑用户
     * @author zhangzj
     * @param string   $access_token
     * @param json      $data
     */
    public function memberBatchUnblackList($access_token, $data){
        $userTags = new WechatUserTags();
        $data = $userTags->memberBatchBlackDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::TAG_MEMBERS_BATCHUNBLACKLIST_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 新增临时素材
     * @author zhangzj
     * @param string   $access_token
     * @param json      $data
     */
    public function tempMediaUpload($access_token, $data){
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MEDIA_UPLOAD_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取临时素材
     * @author zhangzj
     * @param string   $access_token
     * @param string   $media_id
     */
    public function tempMediaGet($access_token, $media_id){
        $param['access_token']  = $access_token;
        $param['media_id']         = $media_id;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::MEDIA_GET_URL . http_build_query($param));
        return $result;
    }
    
    /**
     * 新增永久图文素材
     * @author zhangzj
     * @param string   $access_token
     * @param json      $data
     */
    public function materialAddNews($access_token, $data){
        $media = new WechatMedia();
        $data = $media->uploadNewsDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MATERIAL_ADD_NEWS_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 新增永久图片素材
     * @author zhangzj
     * @param string    $access_token 
     * @param json      $data
     */
    public function mediaUploadImg($access_token, $data){
        $media = new WechatMedia();
        $data = $media->uploadMediaDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MEDIA_UPLOADIMG_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 新增其他类型永久素材
     * @author zhangzj
     * @param string    $access_token 
     * @param string    $type   [媒体文件类型，图片/image、语音/voice、视频/video、缩略图/thumb]
     * @param json      $data
     */
    public function mediaUpload($access_token, $type, $data,$description){
        $media = new WechatMedia();
        $data = $media->uploadMediaDataBuild($data,$description);
        $param['access_token']  = $access_token;
        $param['type'] = $type;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MATERIAL_ADD_MATERIAL_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取永久素材
     * @author zhangzj
     * @param string   $access_token
     * @param json     $data
     */
    public function materialGet($access_token, $data, &$header){
        $media = new WechatMedia();
        $data = $media->getMediaDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MATERIAL_GET_MATERIAL_URL . http_build_query($param), $useCert = false, $second = 30, $header);
        $arr    = json_decode($result, true);
        return is_null($arr) ? $result : $arr;
    }
    
    /**
     * 删除永久素材
     * @author zhangzj
     * @param string   $access_token
     * @param json     $data
     */
    public function materialDel($access_token, $data){
        $media = new WechatMedia();
        $data = $media->getMediaDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MATERIAL_DEL_MATERIAL_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 修改永久图文素材
     * @author zhangzj
     * @param string   $access_token
     * @param json     $data
     */
    public function materialUpdate($access_token, $data){
        $media = new WechatMedia();
        $data = $media->modifyNewsDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MATERIAL_UPDATE_NEWS_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取素材总数
     * @author zhangzj
     * @param string   $access_token
     */
    public function materialCount($access_token){
        $param['access_token']  = $access_token;
        $result = self::postCurl('', WechatConfig::API_URL_PREFIX . WechatConfig::MATERIAL_GET_MATERIALCOUNT_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 获取素材列表
     * @author zhangzj
     * @param string   $access_token
     * @param json     $data
     */
    public function materialBatchGetList($access_token, $data){
        $media = new WechatMedia();
        $data = $media->getMediaListDataBuild($data);
        $param['access_token']  = $access_token;
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::MATERIAL_BATCHGET_MATERIAL_URL . http_build_query($param));
        return json_decode($result, true);
    }
    
    /**
     * 消息发送
     * @author wangcb
     */
    public function msgSend($msg){
        $Crypt  = new WechatCrypt(
            WechatConfig::COMPONENT_TOKEN,
            WechatConfig::COMPONENT_ENCODING_ASE_KEY,
            WechatConfig::COMPONENT_APPID);
        $input  = new WechatMsg();
        $result = call_user_func(array($input,$msg['MsgType']),$msg);
        return $result;
    }
    /**
     * 获取公众号自动回复配置文件
     * @author wangcb
     */
    public function getMsgConfig($access_token){
        $result = self::postCurl(null, WechatConfig::API_URL_PREFIX . WechatConfig::GET_CURRENT_AUTOREPLAY_INFO."access_token=".$access_token);
        return json_decode($result,true);
    }
    /**
     * 添加客服
     * @author wangcb
     */
    public function kfAdd($access_token, $input){
        $data   = $input->getValues();
        $result = self::postCurl(json_encode($data), WechatConfig::KF_API_URL_PREFIX . WechatConfig::KF_ADD . "access_token=".$access_token);
        return json_decode($result,true);
    }
    /**
     * 修改客服
     * @author wangcb
     */
    public function kfUpdate($access_token, $input){
        $data   = $input->getValues();
        $result = self::postCurl(json_decode($data), WechatConfig::KF_API_URL_PREFIX . WechatConfig::KF_UPDATE . "access_token=".$access_token);
        return json_decode($result,true);
    }
    /**
     * 删除客服
     * @author wangcb
     */
    public function kfDel($access_token, $input){
        $data   = $input->getValues();
        $result = self::postCurl(json_decode($data), WechatConfig::KF_API_URL_PREFIX . WechatConfig::KF_DEL . "access_token=".$access_token);
        return json_decode($result,true);
    }
    /**
     * 设置客服帐号的头像
     * @author wangcb
     */
    public function kfUploadHeadimg($access_token, $input, $file){
        $result = self::postCurl($file, WechatConfig::KF_API_URL_PREFIX . WechatConfig::KF_DEL . "access_token=".$access_token.'&kf_account='.$input->GetKfAccount());
        return json_decode($result,true);
    }
    /**
     * 获取客服列表
     * @author wangcb
     * @param unknown $access_token
     */
    public function kfGetList($access_token){
        $result = self::postCurl(null, WechatConfig::API_URL_PREFIX . WechatConfig::KF_DEL . "access_token=".$access_token);
        return json_decode($result,true);
    }
    /**
     * 发送客服消息
     * @author wangcb
     * @param unknown $token
     * @param unknown $data array
     */
    public function kfSendMsg($access_token, $data){
        $message = new WechatMsg();
        $data = $message->send($data);
        $result = self::postCurl($data, WechatConfig::API_URL_PREFIX . WechatConfig::KF_SEND_MSG . "access_token=".$access_token);
        return json_decode($result,true);
    }
    /**
     * 下载对账单
     * @author wangcb
     * @param unknown $input
     */
    public function downloadBill(WechatBill $input){
        $input->SetAppid(WechatConfig::$appid);
        $input->SetMch_id(WechatConfig::$mchid);
        $Prpcrypt = new Prpcrypt();
        $input->SetNonce_str($Prpcrypt->getRandomStr());
        $input->SetSign();
        $xml = $input->ToXml();
        $result = self::postCurl($xml, WechatConfig::PAY_API_PREFIX . WechatConfig::PAY_DOWNLOAD_BILL);
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$result,true)){
            return $result;
        }else {
            return $input->FromXml($result);
        }
    }
}