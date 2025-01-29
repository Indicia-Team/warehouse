<?php

/**
 * @file
 * View template for the Data Cleaner CSV upload progress.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */
?>
<div class="alert alert-info">
  <h2><?php echo $_GET['total'] ?> operation<?php echo ($_GET['total'] === '1') ? ' was' : 's were'; ?> processed.</h2>
  <p>If you are using Elasticsearch with this warehouse, please refresh the Logstash taxonomy lookups then restart
    Logstash. See <a href="https://github.com/Indicia-Team/support_files/tree/master/Elasticsearch#prepare-the-lookups-for-taxon-data">
    Indicia Elasticsearch documentation</a> for more information.</p>
  <p><a class="btn btn-success" href="<?php echo url::site() . "uksi_operation"; ?>">Return to the UKSI operations list</a></p>
</div>
