<?php

/**
 * @file
 * View template for the list of licences available for a website.
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
if ($this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin')) : ?>
  <a href="<?php echo url::site() . 'licences_website/create/' . $this->uri->argument(1); ?>" class="btn btn-primary">
    Add licence
  </a>
<?php endif;
