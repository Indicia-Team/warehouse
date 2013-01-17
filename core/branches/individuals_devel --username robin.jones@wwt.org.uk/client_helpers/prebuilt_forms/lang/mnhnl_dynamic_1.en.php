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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

include_once 'dynamic_sample_occurrence.en.php';

/**
 * Additional language terms or overrides for mnhnl_dynamic_1 form.
 *
 * @package	Client
 */
$custom_terms = array_merge($custom_terms, array(
	// Below gives an example of setting the biotope and voucher attribute captions used in this tab.
	// Note these do not have LANG_ prefixes.
	'MNHNL Collaborators 1 Biotope' => 'Biotope',
	'Voucher' => 'Voucher Specimen taken?',
	// Can also add entries for 'Yes' and 'No' for the voucher attribute
        'LANG_Trailer_Text' => 'Define trailer text in mnhnl_dynamic_1.en.php'
    
  )
);