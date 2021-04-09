<?php
namespace v10086\pay;

class Ali {
    
    //格式化参数格式化成url参数
    public static function toUrlParams($values){
        $buff = "";
        foreach ($values as $k => $v){
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }


    //支付宝生成签名
    public static function makeSign($priKey,$data, $signType = "RSA"){
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = self::toUrlParams($data);
        $key = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if ("RSA2" == $signType) {
            openssl_sign($string, $sign, $key, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($string, $sign, $key);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
        
    // rsaCheckV1 & rsaCheckV2
    // 支付宝 验证签名
    // 在使用本方法前，必须初始化AopClient且传入公钥参数。
    // 公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
    public static function rsaCheckV1($params, $pubKey,$signType='RSA') {
            $sign = $params['sign'];
            $params['sign_type'] = null;
            $params['sign'] = null;
            return self::verify(self::getSignContent($params), $sign, $pubKey,$signType);
    }
    public static function rsaCheckV2($params, $pubKey, $signType='RSA') {
            $sign = $params['sign'];
            $params['sign'] = null;
            return self::verify(self::getSignContent($params), $sign, $pubKey, $signType);
    }
    
    public static function verify($data, $sign, $pubKey, $signType = 'RSA') {
		$res = "-----BEGIN PUBLIC KEY-----\n" .
			wordwrap($pubKey, 64, "\n", true) .
			"\n-----END PUBLIC KEY-----";

		if ("RSA2" == $signType) {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		}
		//释放资源
		//openssl_free_key($res);
		return $result;
	}
    //支付宝生 成签名内容
    public static function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
                if (false === self::checkEmpty($v) && "@" != substr($v, 0, 1)) {
                        if ($i == 0) {
                                $stringToBeSigned .= "$k" . "=" . "$v";
                        } else {
                                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                        }
                        $i++;
                }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    // 支付宝 校验$value是否非空
    // if not set ,return true;
    // if is null , return true;
    public static function checkEmpty($value) {
            if (!isset($value))
                    return true;
            if ($value === null)
                    return true;
            if (trim($value) === "")
                    return true;
            return false;
    }
    

}
