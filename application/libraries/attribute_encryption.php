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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Helper for encrypting/decrypting custom attribute text values.
 */
class attribute_encryption {

  /**
   * Prefix identifying encrypted payloads.
   */
  private const PREFIX = 'enc:v1:';

  /**
   * Cached parsed key details.
   *
   * @var array|null
   */
  private static $keyInfo;

  /**
   * Encrypt a plaintext value.
   *
   * @param string|null $plaintext
   *   Plaintext value.
   *
   * @return string|null
   *   Encrypted payload, or null when input is null.
   */
  public static function encrypt($plaintext) {
    if ($plaintext === NULL) {
      return NULL;
    }
    $keyInfo = self::getKeyInfo();
    if ($keyInfo['algorithm'] === 'sodium') {
      $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
      $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $keyInfo['key']);
      return self::PREFIX . 'sodium:' . $keyInfo['key_id'] . ':' . base64_encode($nonce . $ciphertext);
    }
    if ($keyInfo['algorithm'] === 'openssl-gcm') {
      $nonce = random_bytes(12);
      $tag = '';
      $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $keyInfo['key'],
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        '',
        16
      );
      if ($ciphertext === FALSE) {
        throw new Exception('Unable to encrypt attribute value.');
      }
      return self::PREFIX . 'openssl-gcm:' . $keyInfo['key_id'] . ':' . base64_encode($nonce) . ':' . base64_encode($tag) . ':' . base64_encode($ciphertext);
    }
    throw new Exception('No supported encryption implementation is available.');
  }

  /**
   * Decrypt an encrypted payload.
   *
   * @param string|null $payload
   *   Encrypted payload.
   *
   * @return string|null
   *   Plaintext string.
   */
  public static function decrypt($payload) {
    if ($payload === NULL || $payload === '') {
      return $payload;
    }
    if (!self::isEncryptedPayload($payload)) {
      return $payload;
    }
    $keyInfo = self::getKeyInfo();
    $parts = explode(':', $payload);
    if (count($parts) < 5 || $parts[0] !== 'enc' || $parts[1] !== 'v1') {
      throw new Exception('Invalid encrypted attribute payload.');
    }
    $algorithm = $parts[2];
    if ($algorithm === 'sodium') {
      if (count($parts) !== 5) {
        throw new Exception('Invalid encrypted attribute payload.');
      }
      $raw = base64_decode($parts[4], TRUE);
      if ($raw === FALSE || strlen($raw) <= SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES) {
        throw new Exception('Invalid encrypted attribute payload.');
      }
      $nonce = substr($raw, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
      $ciphertext = substr($raw, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
      $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, '', $nonce, $keyInfo['key']);
      if ($plaintext === FALSE) {
        throw new Exception('Unable to decrypt attribute value.');
      }
      return $plaintext;
    }
    if ($algorithm === 'openssl-gcm') {
      if (count($parts) !== 7) {
        throw new Exception('Invalid encrypted attribute payload.');
      }
      $nonce = base64_decode($parts[4], TRUE);
      $tag = base64_decode($parts[5], TRUE);
      $ciphertext = base64_decode($parts[6], TRUE);
      if ($nonce === FALSE || $tag === FALSE || $ciphertext === FALSE) {
        throw new Exception('Invalid encrypted attribute payload.');
      }
      $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $keyInfo['key'],
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        ''
      );
      if ($plaintext === FALSE) {
        throw new Exception('Unable to decrypt attribute value.');
      }
      return $plaintext;
    }
    throw new Exception('Unsupported encrypted attribute algorithm.');
  }

  /**
   * Check if a value appears to be an encrypted payload.
   *
   * @param string $value
   *   Value to check.
   *
   * @return bool
   *   True when payload prefix detected.
   */
  public static function isEncryptedPayload($value) {
    return is_string($value) && strpos($value, self::PREFIX) === 0;
  }

  /**
   * Detect if user has core admin role.
   *
   * @param int|null $userId
   *   User ID.
   *
   * @return bool
   *   True when user is core admin.
   */
  public static function isCoreAdmin($userId) {
    if (empty($userId) || !is_numeric($userId)) {
      return FALSE;
    }
    $db = new Database();
    $user = $db->query('SELECT core_role_id FROM users WHERE id=? AND deleted=false', [(int) $userId])->current();
    return !empty($user) && (int) $user->core_role_id === 1;
  }

  /**
   * Get website IDs for which the user has a site role.
   *
   * @param int|null $userId
   *   User ID.
   *
   * @return int[]
   *   Website IDs.
   */
  public static function getUserAdminWebsiteIds($userId) {
    if (empty($userId) || !is_numeric($userId)) {
      return [];
    }
    $db = new Database();
    $rows = $db->query(
      'SELECT website_id FROM users_websites WHERE user_id=? AND site_role_id IS NOT NULL AND banned=false AND deleted=false',
      [(int) $userId]
    )->result_array(FALSE);
    $ids = [];
    foreach ($rows as $row) {
      $ids[] = (int) $row['website_id'];
    }
    return array_values(array_unique($ids));
  }

  /**
   * Determine if user can decrypt records for a website.
   *
   * @param int|null $userId
   *   User ID.
   * @param int|null $websiteId
   *   Website ID.
   *
   * @return bool
   *   True when user has permission.
   */
  public static function canUserDecryptForWebsite($userId, $websiteId) {
    if (self::isCoreAdmin($userId)) {
      return TRUE;
    }
    if (empty($websiteId) || !is_numeric($websiteId)) {
      return FALSE;
    }
    return in_array((int) $websiteId, self::getUserAdminWebsiteIds($userId), TRUE);
  }

  /**
   * Load and parse encryption key settings.
   *
   * @return array
   *   Key metadata and bytes.
   */
  private static function getKeyInfo() {
    if (isset(self::$keyInfo)) {
      return self::$keyInfo;
    }
    $keyString = trim((string) kohana::config('indicia.attribute_encryption_key'));
    if ($keyString === '') {
      throw new Exception('Attribute encryption key is not configured.');
    }
    $keyId = trim((string) kohana::config('indicia.attribute_encryption_key_id'));
    if ($keyId === '') {
      $keyId = 'default';
    }
    if (strpos($keyString, 'base64:') === 0) {
      $decoded = base64_decode(substr($keyString, 7), TRUE);
      if ($decoded === FALSE) {
        throw new Exception('Attribute encryption key is invalid base64.');
      }
      $key = $decoded;
    }
    else {
      $key = hash('sha256', $keyString, TRUE);
    }

    if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')
        && strlen($key) >= SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
      self::$keyInfo = [
        'algorithm' => 'sodium',
        'key_id' => $keyId,
        'key' => substr($key, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES),
      ];
      return self::$keyInfo;
    }
    if (function_exists('openssl_encrypt')) {
      self::$keyInfo = [
        'algorithm' => 'openssl-gcm',
        'key_id' => $keyId,
        'key' => substr($key, 0, 32),
      ];
      return self::$keyInfo;
    }
    throw new Exception('No supported encryption implementation is available.');
  }

}