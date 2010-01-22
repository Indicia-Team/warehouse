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
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

?>
<h2>Welcome to the Indicia Warehouse!</h2>
<?php if ($db_version!=$app_version) : ?>
<div class="ui-state-error ui-corner-all page-notice">Your database needs to be upgraded as the application version is <?php echo $app_version; ?> but the database version is <?php echo $db_version; ?>.
<a class="ui-state-default ui-corner-all button" href="<?php echo url::base();?>index.php/home/upgrade">Run Upgrade</a></div>  
<?php 
endif; 
if (kohana::config('image.driver') == 'GD' && !function_exists('gd_info')) : ?>
<div class="ui-state-error ui-corner-all page-notice">This warehouse is configured to use the GD2 image library but it is not enabled in your PHP ini file. Please enable it before continuing.</div>    
<?php endif; ?>
<p>Indicia is a toolkit that simplifies the construction of new websites which allow data entry, mapping and reporting
of wildlife records. Indicia is an Open Source project funded by the Open Air Laboratories Network and managed
by the Centre for Ecology and Hydrology.</p>
<p>You can see Indicia in action on the <a href="<?php echo url::base();?>modules/demo/index.php">website demonstration pages</a>.</p>
