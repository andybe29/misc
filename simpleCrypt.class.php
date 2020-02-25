<?php
# http://qaru.site/questions/23478/php-7-mcrypt-deprecated-need-alternative
class simpleCrypt
{
    const SIMPLE_KEY  = 'very simple key';
    const SESS_CIPHER = 'AES-128-CBC';

    private $key, $iv;

    public function __construct($key = self::SIMPLE_KEY)
    {
        $this->key = hash('sha256', $key, true);

        $ivlen = openssl_cipher_iv_length(self::SESS_CIPHER);
        $this->iv = substr(md5($this->key), 0, $ivlen);
    }

    public function encrypt($data = null)
    {
        if ($data === null) false;
        $data = json_encode($data);

        $cipher = openssl_encrypt($data, self::SESS_CIPHER, $this->key, OPENSSL_RAW_DATA, $this->iv);

        return $cipher ? base64_encode($cipher) : false;
    }

    public function decrypt($hash)
    {
        $data = base64_decode($hash, true);
        $data = openssl_decrypt($data, self::SESS_CIPHER, $this->key, OPENSSL_RAW_DATA, $this->iv);
        $data = json_decode(rtrim($data, "\0"), true);

        return $data;
    }
}