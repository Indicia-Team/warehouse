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
 * @link https://github.com/Indicia-Team/warehouse
 */

$servers = Kohana::config('rest_api_sync.servers');
if (empty($servers)) : ?>
<div class="alert alert-warning"><strong>Warning!</strong> no configurations defined for the rest_api_sync module.</div>
<?php else : ?>
  <h2>Server configurations</h2>
  <ul class="list-group">
    <?php
    foreach ($servers as $serverId => $server) {
      echo "<li class=\"list-group-item\">$server[url] <span class=\"badge\">$serverId</span></li>\n";
    }
    ?>
  </ul>
  <button type="button" class="btn btn-primary" id="start-sync">Start sync</button>
  <div id="sync-progress">
    <h2>Synchronisation progress</h2>
    <div id="progress"></div><br/>
    <?php echo $grid; ?>
    <div id="output" class="panel panel-default">
      <div class="panel-heading">Synchronisation messages</div>
      <div class="panel-body"></div>
    </div>
  </div>
<?php endif;
