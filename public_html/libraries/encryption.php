<?php

/**
 * Class Encryption
 *
 * @author Travis Neal
 */
class Encryption
{

    private
        $_keys = array(
        'private' => array(
            'key' => false,
            'password' => null //if the key requires a password to open
        ),
        'public' => array(
            'key' => false
        )
    ),
        $_chunked_encryption_delimiter = "\n";

    public function __construct($private_key_path = null, $public_key_path = null)
    {
        $this->_keys['private']['file'] = "file:///" . (is_null($private_key_path) ? ROOTPATH . "keys/pr.der" : $private_key_path);
        $this->_keys['public']['file'] = "file:///" . (is_null($public_key_path) ? ROOTPATH . "keys/pu.pem" : $public_key_path);
    }

    public function serverEncrypt($item)
    {
        if (is_string($item)) {
            if (strlen($item) > 245) { //openssl has a max string length limit of 245, so we need to chunk those out and make the returned string longer.
                $return_str = "";
                foreach (explode("\n", chunk_split($item, 245, "\n")) as $chunk) {
                    if (!empty($chunk)) {
                        $encrypted = $this->_encryptString($chunk);
                        if ($encrypted !== false) {
                            $return_str .= $this->_chunked_encryption_delimiter . $encrypted;
                        }
                    }
                }
                return !empty($return_str) ? substr($return_str, strlen($this->_chunked_encryption_delimiter)) : false;
            }
            return $this->_encryptString($item);
        }
        return false;
    }

    public function serverDecrypt($encryptedItem)
    {
        if (is_string($encryptedItem)) {
            if (strpos($encryptedItem, $this->_chunked_encryption_delimiter)) {
                $return_str = "";
                foreach (explode($this->_chunked_encryption_delimiter, $encryptedItem) as $chunk) {
                    if (strlen($chunk) === 344) {
                        $decrypted = $this->_decryptString($chunk);
                        if ($decrypted !== false) {
                            $return_str .= $decrypted;
                        }
                    }
                }
                return !empty($return_str) ? $return_str : false;
            }
            return $this->_decryptString($encryptedItem);
        }
        return false;
    }

    public function publicDecrypt($encryptedItem)
    {
        if (is_string($encryptedItem)) {
            if (strpos($encryptedItem, $this->_chunked_encryption_delimiter)) {
                $return_str = "";
                foreach (explode($this->_chunked_encryption_delimiter, $encryptedItem) as $chunk) {
                    if (strlen($chunk) === 512) {
                        $decrypted = $this->_decryptPublicString($chunk);
                        if ($decrypted !== false) {
                            $return_str .= $decrypted;
                        }
                    }
                }
                return !empty($return_str) ? $return_str : false;
            }
            return $this->_decryptPublicString($encryptedItem);
        }
        return false;
    }

    public function keyDetails($key = 'public')
    {
        $returnable = array();
        if (array_key_exists($key, $this->_keys)) {
            if ($this->_keys[$key]['key'] === false) {
                $this->{'_load' . ucfirst(strtolower($key)) . 'Key'}();
            }
            if (isset($this->_keys[$key]['details'])) {
                $returnable['n'] = strtoupper(bin2hex($this->_keys[$key]['details']['rsa']['n']));
                $returnable['e'] = strtoupper(bin2hex($this->_keys[$key]['details']['rsa']['e']));
            }
        } else {
            throw new Exception("Unknown key [$key]");
        }
        return $returnable;
    }

    protected function _encryptString($string)
    {
        if ($this->_keys['private']['key'] === false) {
            $this->_loadPrivateKey();
        }
        if (is_string($string)) {
            if (function_exists('openssl_private_encrypt')) {
                if ($this->_keys['private']['key'] !== false) {
                    $encrypted = null;
                    return openssl_private_encrypt($string, $encrypted,
                        $this->_keys['private']['key']) ? base64_encode($encrypted) : false;
                } else {
                    throw new Exception('Reading the encryption key failed.');
                }
            } else {
                throw new Exception('Cannot encrypt string, openssl_private_encrypt() is not a valid function.');
            }
        }
        return false;
    }

    protected function _decryptString($string)
    {
        if ($this->_keys['public']['key'] === false) {
            $this->_loadPublicKey();
        }
        if (is_string($string)) {
            if (function_exists('openssl_public_decrypt')) {
                if ($this->_keys['public']['key'] !== false) {
                    $decrypted = null;
                    return openssl_public_decrypt(base64_decode($string), $decrypted,
                        $this->_keys['public']['key']) ? $decrypted : false;
                } else {
                    throw new Exception('Reading the decryption key failed.');
                }
            } else {
                throw new Exception('Cannot decrypt string, openssl_public_decrypt() is not a valid function.');
            }
        }
        return false;
    }

    protected function _decryptPublicString($string)
    {
        $string = pack('H*', $string);
        if ($this->_keys['private']['key'] === false) {
            $this->_loadPrivateKey();
        }
        if (is_string($string)) {
            if (function_exists('openssl_private_decrypt')) {
                if ($this->_keys['private']['key'] !== false) {
                    $decrypted = null;
                    return openssl_private_decrypt($string, $decrypted,
                        $this->_keys['private']['key']) ? $decrypted : false;
                } else {
                    throw new Exception('Reading the decryption key failed.');
                }
            } else {
                throw new Exception('Cannot decrypt string, openssl_private_decrypt() is not a valid function.');
            }
        }
        return false;
    }

    protected function _loadPrivateKey()
    {
        if (function_exists('openssl_pkey_get_private')) {
            if (file_exists($this->_keys['private']['file'])) {
                //has to read from a der file, and a pem file is required for encrpytions
                $key = $this->_privateDerToPrivatePem(file_get_contents($this->_keys['private']['file']));
                $this->_keys['private']['key'] = openssl_pkey_get_private($key,
                    !empty($this->_keys['private']['password']) ? $this->_keys['private']['password'] : "");
                if (function_exists('openssl_pkey_get_details')) {
                    $this->_keys['private']['details'] = openssl_pkey_get_details($this->_keys['private']['key']);
                }
            } else {
                throw new Exception("Private key file does not exist.");
            }
        } else {
            throw new Exception('openssl_pkey_get_private() is required for this piece of the Encryption class to work.');
        }
    }

    protected function _loadPublicKey()
    {
        if (function_exists('openssl_pkey_get_public')) {
            if (file_exists($this->_keys['public']['file'])) {
                $key = file_get_contents($this->_keys['public']['file']);
                $this->_keys['public']['key'] = openssl_pkey_get_public($key);
                if (function_exists('openssl_pkey_get_details')) {
                    $this->_keys['public']['details'] = openssl_pkey_get_details($this->_keys['public']['key']);
                }
            } else {
                throw new Exception("Public key file does not exist.");
            }
        } else {
            throw new Exception('openssl_pkey_get_public() is required for this piece of the Encryption class to work.');
        }
    }

    protected function _privateDerToPrivatePem($privateDer)
    {
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n";
        $pem .= chunk_split(base64_encode($privateDer), 64, "\n");
        $pem .= "-----END RSA PRIVATE KEY-----\n";
        return $pem;
    }
}