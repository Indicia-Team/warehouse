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

global $default_terms;

/**
 * Provides a list of default localisable terms used by the lang class.
 *
 * @package	Client
 */
$default_terms = array( 
  'add row'=>'Add Row',
  'back'=>'Back',
  'british national grid'=>'British National Grid',
  'click here'=>'Click here',
  'close'=>'Close',
  'email'=>'Email',
  'enter additional species'=>'Enter additional species',
  'error loading control'=>'Error loading control',
  'file too big for webserver' => 'The image file cannot be uploaded because it is bigger than the size limit allowed by the server which this data entry page is running on.',
  'file too big for warehouse' => 'The image file cannot be uploaded because it is larger than the maximum file size allowed.',
  'first name'=>'First Name',
  'lat long 4326'=>'Lat/Long (WGS84)',
  'loading'=>'Loading',
  'next step'=>'Next Step',
  'phone number'=>'Phone Number',
  'prev step'=>'Previous Step',
  'save'=>'Save',
  'search'=>'Search',
  'search for place on map'=>'Search for place on map',
  'spatial ref'=>'Spatial ref.',
  'species_checklist.species'=>'Species',
  'species_checklist.present'=>'Present',
  'submit ok but file failed'=>'Your record was successfully submitted. However, an error occurred when saving an image file. The error was:',
  'surname'=>'Surname',
  'upload error' => 'An error occurred uploading the file.',
  'validation_required' => 'Please enter the a value for the %s',
  'validation_email' => 'Please enter a valid email address',
  'validation_url' => 'Please enter a valid URL',
  'validation_dateISO' => 'Please enter a valid date in yyyy-mm-dd format',

  // Default labels for various database fields
  'occurrence:taxa_taxon_list_id' => 'Species',
  'sample:date' => 'Date',
  'sample:entered_sref' => 'Spatial Reference',
  
  // Spatial reference systems
  'OSGB'=>'British National Grid',
  'OSIE'=>'Irish Grid',
  '4326'=>'WGS84 (decimal lat,long)',
  '2169'=>'LUREF (x,y)',
  
  'column_mapping_instructions' => 'Please map each column in the CSV file you are uploading to the associated attribute in the destination list. Any you do not set '.
      'at this stage can be set on a row per row basis by including a column in the import file for this data.',
  'upload_not_available' => 'The uploaded file is no longer available. Please try uploading again.',
  
  // define the captions for fields in the data dictionary
  'dd:occurrence:fk_taxa_taxon_list' => 'Species or taxon selected from existing list',
  'dd:sample:entered_sref' => 'Sample spatial reference',
  'dd:sample:entered_sref_system' => 'Sample spatial reference system',
  
  // and import model name/field name prefix overrides
  'smpAttr' => 'Sample Custom Attributes',
  'occAttr' => 'Occurrence Custom Attributes',
  'locAttr' => 'Location Custom Attributes',
  'fkFilter' => 'Lookup Filters'
);

