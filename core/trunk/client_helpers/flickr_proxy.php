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
 * @package	Media
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Proxy script to allow JavaScript on the Indicia websites to interact with the Flickr API.
 *
 * Should be called with post data, e.g. method="flickr.photos.comments.getList", arguments=[{"photo_id": '34952612'}].
 * Authentication should have already been performed if required.
 */

require_once 'phpFlickr/phpFlickr.php';
require_once 'data_entry_helper.php';

/**
 * Provide an alternative json_encode, as the standard one doesn't seem to work with Flickr API responses.
 */
function indicia_json_encode($s)
{
  if(is_numeric($s)) return $s;
  if(is_string($s)) {
    return preg_replace("@([\1-\037])@e",
   "sprintf('\\\\u%04X',ord('$1'))",
    str_replace("\0", '\u0000',
    utf8_decode(json_encode(utf8_encode($s)))));
  }
  if($s===false) return 'false';
  if($s===true) return 'true';
  if(is_array($s))
  {
    $c=0;
    foreach($s as $k=>&$v)
      if($k !== $c++)
      {
        foreach($s as $k=>&$v) $v = indicia_json_encode((string)$k).':'.indicia_json_encode($v);
        return '{'.join(',', $s).'}';
      }
    return '[' . join(',', array_map('indicia_json_encode', $s)) . ']';
  }
  return 'null';
}

$method=$_GET['method'];

$arguments=$_GET['arguments'];

$arguments=json_decode($arguments, true);

$f = new phpFlickr(helper_config::$flickr_api_key, helper_config::$flickr_api_secret, true);

$response=$f->call($method, $arguments);

// Dump out the response as JSON
echo indicia_json_encode($response);
