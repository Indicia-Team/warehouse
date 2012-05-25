<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  Core
 *
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
