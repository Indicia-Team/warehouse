<?php defined('SYSPATH') or die('No direct script access.');
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
 * @subpackage Config
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Cache settings, defined as arrays, or "groups". If no group name is
 * used when loading the cache library, the group named "default" will be used.
 *
 * Each group can be used independently, and multiple groups can be used at once.
 *
 * Group Options:
 *  driver   - Cache backend driver. Kohana comes with file, database, and memcache drivers.
 *              > File cache is fast and reliable, but requires many filesystem lookups.
 *              > Database cache can be used to cache items remotely, but is slower.
 *              > Memcache is very high performance, but prevents cache tags from being used.
 *
 *  params   - Driver parameters, specific to each driver.
 *
 *  lifetime - Default lifetime of caches in seconds. By default caches are stored for
 *             thirty minutes. Specific lifetime can also be set when creating a new cache.
 *             Setting this to 0 will never automatically delete caches.
 *
 *  requests - Average number of cache requests that will processed before all expired
 *             caches are deleted. This is commonly referred to as "garbage collection".
 *             Setting this to 0 or a negative number will disable automatic garbage collection.
 */
$config['default'] = array
(
  'driver'   => 'file',
  'params'   => APPPATH.'cache',
  'lifetime' => 1800,
  'requests' => 1000
);
