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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Remove service credentials from diagnostic request data.
 */
class logRedaction {

  /**
   * Remove credentials recursively, preserving other request parameters.
   *
   * @param array $parameters
   *   Request parameters, which may contain arrays, objects, or JSON strings.
   * @param array $skipped
   *   Additional top-level parameter names to omit from this log.
   *
   * @return array
   *   A sanitised copy; the original request is not modified.
   */
  public static function parameters(array $parameters, array $skipped = []): array {
    $skipped = array_merge(['nonce', 'auth_token'], array_map('strtolower', $skipped));
    $result = [];
    foreach ($parameters as $key => $value) {
      if (in_array(strtolower((string) $key), $skipped, TRUE)) {
        continue;
      }
      if (is_array($value)) {
        $value = self::parameters($value);
      }
      elseif ($value instanceof stdClass) {
        $value = (object) self::parameters(get_object_vars($value));
      }
      elseif (is_string($value) && preg_match('/^\s*[\{\[]/', $value)) {
        $value = self::body($value);
      }
      $result[$key] = $value;
    }
    return $result;
  }

  /**
   * Sanitise a JSON or URL-encoded body without retaining unparsed secrets.
   *
   * @param string $body
   *   Raw request body or replacement response data.
   * @param array $skipped
   *   Additional parameter names to omit.
   * @param string $contentType
   *   Request content type, if known. Empty enables format detection.
   *
   * @return string
   *   Sanitised body, or an explicit omission marker for unsupported input.
   */
  public static function body(string $body, array $skipped = [], string $contentType = ''): string {
    if ($body === '') {
      return '';
    }
    $contentType = strtolower(trim(explode(';', $contentType, 2)[0]));
    if (preg_match('/^\s*[\{\[]/', $body) || $contentType === 'application/json' || substr($contentType, -5) === '+json') {
      $decoded = json_decode($body);
      if (json_last_error() !== JSON_ERROR_NONE || (!is_array($decoded) && !$decoded instanceof stdClass)) {
        return '[request body omitted: invalid or unsupported JSON]';
      }
      $redacted = self::parameters((array) $decoded, $skipped);
      return json_encode($decoded instanceof stdClass ? (object) $redacted : $redacted, JSON_THROW_ON_ERROR);
    }
    if ($contentType === 'application/x-www-form-urlencoded' || ($contentType === '' && strpos($body, '=') !== FALSE)) {
      parse_str($body, $parameters);
      return http_build_query(self::parameters($parameters, $skipped), '', '&', PHP_QUERY_RFC3986);
    }
    return '[request body omitted: unsupported content type]';
  }

  /**
   * Remove credentials from a request URI while retaining useful query fields.
   *
   * @param string $uri
   *   Request URI.
   *
   * @return string
   *   URI with sanitised query parameters.
   */
  public static function uri(string $uri): string {
    $parts = explode('?', $uri, 2);
    if (count($parts) === 1) {
      return $uri;
    }
    return $parts[0] . '?' . self::body($parts[1], [], 'application/x-www-form-urlencoded');
  }

}
