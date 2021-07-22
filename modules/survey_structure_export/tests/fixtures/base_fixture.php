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
  "termlists" => [
    [
      "title" => "Test term list",
      "description" => "Term list list for unit testing",
      "website_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "external_key" => "TESTKEY",
    ],
  ],
  "terms" => [
    [
      "term" => "Test term 1",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
    [
      "term" => "Test term 2",
      "language_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
    ],
  ],
  "meanings" => [
    ["id" => 10000],
    ["id" => 10001],
  ],
  "termlists_terms" => [
    [
      "termlist_id" => 1,
      // Test term 1.
      "term_id" => 1,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10000,
      "preferred" => "true",
      "sort_order" => 1,
    ],
    [
      "termlist_id" => 1,
      // Test term 2.
      "term_id" => 2,
      "created_on" => "2016-07-22 16:00:00",
      "created_by_id" => 1,
      "updated_on" => "2016-07-22 16:00:00",
      "updated_by_id" => 1,
      "meaning_id" => 10001,
      "preferred" => "true",
      "sort_order" => 2,
    ],
  ],
  "cache_termlists_terms" => [
    [
      "id" => 1,
      "preferred" => "true",
      "termlist_id" => 1,
      "termlist_title" => "Test term list",
      "website_id" => 1,
      "preferred_termlists_term_id" => 1,
      "sort_order" => 1,
      "term" => "Test term 1",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "Test term 1",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10000,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
    [
      "id" => 2,
      "preferred" => "true",
      "termlist_id" => 1,
      "termlist_title" => "Test term list",
      "website_id" => 1,
      "preferred_termlists_term_id" => 2,
      "sort_order" => 2,
      "term" => "Test term 2",
      "language_iso" => "eng",
      "language" => "English",
      "preferred_term" => "Test term 2",
      "preferred_language_iso" => "eng",
      "preferred_language" => "English",
      "meaning_id" => 10001,
      "cache_created_on" => "2016-07-22 16:00:00",
      "cache_updated_on" => "2016-07-22 16:00:00",
    ],
  ],
];
