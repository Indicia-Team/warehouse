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
<form class="cmxform" action="<?php echo url::site().'language/save' ?>" method="post">
<?php echo $metadata; ?>
<fieldset>
<legend>Language Details</legend>
<ol>
<li>
<input type="hidden" name="language:id" value="<?php echo html::initial_value($values, 'language:id'); ?>" />
<label for="iso">ISO language code</label>
<input id="iso" name="language:iso" class="narrow" value="<?php echo html::initial_value($values, 'language:iso'); ?>"/>
<?php echo html::error_message($model->getError('language:iso')); ?>
</li>
<li>
<label for="language">Language</label>
<input id="language" name="language:language" value="<?php echo html::initial_value($values, 'language:language'); ?>" />
<?php echo html::error_message($model->getError('language:language')); ?>
</li>
</ol>
</fieldset>
<?php 
echo html::form_buttons(html::initial_value($values, 'language:id')!=null)
?>
</form>

