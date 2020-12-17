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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */
?>

<div id="progress-text" class="alert alert-info">
  <p>Processed <span id="done">0</span> out of <?php echo $totalToProcess; ?>.</p>
  <p>Operation: <span id="operation">Initialising</span></p>
</div>

<div id="progress-bar" style="width: 100%" />

<script type="text/javascript">
window.totalToProcess = <?php echo $totalToProcess; ?>;
window.baseUrl = '<?php echo url::base(); ?>';
</script>


