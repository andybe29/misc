<?php
/**
 * @author andy.bezbozhny <andy.bezbozhny@gmail.com>
 */
class simpleCurl
{
    const USER_AGENT = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; ru; rv:1.9.2.13) Gecko/20101203 MRA 5.7 (build 03796) Firefox/3.6.13';

    static $error, $httpCode;

    /**
     * @param string  $url   URL
     * @param string  $proxy URL прокси-сервера
     */
    public static function get($url, $proxy = null)
    {
        self::$error    = null;
        self::$httpCode = null;

        $header   = [];
        $header[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
        $header[] = 'Cache-Control: max-age=0';
        $header[] = 'Connection: keep-alive';
        $header[] = 'Keep-Alive: 300';
        $header[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
        $header[] = 'Accept-Language: en-us,en;q=0.5';
        $header[] = 'Pragma: ';

        $header   = [];
        $header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $header[] = 'Accept-Encoding: gzip, deflate';
        $header[] = 'Accept-Language: en-US,en;q=0.5';
        $header[] = 'Connection: keep-alive';

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL,            $url);
        curl_setopt($curl, CURLOPT_USERAGENT,      self::USER_AGENT);
        if ($proxy) {
            curl_setopt($curl, CURLOPT_PROXY,      $proxy);
            curl_setopt($curl, CURLOPT_PROXYTYPE,  CURLPROXY_HTTP);
        }
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER,     $header);
        curl_setopt($curl, CURLOPT_ENCODING,       'gzip,deflate');
        curl_setopt($curl, CURLOPT_AUTOREFERER,    true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT,        10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($curl);

        if ($errno = curl_errno($curl)) {
            self::$error = (object)['no' => $errno, 'what' => curl_error($curl)];
        }

        self::$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return $res ? $res : false;
    }

}
