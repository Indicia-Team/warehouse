<?php

defined('SYSPATH') or die('No direct access allowed.');

$lang = [
  'address' => [
    'length' => 'The address must be a maximum of 200 characters in length.',
    'default' => 'Invalid input.',
  ],
  'awarded_by' => [
    'required' => 'Please specify the organisation awarding this milestone',
  ],
  'caption' => [
    'required' => 'The caption cannot be blank.',
    'length' => 'The caption must be a maximum of 50 characters in length.',
  ],
  'centroid_sref' => [
    'required' => 'The spatial reference must be supplied.',
    'sref' => 'The spatial reference is not recognised.',
  ],
  'code' => [
    'length' => 'The code must be a maximum of 20 characters.',
  ],
  'comment' => [
    'required' => 'Please provide a comment.',
  ],
  'count' => [
    'required' => 'Count cannot be blank.',
    'digit' => 'This must be a valid whole number.',
  ],
  'csv_upload' => [
    'size' => 'The uploaded file is too large.',
    'required' => 'Missing upload file.',
    'default' => 'Invalid file uploaded',
  ],
  'data_type' => [
    'required' => 'The data type must be supplied.',
  ],
  'date_start' => [
    'date_in_past' => 'Please specify a date that is not in the future.',
  ],
  'date_start_value' => [
    'date_in_past' => 'Please specify a date that is not in the future.',
  ],
  'date_type' => [
    'required' => 'Please supply a date for your observation.',
    'default' => 'Unrecognised vague date type',
  ],
  'deleted' => [
    'has_terms' => 'There are terms belonging to this list.',
    'has_taxa' => 'There are species belonging to this list.',
  ],
  'description' => [
    'standard_text' => 'Only standard characters are allowed.',
    'default' => 'Invalid input.',
  ],
  'determiner_id' => [
    'required' => 'The determiner cannot be blank.',
    'default' => 'Invalid input.',
  ],
  'dna_sequence' => [
    'required' => 'Please provide a value for the DNA sequence.',
  ],
  'email_address' => [
    'required' => 'The email address cannot be blank.',
    'email' => 'This must be a valid email address.',
    'length' => 'The email address must be a maximum of 100 characters in length.',
    'unique' => 'This email address must be unique, i.e. not shared with another person.',
    'default' => 'Invalid input.',
  ],
  'entered_sref' => [
    'required' => 'The spatial reference must be supplied.',
    'sref' => 'The spatial reference is not recognised.',
    'default' => 'Invalid input.',
  ],
  'entered_sref_system' => [
    'required' => 'The spatial reference system must be supplied.',
    'sref_system' => 'The spatial reference system is not a valid EPSG or notation code.',
    'default' => 'Invalid input.',
  ],
  'centroid_sref_system' => [
    'required' => 'The centroid spatial reference system must be supplied.',
    'default' => 'Invalid input.',
  ],
  'external_key' => [
    'length' => 'The external key field can be up to 50 characters long.',
  ],
  'first_name' => [
    'required' => 'The first name cannot be blank.',
    'length' => 'The first name must be between 1 and 50 letters.',
    'default' => 'Invalid input.',
  ],
  'float_value' => [
    'default' => 'This must be a valid number.',
    'maximum' => 'The value specified for this number is too high.',
    'minimum' => 'The value specified for this number is too low.',
    'required' => 'The value is required.',
    'regex' => 'The value is not of the correct format.',
  ],
  'geom' => [
    'required' => 'The spatial reference must be supplied.',
    'default' => 'Invalid input.',
  ],
  'initials' => [
    'length' => 'The initials must be a maximum of 6 characters in length.',
    'default' => 'Invalid input.',
  ],
  'int_value' => [
    'digit' => 'This must be a valid whole number.',
    'maximum' => 'The value specified for this number is too high.',
    'minimum' => 'The value specified for this number is too low.',
    'required' => 'The value is required.',
    'regex' => 'The value is not of the correct format.',
    'default' => 'The value must be a valid whole number.',
  ],
  'iso' => [
    'default' => 'Invalid ISO 639-2 language code.',
  ],
  'language' => [
    'required' => 'The language name is required.',
  ],
  'language_id' => [
    'required' => 'The language is required.',
  ],
  'licence_id' => [
    'integer' => 'The licence ID must be a valid integer.',
  ],
  'location_name' => [
    'required' => 'The location name is required.',
  ],
  'location_id' => [
    'required' => 'This record must be linked to a location.',
  ],
  'machine_involvement' => [
    'minimum' => 'The machine involvement value must be in a range 0-5.',
    'maximum' => 'The machine involvement value must be in a range 0-5.',
  ],
  'name' => [
    'required' => 'The name is required.',
  ],
  'param3' => [
    'email_list' => 'The list of emails supplied is not valid.',
  ],
  'password' => [
    'required' => 'The password cannot be blank.',
    'length' => 'The password must be between 7 and 30 letters in length.',
    'matches' => 'The password and repeat password fields must match.',
    'matches_post' => 'The password and repeat password fields must match.',
    'default' => 'Invalid input.',
  ],
  'path' => [
    'required' => 'The image file must be supplied.',
  ],
  'pcr_primer_reference' => [
    'required' => 'Please provide a value for the PCR primer reference.',
  ],
  'recorder_names' => [
    'required' => 'The recorder names must be supplied.',
  ],
  'sample_id' => [
    'required' => 'The sample must be supplied.',
  ],
  'success_message' => [
    'required' => 'The success message cannot be blank.',
  ],
  'surname' => [
    'required' => 'The surname cannot be blank.',
    'length' => 'The surname must be between 1 and 50 letters.',
    'default' => 'Invalid input.',
  ],
  'survey_id' => [
    'required' => 'The survey must be supplied.',
  ],
  'system_function' => [
    'length' => 'The stored system functin value must be 30 characters or less. Please check the model declaration of system functions.',
  ],
  'target_gene' => [
    'required' => 'Please provide a value for the target gene.',
  ],
  'taxon' => [
    'required' => 'The taxon name is required.',
  ],
  'taxon_group_id' => [
    'required' => 'The taxon group is required.',
  ],
  'taxon_id' => [
    'default' => 'Unable to create a valid taxon entry.',
    'required' => 'The taxon is required.',
  ],
  'taxon_list_id' => [
    'default' => 'Invalid input.',
    'required' => 'The taxon list must be specified.',
  ],
  'taxon_meaning_id' => [
    'required' => 'The taxon meaning is required.',
  ],
  'taxon_relation_type_id' => [
    'default' => 'Invalid input.',
    'required' => 'The taxon relation_type must be specified.',
  ],
  'taxa_taxon_list_id' => [
    'default' => 'Invalid input.',
    'required' => 'The taxon must be specified.',
  ],
  'text_value' => [
    'regex' => 'The value is not of the correct format.',
    'length' => 'The value is not of the correct length.',
    'required' => 'The value is required.',
    'default' => 'The value is not of the correct format.',
    'standard_text' => 'The value contains characters that are not allowed.',
    'decimal' => 'The value does not have the required number of digits before/after the decimal point.',
    'decimal_range' => 'One or more value does not have the required number of digits before/after the decimal point.',
  ],
  'term' => [
    'required' => 'The term must be specified.',
  ],
  'termlist_id' => [
    'required' => 'The termlist must be specified.',
  ],
  'title' => [
    'required' => 'The title cannot be blank.',
    'standard_text' => 'Only standard characters are allowed.',
    // Note that the title name is used for fields which are of different max
    // lengths, so can't be more specific.
    'length' => 'The title supplied is too long.',
    'unique' => 'This title must be unique.',
    'default' => 'Invalid input.',
  ],
  'upper_value' => [
    'default' => 'Invalid upper value.',
    'integer' => 'The upper value must be a whole number.',
    'maximum' => 'The upper value is too high.',
    'minimum' => 'The upper value is too low.',
    'regex' => 'The upper value is not of the correct format.',
  ],
  'url' => [
    'required' => 'The website URL cannot be blank.',
    'url' => 'This must be a valid URL including the http:// prefix.',
    'default' => 'Invalid Input.',
  ],
  'staging_urls' => [
    'url_list' => 'The list of URLs supplied is not valid.',
  ],
  'username' => [
    'required' => 'The username cannot be blank.',
    'length' => 'The username must be between 7 and 30 letters in length.',
    'unique' => 'This username must be unique, i.e. not shared with another user.',
    'default' => 'Invalid input.',
  ],
  'website_id' => [
    'required' => 'The website cannot be blank.',
  ],
  'website_url' => [
    'url' => 'This must be a valid URL (if schema not provided, http:// is assumed).',
    'length' => 'The website URL must be a maximum of 1000 characters in length.',
    'default' => 'Invalid input.',
  ],
  'media_upload' => [
    'valid' => 'The file is being tagged as invalid.',
    'required' => 'The file has not been uploaded to the warehouse. One possible reason is that its size may exceed the server upload limits.',
    'size' => 'The file size exceeds the maximum allowed.',
    'type' => 'The file is not one of the allowed types.',
    'default' => 'Invalid file.',
  ],
  'association_type_id' => [
    'required' => 'The association type must be supplied.',
  ],
  'from_occurrence_id' => [
    'required' => 'The association from_occurrence_id must be supplied.',
  ],
  'to_occurrence_id' => [
    'required' => 'The association to_occurrence_id must be supplied.',
  ],
  'survey_attribute:allow_ranges' => [
    'notmultiple' => 'Multi-value attributes which allow ranges are not currently supported. Please untick Allow ' .
      'multiple values or Allow ranges.',
  ],
  'sample_attribute:allow_ranges' => [
    'notmultiple' => 'Multi-value attributes which allow ranges are not currently supported. Please untick Allow ' .
      'multiple values or Allow ranges.',
  ],
  'occurrence_attribute:allow_ranges' => [
    'notmultiple' => 'Multi-value attributes which allow ranges are not currently supported. Please untick Allow ' .
      'multiple values or Allow ranges.',
  ],
  'location_attribute:allow_ranges' => [
    'notmultiple' => 'Multi-value attributes which allow ranges are not currently supported. Please untick Allow ' .
      'multiple values or Allow ranges.',
  ],
  'taxa_taxon_list_attribute:allow_ranges' => [
    'notmultiple' => 'Multi-value attributes which allow ranges are not currently supported. Please untick Allow ' .
      'multiple values or Allow ranges.',
  ],
  'person_attribute:allow_ranges' => [
    'notmultiple' => 'Multi-value attributes which allow ranges are not currently supported. Please untick Allow ' .
      'multiple values or Allow ranges.',
  ],
];
