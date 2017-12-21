<?php

namespace Library\wechat;

/**
 * @author wangcb	
 * @date: 2017年2月19日 下午2:06:48
 * 微信SDK入口文件
 * 
 */
class Wechat {
    protected $Api;
    /**
     * 构造函数
     * @author wangcb
     */
    function __construct() {
        require_once 'lib/Wechat.Api.php';
        $this->Api = new \WechatApi();
    }
    /**
     * 接受微信推送的component_verify_ticket
     * @author wangcb
     */
    function ticket(&$result){
        $this->Api->xmlCryptResponse($result);
    }
    /**
     * 获取公众号的接口调用凭据和授权信息
     * @author wangcb
     */
    function openLoginPage($redirect_uri){
        $Open   = new \WechatOpenAuth();
        $Open->SetRedirectUri($redirect_uri);
        $result = $this->Api->componentLoginPage($Open);
        return $result;
    }
    /**
     * 使用授权码换取公众号的接口调用凭据和授权信息
     * @author wangcb
     * @return string
     */
    function openQueryAuth($auth_code){
        $Open   = new \WechatOpenAuth();
        $Open->SetAuthCode($auth_code);
        $result = $this->Api->componentQueryAuth($Open);
        return $result;
    }
    /**
     * 获取授权方的公众号帐号基本信息
     * @author wangcb
     */
    function getAuthInfo($appid){
        $Open   = new \WechatOpenAuth();
        $Open->SetAppid(\WechatConfig::COMPONENT_APPID);
        $Open->SetAuthAppid($appid);
        $result = $this->Api->getAuthInfo($Open);
        return $result;
    }
    /**
     * 获取授权方的选项设置信息
     * @author wangcb
     * @param unknown $appid
     */
    function authOption($appid, $option, $value=null){
        $Open   = new \WechatOpenAuth();
        $Open->SetAuthAppid($appid);
        $Open->SetOptionName($option);
        if(!empty($value)){
            $Open->SetOptionValue($value);
            $result = $this->Api->setAuthOption($Open);
        }else{
            $result = $this->Api->getAuthOption($Open);
        }
        return $result;
    }
    /**
     * 获取选项名和选项值表
     * @author wangcb
     * @return multitype:multitype:string multitype:string
     */
    function getOptions(){
        return \WechatConfig::$auth_option;
    }
    /**
     * 获取（刷新）授权公众号的接口调用凭据（令牌）
     * @author wangcb
     */
    function authAccessToken($appid, $refresh_token){
        return $this->Api->authAccessToken($appid, $refresh_token);
    }
    /**
     * 消息接收
     * 1.接收普通消息
     * 2.接收事件推送
     * @author wangcb
     */
    function msgReceive(){
        $this->Api->xmlCryptResponse($result);
        return $result;
    }
    /**
     * 发送消息
     * 1.被动回复消息
     * @author wangcb
     */
    function msgSend($msg, $crypt = false){
        $Crypts = new \WechatCrypt(
            \WechatConfig::COMPONENT_TOKEN, 
            \WechatConfig::COMPONENT_ENCODING_ASE_KEY, 
            \WechatConfig::COMPONENT_APPID);
        $xml = $this->Api->msgSend($msg);
        if($crypt){
            $Crypts->encryptMsg($xml, $_GET['timestamp'], $_GET['nonce'], $encryptMsg);
            return $encryptMsg;
        }else{
            return $xml;
        }
    }
    
    /**
     * 获取公众号自动回复配置
     * @author wangcb
     */
    function getMsgConfig($access_token){
        return $this->Api->getMsgConfig($access_token);
    }
    /**
     * 添加客服
     * @author wangcb
     * @param unknown $access_token
     */
    function kfAdd($access_token, $kf_account, $nickname, $password) {
        $input  = new \WechatMsg();
        $input->SetKfAccount($kf_account);
        $input->SetKfNickName($nickname);
        $input->SetKfPassword($password);
        return $this->Api->kfAdd($access_token,$input);
    }
    /**
     * 修改客服
     * @author wangcb
     * @param unknown $access_token
     */
    function kfUpdate($access_token, $kf_account, $nickname, $password) {
        $input  = new \WechatMsg();
        $input->SetKfAccount($kf_account);
        $input->SetKfNickName($nickname);
        $input->SetKfPassword($password);
        return $this->Api->kfUpdate($access_token,$input);
    }
    /**
     * 修改客服
     * @author wangcb
     * @param unknown $access_token
     */
    function kfDel($access_token, $kf_account, $nickname, $password) {
        $input  = new \WechatMsg();
        $input->SetKfAccount($kf_account);
        $input->SetKfNickName($nickname);
        $input->SetKfPassword($password);
        return $this->Api->kfDel($access_token,$input);
    }
    /**
     * 修改客服
     * @author wangcb
     * @param unknown $access_token
     * @param $file array("media"=>"@http://domian/images/1.png",'form-data'=>array('filename'=>'/images/1.png','content-type'=>'image/png','filelength'=>'11011'));
     */
    function kfUploadHeadimg($access_token, $kf_account, $file) {
        $input  = new \WechatMsg();
        $input->SetKfAccount($kf_account);
        return $this->Api->kfDel($access_token, $input, $file);
    }
    /**
     * 获取客服列表
     * @author wangcb
     */
    function kfGetList($access_token){
        return $this->Api->kfGetList($access_token);
    }
    /**
     * 发送客服消息详情查看微信客服消息文档
     */
    function kfSendMsg($access_token,$data){
        return $this->Api->kfSendMsg($access_token,$data);
    }
    /**
     * 获取素材列表
     * @author wangcb
     */
    function getMediaList($access_token,$data){
        return $this->Api->materialBatchGetList($access_token, $data);
    }
    /**
     * 获取素材总数
     * @author zhangzj
     */
    public function materialCount($access_token){
        return $this->Api->materialCount($access_token);
    }
    /**
     * 获取永久素材
     * @author wangcb
     */
    function getMedia($access_token,$media_id, &$header){
        $result = $this->Api->materialGet($access_token, $media_id, $header);
        if(isset($result['news_item'])){
            array_walk($result['news_item'], function (&$v,$k){
                if(isset($v['content'])){
                    $v['content'] = str_replace(array('/cgi-bin/','/mp/newappmsgvote'), array('https://mp.weixin.qq.com/cgi-bin/','https://mp.weixin.qq.com/mp/newappmsgvote'), $v['content']);
                    $v['content']   = preg_replace_callback(array('/(<img[\s\S]+?)data-src="([\s\S]+?)"/','/(<iframe[\s\S]+?)data-src="([\s\S]+?)"/','/<mpvoice([\s\S]+?)src="([\s\S]+?)"([\s\S]+?)><\/mpvoice>/'), function($v){
                        if(strpos($v[0], 'mpvoice') !== false){
                            return '<iframe'.$v[1].'src="https://mp.weixin.qq.com'.$v[2].'"'.$v[3].'></iframe>';
                        }
                        if(strpos($v[1], 'img') !== false){
                            return $v[1].'src="/Wechat/Public/img?img='.$v[2].'"';
                        }
                        return $v[1].'src="'.$v[2].'"';
                    }, $v['content']);
                }
            });
        }
        return $result;
    }
    /**
     * 新建图文素材
     * @author wangcb
     */
    function addMediaNews($access_token,$data){
        array_walk($data, function(&$v){
            $v['content']   = urldecode(str_replace('/Wechat/Public/img?img=', '', $v['content']));
            $v['content']   = preg_replace_callback(array('/<iframe([\s\S^<>]*?)src="([\s\S^<>]*?)"([\s\S^<>]*?)><\/iframe>/'), function($v){
                if(strpos($v[3], 'voice_encode_fileid') !== false){
                    return '<mpvoice'.$v[1].'src="'.str_replace('https://mp.weixin.qq.com', '', $v[2]).'"'.$v[3].'></mpvoice>';
                }
                return $v[0];
            }, $v['content']);
        });
        return $this->Api->materialAddNews($access_token, $data);
    }
    /**
     * 上传素材
     */
    function upload($access_token, $type, $data, $description){
        return $this->Api->mediaUpload($access_token, $type, $data, $description);
    }
    /**
     * 修改图文素材
     * @author wangcb
     */
    function editMediaNews($access_token,$data){
        return $this->Api->materialUpdate($access_token, $data);
    }
    /**
     * 删除素材
     * @author wangcb
     * @param unknown $access_token 接口凭证
     * @param unknown $media_id 素材id
     */
    function delMedia($access_token,$media_id){
        return $this->Api->materialDel($access_token, $media_id);
    }
    /**
     * 自定义菜单创建
     * @author zhangzj
     * @param string    $access_token [公众平台授权令牌]
     * @param array     $data         [自定义菜单参数]
     */
    public function menuCreate($access_token, $data){
        return $this->Api->menuCreate($access_token, $data);
    }

    /**
     * 自定义菜单删除
     * @author zhangzj
     * @param string $access_token
     */
    public function menuDelete($access_token) {
        return $this->Api->menuDelete($access_token);
    }

    /**
     * 自定义菜单修改
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function menuModify($access_token, $data) {
        return $this->Api->menuModify($access_token, $data);
    }

    /**
     * 自定义菜单查询
     * @author zhangzj
     * @param string $access_token
     */
    public function menuQuery($access_token) {
        return $this->Api->menuQuery($access_token);
    }

    /**
     * 获取自定义菜单配置
     * @author zhangzj
     * @param string $access_token
     */
    public function menuQueryAll($access_token) {
        return $this->Api->menuQueryAll($access_token);
    }

    /**
     * 创建标签
     * @author zhangzj
     * @param string $access_token
     * @param string $data
     */
    public function userTagCreate($access_token, $data){
        return $this->Api->userTagCreate($access_token, $data);
    }

    /**
     * 获取标签
     * @author zhangzj
     * @param string $access_token
     */
    public function userTagQuery($access_token){
        return $this->Api->userTagQuery($access_token);
    }

    /**
     * 编辑标签
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function userTagUpdate($access_token, $data){
        return $this->Api->userTagUpdate($access_token, $data);
    }

    /**
     * 删除标签
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function userTagDelete($access_token, $data){
        return $this->Api->userTagDelete($access_token, $data);
    }

    /**
     * 获取标签下粉丝列表
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function userListTag($access_token, $data){
        return $this->Api->userListTag($access_token, $data);
    }

    /**
     * 批量为用户打标签
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function userAddTags($access_token, $data){
        return $this->Api->userAddTags($access_token, $data);
    }

    /**
     * 批量为用户取消标签
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function userDelTags($access_token, $data){
        return $this->Api->userDelTags($access_token, $data);
    }

    /**
     * 获取用户身上的标签列表
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function userGetByTags($access_token, $data){
        return $this->Api->userGetByTags($access_token, $data);
    }
    
     /**
     * 设置用户备注名
     * @author zhangzj
     * @param string $access_token
     * @param array  $data
     */
    public function userUpdateRemark($access_token, $data){
        return $this->Api->userUpdateRemark($access_token, $data);
    }
    
    /**
     * 获取用户基本信息
     * @author zhangzj
     * @param string   $access_token
     * @param string   $openid
     * @param string  $lang                     [语言版本][zh_CN 简体，zh_TW 繁体，en 英语]
     */
    public function userInfoQuery($access_token, $openid, $lang='zh_CN'){
        return $this->Api->userInfoQuery($access_token, $openid, $lang);
    }
    
    /**
     * 批量获取用户基本信息
     * @author zhangzj
     * @param string    $access_token
     * @param array     $data
     */
    public function userInfoBatchQuery($access_token, $data){
        return $this->Api->userInfoBatchQuery($access_token, $data);
    }
    
    /**
     * 获取用户列表
     * @author zhangzj
     * @param string   $access_token
     * @param string  $next_openid          [第一个拉取的OPENID，默认从头开始拉取]
     */
    public function userListQuery($access_token, $next_openid = ''){
        return $this->Api->userListQuery($access_token, $next_openid);
    }
    
    /**
     * 获取公众号的黑名单列表
     * @author zhangzj
     * @param string   $access_token
     * @param string  $begin_openid         [开始拉取的OPENID，默认从头开始拉取]
     */
    public function memberGetBlackList($access_token, $begin_openid = ''){
        return $this->Api->memberGetBlackList($access_token, $begin_openid);
    }
    
    /**
     * 拉黑用户
     * @author zhangzj
     * @param string   $access_token
     * @param json   $data  
     */
    public function memberBatchBlackList($access_token, $data){
        return $this->Api->memberBatchBlackList($access_token, $data);
    }
    
    /**
     * 取消拉黑用户
     * @author zhangzj
     * @param string   $access_token
     * @param json      $data
     */
    public function memberBatchUnblackList($access_token, $data){
        return $this->Api->memberBatchUnblackList($access_token, $data);
    }

    /**
     * 获取临时素材
     * @author zhangzj
     * @param string   $access_token
     * @param string   $media_id
     */
    public function tempMediaGet($access_token, $media_id){
        return $this->Api->tempMediaGet($access_token, $media_id);
    }
    
    /**
     * 清空授权码
     * @author wangcb
     */
    public function clearPreAuthCode() {
        return $this->Api->clearPreAuthCode();
    }
    /**
     * 下载对账单
     * @author wangcb
     */
    public function downloadBill($input){
        return $this->Api->downloadBill($input);
    }
}
