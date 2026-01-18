<?php

class Rijndael extends RijndaelCore
{
    /**
     * Base64 is not required, but it is be more compact than urlencode
     *
     * @param string $plaintext
     * @return bool|string
     */
    public function encrypt($plaintext)
    {
        $length = (ini_get('mbstring.func_overload') & 2) ? mb_strlen($plaintext, ini_get('default_charset')) : strlen($plaintext);

        if ($length >= 1048576) {
            return false;
        }

        //return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->_key, $plaintext, MCRYPT_MODE_CBC, $this->_iv)).sprintf('%06d', $length);
        return $this->encryptOpenSSL($this->_key, $plaintext, $this->_iv).sprintf('%06d', $length);
    }

    public function decrypt($ciphertext)
    {
        if (ini_get('mbstring.func_overload') & 2) {
            $length = intval(mb_substr($ciphertext, -6, 6, ini_get('default_charset')));
            $ciphertext = mb_substr($ciphertext, 0, -6, ini_get('default_charset'));

            return mb_substr(
                //mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->_key, base64_decode($ciphertext), MCRYPT_MODE_CBC, $this->_iv),
                $this->decryptOpenSSL($this->_key, $ciphertext, $this->_iv),
                0,
                $length,
                ini_get('default_charset')
            );
        } else {
            $length = intval(substr($ciphertext, -6));
            $ciphertext = substr($ciphertext, 0, -6);
            //return substr(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->_key, base64_decode($ciphertext), MCRYPT_MODE_CBC, $this->_iv), 0, $length);
            return substr($this->decryptOpenSSL($this->_key, $ciphertext, $this->_iv), 0, $length);
        }
    }

    public function encryptOpenSSL($key, $str, $iv) {
        // $ciphertext = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_CBC, $iv));
        if (($l = (strlen($str) & 15)) > 0) { $str .= str_repeat(chr(0), 16 - $l); }
        $ciphertext = base64_encode(openssl_encrypt($str, 'aes-256-cbc', $key,  OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv));
        return $ciphertext;
    }

    public function decryptOpenSSL($key, $str, $iv) {
        // $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($str), MCRYPT_MODE_CBC, $iv);
        $plaintext_dec = openssl_decrypt(base64_decode($str), 'aes-256-cbc', $key,  OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
        return $plaintext_dec;
    }
}