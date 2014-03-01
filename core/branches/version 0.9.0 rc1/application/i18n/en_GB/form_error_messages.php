<?php defined('SYSPATH') or die('No direct access allowed.');

$lang = array (
  'address' => Array (
    'length' => 'The address must be a maximum of 200 characters in length.',
    'default' => 'Invalid input.',
  ),
  'caption' => Array (
    'required' => 'The caption cannot be blank.',
  ),
  'centroid_sref' => Array (
    'required' => 'The spatial reference must be supplied.',
    'sref' => 'The spatial reference is not recognised.',
  ),
  'comment' => Array (
    'required' => 'Please provide a comment.',
  ),
  'data_type' => Array (
    'required' => 'The data type must be supplied.',
  ),
  'date_start' => Array (
    'date_in_past' => 'Please specify a date that is not in the future.'
  ),
  'date_start_value' => Array (
    'date_in_past' => 'Please specify a date that is not in the future.'
  ),
  'date_type' => Array (
    'required' => 'Please supply a date for your observation.',
    'default' => 'Unrecognised vague date type',
  ),
  'deleted' => Array (
    'has_terms' => 'There are terms belonging to this list.',
    'has_taxa' => 'There are species belonging to this list.',
  ),
  'description' => Array (
    'standard_text' => 'Only standard characters are allowed.',
    'default' => 'Invalid input.',
  ),
  'determiner_id' => Array (
    'required' => 'The determiner cannot be blank.',
    'default' => 'Invalid input.',
  ),
  'email_address' => Array (
    'required' => 'The email address cannot be blank.',
    'email' => 'This must be a valid email address.',
    'length' => 'The email address must be a maximum of 50 characters in length.',
    'unique' => 'This email address must be unique, i.e. not shared with another person.',
    'default' => 'Invalid input.',
  ),
  'entered_sref' => Array (
    'required' => 'The spatial reference must be supplied.',
    'sref' => 'The spatial reference is not recognised.',
    'default' => 'Invalid input.',
  ),
  'entered_sref_system' => Array (
    'required' => 'The spatial reference system must be supplied.',
    'sref_system' => 'The spatial reference system is not a valid EPSG or notation code.',
    'default' => 'Invalid input.',
  ),
  'centroid_sref_system' => Array (
    'required' => 'The centorid spatial reference system must be supplied.',
    'default' => 'Invalid input.',
  ),
  'external_key' => Array (
    'length' => 'The external key field can be up to 50 characters long.',
  ),
  'first_name' => Array (
    'required' => 'The first name cannot be blank.',
    'length' => 'The first name must be between 1 and 30 letters.',
    'default' => 'Invalid input.',
  ),
  'float_value' => Array (
    'default' => 'This must be a valid number.',
    'maximum' => 'The value specified for this number is too high',
    'minimum' => 'The value specified for this number is too low',
    'required' => 'The value is required.',
  ),
  'geom' => array (
    'required' => 'The spatial reference must be supplied.',
    'default' => 'Invalid input.'
  ),
  'initials' => Array (
    'length' => 'The initials must be a maximum of 6 characters in length.',
    'default' => 'Invalid input.',
  ),
  'int_value' => Array (
    'digit' => 'This must be a valid whole number.',
    'maximum' => 'The value specified for this number is too high',
    'minimum' => 'The value specified for this number is too low',
    'required' => 'The value is required.',
    'regex' => 'The value is not of the correct format.',
    'default' => 'The value must be a valid whole number.',
  ),
  'iso' => Array (
    'default' => 'Invalid ISO 639-2 language code.',
  ),
  'language' => array (
    'required' => 'The language name is required.',
  ),
  'language_id' => array (
    'required' => 'The language is required.',
  ),
  'name' => array (
    'required' => 'The name is required.',
  ),
  'param3' => Array(
    'email_list' => 'The list of emails supplied is not valid.',
  ),
  'password' => Array (
    'required' => 'The password cannot be blank.',
    'length' => 'The password must be between 7 and 30 letters in length.',
    'matches' => 'The password and repeat password fields must match.',
    'matches_post' => 'The password and repeat password fields must match.',
      'default' => 'Invalid input.',
  ),
  'path' => Array (
    'required' => 'The image file must be supplied.',
  ),
  'sample_id' => Array (
    'required' => 'The sample must be supplied.',
  ),
  'surname' => Array (
    'required' => 'The surname cannot be blank.',
    'length' => 'The surname must be between 1 and 30 letters.',
    'default' => 'Invalid input.',
  ),
  'survey_id' => Array (
    'required' => 'The survey must be supplied.',
  ),
  'system_function' => Array (
    'length' => 'The stored system functin value must be 30 characters or less. Please check the model declaration of system functions.',
  ),
  'taxon' => array (
    'required' => 'The taxon name is required.',
  ),
  'taxon_group_id' => array (
    'required' => 'The taxon group is required.',
  ),
  'taxon_id' => array (
    'default' => 'Unable to create a valid taxon entry.',
    'required' => 'The taxon is required.',
  ),
  'taxon_list_id' => array (
    'default' => 'Invalid input.',
    'required' => 'The taxon list must be specified.',
  ),
  'taxon_meaning_id' => array (
    'required' => 'The taxon meaning is required.',
  ),
  'taxon_relation_type_id' => array (
    'default' => 'Invalid input.',
    'required' => 'The taxon relation_type must be specified.',
  ),
  'taxa_taxon_list_id' => array (
    'default' => 'Invalid input.',
    'required' => 'The taxon must be specified.',
  ),
  'text_value' => array (
    'regex' => 'The value is not of the correct format.',
    'length' => 'The value is not of the correct length.',
    'required' => 'The value is required.',
    'default' => 'The value is not of the correct format.',
    'standard_text' => 'The value contains characters that are not allowed.',
  ),
  'term' => array (
    'required' => 'The term must be specified.',
  ),
  'termlist_id' => array (
    'required' => 'The termlist must be specified.',
  ),
  'title' => Array (
    'required' => 'The title cannot be blank.',
    'standard_text' => 'Only standard characters are allowed.',
    'length' => 'The title supplied is too long.', // note that the title name is used for fields which are of different max lengths, so can't be more specific
    'default' => 'Invalid input.',
  ),
  'url' => Array (
    'required' => 'The website URL cannot be blank.',
    'url' => 'This must be a valid URL including the http:// prefix.',
    'default' => 'Invalid Input.',
  ),
  'username' => Array (
    'required' => 'The username cannot be blank.',
    'length' => 'The username must be between 7 and 30 letters in length.',
    'unique' => 'This username must be unique, i.e. not shared with another user.',
    'default' => 'Invalid input.',
  ),
  'website_id' => Array (
    'required' => 'The website cannot be blank.',
  ),
  'website_url' => Array (
    'url' => 'This must be a valid URL (if schema not provided, http:// is assumed).',
    'length' => 'The website URL must be a maximum of 1000 characters in length.',
    'default' => 'Invalid input.',
  ),
  'media_upload' => Array (
    'valid' => 'The file is being tagged as invalid.',
  	'required' => 'The file has not been uploaded to the warehouse. One possible reason is that its size may exceed the server upload limits.',
  	'size' => 'The file size exceeds the maximum allowed.',
    'type' => 'The file is not one of the allowed types [png,gif,jpg,jpeg].',
    'default' => 'Invalid file.',
  ),
);

?>
