<?php
/**
 * 微信配置类
 * @author wangcb
 *
 */
class WechatConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * TODO: 修改这里配置为您自己申请的商户信息
	 * 微信公众号信息配置
	 * 
	 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
	 * 
	 * MCHID：商户号（必须配置，开户邮件中可查看）
	 * 
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置） 
	 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	 * 
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
	 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
	 * @var string
	 */
	 public static $appid      = '';
	 public static $mchid      = '';
	 public static $pay_key    = '';
	 public static $appsecret  = '';

	//=======【证书路径设置】=====================================
	/**
	 * TODO：设置商户证书路径
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
	 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
	 * @var path
	 */
	//	const SSLCERT_PATH = dirname(dirname(__FILE__)).'/cert/apiclient_cert.pem';
	//	const SSLKEY_PATH = dirname(dirname(__FILE__)).'/cert/apiclient_key.pem';
	const SSLCERT_PATH = '../cert/apiclient_cert.pem';
	const SSLKEY_PATH = '../cert/apiclient_key.pem';
	
	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	const CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	const CURL_PROXY_PORT = 0;//8080;
	
	//=======【上报信息配置】===================================
	/**
	 * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	const REPORT_LEVENL = 1;
	
	
	public static $auth_func = array(
	    1=>'消息管理权限', 
	    2=>'用户管理权限', 
	    3=>'帐号服务权限', 
	    4=>'网页服务权限',
	    5=>'微信小店权限',
	    6=>'微信多客服权限',
	    7=>'群发与通知权限',
	    8=>'微信卡券权限',
	    9=>'微信扫一扫权限',
	    10=>'微信连WIFI权限',
	    11=>'素材管理权限',
	    12=>'微信摇周边权限',
	    13=>'微信门店权限',
	    14=>'微信支付权限',
	    15=>'自定义菜单权限'
	);
	
	public static $auth_option = array(
	   'location_report'=>array(
	       'name' => '地理位置上报选项',
	       'config' => array(
	           0 => '无上报',
	           1 => '进入会话时上报',
	           2 => '每5s上报'
	       )
	   ),
	   'voice_recognize'=>array(
	       'name' => '语音识别开关选项',
	       'config' => array(
	           0 => '关闭语音识别',
	           1 => '开启语音识别'
	       )
	   ),
	   'customer_service'=>array(
	       'name' => '多客服开关选项',
	       'config' => array(
	           0 => '关闭多客服',
	           1 => '开启多客服'
	       )
	   )
	);
	
	//微信表情符号
	public static $emoji = array(
	    "/::)","/::~","/::B","/::|","/:8-)","/::<","/::$","/::X","/::Z","/::'(",
	    "/::-|","/::@","/::P","/::D","/::O","/::(","/::+","/:--b","/::Q","/::T",
	    "/:,@P","/:,@-D","/::d","/:,@o","/::g","/:|-)","/::!","/::L","/::>","/::,@",
	    "/:,@f","/::-S","/:?","/:,@x","/:,@@","/::8","/:,@!","/:!!!","/:xx","/:bye",
	    "/:wipe","/:dig","/:handclap","/:&-(","/:B-)","/:<@","/:@>","/::-O","/:>-|",
	    "/:P-(","/::'|","/:X-)","/::*","/:@x","/:8*","/:pd","/:<W>","/:beer","/:basketb",
	    "/:oo","/:coffee","/:eat","/:pig","/:rose","/:fade","/:showlove","/:heart",
	    "/:break","/:cake","/:li","/:bome","/:kn","/:footb","/:ladybug","/:shit","/:moon",
	    "/:sun","/:gift","/:hug","/:strong","/:weak","/:share","/:v","/:@)","/:jj","/:@@",
	    "/:bad","/:lvu","/:no","/:ok","/:love","/:<L>","/:jump","/:shake","/:<O>","/:circle",
	    "/:kotow","/:turn","/:skip","/:oY","/:#-0","/:hiphot","/:kiss","/:<&","/:&>"
	);
	
	/**
	 * 第三方平台配置
	 * @var unknown
	 */
	const COMPONENT_APPID          = '';
	const COMPONENT_APPSECRET      = '';
	const COMPONENT_TOKEN          = '';
	const COMPONENT_ENCODING_ASE_KEY='';
	
	
	const MSGTYPE_TEXT 		= 'text';
	const MSGTYPE_IMAGE 	= 'image';
	const MSGTYPE_LOCATION 	= 'location';
	const MSGTYPE_LINK 		= 'link';    	//暂不支持
	const MSGTYPE_EVENT 	= 'event';
	const MSGTYPE_MUSIC 	= 'music';    	//暂不支持
	const MSGTYPE_NEWS 		= 'news';
	const MSGTYPE_VOICE 	= 'voice';
	const MSGTYPE_VIDEO 	= 'video';
	
	const EVENT_SUBSCRIBE 	= 'subscribe';      //订阅
	const EVENT_UNSUBSCRIBE = 'unsubscribe'; 	//取消订阅
	const EVENT_LOCATION 	= 'LOCATION';       //上报地理位置
	const EVENT_ENTER_AGENT = 'enter_agent';   	//用户进入应用
	
	const EVENT_MENU_VIEW 		   = 'view'; 				//菜单 - 点击菜单跳转链接
	const EVENT_MENU_CLICK         = 'click';              //菜单 - 点击菜单拉取消息
	const EVENT_MENU_SCAN_PUSH 	   = 'scancode_push';      //菜单 - 扫码推事件(客户端跳URL)
	const EVENT_MENU_SCAN_WAITMSG  = 'scancode_waitmsg'; 	//菜单 - 扫码推事件(客户端不跳URL)
	const EVENT_MENU_PIC_SYS       = 'pic_sysphoto';       //菜单 - 弹出系统拍照发图
	const EVENT_MENU_PIC_PHOTO     = 'pic_photo_or_album'; //菜单 - 弹出拍照或者相册发图
	const EVENT_MENU_PIC_WEIXIN    = 'pic_weixin';         //菜单 - 弹出微信相册发图器
	const EVENT_MENU_LOCATION      = 'location_select';    //菜单 - 弹出地理位置选择器
	const EVENT_MENU_MEDIA_ID      = 'media_id';    	   //菜单 - 未认证订阅号消息
	const EVENT_MENU_VIEW_LIMITED  = 'view_limited';   	   //菜单 - 未认证订阅号跳转链接
	
	const EVENT_SEND_MASS          = 'MASSSENDJOBFINISH';        //发送结果 - 高级群发完成
	const EVENT_SEND_TEMPLATE      = 'TEMPLATESENDJOBFINISH';//发送结果 - 模板消息发送结果
	
	const API_URL_PREFIX           = 'https://api.weixin.qq.com/cgi-bin';
	const KF_API_URL_PREFIX        = 'https://api.weixin.qq.com/customservice';
	const COMPONENT_LOGIN          = 'https://mp.weixin.qq.com/cgi-bin';
	const PAY_API_PREFIX           = 'https://api.mch.weixin.qq.com/pay';
	
	const USER_CREATE_URL 		= '/user/create?';
	const USER_UPDATE_URL 		= '/user/update?';
	const USER_DELETE_URL 		= '/user/delete?';
	const USER_BATCHDELETE_URL 	= '/user/batchdelete?';
	const USER_GET_URL 			= '/user/get?';
	const USER_LIST_URL 		= '/user/simplelist?';
	const USER_LIST_INFO_URL 	= '/user/list?';
	const USER_GETINFO_URL 		= '/user/getuserinfo?';
	const USER_TAG_GET_URL 		= '/user/tag/get?';
	const USER_INVITE_URL 		= '/invite/send?';
	const DEPARTMENT_CREATE_URL = '/department/create?';
	const DEPARTMENT_UPDATE_URL = '/department/update?';
	const DEPARTMENT_DELETE_URL = '/department/delete?';
	const DEPARTMENT_MOVE_URL 	= '/department/move?';
	const DEPARTMENT_LIST_URL 	= '/department/list?';
	const TAG_CREATE_URL 		= '/tags/create?';
	const TAG_UPDATE_URL 		= '/tags/update?';
	const TAG_DELETE_URL 		= '/tags/delete?';
	const TAG_GET_URL 			= '/tags/get?';
	const TAG_ADDUSER_URL 		= '/tag/addtagusers?';
	const TAG_DELUSER_URL 		= '/tag/deltagusers?';
	const TAG_LIST_URL 			= '/tag/list?';
	const MEDIA_UPLOAD_URL 		= '/media/upload?';
	const MEDIA_GET_URL 		= '/media/get?';
	const AUTHSUCC_URL 			= '/user/authsucc?';
	const MASS_SEND_URL 		= '/message/send?';
	const MENU_CREATE_URL 		= '/menu/create?';
	const MENU_GET_URL 			= '/menu/get?';
	const MENU_DELETE_URL 		= '/menu/delete?';
	const TOKEN_GET_URL 		= '/gettoken?';
	const TICKET_GET_URL 		= '/get_jsapi_ticket?';
	const CALLBACKSERVER_GET_URL           = '/getcallbackip?';
	const OAUTH_PREFIX                     = 'https://open.weixin.qq.com/connect/oauth2';
	const OAUTH_AUTHORIZE_URL              = '/authorize?';
	const TAGS_MEMBERS_BATCHTAGGING_URL    = '/tags/members/batchtagging?';
	const TAGS_MEMBERS_BATCHUNTAGGING_URL  = '/tags/members/batchuntagging?';
	const TAGS_GETIDLIST_URL               = '/tags/getidlist?';
	const GET_CURRENT_SELFMENU_INFO_URL    = '/get_current_selfmenu_info?';
	const USER_INFO_UPDATEREMARK_URL       = '/user/info/updateremark?';
	const USER_INFO_URL                    = '/user/info?';
	const USER_INFO_BATCHGET_URL           = '/user/info/batchget?';
	const TAG_MEMBERS_GETBLACKLIST_URL     = '/tags/members/getblacklist?';
	const TAG_MEMBERS_BATCHBLACKLIST_URL   = '/tags/members/batchblacklist?';
	const TAG_MEMBERS_BATCHUNBLACKLIST_URL = '/tags/members/batchunblacklist?';
	const MATERIAL_ADD_NEWS_URL            = '/material/add_news?';
	const MEDIA_UPLOADIMG_URL              = '/media/uploadimg?';
	const MATERIAL_ADD_MATERIAL_URL        = '/material/add_material?';
	const MATERIAL_GET_MATERIAL_URL        = '/material/get_material?';
	const MATERIAL_DEL_MATERIAL_URL        = '/material/del_material?';
	const MATERIAL_UPDATE_NEWS_URL         = '/material/update_news?';
	const MATERIAL_GET_MATERIALCOUNT_URL   = '/material/get_materialcount?';
	const MATERIAL_BATCHGET_MATERIAL_URL   = '/material/batchget_material?';
	const GET_CURRENT_AUTOREPLAY_INFO      = '/get_current_autoreply_info?';
	const KF_ADD                           = '/kfaccount/add?';
	const KF_UPDATE                        = '/kfaccount/update?';
	const KF_DEL                           = '/kfaccount/del?';
	const KF_UPLOAD_HEADIMG                = '/kfaccount/uploadheadimg?';
	const KF_GET_LIST                      = '/customservice/getkflist?';
	const KF_SEND_MSG                      = '/message/custom/send?';
	//第三方授权接口常量
	const COMPONENT_ACCESS_TOKEN   = '/component/api_component_token';
	const PRE_AUTH_CODE            = '/component/api_create_preauthcode?';
	const COMPONENT_LOGIN_PAGE     = '/componentloginpage?';
	const API_QUERY_AUTH           = '/component/api_query_auth';
	const API_AUTHORIZER_INFO      = '/component/api_get_authorizer_info?';
	const API_AUTHORIZER_TOKEN     = '/component/api_authorizer_token?';
	const GET_AUTHORIZER_OPTION    = '/component/api_get_authorizer_option?';
	const SET_AUTHORIZER_OPTION    = '/component/api_set_authorizer_option?';
	//商户接口
	const PAY_DOWNLOAD_BILL            = '/downloadbill?';
}
