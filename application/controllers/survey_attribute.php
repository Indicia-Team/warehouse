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

/**
 * Controller for survey attributes.
 */
class Survey_attribute_Controller extends Attr_Base_Controller {

  public function __construct() {
    $this->prefix = 'survey';
    parent::__construct();
    // Update the default columns as survey attributes
    // are not attached to surveys.
    unset($this->columns['survey']);
  }

  /**
   * Returns the shared view for all custom attribute edits.
   */
  protected function editViewName() {
    $this->associationsView = new View('templates/attribute_associations_website');
    return 'custom_attribute/custom_attribute_edit';
  }

  /**
   * Returns some addition information required by the edit view, which is not
   * associated with a particular record.
   */
  protected function prepareOtherViewData(array $values) {
    return array_merge(
      (array) parent::prepareOtherViewData($values),
      ['publicFieldName' => 'Public (available for all survey datasets on this warehouse)']
    );
  }

}
