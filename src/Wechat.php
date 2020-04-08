<?php
namespace v10086\pay;

class Wechat {
    
    //格式化参数格式化成url参数
    public static function toUrlParams($values){
        $buff = "";
        foreach ($values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }


    // 产生随机字符串，不长于32位
    // int $length
    // 产生的随机字符串
    public static function getNonceStr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        } 
        return $str;
    }


    // 生成签名
    // @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
    public static function makeSign($values,$key){
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = self::toUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    
    //验证签名
    public static function checkSign($values,$key,$sign){
        $n_sign = self::makeSign($values, $key);
        if($n_sign!==$sign){
                return FALSE;
        }
        return TRUE;
    }
    
    // 输出xml字符
    public static function toXml($values){
        if(!is_array($values) || count($values) <= 0){
            throw new Exception("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($values as $key=>$val)
        {
                if (is_numeric($val)){
                        $xml.="<".$key.">".$val."</".$key.">";
                }else{
                        $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
                }
        }
        $xml.="</xml>";
        return $xml; 
    }
    
    // 将xml转为array
    // string $xml
    // WxPayException
    public static function fromXml($xml){	
            if(!$xml){
                    throw new Exception("xml数据异常！");
            }
            //将XML转为array
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $xml = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
            return $xml;
    }
    
    // 仅用以微信支付post方式提交xml到对应的接口url
    // @param string $xml  需要post的xml数据
    // @param string $url  url
    // @param bool $useCert 是否需要证书，默认不需要
    // @param int $second   url执行超时时间，默认30s
    // @throws WxPayException
    public static function postXmlCurl($xml, $url, $useCert = false, $second = 30){		
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
                //设置证书
                //使用证书：cert 与 key 分别属于两个.pem文件
                curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLCERT,config('pay.wechat.cert_client'));
                curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLKEY, config('pay.wechat.cert_key'));
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
                curl_close($ch);
                return $data;
        } else { 
                $error = curl_errno($ch);
                curl_close($ch);
                throw new \Exception("curl出错，错误码:$error");
        }
    }
    

    
}
