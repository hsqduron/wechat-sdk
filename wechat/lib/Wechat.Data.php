<?php

require_once "Wechat.Config.php";
require_once "Wechat.Exception.php";

/**
 * 
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出xml格式的参数、从xml读取数据对象等
 * @author wangcb
 *
 */
class WechatDataBase {

    protected $values = array();

    /**
     * 设置签名，详见签名生成算法
     * @param string $value 
     * */
    public function SetSign() {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return 值
     * */
    public function GetSign() {
        return $this->values['sign'];
    }

    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     * */
    public function IsSignSet() {
        return array_key_exists('sign', $this->values);
    }

    /**
     * 输出xml字符
     * @throws WechatException
     * */
    public function ToXml() {
        if (!is_array($this->values) || count($this->values) <= 0) {
            throw new WechatException("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($this->values as $key => $val) {
            if (is_numeric($val)) {
                $xml.="<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml.="<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WechatException
     */
    public function FromXml($xml) {
        if (!$xml) {
            throw new WechatException("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams() {
        $buff = "";
        foreach ($this->values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign() {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . WechatConfig::$pay_key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 获取设置的值
     */
    public function GetValues() {
        return $this->values;
    }
}

/**
 * 
 * 接口调用结果类
 * @author wangcb
 *
 */
class WechatPayResults extends WechatDataBase {

    /**
     * 
     * 检测签名
     */
    public function CheckSign() {
        //fix异常
        if (!$this->IsSignSet()) {
            throw new WechatException("签名错误！");
        }

        $sign = $this->MakeSign();
        if ($this->GetSign() == $sign) {
            return true;
        }
        throw new WechatException("签名错误！");
    }

    /**
     * 
     * 使用数组初始化
     * @param array $array
     */
    public function FromArray($array) {
        $this->values = $array;
    }

    /**
     * 
     * 使用数组初始化对象
     * @param array $array
     * @param 是否检测签名 $noCheckSign
     */
    public static function InitFromArray($array, $noCheckSign = false) {
        $obj = new self();
        $obj->FromArray($array);
        if ($noCheckSign == false) {
            $obj->CheckSign();
        }
        return $obj;
    }

    /**
     * 
     * 设置参数
     * @param string $key
     * @param string $value
     */
    public function SetData($key, $value) {
        $this->values[$key] = $value;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WechatException
     */
    public static function Init($xml) {
        $obj = new self();
        $obj->FromXml($xml);
        //fix bug 2015-06-29
        if ($obj->values['return_code'] != 'SUCCESS') {
            return $obj->GetValues();
        }
        $obj->CheckSign();
        return $obj->GetValues();
    }

}

/**
 * 
 * 回调基础类
 * @author wangcb
 *
 */
class WechatPayNotifyReply extends WechatDataBase {

    /**
     * 
     * 设置错误码 FAIL 或者 SUCCESS
     * @param string
     */
    public function SetReturn_code($return_code) {
        $this->values['return_code'] = $return_code;
    }

    /**
     * 
     * 获取错误码 FAIL 或者 SUCCESS
     * @return string $return_code
     */
    public function GetReturn_code() {
        return $this->values['return_code'];
    }

    /**
     * 
     * 设置错误信息
     * @param string $return_code
     */
    public function SetReturn_msg($return_msg) {
        $this->values['return_msg'] = $return_msg;
    }

    /**
     * 
     * 获取错误信息
     * @return string
     */
    public function GetReturn_msg() {
        return $this->values['return_msg'];
    }

    /**
     * 
     * 设置返回参数
     * @param string $key
     * @param string $value
     */
    public function SetData($key, $value) {
        $this->values[$key] = $value;
    }

}

/**
 * 
 * 统一下单输入对象
 * @author wangcb
 *
 */
class WechatPayUnifiedOrder extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置微信支付分配的终端设备号，商户自定义
     * @param string $value 
     * */
    public function SetDevice_info($value) {
        $this->values['device_info'] = $value;
    }

    /**
     * 获取微信支付分配的终端设备号，商户自定义的值
     * @return 值
     * */
    public function GetDevice_info() {
        return $this->values['device_info'];
    }

    /**
     * 判断微信支付分配的终端设备号，商户自定义是否存在
     * @return true 或 false
     * */
    public function IsDevice_infoSet() {
        return array_key_exists('device_info', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置商品或支付单简要描述
     * @param string $value 
     * */
    public function SetBody($value) {
        $this->values['body'] = $value;
    }

    /**
     * 获取商品或支付单简要描述的值
     * @return 值
     * */
    public function GetBody() {
        return $this->values['body'];
    }

    /**
     * 判断商品或支付单简要描述是否存在
     * @return true 或 false
     * */
    public function IsBodySet() {
        return array_key_exists('body', $this->values);
    }

    /**
     * 设置商品名称明细列表
     * @param string $value 
     * */
    public function SetDetail($value) {
        $this->values['detail'] = $value;
    }

    /**
     * 获取商品名称明细列表的值
     * @return 值
     * */
    public function GetDetail() {
        return $this->values['detail'];
    }

    /**
     * 判断商品名称明细列表是否存在
     * @return true 或 false
     * */
    public function IsDetailSet() {
        return array_key_exists('detail', $this->values);
    }

    /**
     * 设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
     * @param string $value 
     * */
    public function SetAttach($value) {
        $this->values['attach'] = $value;
    }

    /**
     * 获取附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据的值
     * @return 值
     * */
    public function GetAttach() {
        return $this->values['attach'];
    }

    /**
     * 判断附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据是否存在
     * @return true 或 false
     * */
    public function IsAttachSet() {
        return array_key_exists('attach', $this->values);
    }

    /**
     * 设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型
     * @param string $value 
     * */
    public function SetFee_type($value) {
        $this->values['fee_type'] = $value;
    }

    /**
     * 获取符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型的值
     * @return 值
     * */
    public function GetFee_type() {
        return $this->values['fee_type'];
    }

    /**
     * 判断符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型是否存在
     * @return true 或 false
     * */
    public function IsFee_typeSet() {
        return array_key_exists('fee_type', $this->values);
    }

    /**
     * 设置订单总金额，只能为整数，详见支付金额
     * @param string $value 
     * */
    public function SetTotal_fee($value) {
        $this->values['total_fee'] = $value;
    }

    /**
     * 获取订单总金额，只能为整数，详见支付金额的值
     * @return 值
     * */
    public function GetTotal_fee() {
        return $this->values['total_fee'];
    }

    /**
     * 判断订单总金额，只能为整数，详见支付金额是否存在
     * @return true 或 false
     * */
    public function IsTotal_feeSet() {
        return array_key_exists('total_fee', $this->values);
    }

    /**
     * 设置APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
     * @param string $value 
     * */
    public function SetSpbill_create_ip($value) {
        $this->values['spbill_create_ip'] = $value;
    }

    /**
     * 获取APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。的值
     * @return 值
     * */
    public function GetSpbill_create_ip() {
        return $this->values['spbill_create_ip'];
    }

    /**
     * 判断APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。是否存在
     * @return true 或 false
     * */
    public function IsSpbill_create_ipSet() {
        return array_key_exists('spbill_create_ip', $this->values);
    }

    /**
     * 设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
     * @param string $value 
     * */
    public function SetTime_start($value) {
        $this->values['time_start'] = $value;
    }

    /**
     * 获取订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则的值
     * @return 值
     * */
    public function GetTime_start() {
        return $this->values['time_start'];
    }

    /**
     * 判断订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则是否存在
     * @return true 或 false
     * */
    public function IsTime_startSet() {
        return array_key_exists('time_start', $this->values);
    }

    /**
     * 设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
     * @param string $value 
     * */
    public function SetTime_expire($value) {
        $this->values['time_expire'] = $value;
    }

    /**
     * 获取订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则的值
     * @return 值
     * */
    public function GetTime_expire() {
        return $this->values['time_expire'];
    }

    /**
     * 判断订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则是否存在
     * @return true 或 false
     * */
    public function IsTime_expireSet() {
        return array_key_exists('time_expire', $this->values);
    }

    /**
     * 设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
     * @param string $value 
     * */
    public function SetGoods_tag($value) {
        $this->values['goods_tag'] = $value;
    }

    /**
     * 获取商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠的值
     * @return 值
     * */
    public function GetGoods_tag() {
        return $this->values['goods_tag'];
    }

    /**
     * 判断商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠是否存在
     * @return true 或 false
     * */
    public function IsGoods_tagSet() {
        return array_key_exists('goods_tag', $this->values);
    }

    /**
     * 设置接收微信支付异步通知回调地址
     * @param string $value 
     * */
    public function SetNotify_url($value) {
        $this->values['notify_url'] = $value;
    }

    /**
     * 获取接收微信支付异步通知回调地址的值
     * @return 值
     * */
    public function GetNotify_url() {
        return $this->values['notify_url'];
    }

    /**
     * 判断接收微信支付异步通知回调地址是否存在
     * @return true 或 false
     * */
    public function IsNotify_urlSet() {
        return array_key_exists('notify_url', $this->values);
    }

    /**
     * 设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
     * @param string $value 
     * */
    public function SetTrade_type($value) {
        $this->values['trade_type'] = $value;
    }

    /**
     * 获取取值如下：JSAPI，NATIVE，APP，详细说明见参数规定的值
     * @return 值
     * */
    public function GetTrade_type() {
        return $this->values['trade_type'];
    }

    /**
     * 判断取值如下：JSAPI，NATIVE，APP，详细说明见参数规定是否存在
     * @return true 或 false
     * */
    public function IsTrade_typeSet() {
        return array_key_exists('trade_type', $this->values);
    }

    /**
     * 设置trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
     * @param string $value 
     * */
    public function SetProduct_id($value) {
        $this->values['product_id'] = $value;
    }

    /**
     * 获取trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。的值
     * @return 值
     * */
    public function GetProduct_id() {
        return $this->values['product_id'];
    }

    /**
     * 判断trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。是否存在
     * @return true 或 false
     * */
    public function IsProduct_idSet() {
        return array_key_exists('product_id', $this->values);
    }

    /**
     * 设置trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。 
     * @param string $value 
     * */
    public function SetOpenid($value) {
        $this->values['openid'] = $value;
    }

    /**
     * 获取trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。 的值
     * @return 值
     * */
    public function GetOpenid() {
        return $this->values['openid'];
    }

    /**
     * 判断trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。 是否存在
     * @return true 或 false
     * */
    public function IsOpenidSet() {
        return array_key_exists('openid', $this->values);
    }

}

/**
 * 
 * 订单查询输入对象
 * @author wangcb
 *
 */
class WechatPayOrderQuery extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置微信的订单号，优先使用
     * @param string $value 
     * */
    public function SetTransaction_id($value) {
        $this->values['transaction_id'] = $value;
    }

    /**
     * 获取微信的订单号，优先使用的值
     * @return 值
     * */
    public function GetTransaction_id() {
        return $this->values['transaction_id'];
    }

    /**
     * 判断微信的订单号，优先使用是否存在
     * @return true 或 false
     * */
    public function IsTransaction_idSet() {
        return array_key_exists('transaction_id', $this->values);
    }

    /**
     * 设置商户系统内部的订单号，当没提供transaction_id时需要传这个。
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号，当没提供transaction_id时需要传这个。的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号，当没提供transaction_id时需要传这个。是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

}

/**
 * 
 * 关闭订单输入对象
 * @author wangcb
 *
 */
class WechatPayCloseOrder extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置商户系统内部的订单号
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

}

/**
 * 
 * 提交退款输入对象
 * @author wangcb
 *
 */
class WechatPayRefund extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置微信支付分配的终端设备号，与下单一致
     * @param string $value 
     * */
    public function SetDevice_info($value) {
        $this->values['device_info'] = $value;
    }

    /**
     * 获取微信支付分配的终端设备号，与下单一致的值
     * @return 值
     * */
    public function GetDevice_info() {
        return $this->values['device_info'];
    }

    /**
     * 判断微信支付分配的终端设备号，与下单一致是否存在
     * @return true 或 false
     * */
    public function IsDevice_infoSet() {
        return array_key_exists('device_info', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置微信订单号
     * @param string $value 
     * */
    public function SetTransaction_id($value) {
        $this->values['transaction_id'] = $value;
    }

    /**
     * 获取微信订单号的值
     * @return 值
     * */
    public function GetTransaction_id() {
        return $this->values['transaction_id'];
    }

    /**
     * 判断微信订单号是否存在
     * @return true 或 false
     * */
    public function IsTransaction_idSet() {
        return array_key_exists('transaction_id', $this->values);
    }

    /**
     * 设置商户系统内部的订单号,transaction_id、out_trade_no二选一，如果同时存在优先级：transaction_id> out_trade_no
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号,transaction_id、out_trade_no二选一，如果同时存在优先级：transaction_id> out_trade_no的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号,transaction_id、out_trade_no二选一，如果同时存在优先级：transaction_id> out_trade_no是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置商户系统内部的退款单号，商户系统内部唯一，同一退款单号多次请求只退一笔
     * @param string $value 
     * */
    public function SetOut_refund_no($value) {
        $this->values['out_refund_no'] = $value;
    }

    /**
     * 获取商户系统内部的退款单号，商户系统内部唯一，同一退款单号多次请求只退一笔的值
     * @return 值
     * */
    public function GetOut_refund_no() {
        return $this->values['out_refund_no'];
    }

    /**
     * 判断商户系统内部的退款单号，商户系统内部唯一，同一退款单号多次请求只退一笔是否存在
     * @return true 或 false
     * */
    public function IsOut_refund_noSet() {
        return array_key_exists('out_refund_no', $this->values);
    }

    /**
     * 设置订单总金额，单位为分，只能为整数，详见支付金额
     * @param string $value 
     * */
    public function SetTotal_fee($value) {
        $this->values['total_fee'] = $value;
    }

    /**
     * 获取订单总金额，单位为分，只能为整数，详见支付金额的值
     * @return 值
     * */
    public function GetTotal_fee() {
        return $this->values['total_fee'];
    }

    /**
     * 判断订单总金额，单位为分，只能为整数，详见支付金额是否存在
     * @return true 或 false
     * */
    public function IsTotal_feeSet() {
        return array_key_exists('total_fee', $this->values);
    }

    /**
     * 设置退款总金额，订单总金额，单位为分，只能为整数，详见支付金额
     * @param string $value 
     * */
    public function SetRefund_fee($value) {
        $this->values['refund_fee'] = $value;
    }

    /**
     * 获取退款总金额，订单总金额，单位为分，只能为整数，详见支付金额的值
     * @return 值
     * */
    public function GetRefund_fee() {
        return $this->values['refund_fee'];
    }

    /**
     * 判断退款总金额，订单总金额，单位为分，只能为整数，详见支付金额是否存在
     * @return true 或 false
     * */
    public function IsRefund_feeSet() {
        return array_key_exists('refund_fee', $this->values);
    }

    /**
     * 设置货币类型，符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型
     * @param string $value 
     * */
    public function SetRefund_fee_type($value) {
        $this->values['refund_fee_type'] = $value;
    }

    /**
     * 获取货币类型，符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型的值
     * @return 值
     * */
    public function GetRefund_fee_type() {
        return $this->values['refund_fee_type'];
    }

    /**
     * 判断货币类型，符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型是否存在
     * @return true 或 false
     * */
    public function IsRefund_fee_typeSet() {
        return array_key_exists('refund_fee_type', $this->values);
    }

    /**
     * 设置操作员帐号, 默认为商户号
     * @param string $value 
     * */
    public function SetOp_user_id($value) {
        $this->values['op_user_id'] = $value;
    }

    /**
     * 获取操作员帐号, 默认为商户号的值
     * @return 值
     * */
    public function GetOp_user_id() {
        return $this->values['op_user_id'];
    }

    /**
     * 判断操作员帐号, 默认为商户号是否存在
     * @return true 或 false
     * */
    public function IsOp_user_idSet() {
        return array_key_exists('op_user_id', $this->values);
    }

}

/**
 * 
 * 退款查询输入对象
 * @author wangcb
 *
 */
class WechatPayRefundQuery extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置微信支付分配的终端设备号
     * @param string $value 
     * */
    public function SetDevice_info($value) {
        $this->values['device_info'] = $value;
    }

    /**
     * 获取微信支付分配的终端设备号的值
     * @return 值
     * */
    public function GetDevice_info() {
        return $this->values['device_info'];
    }

    /**
     * 判断微信支付分配的终端设备号是否存在
     * @return true 或 false
     * */
    public function IsDevice_infoSet() {
        return array_key_exists('device_info', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置微信订单号
     * @param string $value 
     * */
    public function SetTransaction_id($value) {
        $this->values['transaction_id'] = $value;
    }

    /**
     * 获取微信订单号的值
     * @return 值
     * */
    public function GetTransaction_id() {
        return $this->values['transaction_id'];
    }

    /**
     * 判断微信订单号是否存在
     * @return true 或 false
     * */
    public function IsTransaction_idSet() {
        return array_key_exists('transaction_id', $this->values);
    }

    /**
     * 设置商户系统内部的订单号
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置商户退款单号
     * @param string $value 
     * */
    public function SetOut_refund_no($value) {
        $this->values['out_refund_no'] = $value;
    }

    /**
     * 获取商户退款单号的值
     * @return 值
     * */
    public function GetOut_refund_no() {
        return $this->values['out_refund_no'];
    }

    /**
     * 判断商户退款单号是否存在
     * @return true 或 false
     * */
    public function IsOut_refund_noSet() {
        return array_key_exists('out_refund_no', $this->values);
    }

    /**
     * 设置微信退款单号refund_id、out_refund_no、out_trade_no、transaction_id四个参数必填一个，如果同时存在优先级为：refund_id>out_refund_no>transaction_id>out_trade_no
     * @param string $value 
     * */
    public function SetRefund_id($value) {
        $this->values['refund_id'] = $value;
    }

    /**
     * 获取微信退款单号refund_id、out_refund_no、out_trade_no、transaction_id四个参数必填一个，如果同时存在优先级为：refund_id>out_refund_no>transaction_id>out_trade_no的值
     * @return 值
     * */
    public function GetRefund_id() {
        return $this->values['refund_id'];
    }

    /**
     * 判断微信退款单号refund_id、out_refund_no、out_trade_no、transaction_id四个参数必填一个，如果同时存在优先级为：refund_id>out_refund_no>transaction_id>out_trade_no是否存在
     * @return true 或 false
     * */
    public function IsRefund_idSet() {
        return array_key_exists('refund_id', $this->values);
    }

}

/**
 * 
 * 下载对账单输入对象
 * @author wangcb
 *
 */
class WechatPayDownloadBill extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置微信支付分配的终端设备号，填写此字段，只下载该设备号的对账单
     * @param string $value 
     * */
    public function SetDevice_info($value) {
        $this->values['device_info'] = $value;
    }

    /**
     * 获取微信支付分配的终端设备号，填写此字段，只下载该设备号的对账单的值
     * @return 值
     * */
    public function GetDevice_info() {
        return $this->values['device_info'];
    }

    /**
     * 判断微信支付分配的终端设备号，填写此字段，只下载该设备号的对账单是否存在
     * @return true 或 false
     * */
    public function IsDevice_infoSet() {
        return array_key_exists('device_info', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置下载对账单的日期，格式：20140603
     * @param string $value 
     * */
    public function SetBill_date($value) {
        $this->values['bill_date'] = $value;
    }

    /**
     * 获取下载对账单的日期，格式：20140603的值
     * @return 值
     * */
    public function GetBill_date() {
        return $this->values['bill_date'];
    }

    /**
     * 判断下载对账单的日期，格式：20140603是否存在
     * @return true 或 false
     * */
    public function IsBill_dateSet() {
        return array_key_exists('bill_date', $this->values);
    }

    /**
     * 设置ALL，返回当日所有订单信息，默认值SUCCESS，返回当日成功支付的订单REFUND，返回当日退款订单REVOKED，已撤销的订单
     * @param string $value 
     * */
    public function SetBill_type($value) {
        $this->values['bill_type'] = $value;
    }

    /**
     * 获取ALL，返回当日所有订单信息，默认值SUCCESS，返回当日成功支付的订单REFUND，返回当日退款订单REVOKED，已撤销的订单的值
     * @return 值
     * */
    public function GetBill_type() {
        return $this->values['bill_type'];
    }

    /**
     * 判断ALL，返回当日所有订单信息，默认值SUCCESS，返回当日成功支付的订单REFUND，返回当日退款订单REVOKED，已撤销的订单是否存在
     * @return true 或 false
     * */
    public function IsBill_typeSet() {
        return array_key_exists('bill_type', $this->values);
    }

}

/**
 * 
 * 测速上报输入对象
 * @author wangcb
 *
 */
class WechatPayReport extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置微信支付分配的终端设备号，商户自定义
     * @param string $value 
     * */
    public function SetDevice_info($value) {
        $this->values['device_info'] = $value;
    }

    /**
     * 获取微信支付分配的终端设备号，商户自定义的值
     * @return 值
     * */
    public function GetDevice_info() {
        return $this->values['device_info'];
    }

    /**
     * 判断微信支付分配的终端设备号，商户自定义是否存在
     * @return true 或 false
     * */
    public function IsDevice_infoSet() {
        return array_key_exists('device_info', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置上报对应的接口的完整URL，类似：https://api.mch.weixin.qq.com/pay/unifiedorder对于被扫支付，为更好的和商户共同分析一次业务行为的整体耗时情况，对于两种接入模式，请都在门店侧对一次被扫行为进行一次单独的整体上报，上报URL指定为：https://api.mch.weixin.qq.com/pay/micropay/total关于两种接入模式具体可参考本文档章节：被扫支付商户接入模式其它接口调用仍然按照调用一次，上报一次来进行。
     * @param string $value 
     * */
    public function SetInterface_url($value) {
        $this->values['interface_url'] = $value;
    }

    /**
     * 获取上报对应的接口的完整URL，类似：https://api.mch.weixin.qq.com/pay/unifiedorder对于被扫支付，为更好的和商户共同分析一次业务行为的整体耗时情况，对于两种接入模式，请都在门店侧对一次被扫行为进行一次单独的整体上报，上报URL指定为：https://api.mch.weixin.qq.com/pay/micropay/total关于两种接入模式具体可参考本文档章节：被扫支付商户接入模式其它接口调用仍然按照调用一次，上报一次来进行。的值
     * @return 值
     * */
    public function GetInterface_url() {
        return $this->values['interface_url'];
    }

    /**
     * 判断上报对应的接口的完整URL，类似：https://api.mch.weixin.qq.com/pay/unifiedorder对于被扫支付，为更好的和商户共同分析一次业务行为的整体耗时情况，对于两种接入模式，请都在门店侧对一次被扫行为进行一次单独的整体上报，上报URL指定为：https://api.mch.weixin.qq.com/pay/micropay/total关于两种接入模式具体可参考本文档章节：被扫支付商户接入模式其它接口调用仍然按照调用一次，上报一次来进行。是否存在
     * @return true 或 false
     * */
    public function IsInterface_urlSet() {
        return array_key_exists('interface_url', $this->values);
    }

    /**
     * 设置接口耗时情况，单位为毫秒
     * @param string $value 
     * */
    public function SetExecute_time_($value) {
        $this->values['execute_time_'] = $value;
    }

    /**
     * 获取接口耗时情况，单位为毫秒的值
     * @return 值
     * */
    public function GetExecute_time_() {
        return $this->values['execute_time_'];
    }

    /**
     * 判断接口耗时情况，单位为毫秒是否存在
     * @return true 或 false
     * */
    public function IsExecute_time_Set() {
        return array_key_exists('execute_time_', $this->values);
    }

    /**
     * 设置SUCCESS/FAIL此字段是通信标识，非交易标识，交易是否成功需要查看trade_state来判断
     * @param string $value 
     * */
    public function SetReturn_code($value) {
        $this->values['return_code'] = $value;
    }

    /**
     * 获取SUCCESS/FAIL此字段是通信标识，非交易标识，交易是否成功需要查看trade_state来判断的值
     * @return 值
     * */
    public function GetReturn_code() {
        return $this->values['return_code'];
    }

    /**
     * 判断SUCCESS/FAIL此字段是通信标识，非交易标识，交易是否成功需要查看trade_state来判断是否存在
     * @return true 或 false
     * */
    public function IsReturn_codeSet() {
        return array_key_exists('return_code', $this->values);
    }

    /**
     * 设置返回信息，如非空，为错误原因签名失败参数格式校验错误
     * @param string $value 
     * */
    public function SetReturn_msg($value) {
        $this->values['return_msg'] = $value;
    }

    /**
     * 获取返回信息，如非空，为错误原因签名失败参数格式校验错误的值
     * @return 值
     * */
    public function GetReturn_msg() {
        return $this->values['return_msg'];
    }

    /**
     * 判断返回信息，如非空，为错误原因签名失败参数格式校验错误是否存在
     * @return true 或 false
     * */
    public function IsReturn_msgSet() {
        return array_key_exists('return_msg', $this->values);
    }

    /**
     * 设置SUCCESS/FAIL
     * @param string $value 
     * */
    public function SetResult_code($value) {
        $this->values['result_code'] = $value;
    }

    /**
     * 获取SUCCESS/FAIL的值
     * @return 值
     * */
    public function GetResult_code() {
        return $this->values['result_code'];
    }

    /**
     * 判断SUCCESS/FAIL是否存在
     * @return true 或 false
     * */
    public function IsResult_codeSet() {
        return array_key_exists('result_code', $this->values);
    }

    /**
     * 设置ORDERNOTEXIST—订单不存在SYSTEMERROR—系统错误
     * @param string $value 
     * */
    public function SetErr_code($value) {
        $this->values['err_code'] = $value;
    }

    /**
     * 获取ORDERNOTEXIST—订单不存在SYSTEMERROR—系统错误的值
     * @return 值
     * */
    public function GetErr_code() {
        return $this->values['err_code'];
    }

    /**
     * 判断ORDERNOTEXIST—订单不存在SYSTEMERROR—系统错误是否存在
     * @return true 或 false
     * */
    public function IsErr_codeSet() {
        return array_key_exists('err_code', $this->values);
    }

    /**
     * 设置结果信息描述
     * @param string $value 
     * */
    public function SetErr_code_des($value) {
        $this->values['err_code_des'] = $value;
    }

    /**
     * 获取结果信息描述的值
     * @return 值
     * */
    public function GetErr_code_des() {
        return $this->values['err_code_des'];
    }

    /**
     * 判断结果信息描述是否存在
     * @return true 或 false
     * */
    public function IsErr_code_desSet() {
        return array_key_exists('err_code_des', $this->values);
    }

    /**
     * 设置商户系统内部的订单号,商户可以在上报时提供相关商户订单号方便微信支付更好的提高服务质量。 
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号,商户可以在上报时提供相关商户订单号方便微信支付更好的提高服务质量。 的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号,商户可以在上报时提供相关商户订单号方便微信支付更好的提高服务质量。 是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置发起接口调用时的机器IP 
     * @param string $value 
     * */
    public function SetUser_ip($value) {
        $this->values['user_ip'] = $value;
    }

    /**
     * 获取发起接口调用时的机器IP 的值
     * @return 值
     * */
    public function GetUser_ip() {
        return $this->values['user_ip'];
    }

    /**
     * 判断发起接口调用时的机器IP 是否存在
     * @return true 或 false
     * */
    public function IsUser_ipSet() {
        return array_key_exists('user_ip', $this->values);
    }

    /**
     * 设置系统时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
     * @param string $value 
     * */
    public function SetTime($value) {
        $this->values['time'] = $value;
    }

    /**
     * 获取系统时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则的值
     * @return 值
     * */
    public function GetTime() {
        return $this->values['time'];
    }

    /**
     * 判断系统时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则是否存在
     * @return true 或 false
     * */
    public function IsTimeSet() {
        return array_key_exists('time', $this->values);
    }

}

/**
 * 
 * 短链转换输入对象
 * @author wangcb
 *
 */
class WechatPayShortUrl extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置需要转换的URL，签名用原串，传输需URL encode
     * @param string $value 
     * */
    public function SetLong_url($value) {
        $this->values['long_url'] = $value;
    }

    /**
     * 获取需要转换的URL，签名用原串，传输需URL encode的值
     * @return 值
     * */
    public function GetLong_url() {
        return $this->values['long_url'];
    }

    /**
     * 判断需要转换的URL，签名用原串，传输需URL encode是否存在
     * @return true 或 false
     * */
    public function IsLong_urlSet() {
        return array_key_exists('long_url', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

}

/**
 * 
 * 提交被扫输入对象
 * @author wangcb
 *
 */
class WechatPayMicroPay extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置终端设备号(商户自定义，如门店编号)
     * @param string $value 
     * */
    public function SetDevice_info($value) {
        $this->values['device_info'] = $value;
    }

    /**
     * 获取终端设备号(商户自定义，如门店编号)的值
     * @return 值
     * */
    public function GetDevice_info() {
        return $this->values['device_info'];
    }

    /**
     * 判断终端设备号(商户自定义，如门店编号)是否存在
     * @return true 或 false
     * */
    public function IsDevice_infoSet() {
        return array_key_exists('device_info', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置商品或支付单简要描述
     * @param string $value 
     * */
    public function SetBody($value) {
        $this->values['body'] = $value;
    }

    /**
     * 获取商品或支付单简要描述的值
     * @return 值
     * */
    public function GetBody() {
        return $this->values['body'];
    }

    /**
     * 判断商品或支付单简要描述是否存在
     * @return true 或 false
     * */
    public function IsBodySet() {
        return array_key_exists('body', $this->values);
    }

    /**
     * 设置商品名称明细列表
     * @param string $value 
     * */
    public function SetDetail($value) {
        $this->values['detail'] = $value;
    }

    /**
     * 获取商品名称明细列表的值
     * @return 值
     * */
    public function GetDetail() {
        return $this->values['detail'];
    }

    /**
     * 判断商品名称明细列表是否存在
     * @return true 或 false
     * */
    public function IsDetailSet() {
        return array_key_exists('detail', $this->values);
    }

    /**
     * 设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
     * @param string $value 
     * */
    public function SetAttach($value) {
        $this->values['attach'] = $value;
    }

    /**
     * 获取附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据的值
     * @return 值
     * */
    public function GetAttach() {
        return $this->values['attach'];
    }

    /**
     * 判断附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据是否存在
     * @return true 或 false
     * */
    public function IsAttachSet() {
        return array_key_exists('attach', $this->values);
    }

    /**
     * 设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置订单总金额，单位为分，只能为整数，详见支付金额
     * @param string $value 
     * */
    public function SetTotal_fee($value) {
        $this->values['total_fee'] = $value;
    }

    /**
     * 获取订单总金额，单位为分，只能为整数，详见支付金额的值
     * @return 值
     * */
    public function GetTotal_fee() {
        return $this->values['total_fee'];
    }

    /**
     * 判断订单总金额，单位为分，只能为整数，详见支付金额是否存在
     * @return true 或 false
     * */
    public function IsTotal_feeSet() {
        return array_key_exists('total_fee', $this->values);
    }

    /**
     * 设置符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型
     * @param string $value 
     * */
    public function SetFee_type($value) {
        $this->values['fee_type'] = $value;
    }

    /**
     * 获取符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型的值
     * @return 值
     * */
    public function GetFee_type() {
        return $this->values['fee_type'];
    }

    /**
     * 判断符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型是否存在
     * @return true 或 false
     * */
    public function IsFee_typeSet() {
        return array_key_exists('fee_type', $this->values);
    }

    /**
     * 设置调用微信支付API的机器IP 
     * @param string $value 
     * */
    public function SetSpbill_create_ip($value) {
        $this->values['spbill_create_ip'] = $value;
    }

    /**
     * 获取调用微信支付API的机器IP 的值
     * @return 值
     * */
    public function GetSpbill_create_ip() {
        return $this->values['spbill_create_ip'];
    }

    /**
     * 判断调用微信支付API的机器IP 是否存在
     * @return true 或 false
     * */
    public function IsSpbill_create_ipSet() {
        return array_key_exists('spbill_create_ip', $this->values);
    }

    /**
     * 设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。详见时间规则
     * @param string $value 
     * */
    public function SetTime_start($value) {
        $this->values['time_start'] = $value;
    }

    /**
     * 获取订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。详见时间规则的值
     * @return 值
     * */
    public function GetTime_start() {
        return $this->values['time_start'];
    }

    /**
     * 判断订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。详见时间规则是否存在
     * @return true 或 false
     * */
    public function IsTime_startSet() {
        return array_key_exists('time_start', $this->values);
    }

    /**
     * 设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。详见时间规则
     * @param string $value 
     * */
    public function SetTime_expire($value) {
        $this->values['time_expire'] = $value;
    }

    /**
     * 获取订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。详见时间规则的值
     * @return 值
     * */
    public function GetTime_expire() {
        return $this->values['time_expire'];
    }

    /**
     * 判断订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。详见时间规则是否存在
     * @return true 或 false
     * */
    public function IsTime_expireSet() {
        return array_key_exists('time_expire', $this->values);
    }

    /**
     * 设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
     * @param string $value 
     * */
    public function SetGoods_tag($value) {
        $this->values['goods_tag'] = $value;
    }

    /**
     * 获取商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠的值
     * @return 值
     * */
    public function GetGoods_tag() {
        return $this->values['goods_tag'];
    }

    /**
     * 判断商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠是否存在
     * @return true 或 false
     * */
    public function IsGoods_tagSet() {
        return array_key_exists('goods_tag', $this->values);
    }

    /**
     * 设置扫码支付授权码，设备读取用户微信中的条码或者二维码信息
     * @param string $value 
     * */
    public function SetAuth_code($value) {
        $this->values['auth_code'] = $value;
    }

    /**
     * 获取扫码支付授权码，设备读取用户微信中的条码或者二维码信息的值
     * @return 值
     * */
    public function GetAuth_code() {
        return $this->values['auth_code'];
    }

    /**
     * 判断扫码支付授权码，设备读取用户微信中的条码或者二维码信息是否存在
     * @return true 或 false
     * */
    public function IsAuth_codeSet() {
        return array_key_exists('auth_code', $this->values);
    }

}

/**
 * 
 * 撤销输入对象
 * @author wangcb
 *
 */
class WechatPayReverse extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置微信的订单号，优先使用
     * @param string $value 
     * */
    public function SetTransaction_id($value) {
        $this->values['transaction_id'] = $value;
    }

    /**
     * 获取微信的订单号，优先使用的值
     * @return 值
     * */
    public function GetTransaction_id() {
        return $this->values['transaction_id'];
    }

    /**
     * 判断微信的订单号，优先使用是否存在
     * @return true 或 false
     * */
    public function IsTransaction_idSet() {
        return array_key_exists('transaction_id', $this->values);
    }

    /**
     * 设置商户系统内部的订单号,transaction_id、out_trade_no二选一，如果同时存在优先级：transaction_id> out_trade_no
     * @param string $value 
     * */
    public function SetOut_trade_no($value) {
        $this->values['out_trade_no'] = $value;
    }

    /**
     * 获取商户系统内部的订单号,transaction_id、out_trade_no二选一，如果同时存在优先级：transaction_id> out_trade_no的值
     * @return 值
     * */
    public function GetOut_trade_no() {
        return $this->values['out_trade_no'];
    }

    /**
     * 判断商户系统内部的订单号,transaction_id、out_trade_no二选一，如果同时存在优先级：transaction_id> out_trade_no是否存在
     * @return true 或 false
     * */
    public function IsOut_trade_noSet() {
        return array_key_exists('out_trade_no', $this->values);
    }

    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

}

/**
 * 
 * 提交JSAPI输入对象
 * @author wangcb
 *
 */
class WechatPayJsApiPay extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appId'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appId'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appId', $this->values);
    }

    /**
     * 设置支付时间戳
     * @param string $value 
     * */
    public function SetTimeStamp($value) {
        $this->values['timeStamp'] = $value;
    }

    /**
     * 获取支付时间戳的值
     * @return 值
     * */
    public function GetTimeStamp() {
        return $this->values['timeStamp'];
    }

    /**
     * 判断支付时间戳是否存在
     * @return true 或 false
     * */
    public function IsTimeStampSet() {
        return array_key_exists('timeStamp', $this->values);
    }

    /**
     * 随机字符串
     * @param string $value 
     * */
    public function SetNonceStr($value) {
        $this->values['nonceStr'] = $value;
    }

    /**
     * 获取notify随机字符串值
     * @return 值
     * */
    public function GetReturn_code() {
        return $this->values['nonceStr'];
    }

    /**
     * 判断随机字符串是否存在
     * @return true 或 false
     * */
    public function IsReturn_codeSet() {
        return array_key_exists('nonceStr', $this->values);
    }

    /**
     * 设置订单详情扩展字符串
     * @param string $value 
     * */
    public function SetPackage($value) {
        $this->values['package'] = $value;
    }

    /**
     * 获取订单详情扩展字符串的值
     * @return 值
     * */
    public function GetPackage() {
        return $this->values['package'];
    }

    /**
     * 判断订单详情扩展字符串是否存在
     * @return true 或 false
     * */
    public function IsPackageSet() {
        return array_key_exists('package', $this->values);
    }

    /**
     * 设置签名方式
     * @param string $value 
     * */
    public function SetSignType($value) {
        $this->values['signType'] = $value;
    }

    /**
     * 获取签名方式
     * @return 值
     * */
    public function GetSignType() {
        return $this->values['signType'];
    }

    /**
     * 判断签名方式是否存在
     * @return true 或 false
     * */
    public function IsSignTypeSet() {
        return array_key_exists('signType', $this->values);
    }

    /**
     * 设置签名方式
     * @param string $value 
     * */
    public function SetPaySign($value) {
        $this->values['paySign'] = $value;
    }

    /**
     * 获取签名方式
     * @return 值
     * */
    public function GetPaySign() {
        return $this->values['paySign'];
    }

    /**
     * 判断签名方式是否存在
     * @return true 或 false
     * */
    public function IsPaySignSet() {
        return array_key_exists('paySign', $this->values);
    }

}

/**
 * 
 * 扫码支付模式一生成二维码参数
 * @author wangcb
 *
 */
class WechatPayBizPayUrl extends WechatDataBase {

    /**
     * 设置微信分配的公众账号ID
     * @param string $value 
     * */
    public function SetAppid($value) {
        $this->values['appid'] = $value;
    }

    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['appid'];
    }

    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     * */
    public function IsAppidSet() {
        return array_key_exists('appid', $this->values);
    }

    /**
     * 设置微信支付分配的商户号
     * @param string $value 
     * */
    public function SetMch_id($value) {
        $this->values['mch_id'] = $value;
    }

    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     * */
    public function GetMch_id() {
        return $this->values['mch_id'];
    }

    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     * */
    public function IsMch_idSet() {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置支付时间戳
     * @param string $value 
     * */
    public function SetTime_stamp($value) {
        $this->values['time_stamp'] = $value;
    }

    /**
     * 获取支付时间戳的值
     * @return 值
     * */
    public function GetTime_stamp() {
        return $this->values['time_stamp'];
    }

    /**
     * 判断支付时间戳是否存在
     * @return true 或 false
     * */
    public function IsTime_stampSet() {
        return array_key_exists('time_stamp', $this->values);
    }

    /**
     * 设置随机字符串
     * @param string $value 
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }

    /**
     * 判断随机字符串是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置商品ID
     * @param string $value 
     * */
    public function SetProduct_id($value) {
        $this->values['product_id'] = $value;
    }

    /**
     * 获取商品ID的值
     * @return 值
     * */
    public function GetProduct_id() {
        return $this->values['product_id'];
    }

    /**
     * 判断商品ID是否存在
     * @return true 或 false
     * */
    public function IsProduct_idSet() {
        return array_key_exists('product_id', $this->values);
    }

}

/**
 * 微信返回结果处理类
 * @author wangcb
 *
 */
class WechatResult extends WechatDataBase{
    /**
     * 将xml转为array
     * @param string $xml
     * @throws WechatException
     */
    public static function Init($xml) {
        $obj = new self();
        $obj->FromXml($xml);
        if (isset($obj->values['return_code']) && $obj->values['return_code'] != 'SUCCESS') {
            return $obj->GetValues();
        }
        if (isset($obj->values['sign'])){
            $obj->CheckSign();
        }
        return $obj->GetValues();
    }
}

/**
 * 微信开放平台授权类
 * @author wangcb
 *
 */
class WechatOpenAuth extends WechatDataBase{
    /**
     * 设置微信分配的第三方平台appid
     * @param string $value
     * */
    public function SetAppid($value) {
        $this->values['component_appid'] = $value;
    }
    /**
     * 获取微信分配的第三方平台appid
     * @return 值
     * */
    public function GetAppid() {
        return $this->values['component_appid'];
    }
    /**
     * 设置微信分配的第三方平台appid
     * @param string $value
     * */
    public function SetAppsecret($value) {
        $this->values['component_appsecret'] = $value;
    }
    /**
     * 获取微信分配的第三方平台appid
     * @return 值
     * */
    public function GetAppsecret() {
        return $this->values['component_appsecret'];
    }
    /**
     * 设置微信推送的component_verify_ticket协议
     * @author wangcb
     * @param unknown $value
     */
    public function SetTicket($value){
        $this->values['component_verify_ticket'] = $value;
    }
    /**
     * 获取微信推送的component_verify_ticket协议
     * @author wangcb
     * @return multitype:
     */
    public function GetTicket() {
        return $this->values['component_verify_ticket'];
    }
    /**
     * 设置授权页回调地址
     * @author wangcb
     * @param unknown $value
     */
    public function SetRedirectUri($value){
        $this->values['redirect_uri'] = $value;
    }
    /**
     * 获取授权页回调地址
     * @author wangcb
     * @return multitype:
     */
    public function GetRedirectUri() {
        return $this->values['redirect_uri'];
    }
    /**
     * 设置授权码
     * @author wangcb
     * @param unknown $value
     */
    public function SetAuthCode($value){
        $this->values['authorization_code'] = $value;
    }
    /**
     * 获取授权码
     * @author wangcb
     * @return multitype:
     */
    public function GetAuthCode() {
        return $this->values['authorization_code'];
    }
    /**
     * 设置授权方appid
     * @author wangcb
     * @param unknown $value
     */
    public function SetAuthAppid($value) {
        $this->values['authorizer_appid'] = $value;
    }
    /**
     * 设置授权方appid
     * @author wangcb
     * @param unknown $value
     */
    public function GetAuthAppid($value) {
        return $this->values['authorizer_appid'];
    }
    /**
     * 设置选项名称
     * @author wangcb
     * @param unknown $value
     */
    public function SetOptionName($value) {
        $this->values['option_name'] = $value;
    }
    /**
     * 获取选项名称
     * @author wangcb
     */
    public function GetOptionName() {
        return $this->values['option_name'];
    }
    /**
     * 设置的选项值
     * @author wangcb
     * @param unknown $value
     */
    public function SetOptionValue($value) {
        $this->values['option_value'] = $value;
    }
    /**
     * 获取设置的选项值
     * @author wangcb
     */
    public function GetOptionValue() {
        return $this->values['option_value'];
    }
}


/**
 * 微信公众平台菜单类
 * @author zhangzj
 *
 */
class WechatMenu extends WechatDataBase{
    /**
     * 自定义菜单类型获取
     * @author zhangzj
     * @return array
     */
    private function getMenuType(){
        $type = array();
        $type[] = WechatConfig::EVENT_MENU_VIEW;
        $type[] = WechatConfig::EVENT_MENU_CLICK;
        $type[] = WechatConfig::EVENT_MENU_SCAN_PUSH;
        $type[] = WechatConfig::EVENT_MENU_SCAN_WAITMSG;
        $type[] = WechatConfig::EVENT_MENU_PIC_SYS;
        $type[] = WechatConfig::EVENT_MENU_PIC_PHOTO;
        $type[] = WechatConfig::EVENT_MENU_PIC_WEIXIN;
        $type[] = WechatConfig::EVENT_MENU_LOCATION;
        return $type;
    }

    /**
     * 自定义菜单查询数据解析
     * @author zhangzj
     * @param array     [查询自定义菜单返回值]
     * @return array
     */
    public function menuDataParse($content) {
        $menu_type = self::getMenuType();
        if(is_array($content) && !empty($content['menu'])) {
            $menus = array();
            foreach($content['menu']['button'] as $val) {
                $m = array();
                $m['type'] = in_array($val['type'], $menu_type) ? $val['type'] : WechatConfig::EVENT_MENU_CLICK;
                $m['name'] = $val['name'];
                if($m['type'] != WechatConfig::EVENT_MENU_VIEW) {
                    $m['key'] = $val['key'];
                } else {
                    $m['url'] = $val['url'];
                }
                $m['sub_button'] = array();
                if(!empty($val['sub_button'])) {
                    foreach($val['sub_button'] as $v) {
                        $s = array();
                        $s['type'] = in_array($v['type'], $menu_type) ? $v['type'] : WechatConfig::EVENT_MENU_CLICK;
                        $s['name'] = $v['name'];
                        if($s['type'] != WechatConfig::EVENT_MENU_VIEW) {
                            $s['key'] = $v['key'];
                        } else {
                            $s['url'] = $v['url'];
                        }
                        $m['sub_button'][] = $s;
                    }
                }
                $menus[] = $m;
            }
            $menu['button'] = $menus;
            return $menu;
        } else {
            if(is_array($content) && !empty($content['errcode'])) {
                return $content['errcode'];
            } else {
                return array();
            }
        }
    }

    /**
     * 自定义菜单新增/修改数据处理
     * @author zhangzj
     * @param unknown   [创建自定义菜单参数]
     * @return json
     */
    public function menuDataBuild($content) {
        $menu = $menu['button'] = array();
        $menu_type = self::getMenuType();
        if($content['verified'] === true){
            if(is_array($content['button']) && !empty($content['button'])){
                foreach($content['button'] as $val) {
                    $data = array();
                    $data['name'] = urlencode($val['name']);
                    if(!empty($val['sub_button'])) {
                        $data['sub_button'] = array();
                        foreach($val['sub_button'] as $v) {
                            $d = array();
                            $d['name'] = urlencode($v['name']);
                            if(in_array($v['type'], $menu_type)){
                                $d['type'] = $v['type'];
                                if($v['type'] == WechatConfig::EVENT_MENU_VIEW){
                                    $d['url'] = urlencode($v['url']);
                                }else{
                                    $d['key'] = urlencode($v['key']);
                                }
                            }else{
                                $d['type'] = WechatConfig::EVENT_MENU_CLICK;
                                $d['key'] = urlencode($v['key']);
                            }
                            $data['sub_button'][] = $d;
                        }
                    } else {
                        if(in_array($val['type'], $menu_type)){
                            $data['type'] = $val['type'];
                            if($val['type'] == WechatConfig::EVENT_MENU_VIEW){
                                $data['url'] = urlencode($val['url']);
                            }else{
                                $data['key'] = urlencode($val['key']);
                            }
                        }else{
                            $data['type'] = WechatConfig::EVENT_MENU_CLICK;
                            $data['key'] = urlencode($val['key']);
                        }
                    }
                    $menu['button'][] = $data;
                }
            }
        }else{
            if(is_array($content['button']) && !empty($content['button'])){
                foreach($content['button'] as $val) {
                    $data = array();
                    $data['name'] = urlencode($val['name']);
                    if(!empty($val['sub_button'])) {
                        $data['sub_button'] = array();
                        foreach($val['sub_button'] as $v) {
                            $d = array();
                            $d['name'] = urlencode($v['name']);
                            if($v['type'] == WechatConfig::EVENT_MENU_VIEW){
                                $d['type'] = WechatConfig::EVENT_MENU_VIEW_LIMITED;
                            }else{
                                $d['type'] = WechatConfig::EVENT_MENU_MEDIA_ID;
                            }
                            $d['media_id'] = urlencode($v['key']);
                            $data['sub_button'][] = $d;
                        }
                    } else {
                        if($val['type'] == WechatConfig::EVENT_MENU_VIEW){
                            $data['type'] = WechatConfig::EVENT_MENU_VIEW_LIMITED;
                        }else{
                            $data['type'] = WechatConfig::EVENT_MENU_MEDIA_ID;
                        }
                        $data['media_id'] = urlencode($val['key']);
                    }
                    $menu['button'][] = $data;
                }
            }
        }
        $res = json_encode($menu);
        $res = urldecode($res);
        return $res;
    }
}

/**
 * 素材类
 * @author zhangzj
 *
 */
class WechatMedia extends WechatDataBase{

    /**
     * 新增永久素材[图片/视频/音频]数据处理
     * @author zhangzj
     * @param string    $path   素材绝对地址
     */
    public function uploadMediaDataBuild($filepath,$description){
        if(empty($filepath)) return array();
        $filename = basename($filepath);
        if(class_exists('CURLFile')){
            $_data = array(
                'media' => new CURLFile($filepath,'',$filename)
            );
        }else{
            $_data = array(
                'media' => '@'.$filepath.';filename='.$filename
            );
        }
        if(!empty($description)){
            $_data['description'] = json_encode($description, JSON_UNESCAPED_UNICODE);
        }
        return $_data;
    }

    /**
     * 新增永久图文素材数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function uploadNewsDataBuild($data){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        foreach ($data as $v){
            $row[] = array(
                'title'              => $v['title'],
                'author'             => $v['author'],
                'digest'             => $v['digest'],
                'content'            => $v['content'],
                'show_cover_pic'     => isset($v['show_cover_pic']) ? $v['show_cover_pic'] : 0,
                'content_source_url' => $v['content_source_url'],
                'thumb_media_id'     => $v['thumb_media_id'],
            );
        }
        $result['articles'] = $row;
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 修改永久图文素材数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function modifyNewsDataBuild($data){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        $result['media_id'] = urlencode($data['media_id']);
        $result['index']    = intval($data['index']);
        $result['articles'] = array(
            'title'              => $data['title'],
            'author'             => $data['author'],
            'digest'             => $data['digest'],
            'content'            => $data['content'],
            'show_cover_pic'     => isset($v['show_cover_pic']) ? $data['show_cover_pic'] : 0,
            'content_source_url' => $data['content_source_url'],
            'thumb_media_id'     => $data['thumb_media_id'],
        );
        return json_encode($result,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取永久图文素材数据处理
     * @author zhangzj
     * @param string    $mediaid 
     */
    public function getMediaDataBuild($mediaid){
        if(empty($mediaid)) {
            return json_encode(array());
        }
        $data['media_id'] = $mediaid;
        return json_encode($data);
    }

    /**
     * 获取永久素材列表数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function getMediaListDataBuild($data){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        $result = array(
            'type'   => $data['type'],
            'offset' => intval($data['offset']),
            'count'  => intval($data['count']) ? intval($data['count']) : 1
        );
        return json_encode($result);
    }
}

/**
 * 用户标签类
 * @author zhangzj
 *
 */
class WechatUserTags extends WechatDataBase{

    /**
     * 创建/修改用户标签数据处理
     * @author zhangzj
     * @param array    $data 
     * @param string   $type
     */
    public function creatUserTagDataBuild($data, $type = 'add'){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        if($type == 'modify'){
            $result['tag'] = array(
                'id'   => intval($data['id']),
                'name' => urlencode($data['name'])
            );
        }elseif($type == 'del'){
            $result['tag'] = array(
                'id' => intval($data['id'])
            );
        }else{
            $result['tag'] = array(
                'name' => urlencode($data['name'])
            );
        }
        return urldecode(json_encode($result));
    }

    /**
     * 获取粉丝列表数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function getUserTagDataBuild($data){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        $result = array(
            'tagid'       => intval($data['tagid']),
            'next_openid' => urlencode($data['next_openid'])
        );
        return urldecode(json_encode($result));
    }

    /**
     * 批量打/删标签数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function addUserTagDataBuild($data){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        $result = array(
            'openid_list' => array_values($data['openid_list']),
            'tagid'       => intval($data['tagid'])
        );
        return json_encode($result);
    }

    /**
     * 获取用户标签列表数据处理
     * @author zhangzj
     * @param string    $openid 
     */
    public function getUserByTagDataBuild($openid){
        if(empty($openid)) {
            return json_encode(array());
        }
        $result = array(
            'openid' => urlencode($openid),
        );
        return urldecode(json_encode($result));
    }

    /**
     * 设置用户备注名数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function userMarkDataBuild($data){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        $result = array(
            'openid' => urlencode($data['openid']),
            'remark' => urlencode($data['remark'])
        );
        return urldecode(json_encode($result));
    }

    /**
     * 批量获取用户信息数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function userInfoBatchGetDataBuild($data){
        if(empty($data['openid']) || !is_array($data['openid'])) {
            return json_encode(array());
        }
        $result = array();
        foreach ($data['openid'] as $key => $val) {
            $result['user_list'][] = array(
                'openid' => urlencode($val),
                'lang'   => $data['lang'] ? $data['lang'] : 'zh_CN',
            );
        }
        return urldecode(json_encode($result));
    }

    /**
     * 批量[取消]拉黑用户数据处理
     * @author zhangzj
     * @param array    $data 
     */
    public function memberBatchBlackDataBuild($data){
        if(empty($data) || !is_array($data)) {
            return json_encode(array());
        }
        $result['openid_list'] = array_values($data);
        return json_encode($result);
    }
}

/**
 * 消息类
 * @author wangcb
 *
 */
class WechatMsg extends WechatDataBase{
    /**
     * 回复文本消息
     */
    public function text($data) {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>  
            <FromUserName><![CDATA[%s]]></FromUserName>  
            <CreateTime>%s</CreateTime>  
            <MsgType><![CDATA[%s]]></MsgType>  
            <Content><![CDATA[%s]]></Content>  
            </xml>";
        $emoji = WechatConfig::$emoji;
        $content = preg_replace_callback('/(<img src="https:\/\/res.wx.qq.com\/mpres\/htmledition\/images\/icon\/emotion\/(\d+)\.gif"[\s\S^<>]*?>)/i',function($v) use ($emoji){return $emoji[$v[2]];}, $data['Content']);
        $resultStr = sprintf($textTpl, $data['ToUserName'], $data['FromUserName'], $data['CreateTime'], WechatConfig::MSGTYPE_TEXT, $content);
        return $resultStr;
    }
    /**
     * 回复图片消息
     */
    public function image($data) {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Image>
            <MediaId><![CDATA[%s]]></MediaId>
            </Image>
            </xml>";
        $resultStr = sprintf($textTpl, $data['ToUserName'], $data['FromUserName'], time(), WechatConfig::MSGTYPE_IMAGE, $data['MediaId']);
        return $resultStr;
    }
    /**
     * 回复语音消息
     */
    public function voice($data) {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Voice>
            <MediaId><![CDATA[%s]]></MediaId>
            </Voice>
            </xml>";
        $resultStr = sprintf($textTpl, $data['ToUserName'], $data['FromUserName'], time(), WechatConfig::MSGTYPE_VOICE, $data['MediaId']);
        return $resultStr;
    }
    /**
     * 回复视频消息
     */
    public function video($data) {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Video>
            <MediaId><![CDATA[%s]]></MediaId>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            </Video>
            </xml>";
        $resultStr = sprintf($textTpl, $data['ToUserName'], $data['FromUserName'], time(), WechatConfig::MSGTYPE_VIDEO, $data['MediaId'], $data['Title'], $data['Description']);
        return $resultStr;
    }
    /**
     * 回复音乐消息
     */
    public function music($data){
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Music>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <MusicUrl><![CDATA[%s]]></MusicUrl>
            <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
            <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
            </Music>
            </xml>";
        $resultStr = sprintf($textTpl, $data['ToUserName'], $data['FromUserName'], time(), WechatConfig::MSGTYPE_MUSIC, $data['Title'], $data['Description'], $data['MusicUrl'], $data['HQMusicUrl'], $data['ThumbMediaId']);
        return $resultStr;
    }
    /**
     * 回复图文消息
     * @author wangcb
     * @param unknown $param
     */
    public function news($data) {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>";
        for ($i=0; $i < count($data['news']); $i++){
            $newsTpl = '<item>
            <Title><![CDATA[%s]]></Title> 
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>';
            $textTpl .= sprintf($newsTpl, $data['news'][$i]['Title'], $data['news'][$i]['Description'], $data['news'][$i]['PicUrl'], $data['news'][$i]['Url']);
        }
        $textTpl .= '</Articles>';
        $textTpl .= "</xml>";
        $resultStr = sprintf($textTpl, $data['ToUserName'], $data['FromUserName'], time(), WechatConfig::MSGTYPE_NEWS,count($data['news']));
        return $resultStr;
    }
    /**
     * 主动发送消息
     */
    public function send($data) {
        switch ($data['msgtype']) {
            case 'text':
                $emoji = WechatConfig::$emoji;
                $content = preg_replace_callback('/(<img src="https:\/\/res.wx.qq.com\/mpres\/htmledition\/images\/icon\/emotion\/(\d+)\.gif"[\s\S^<>]*?>)/i',function($v) use ($emoji){return $emoji[$v[2]];}, $data['text']['content']);
                $content = strip_tags($content);
                $msgContent = '{"touser":"%s","msgtype":"text","text":{"content":"%s"}}';
                $msgData = sprintf($msgContent, $data['touser'], $content);
                break;
            case 'image':
                $msgContent = '{"touser":"%s","msgtype":"image","image":{"media_id":"%s"}}';
                $msgData = sprintf($msgContent, $data['touser'], $data['image']['mediaid']);
                break;
            case 'voice':
                $msgContent = '{"touser":"%s","msgtype":"voice","voice":{"media_id":"%s"}}';
                $msgData = sprintf($msgContent, $data['touser'], $data['voice']['mediaid']);
                break;
            case 'video':
                $msgContent = '{"touser":"%s","msgtype":"video","video":{"media_id":"%s","title":"%s","description":"%s"}}';
                $msgData = sprintf($msgContent, $data['touser'], $data['video']['mediaid'], $data['video']['title'], $data['video']['description']);
                break;
            case 'news':
                $msgContent = '{"touser":"%s","msgtype":"news","news":{"articles": [%s]}}';
                $msgItem = '{"title":"%s","description":"%s","url":"%s","picurl":"%s"}';
                $msgItems = '';
                foreach ($data['news'] as $key => $value) {
                    $msgItems .= ','.sprintf($msgItem, $value['Title'], $value['Description'], $value['Url'], $value['PicUrl']);
                }
                $msgItems = ltrim($msgItems,",");
                $msgData = sprintf($msgContent, $data['touser'], $msgItems);
                break;
            default:
                return false;
                break;
        }
        return $msgData;
    }
    public function SetKfAccount($value) {
        $this->values['kf_account'] = $value;
    }
    public function GetKfAccount() {
        return $this->values['kf_account'];
    }
    public function SetKfNickName($value) {
        $this->values['nickname'] = $value;
    }
    public function GetKfNickName() {
        return $this->values['nickname'];
    }
    public function SetKfPassword($value) {
        $this->values['password'] = md5($value);
    }
    public function GetKfPassword() {
        return $this->values['password'];
    }
}
/**
 * 对账单类
 * @author Administrator
 *
 */
class WechatBill extends WechatDataBase{
    /**
     * 设置公众号id
     * @author wangcb
     * @param unknown $value
     */
    public function SetAppid($value){
        $this->values['appid'] = $value;    
    }
    /**
     * 获取公众号id
     * @author wangcb
     * @return multitype:
     */
    public function GetAppid(){
        return $this->values['appid'];
    }
    /**
     * 设置商户号
     * @author wangcb
     * @param unknown $value
     */
    public function SetMch_id($value){
        $this->values['mch_id'] = $value;
    }
    /**
     * 获取商户号
     * @author wangcb
     * @return multitype:
     */
    public function GetMch_id(){
        return $this->values['mch_id'];
    }
    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value
     * */
    public function SetNonce_str($value) {
        $this->values['nonce_str'] = $value;
    }
    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     * */
    public function GetNonce_str() {
        return $this->values['nonce_str'];
    }
    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     * */
    public function IsNonce_strSet() {
        return array_key_exists('nonce_str', $this->values);
    }
    /**
     * 设置签名类型
     * @author wangcb
     * @param string $value
     * @return string
     */
    public function SetSign_type($value = 'MD5') {
        $this->values['sign_type'] = $value;
    }
    /**
     * 获取签名类型
     * @author wangcb
     * @return multitype:
     */
    public function GetSign_type(){
        return $this->values['sign_type'];
    }
    /**
     * 设置对账单日期
     * @author wangcb
     * @param unknown $value
     * @return unknown
     */
    public function SetBill_date($value){
        $this->values['bill_date'] = $value;
    }
    /**
     * 获取对账单日期
     * @author wangcb
     */
    public function GetBill_date(){
        return $this->values['bill_data'];
    }
    /**
     * 设置账单类型
     * @author wangcb
     * @param unknown $value
     */
    public function SetBill_type($value = 'ALL'){
        $this->values['bill_type'] = $value;
    }
    /**
     * 获取账单类型
     * @author wangcb
     * @return multitype:
     */
    public function GetBill_type(){
        return $this->values['bill_type'];
    }
    /**
     * 设置压缩账单
     * @author wangcb
     * @param unknown $value
     */
    public function SetTar_type($value) {
        $this->values['tar_type'] = $value;
    }
    /**
     * 获取压缩账单
     * @author wangcb
     * @return multitype:
     */
    public function GetTar_type(){
        return $this->values['tar_type'];
    }
}