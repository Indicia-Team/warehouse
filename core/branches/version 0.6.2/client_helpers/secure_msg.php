<?php
/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Helper class to provide secure messaging.
 * Allows data to be encrypted and checked for tampering.
 * Note, there is a copy of this file in client_helpers which
 * must be kept identical to this one.
 *
 * @package	Core
 * @subpackage Helpers
 */
class secure_msg {

  /**
   * constants to identify encrypted data with an embedded hash for anti-tampering
   * and error message from this helper class
   */
  const SEALED = 'secure_msg::__sealed';
  const ERROR_MSG = 'secure_msg::__error_msg';

  /**
   * Returns an encrypted string containing the supplied string and its hash.
   * 1) json_encode array
   * 2) create sha1 hash and prepend to input
   * 3) encrypt with website_password
   * 4) base64 encode so network safe and return
   *
   * @param array $secret secret data as an associative array
   * @return string $sealed Contains encrypted secrets.
   */
  public static function seal($secrets, $website_password)
  {
    $json = json_encode($secrets);
    $hash = sha1($json);
    $sealed = self::encrypt($hash.":".$json, sha1($website_password));
    $result = base64_encode($sealed);
    return $result;
  }

  /**
   * Unpacks and decrypts any secret parameters in the request, namely those with param name __sealed
   * and returns plain text params as an associative array.
   * 1) passes sealed item to self::unseal to unpack and return as decrypted array.
   * 2) merges unpacked secrets with supplied array
   * 3) removes sealed item
   *
   * @param array $input an array of the request params.
   * @return array $unsealed the supplied array with the secrets restored to plain text items.
   * or array containing an error message in the key secure_msg::ERROR_MSG on failure.
   */
  public static function unseal_request($input, $website_password)
  {
    if (! array_key_exists(self::SEALED, $input)) {
      // no secrets to unpack
      return $input;
    }
     
    $secrets = self::unseal($input[self::SEALED], $website_password);
    if (array_key_exists(self::ERROR_MSG, $secrets)) {
      return $secrets;
    }
   	unset($input[self::SEALED]);

   	return array_merge($input, $secrets);
  }

/**
  * Unpacks and decrypts a secret response, namely one which begins with __sealed= 
  * and returns plain text response as a string.
  * 1) base64 decodes sealed string
  * 2) decrypts
  * 3) splits into hash and data on first ':'
  * 4) checks hash matches the data
  *
  * @param array $input an array of the request params.
  * @return array $unsealed the supplied array with the secrets restored to plain text items. 
  */
  public static function unseal_response($input, $website_password)
  {
  	if (strcmp(substr($input, 0, strlen(self::SEALED)), self::SEALED)) {
  		// no secrets to unpack
  		return $input;
  	}
  	
    return self::unseal(substr($input, strlen(self::SEALED)), $website_password);
  }
  
  /**
   * Unpacks and decrypts the coded data
   * and returns plain text params as an associative array.
   * 1) base64 decodes sealed string
   * 2) decrypts
   * 3) splits into hash and json params on first ':'
   * 4) checks hash matches the json data
   * 5) converts json to array
   *
   * @param string $coded a string of coded data.
   * @return array $unsealed an array with the secrets restored to plain text items.
   */
  public static function unseal($coded, $website_password)
  {
    $sealed = base64_decode($coded);
    $unsealed = self::decrypt($sealed, sha1($website_password));
    $parts = explode(":", $unsealed, 2);
    $hash = $parts[0];
    $json = $parts[1];
    if ($hash != sha1($json)) {
      return array(self::ERROR_MSG => 'secure_msg::unseal, wrong hash value, message corrupted');
   	}

   	return json_decode($json, true);
  }

  /**
   * Returns an encrypted string of the input.
   * ToDo, make cipher etc. configurable.
   *
   * @param string $plain The plain text string to encrypt.
   * @param string $key The encryption key
   * @return string $crypt The encrypted input.
   */
  private static function encrypt($plain, $key)
  {
    // note, max key length may be cipher dependent
    if (strlen($key) > 32) {$key = substr($key, 0, 32);}
    //$td = mcrypt_module_open('tripledes', '', 'ecb', '');
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
    $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    mcrypt_generic_init($td, $key, $iv);
    $crypt = mcrypt_generic($td, $plain);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
     
    return $crypt;
  }

  /**
   * Returns a plain text string of the encrypted input.
   * ToDo, make cipher etc. configurable.
   *
   * @param string $crypt The encrypted string to decrypt.
   * @param string $key The encryption key
   * @return string $plain The decrypted input.
   */
  private static function decrypt($crypt, $key)
  {
    // note, max key length may be cipher dependent
    if (strlen($key) > 32) {$key = substr($key, 0, 32);}
   // $td = mcrypt_module_open('tripledes', '', 'ecb', '');
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
    $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    mcrypt_generic_init($td, $key, $iv);
    $plain = mdecrypt_generic($td, $crypt);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // trim padding \0, may be cipher dependent
    $plain = rtrim($plain,chr(0));
     
    return $plain;
  }
}
