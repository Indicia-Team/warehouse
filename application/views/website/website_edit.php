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
<p>This page allows you to specify the details of a website that will use the services provided by this Indicia Warehouse instance.</p>
<form class="cmxform" action="<?php echo url::site().'website/save'; ?>" method="post">
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<fieldset>
<legend>Website details</legend>
<ol>
<li>
<label for="title">Title</label>
<input id="title" name="title" value="<?php echo html::specialchars($model->title); ?>" />
<?php echo html::error_message($model->getError('title')); ?>
</li>
<li>
<label for="url">URL</label>
<input id="url" name="url" value="<?php echo html::specialchars($model->url); ?>" />
<?php echo html::error_message($model->getError('url')); ?>
</li>
<li>
<label for="description">Description</label>
<textarea rows="7" id="description" name="description"><?php echo html::specialchars($model->description); ?></textarea>
<?php echo html::error_message($model->getError('description')); ?>
</li>
<li>
<label for="password">Password</label>
<input type="password" id="password" name="password" value="<?php echo html::specialchars($model->password); ?>" />
<?php echo html::error_message($model->getError('password')); ?>
</li>
<li>
<label for="password2">Retype Password</label>
<input type="password" id="password2" name="password2" value="<?php echo html::specialchars($model->password2); ?>" />
<?php echo html::error_message($model->getError('password2')); ?>
</li>
</ol>
</fieldset>
<?php echo $metadata ?>
<input type="submit" name="submit" value="Save" />
<input type="submit" name="submit" value="Delete" />
</form>
