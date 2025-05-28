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
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

class html extends html_Core {

  /**
   * Outputs an error message in a span only if there is something to output.
   */
  public static function error_message($message) {
    if ($message) {
      echo "<div class=\"alert alert-danger\">$message</div>";
    }
  }

  /**
   * Outputs an error message in a span only if there is something to output.
   */
  public static function error_class($message) {
    if ($message) {
      echo "has-error";
    }
  }

  /**
   * Outputs a page flash notice.
   */
  public static function page_notice(
      $title,
      $description,
      $level = 'info',
      $icon = 'info',
      $linkTitle = NULL,
      $link = NULL) {
    $iconSpan = empty($icon) ? '' : "<span class=\"glyphicon glyphicon-$icon\"></span> ";
    $link = empty($linkTitle) ? '' : "<br/><a href=\"$link\" class=\"btn btn-default\">$linkTitle</a>";
    return <<<HTML
<div class="alert alert-$level alert-dismissable">
  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  $iconSpan<strong>$title - </strong>
  $description$link
</div>
HTML;
  }

  /**
   * Finds the value to load into a form edit control.
   *
   * Returns the initial value for an edit control on a page. This is either
   * loaded from the $_POST array (if reloading after a failed attempt to save)
   * or from the model or initial default value otherwise.
   *
   * @param array $values
   *   List of values to load in an array.
   * @param string $fieldname
   *   The fieldname should be of form model:fieldname. If the model part
   *   indicates a different model then the field value will be loaded from the
   *   other model (assuming that model is linked to the main one. E.g.
   *   'taxon:description' would load the $model->taxon->description field.
   */
  public static function initial_value($values, $fieldname) {
    return $values[$fieldname] ?? '';
  }

  /**
   * Output buttons for an edit form.
   *
   * Return HTML to output the default OK and Cancel buttons to display at the
   * bottom of an edit form. Also outputs a delete button if the $allowDelete
   * parameter is true.
   *
   * @param bool $allowDelete
   *   If true, then a delete button is included in the output.
   * @param bool $readOnly
   *   If true, then the only button is a form cancel button.
   * @param bool $allowUserSelectNextPage
   *   If true then then a select control is output which lets the user define
   *   whether to continue adding records on the add new page or to return the
   *   index page for the current model.
   */
  public static function form_buttons($allowDelete, $readOnly = FALSE, $allowUserSelectNextPage = TRUE) {
    $r = '<fieldset class="button-set form-inline">' . "\n";
    if ($readOnly) {
      $r .= '  <input type="submit" name="submit" value="' . kohana::lang('misc.cancel') . '" class="btn btn-default" />' . "\n";
    }
    else {
      $r .= '  <input type="submit" name="submit" value="' . kohana::lang('misc.save') . '" class="btn btn-primary" />' . "\n";
      $r .= '  <input type="submit" name="submit" value="' . kohana::lang('misc.cancel') . '" class="btn btn-default" />' . "\n";
      if ($allowDelete) {
        $r .= '  <input type="submit" name="submit" value="' . kohana::lang('misc.delete') . '" onclick="if (!confirm(\'' .
          kohana::lang('misc.confirm_delete') . '\')) {return false;}" class="btn btn-warning" />' . "\n";
      }
      // Add a drop down to select action after submit clicked. Needs to
      // remember its previous setting from the session, since we normally
      // arrive here after a redirect.
      if (isset($_SESSION['what-next']) && $_SESSION['what-next'] === 'add') {
        $selAdd = ' selected="selected"';
        $selReturn = '';
      }
      else {
        $selAdd = '';
        $selReturn = ' selected="selected"';
      }
      if ($allowUserSelectNextPage) {
        $langThen = kohana::lang('misc.then');
        $r .= <<<CTRL
  <label for="next-action">$langThen</label>
  <select id="what-next" class="form-control" name="what-next">
    <option value="return"$selReturn>go back to the list</option>
    <option value="add"$selAdd>add new</option>
  </select>

CTRL;
      }
    }
    $r .= "</fieldset>\n";
    return $r;
  }

  /**
   * Outputs an image.
   *
   * Output a thumbnail or other size of an image, with a link to the full
   * sized image suitable for the fancybox jQuery plugin.
   *
   * @param string $filename
   *   Name of a file within the upload folder.
   * @param string $size
   *   Name of the file size, normally thumb or med depending on the image
   *   handling config.
   *
   * @return string
   *   HTML to insert into the page, with the anchored image element.
   */
  public static function sized_image($filename, $size = 'thumb') {
    helper_base::add_resource('fancybox');
    $img_config = kohana::config('indicia.image_handling');
    // Dynamically build the HTML sizing attrs for the thumbnail from the
    // config. We may not know both dimensions.
    $sizing = '';
    if ($img_config && array_key_exists($size, $img_config)) {
      if (array_key_exists('width', $img_config['thumb'])) {
        $sizing = ' width="' . $img_config[$size]['width'] . '"';
      }
      if (array_key_exists('height', $img_config[$size])) {
        $sizing .= ' height="' . $img_config[$size]['height'] . '"';
      }
    }
    $base = url::base();
    return <<<HTML
<a href="{$base}upload/$filename" class="fancybox">
  <img src="{$base}upload/$size-$filename"$sizing />
</a>
HTML;
  }

}
