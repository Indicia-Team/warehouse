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

 defined('SYSPATH') or die('No direct access allowed.');

 /**
 * This path is relative to your index file. Absolute paths are also supported.
 */
$config['directory'] = DOCROOT.'upload';

/*
 * This path is relative to your index file. Absolute paths are also supported.
 */
$config['zip_extract_directory'] = DOCROOT.'extract';

/**
 * Enable or disable directory creation.
 */
$config['create_directories'] = TRUE;

/**
 * Remove spaces from uploaded filenames.
 */
$config['remove_spaces'] = TRUE;

/**
 * Directory levels to create in the upload directory, dependent on time function, takes pairs of digits, from the front, to form the directory names.
 * Default is zero - ie OFF, no directory sub structure
 */
$config['use_sub_directory_levels'] = 0;
