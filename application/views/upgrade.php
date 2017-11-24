<?php

/**
 * @file
 * View template for the upgrade page.
 *
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
 * @package Core
 * @subpackage Views
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */
?>
<?php if (!empty($pgUserScriptsToBeApplied)) : ?>
  <p>The following script includes changes which need to be run against the database using the postgres root user account. Please run them manually
  as the upgrade tool does not have the required level of privileges.</p>
  <pre><?php echo $pgUserScriptsToBeApplied; ?></pre>
<?php endif; ?>
<?php if (!empty($slowScriptsToBeApplied)) : ?>
  <p>The following script includes changes which need to be run against the database using the postgres root user account. Please run them manually
  as the may take a while to run and could cause a timeout if ran through the standard web interface upgrade process.</p>
  <pre><?php echo $slowScriptsToBeApplied; ?></pre>
<?php endif; ?>
<?php if ($db_version !== $app_version || isset($error)) : ?>
  <div class="alert alert-danger">
    <p><strong>The upgrade failed.</strong></p>
    <?php if ($db_version !== $app_version  && !isset($error)) : ?>
      <p>Database version and application version do not match.</p>
    <?php endif; ?>
    <?php if (isset($error)) : ?>
      <p>An error occurred during the upgrade.</p>
      <?php echo $error; ?><br/>
      <p>Please refer to the application log files for more information.</p>
    <?php endif; ?>
  </div>
<?php else : ?>
  <div class="alert alert-info">
    Your system has been upgraded to version <?php echo $app_version; ?>.
  </div>
  <a class="btn btn-primary" href="<?php echo url::base();?>index.php/home">Return to the Home Page</a>
<?php endif;
