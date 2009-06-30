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
<p>This page allows you to specify the details of a persons title.</p>
<form class="cmxform" action="<?php echo url::site().'title/save'; ?>" method="post">
<?php echo $metadata ?>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<fieldset>
<legend>Title details</legend>
<ol>
<li>
<label for="title">Title</label>
<input class="narrow" id="title" name="title" value="<?php echo html::specialchars($model->title); ?>" />
<?php echo html::error_message($model->getError('title')); ?>
</li>
</ol>
<input type="submit" value="Save" class="default" name="submit" />
<input type="submit" value="Delete" class="default" name="submit" />
</fieldset>
</form>
