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
<div class="ui-widget-content ui-corner-all ui-state-highlight page-notice" >
  <strong>Notes:</strong>
  <p>All Users must have an associated 'Person' - in order to create a new user the 'Person' must exist first.</p>
  <p>In order to be on the list of potential users, the person must have an email address.</p>
</div>

<?php echo $grid; ?>
<form action="<?php echo url::site(); ?>person/create_from_user">
<input type="submit" value="New person" class="ui-corner-all ui-state-default button" />
</form>