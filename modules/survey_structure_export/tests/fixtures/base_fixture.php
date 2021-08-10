<?php

/**
 * @file
 * A standard database fixture definition needed in all tests.
 *
 * The base fixture sets up the Indicia database with a consistent set of
 * test data in the core tables.
 * Id values in tables having sequences are never supplied so that, if a test
 * adds a record to a table, the sequence will supply it the next valid id.
 */

$fixture = [
  "websites" => [
    [
      "title" => "Test website",
      "description" => "Website for unit testing",
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "url" => "http:,//www.indicia.org.uk",
      "password" => "password",
      "verification_checks_enabled" => 'true',
    ],
  ],
  "users_websites" => [
    [
      "user_id" => 1,
      "website_id" => 1,
      "site_role_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "surveys" => [
    [
      "title" => "Test survey",
      "description" => "Survey for unit testing",
      "website_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
];
