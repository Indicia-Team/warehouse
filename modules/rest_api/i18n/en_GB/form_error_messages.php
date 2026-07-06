<?php

defined('SYSPATH') or die('No direct access allowed.');

$lang = [
  'sample_attribute_id' => [
    'required' => 'The sample attribute id is required.',
    'integer' => 'The sample attribute id must be an integer.',
  ],
  'secret' => [
    'length' => 'The secret must be between 7 and 30 letters in length.',
    'matches_post' => 'The secret and repeat secret fields must match.',
  ],
];
