<?php
/**
 * 
 * 微信支付API异常类
 * @author wangcb
 *
 */
class WechatException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
