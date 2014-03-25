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
<p>Provide a list of known identifiers used by this user on other systems. When identifiers are
from systems which can be used during the login process on a client website (e.g. Twitter, Facebook
or OpenID), identifiers can be used to ensure that even when logged in on multiple websites, a 
single recorder is recognised as such.</p>
<?php echo $grid; ?>
<form action="<?php echo url::site().'user_identifier/create/'.$user_id; ?>">
<?php 
// @todo Also allow current user to add identifiers
if ($this->auth->logged_in('CoreAdmin')): ?>
<input type="submit" value="New identifier" class="ui-corner-all ui-state-default button" />
<?php endif; ?>
</form>