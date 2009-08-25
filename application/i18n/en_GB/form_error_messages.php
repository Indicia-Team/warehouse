<?php defined('SYSPATH') or die('No direct access allowed.');

$lang = array (
  'geom' => array (
    'required' => 'The spatial reference must be supplied.',
    'default' => 'Invalid input.'
  ),
  'title' => Array (
    'required' => 'The title cannot be blank.',
    'standard_text' => 'Only standard characters are allowed.',
    'length' => 'The title supplied is too long.', // note that the title name is used for fields which are of different max lengths, so can't be more specific
    'default' => 'Invalid Input.',
  ),
  'description' => Array (
    'standard_text' => 'Only standard characters are allowed.',
    'default' => 'Invalid Input.',
  ),
  'deleted' => Array (
    'has_terms' => 'There are terms belonging to this list.',
    'has_taxa' => 'There are species belonging to this list.',
  ),
  'iso' => Array (
    'default' => 'Invalid ISO 639-2 language code.',
  ),
  'website_id' => Array (
    'required' => 'The website cannot be blank.',
  ),
  'surname' => Array (
    'required' => 'The surname cannot be blank.',
    'length' => 'The surname must be between 1 and 30 letters.',
    'default' => 'Invalid Input.',
  ),
  'email_address' => Array (
    'required' => 'The email address cannot be blank.',
    'email' => 'This must be a valid email address.',
    'length' => 'The email address must be a maximum of 50 characters in length.',
    'unique' => 'This email address must be unique, i.e. not shared with another person.',
    'default' => 'Invalid Input.',
  ),
  'url' => Array (
    'required' => 'The website URL cannot be blank.',
    'url' => 'This must be a valid URL including the http:// prefix.',
    'default' => 'Invalid Input.',
  ),
  'website_url' => Array (
    'url' => 'This must be a valid URL (if schema not provided, http:// is assumed).',
    'length' => 'The website URL must be a maximum of 1000 characters in length.',
    'default' => 'Invalid Input.',
  ),
  'taxon_id' => array (
    'default' => 'Unable to create a valid taxon entry.',
  ),
  'taxon_list_id' => array (
    'default' => 'Invalid input.',
    'required' => 'The taxon list must be specified.',
  ),
  'taxa_taxon_list_id' => array (
    'default' => 'Invalid input.',
    'required' => 'The taxon must be specified.',
  ),
  'taxon_group_id' => array (
    'required' => 'The taxon group is required.',
  ),
  'text_value' => array (
    'regex' => 'The value is not of the correct format.',
  ),
  'username' => Array (
    'required' => 'The username cannot be blank.',
    'length' => 'The username must be between 7 and 30 letters in length.',
    'unique' => 'This username must be unique, i.e. not shared with another user.',
    'default' => 'Invalid Input.',
  ),
  'password' => Array (
    'required' => 'The password cannot be blank.',
    'length' => 'The password must be between 7 and 30 letters in length.',
    'matches' => 'The password and repeat password fields must match.',
    'matches_post' => 'The password and repeat password fields must match.',
      'default' => 'Invalid Input.',
  ),
  'entered_sref_system' => Array (
    'sref_system' => 'The spatial reference system is not a valid EPSG or notation code.',
  ),
  'entered_sref' => Array (
    'required' => 'The spatial reference must be supplied.',
    'sref' => 'The spatial reference is not recognised.',
  ),
  'date_start' => Array (
    'date_in_past' => 'Please specify a date that is not in the future.'
  ),
  'date_type' => Array (
    'required' => 'Please supply a date for your observation.',
    'default' => 'Unrecognised vague date type',
  ),
  'first_name' => Array (
    'required' => 'The first name cannot be blank.',
    'length' => 'The first name must be between 1 and 30 letters.',
    'default' => 'Invalid Input.',
  ),
  'initials' => Array (
    'length' => 'The initials must be a maximum of 6 characters in length.',
    'default' => 'Invalid Input.',
  ),
  'address' => Array (
    'length' => 'The address must be a maximum of 200 characters in length.',
    'default' => 'Invalid Input.',
  ),
  'determiner_id' => Array (
    'required' => 'The determiner cannot be blank.',
    'default' => 'Invalid Input.',
  ),
  'caption' => Array (
    'required' => 'The caption cannot be blank.',
  ),
  'path' => Array (
    'required' => 'The image path must be supplied.',
  )
);

?>
