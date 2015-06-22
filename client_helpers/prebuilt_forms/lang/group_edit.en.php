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

global $custom_terms;

/**
 * Language terms for the group_edit form.
 *
 * @package	Client
 */
$custom_terms = array(
  "group's" => "activity's",
  "groups" => "activities",
  'LANG_Filter_Instruct' => 'Your {1} might be only interested in certain '
    . 'records, e.g. records from a reserve, records from a species group or '
    . 'records within a set date range. If this is the case then you can use '
    . 'the following controls to define which records are of interest to the '
    . '{1} members and therefore which records will appear on the {2} '
    . 'reporting pages. For example, you might want to specify the species or '
    . 'species groups that relate to your {1} using the <strong>What</strong> '
    . 'option, as well as the geographic area your {1} covers using the '
    . '<strong>Where</strong> option.',
  'LANG_Pages_Instruct' => 'Use the following grid to define any pages that '
    . 'you would like to make available for your {1} members to use. These '
    . 'pages will then appear in the Links column in the list of {2}. You only '
    . 'need to specify a link caption if you want to override the default page '
    . 'name when accessed via your {1}. Please note that you must link at '
    . 'least one recording form page to the {1} if you want to allow your '
    . 'members to explicitly post records into the {1}. You must also link at '
    . 'least one reporting page if you want your members to be able to view '
    . 'the records generated during this {1}.',
  'LANG_Record_Inclusion_Instruct_1' => 'This option defines how records need '
    . 'to be submitted in order for them to be included on the {2} reporting '
    . 'pages.',
  'LANG_Record_Inclusion_Instruct_Sensitive' => 'Note that some functionality '
    . 'such as allowing group members to view sensitive records at full record '
    . 'precision depends on records being submitted via a {1} data entry '
    . 'form.',
  'LANG_Record_Inclusion_Instruct_2' => 'If you choose to require records to '
    . 'be submitted into the {1} via a {1} data entry form then make sure that '
    . 'you select at least 1 data entry form in the <strong>{2} pages</strong> '
    . 'section below. Otherwise {1} members won\'t have a means to post '
    . 'records into the {1}.',
  'LANG_Description_Field_Instruct' => 'Description and notes about the {1} '
    . 'which will be shown in the {1} listing pages to help other users find '
    . 'your {1}.',
  'LANG_From_Field_Instruct' => '',
  'LANG_To_Field_Instruct' => '',
  'LANG_Admins_Field_Instruct' => 'Search for additional users to make '
    . 'administrators of this {1} by typing a few characters of their surname '
    . 'then selecting their name from the list of suggestions and clicking the '
    . 'Add button. Administrators will need to register on this website before '
    . 'you can add them.',
  'LANG_Members_Field_Instruct' => 'Search for users to give membership to by '
    . 'typing a few characters of their surname then selecting their name from '
    . 'the list of suggestions and clicking the Add button. Users will need '
    . 'register on this website before you can add them.',
);