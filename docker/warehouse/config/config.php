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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Base path of the web site.
 *
 * If this includes a domain, eg: localhost/kohana/ then a full URL will be
 * used, eg: http://localhost/kohana/. If it only includes the path, and a
 * site_protocol is specified, the domain will be auto-detected.
 */
$config['site_domain'] = 'localhost:8080';

/**
 * Force a default protocol to be used by the site.
 *
 * If no site_protocol is specified, then the current protocol is used, or when
 * possible, only an absolute path (with no protocol/domain) is used.
 */
$config['site_protocol'] = '';

/**
 * Name of the front controller for this application. Default: index.php.
 *
 * This can be removed by using URL rewriting.
 */
$config['index_page'] = 'index.php';

/**
 * Fake file extension that will be added to all generated URLs. Example: .html.
 */
$config['url_suffix'] = '';

/**
 * Length of time of the internal cache in seconds.
 *
 * 0 or FALSE means no caching. The internal cache stores file paths and config
 * entries across requests and can give significant speed improvements at the
 * expense of delayed updating.
 */
$config['internal_cache'] = FALSE;

/**
 * Enable or disable gzip output compression.
 *
 * This can dramatically decrease server bandwidth usage, at the cost of
 * slightly higher CPU usage. Set to the compression level (1-9) that you want
 * to use, or FALSE to disable.
 *
 * Do not enable this option if you are using output compression in php.ini!
 */
$config['output_compression'] = FALSE;

/**
 * Enable or disable global XSS filtering of GET, POST, and SERVER data.
 *
 * This option also accepts a string to specify a specific XSS filtering tool.
 */
$config['global_xss_filtering'] = TRUE;

/**
 * Enable or disable hooks.
 *
 * Setting this option to TRUE will enable all hooks. By using an array of hook
 * filenames, you can control which hooks are enabled. Setting this option to
 * FALSE disables hooks.
 */
$config['enable_hooks'] = TRUE;

/**
 * Log thresholds.
 *
 *  0 - Disable logging,
 *  1 - Errors and exceptions,
 *  2 - Warnings,
 *  3 - Notices,
 *  4 - Debugging.
 */
$config['log_threshold'] = 4;

/**
 * Message logging directory.
 */
$config['log_directory'] = APPPATH.'logs';

/**
 * Enable or disable displaying of Kohana error pages.
 *
 * This will not affect logging. Turning this off will disable ALL error pages.
 */
$config['display_errors'] = false;

/**
 * Enable or disable statistics in the final output.
 * 
 * Stats are replaced via specific strings, such as {execution_time}.
 *
 * @see http://docs.kohanaphp.com/general/configuration
 */
$config['render_stats'] = false;

/**
 * Filename prefixed used to determine extensions.
 *
 * For example, an extension to the Controller class would be named
 * MY_Controller.php.
 */
$config['extension_prefix'] = 'MY_';

/**
 * Additional resource paths, or "modules".
 *
 * Each path can either be absolute or relative to the docroot. Modules can
 * include any resource that can exist in your application directory,
 * configuration files, controllers, views, etc.
 */
$config['modules'] = [
         MODPATH.'indicia_auth',          // Authentication
         MODPATH.'indicia_svc_base',      // Services
         MODPATH.'indicia_svc_data',      // Data services
         MODPATH.'indicia_svc_import',    // Import services
         MODPATH.'indicia_svc_validation',// Validation services
         MODPATH.'indicia_svc_security',  // Security services
         MODPATH.'indicia_svc_spatial',   // Spatial services
         MODPATH.'rest_api',              // REST API
         MODPATH.'indicia_setup',         // Setup procedures
         MODPATH.'sref_osgb',             // OSGB grid notation
         MODPATH.'sref_osie',             // Irish grid notation (TM75)
         MODPATH.'sref_channel_islands',  // Jersey and Guernsey grid notations
         MODPATH.'sref_utm',              // UTM grid notation
         MODPATH.'cache_builder',         // Build a cache for performance reporting
         MODPATH.'data_cleaner',          // Automatic record check manager
//         MODPATH.'data_cleaner_ancillary_species',                      // Automatic record checks
//         MODPATH.'data_cleaner_designated_taxa',                        // Automatic record checks
//         MODPATH.'data_cleaner_identification_difficulty',              // Automatic record checks
//         MODPATH.'data_cleaner_location_lookup_attr_list',              // Automatic record checks
//         MODPATH.'data_cleaner_new_species_for_site',                   // Automatic record checks
//         MODPATH.'data_cleaner_occurrence_lookup_attr_outside_range',   // Automatic record checks
//         MODPATH.'data_cleaner_period',                                 // Automatic record checks
//         MODPATH.'data_cleaner_period_within_year',                     // Automatic record checks
//         MODPATH.'data_cleaner_sample_attribute_changes_for_site',      // Automatic record checks
//         MODPATH.'data_cleaner_sample_lookup_attr_outside_range',       // Automatic record checks
//         MODPATH.'data_cleaner_sample_number_attr_outside_range',       // Automatic record checks
//         MODPATH.'data_cleaner_sample_time_attr_outside_range',         // Automatic record checks
//         MODPATH.'data_cleaner_species_location',                       // Automatic record checks
//         MODPATH.'data_cleaner_species_location_name',                  // Automatic record checks
//         MODPATH.'data_cleaner_without_polygon',                        // Automatic record checks
//         MODPATH.'taxon_designations',    // Protection designations for taxa
//         MODPATH.'taxon_associations',    // Associations between two taxa
//         MODPATH.'species_alerts',        // Alerts when records meeting criteria are added
//         MODPATH.'spatial_index_builder', // Index of location occurrence overlaps
//         MODPATH.'summary_builder'        // Build a cache for improving the performance of reporting summary data
];
