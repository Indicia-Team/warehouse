<?php

/**
 * @file
 * View template for the list of licences.
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
 * @link https://github.com/indicia-team/warehouse
 */
?>
<p>This is the master list of licences available on this warehouse which can be applied to
occurrences. Before they can be used the licences must be added to each website's Licences tab,
allowing the specific licences available for each website to be controllled.</p>
<?php echo $grid; ?>
<a href="<?php echo url::site() . 'licence/create'; ?>" class="btn btn-primary">New licence</a>
<?php echo $upload_csv_form;
