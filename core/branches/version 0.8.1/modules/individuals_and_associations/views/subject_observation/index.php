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
 * @package	Groups and individuals module
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
echo $grid;
?>
<?php if (!empty($sample_id)) : ?>
<form action="<?php echo url::site().'subject_observation/create'; ?>" method="post">
<input type="hidden" name="subject_observation:sample_id" value="<?php echo $sample_id; ?>" />
<input type="submit" value="New subject observation" class="ui-corner-all ui-state-default button" />
</form>
<?php endif;?>
<br />

