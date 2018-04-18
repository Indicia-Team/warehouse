<?php

/**
 * @file
 * View template for the list of locations.
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

echo $grid;
?>
<form action="<?php echo url::site() . 'location/create'; ?>" method="post">
  <input type="submit" value="New location" class="btn btn-primary" />
  <?php if (isset($parent_id)) : ?>
    <input name="parent_id" type="hidden" value="<?php echo $parent_id; ?>" />
  <?php endif; ?>
</form>
<br />
<?php
echo $upload_csv_form;
echo $upload_shp_form;