<?php
/**
 * @author andy.bezbozhny <andy.bezbozhny@gmail.com>
 */
class simpleCurl
{
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';

    const HTTP_CODE_100 = 100;
    const HTTP_CODE_101 = 101;
    const HTTP_CODE_102 = 102;
    const HTTP_CODE_103 = 103;

    const HTTP_CODE_200 = 200;
    const HTTP_CODE_201 = 201;
    const HTTP_CODE_202 = 202;
    const HTTP_CODE_203 = 203;
    const HTTP_CODE_204 = 204;
    const HTTP_CODE_205 = 205;
    const HTTP_CODE_206 = 206;
    const HTTP_CODE_207 = 207;
    const HTTP_CODE_208 = 208;
    const HTTP_CODE_226 = 226;

    const HTTP_CODE_300 = 300;
    const HTTP_CODE_301 = 301;
    const HTTP_CODE_302 = 302;
    const HTTP_CODE_303 = 303;
    const HTTP_CODE_304 = 304;
    const HTTP_CODE_305 = 305;
    const HTTP_CODE_306 = 306;
    const HTTP_CODE_307 = 307;
    const HTTP_CODE_308 = 308;

    const HTTP_CODE_400 = 400;
    const HTTP_CODE_401 = 401;
    const HTTP_CODE_402 = 402;
    const HTTP_CODE_403 = 403;
    const HTTP_CODE_404 = 404;
    const HTTP_CODE_405 = 405;
    const HTTP_CODE_406 = 406;
    const HTTP_CODE_407 = 407;
    const HTTP_CODE_408 = 408;
    const HTTP_CODE_409 = 409;
    const HTTP_CODE_410 = 410;
    const HTTP_CODE_411 = 411;
    const HTTP_CODE_412 = 412;
    const HTTP_CODE_413 = 413;
    const HTTP_CODE_414 = 414;
    const HTTP_CODE_415 = 415;
    const HTTP_CODE_416 = 416;
    const HTTP_CODE_417 = 417;
    const HTTP_CODE_418 = 418;
    const HTTP_CODE_421 = 421;
    const HTTP_CODE_422 = 422;
    const HTTP_CODE_423 = 423;
    const HTTP_CODE_424 = 424;
    const HTTP_CODE_425 = 425;
    const HTTP_CODE_426 = 426;
    const HTTP_CODE_428 = 428;
    const HTTP_CODE_429 = 429;
    const HTTP_CODE_431 = 431;
    const HTTP_CODE_451 = 451;

    const HTTP_CODE_500 = 500;
    const HTTP_CODE_501 = 501;
    const HTTP_CODE_502 = 502;
    const HTTP_CODE_503 = 503;
    const HTTP_CODE_504 = 504;
    const HTTP_CODE_505 = 505;
    const HTTP_CODE_506 = 506;
    const HTTP_CODE_507 = 507;
    const HTTP_CODE_508 = 508;
    const HTTP_CODE_510 = 510;
    const HTTP_CODE_511 = 511;

    static $error, $httpCode, $httpInfo;

    static $httpCodes = [
        self::HTTP_CODE_100 => 'Continue',
        self::HTTP_CODE_101 => 'Switching Protocols',
        self::HTTP_CODE_102 => 'Processing',
        self::HTTP_CODE_103 => 'Name Not Resolved',

        self::HTTP_CODE_200 => 'OK',
        self::HTTP_CODE_201 => 'Created',
        self::HTTP_CODE_202 => 'Accepted',
        self::HTTP_CODE_203 => 'Non-Authoritative Information',
        self::HTTP_CODE_204 => 'No Content',
        self::HTTP_CODE_205 => 'Reset Content',
        self::HTTP_CODE_206 => 'Partial Content',
        self::HTTP_CODE_207 => 'Multi-Status',
        self::HTTP_CODE_208 => 'Already Reported',
        self::HTTP_CODE_226 => 'IM Used',

        self::HTTP_CODE_300 => 'Multiple Choices',
        self::HTTP_CODE_301 => 'Moved Permanently',
        self::HTTP_CODE_302 => 'Moved Temporarily',
        self::HTTP_CODE_303 => 'See Other',
        self::HTTP_CODE_304 => 'Not Modified',
        self::HTTP_CODE_305 => 'Use Proxy',
        self::HTTP_CODE_306 => 'Switch Proxy',
        self::HTTP_CODE_307 => 'Temporary Redirect',
        self::HTTP_CODE_308 => 'Permanent Redirect',

        self::HTTP_CODE_400 => 'Bad Request',
        self::HTTP_CODE_401 => 'Unauthorized',
        self::HTTP_CODE_402 => 'Payment Required',
        self::HTTP_CODE_403 => 'Forbidden',
        self::HTTP_CODE_404 => 'Not Found',
        self::HTTP_CODE_405 => 'Method Not Allowed',
        self::HTTP_CODE_406 => 'Not Acceptable',
        self::HTTP_CODE_407 => 'Proxy Authentication Required',
        self::HTTP_CODE_408 => 'Request Time-out',
        self::HTTP_CODE_409 => 'Conflict',
        self::HTTP_CODE_410 => 'Gone',
        self::HTTP_CODE_411 => 'Length Required',
        self::HTTP_CODE_412 => 'Precondition Failed',
        self::HTTP_CODE_413 => 'Request Entity Too Large',
        self::HTTP_CODE_414 => 'Request-URI Too Large',
        self::HTTP_CODE_415 => 'Unsupported Media Type',
        self::HTTP_CODE_416 => 'Requested Range Not Satisfiable',
        self::HTTP_CODE_417 => 'Expectation Failed',
        self::HTTP_CODE_418 => 'I\'m a teapot',
        self::HTTP_CODE_421 => 'Misdirected Request',
        self::HTTP_CODE_422 => 'Unprocessable Content',
        self::HTTP_CODE_423 => 'Locked',
        self::HTTP_CODE_424 => 'Failed Dependency',
        self::HTTP_CODE_425 => 'Too Early',
        self::HTTP_CODE_426 => 'Upgrade Required',
        self::HTTP_CODE_428 => 'Precondition Required',
        self::HTTP_CODE_429 => 'Too Many Requests',
        self::HTTP_CODE_431 => 'Request Header Fields Too Large',
        self::HTTP_CODE_451 => 'Unavailable For Legal Reasons',

        self::HTTP_CODE_500 => 'Internal Server Error',
        self::HTTP_CODE_501 => 'Not Implemented',
        self::HTTP_CODE_502 => 'Bad Gateway',
        self::HTTP_CODE_503 => 'Service Unavailable',
        self::HTTP_CODE_504 => 'Gateway Timeout',
        self::HTTP_CODE_505 => 'HTTP Version Not Supported',
        self::HTTP_CODE_506 => 'Variant Also Negotiates',
        self::HTTP_CODE_507 => 'Insufficient Storage',
        self::HTTP_CODE_508 => 'Loop Detected',
        self::HTTP_CODE_510 => 'Not Extended',
        self::HTTP_CODE_511 => 'Network Authentication Required',
    ];

    /**
     * @param string  $url   URL
     * @param string  $proxy URL прокси-сервера
     */
    public static function _get($url, $proxy = null)
    {
        self::$error    = null;
        self::$httpCode = null;
        self::$httpInfo = null;

        $header   = [];
        $header['Accept']           = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7';
        $header['Accept-Encoding']  = 'gzip, deflate, br';
        $header['Accept-Language']  = 'en-US,en;q=0.9,ru-RU;q=0.8,ru;q=0.7';
        $header['Cache-Control']    = 'max-age=0';
        $header['Connection']       = 'keep-alive';

        $header = array_map(function($key, $value) { return $key . ': ' . $value; }, array_keys($header), $header);

        $curl = curl_init();

        $options = [
            CURLOPT_AUTOREFERER     => true,
            CURLOPT_CONNECTTIMEOUT  => 6,
            CURLOPT_ENCODING        => 'gzip,deflate',
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTPHEADER      => $header,
            CURLOPT_POST            => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_TIMEOUT         => 12,
            CURLOPT_URL             => $url,
            CURLOPT_USERAGENT       => self::USER_AGENT
        ];

        if ($proxy) {
            $options[CURLOPT_PROXY]     = $proxy;
            $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        }

        curl_setopt_array($curl, $options);

        $result = curl_exec($curl);

        if ($errno = curl_errno($curl)) {
            self::$error = new stdClass;
            self::$error->code        = $errno;
            self::$error->description = curl_error($curl);
        }

        self::$httpInfo = curl_getinfo($curl);
        self::$httpCode = self::$httpInfo['http_code'];

        curl_close($curl);

        return empty($result) ? null : $result;
    }

}
