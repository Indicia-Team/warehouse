# Version 6.8.0
*2021-10-11*

* Bugfixes.
* Adds a custom synchronisation handler for Odonata data synchronised from BTO Birdtrack, to meet
  the needs of the recording scheme.
* Update taxon associations module tests to new PHPUnit version.

# Version 6.7.0
*2021-09-30*

* Allow Users to be searched using an autocomplete when linking users to a location.

# Version 6.6.0
*2021-09-28*

* Added language_iso column to the taxon_list report. This is needed when using this report
  to drive a species_autocomplete, it does not work without this information.
# Version 6.5.0
*2021-09-25*

* Adds cache_occurrences_functional.private field to reflect site privacy status.
* Updates the extraction to Elasticsearch to include additional privacy metadata.

# Version 6.4.0
*2021-09-24*

* Occurrence Elasticsearch extraction updated to include sample (event) media.

# Version 6.3.0
*2021-09-21*

* Support for Elasticsearch indexes which contain samples as documents (including empty samples).
  These can be enabled for access via the REST API.
* Addition of occurrences.verifier_only_data field to support data synced from other systems where
  the data is supplied with attribute values that are only permitted to be used for verification.
* Code updates for PHP 8 compatibility and updated unit test libraries.
* Improvements to sensitivity handling for sample cache data, including:
  * Addition of sensitive & private flags.
  * Blurring the public geometry when any contained occurrences are sensitive,.
  * The public_entered_sref is now populated with the blurred and localised grid reference when
    there are sensitive records in a sample. Formerly it was left null.
  * Fixes a bug where the map square links were not being populated for the full-precision copy of
    sensitive records.
* Adds the following fields fields to samples cache for consistency with the occurrences cache
  tables:
  * cache_samples_functional.external_key
  * cache_samples_functional.sensitive
  * cache_samples_functional.private
  * cache_samples_nonfunctional.output_sref
  * cache_samples_nonfunctional.output_sref_system
  * cache_samples_nonfunctional.private
* Updating an occurrence in isolation (via web services) now updates the tracking ID associated
  with the sample that contains the occurrence. This is so that any sample data feeds receive an
  updated copy of the sample, as the occurrence statistics will have changed.
* Workflow events now allow filters on location, or stage term. These are applied retrospectively
  using a Work Queue task, allowing spatial indexing to be applied to the record first. For example
  this allows a workflow event's effect to be removed from a record if it does not fall inside a
  boundary or is a juvenile.
* REST API module provides sync-taxon-observations and sync-annotation end-points designed for
  synchronising records and verification decisions with remote servers.
* New json_occurrences server type for the REST API Sync module which sychronises data with any
  remote (Indicia or otherwise) server that supports the sync-taxon-observations and
  sync-annotations API format.
* Bug fixes.

## Deprecation notice

* The previously provided taxon-observations and annotations end-points in the REST API (which were
  based on the defunct NBN Gateway Exchange Format) are now deprecated and may be removed in a
  future version.

# Version 6.2.0
*2021-08-02*

* Support for life stages in period-within-year verification rules
* Support for csv import of survey attributes to a website.
* Additional iNat fields can be mapped to custom attributes.
* Allow alert filters to include survey.
* Import/Export of surveys now includes survey attributes.
* Updates to unit test harness.
* Allow dependencies to be maintained with Composer.

# Version 6.1.0
*2021-07-27*

* Reporting updates to support the sample details page when showing parent/child samples, e.g.
  transect walks.

# Version 6.0.0
*2021-05-14*

* Addition of a script for Docker support (work in progress).
* Enhanced support for importing taxonomy updates from the UKSI_History table provided with a
  raw copy of the UK Species Inventory.
* Adds a help link to the top of the list page for events in the Workflow module, plus small
  improvements to help text on the edit form.
* Support for uploading spreadsheets of verification decisions as per enhancements to the
  Elasticsearch [verificationButtons] control.
* Updates to taxa_taxon_list entity now more intelligently decide when the associated occurrence
  cache data also needs to be refreshed.
* Boolean custom attributes now have their underlying datatype set correctly in report outputs.
* Taxon.scientific field value now correctly set after saving taxa via the warehouse UI.
* Fixes an error in scheduled tasks that occurs if there are no data to auto-verify.
* Switch from using feature-bootstrap for client_helpers and media submodules to the default
  master branch (unifying code with Drupal clients).
* Replace jQuery.UI datepicker with HTML5 date input.
* Adds a datepicker polyfill for browsers which don't support HTML 5 dates, e.g. MacOS Safari.
* Switch from jQuery.ui.progress to HTML5 progress in preparation for Drupal 9 support.
* Remove use of jQuery.UI's shake effect in preparation for Drupal 9 support.
* Replace jQuery.sortable with Sortable.js, in preparation for Drupal 9 support.
* Replace jQuery.ui.dialog with a Fancybox derived dialog control, in preparation for Drupal 9
  support.
* Reports for extracting data for Elasticsearch now include taxon list information, accepted taxon
  name and original taxon group information improving support for names that don't map well to
  the configured master list (e.g. UKSI).
* Mime type support for *.zc (Zerocrossing) files.

# Version 5.1.0
*2021-03-22*

* Adds reports to support a new sample_details prebuilt form.

# Version 5.0.0
*2021-02-28*

* Dropped support for oAuth2 password authentication as no longer recommended in the oAuth 2.0
  Security Best Current Practice. See https://oauth.net/2/grant-types/password/.
* JWT authentication on the REST API - dropped http://indicia.org.uk/alldata token in the payload.
  Now, default is to return user's own data only, but this can be expanded by specifying scope
  in the payload with a space separated list of scopes (sharing modes) - defining which website's
  records will appear in the response. Options include: userWithinWebsite, user (all records for
  user across all websites), reporting, verification, data_flow, editing, peer_review, moderation.
  If a JWT token claims multiple scopes, the URL parameter scope can select the active one for a
  request.
* Adds occurrence custom attribute system functions for behaviour and reproductive_condition.

# Version 4.12.0
*2021-02-12*

* Submitting a location record now supports the following metadata field values:
  * metaFields:srid - the SRID of the provided boundary geometry if not 900913 or 3857 (Web
    Mercator).
  * metaFields:mergeBoundary - if set to 't' then when updating an existing location with a new
    submission, the provided boundary is merged with the existing boundary rather than the provided
    boundary overwriting the existing boundary.
* Additional system functions added for occurrences - behaviour and reproductive_condition,
  reflecting Darwin Core terms of the same names.
* Base 64 decoding used to read JWT authentication tokens in the REST API now uses Base 64 URL
  decoding, fixing problems with some characters in tokens.
* REST API and Elasticsearch API now support Website ID and password based authentication with
  automatic application of appropriate website sharing permissions. This reduces the need to
  create multiple Elasticsearch aliases to control website sharing.

# Version 4.11.0
*2021-01-19*

* New UKSI operations warehouse module which accepts a log of taxonomic updates in the same format
  as used to update the master copy of the UKSI taxon list. This reduces the need for periodic and
  complex full updates of the UKSI taxonomy.
* New `es_key_prefix` option in `application/config/indicia.php` which allows a prefix to be added
  to IDs in Elasticsearch downloads (to uniquely ID the warehouse).
* Support for Excel (*.xls or *.xlsx) files in the importer (experimental).
* After upgrades, now more effectively clears appropriate parts of the cache so that UI and ORM
  updates are immediate.
* Performance improvement by indexing notifications table for count of user's outstanding
  notifications.
* Improvements to Elasticsearch download column templates, e.g. for better MapMate export support.
* Option to skip overwrite of verified records in the Rest API sync module (e.g. for iNaturalist
  synchronisation), see https://github.com/BiologicalRecordsCentre/iRecord/issues/972.
# Version 4.10.0
*2020-12-17*

* New uksi_operations module allows update logs for UKSI to be directly imported, with support for
  new taxon, amend metadata, promote name, merge taxa, rename taxon operations.
* Addition of organism_key to taxon table for improved links with UKSI.
* Generation of taxon path data now more reliable (report filtering by higher taxa).
* New work queue task for efficient updates of taxonomy related fields in occurrence cache tables.
* Server status message on home page fixed immediately after install.
* Minor tweaks to improve the install process.
* Support for dynamic addition of termlist terms when inserting attribute values (tag style, e.g.
  using the data_entry_helper::sublist control).
* Fix to check constraint for unique email addresses. Excludes deleted records.
* Fixes a problem where importing to update existing records caused duplicates.
* Elasticsearch downloads now support automatic inclusion of custom attributes for a survey.
* Fix for importing anonymous records into Recorder 6 using Indicia2Recorder.

# Version 4.5.0
*2020-09-16*

* Bugfix for saving an occurrence via the warehouse UI.
* Refactoring of way entities are configured in the REST API.
* REST API now supports creation of surveys, sample and occurrence attributes for users with site
  admin role.
* Several bugfixes relating to RESTful API behaviour.
* Support for creating DINTY tetrad references from server-side database code (e.g. in reports).

# Version 4.4.0
*2020-08-21*

* The training flag can now be applied to samples, not just occurrences.
* Add support for importing locations from SHP files in Web Mercator projection.
* Importing a location with multiple entries in the SHP file *.dbf file now results in multiple
  locations, rather than only the first being imported. They can be manually merged afterwards if
  required.
* Rest API supports JWT authentication, including support for storing a public key in website
  registration details that can be used for JWT authentication in the REST API.
* Rest API supports creating, updating and reading samples, occurrences and locations. See
  /index.php/services/rest after upgrading the warehouse for details.
* Rest API suports a media-queue endpoint which allows images to be posted first then attached to
  subsequent data submissions.
* Submissions of any media data can include a `queue` field value instead of a path. If supplied,
  then a matching file will be copied from the media queue if it exists and linked to the
  submission.

# Version 4.3.0

* Adds `taxon_id` and `search_code` to cache_taxa_taxon_lists.
* Performance improvements by removing joins that are no longer necessary.
* Adds a performance diagnostics dashboard to the admin menu.
* Fixes validation of float attribute values so that negative numbers with zero
  at end of numbers after decimal point are not rejected.

# Version 4.2.0
*2020-08-03*

* Attributes display additional info for termlists in lookup to help disambiguate similar names.
* Addition of freshwater_flag, terrestrial_flag, non_native_flag to taxonomic data model.
* Support for a new easy-download format in Elasticsearch downloads.
* Summariser module integration with work queue.

# Version 4.1.0
*2020-06-22*

*Important info for Elasticsearch users*

Before updating to this version, if you are using Elasticsearch please add the
following mappings:
```
PUT <index name>/_mapping/doc
{
  "properties": {
    "location.code": { "type": "keyword" },
    "location.parent.code": { "type": "keyword" },
    "event.parent_attributes": {
      "type": "nested"
    }
  }
}
```

After updating to this version, use Kibana Dev to post the following to clean up
location info for sensitive records:

```
POST /<index name>/_update_by_query
{
  "script": {
    "source": """
      ctx._source.location.remove('location_id');
      ctx._source.location.remove('location_name');
    """,
    "lang": "painless"
  },
  "query": {
    "bool": {
      "must": [{
        "term": {
          "metadata.sensitivity_blur.keyword": "B"
        }
      }]
    }
  }
}
```

* Adds location and parent location codes to the Elasticsearch extraction reports.
* Adds parent sample attributes to the Elasticsearch extraction reports.

# Version 4.0.0
*2020-05-20*

Major version update due to breaking changes in the Elasticsearch REST API:

  * Format for the addColumns parameter in calls to the Elasticsearch REST API
    endpoints (for CSV downloads) now changed to match the format of the client
    [dataGrid] control's columns configuration. Therefore custom ES download
    formats will need reconfiguring on the client.

* PHP minimum version supported now 5.6.

# Version 3.4.0
*2020-05-04*

* Ability to import into the `locations` table whilst referencing the
  location's parent by `id`.
* Ability to import into the `samples` table whilst looking up the
  associated location by `id`.
* If location ID provided when importing a sample, then the sample's
  `entered_sref` and `entered_sref_system` fields are not required in the
  import data as they can be extracted from the location.

# Version 3.3.0
*2020-04-16*

* Reduced likelihood that emails sent by scheduled tasks are detected as spam:
    * Receipient names correctly added.
    * HTML structure improvements.
* Possible to import and update existing samples using their external_key field
  as a unique row identifier.
* Improvements to cascading mark-deletion of sample records.
* New report `library/locations/locations_list_3.xml` which allows the list to
  be filtered by an intersecting point.
* Support for remote download into Recorder 6 using the original record creator
  as the creator of the record in Recorder 6 (rather than the person doing the
  import).
* New Darwin Core archive download reports for GBIF IPT compatible data
  extraction.
* Darwin Core archive download reports allow BasisOfRecord to be overridden.
* Darwin Core archive download reports remove line breaks from comments.
* Bug fixes to the updating of single attribute values into the reporting
  cache tables in the work queue system.
* Bug fixes around the auto-feed tracking of data into Elasticsearch.
* Fixes to CSV formatting when extracting CSV data from Elasticsearch.
* Bug fixes for upgrades from very old warehouse installations.

# Version 3.2.0
*2020-03-29*

* Report `reports/library/locations/locations_list_from_search_location.xml`
  allows multiple location_type_ids to be selected.

# Version 3.1.0
*2020-01-15*

* Support for Swedish reference systems, EPSG:3006 and EPSG:3021.
* Significant performance enhancements in the auto_verify module.
* Updates of individual attribute value tables now create work queue tasks to update the
  cache tables.
* Bug fixes on the edit form for workflow events.
* Workflow events can now be linked to species on non-standard species lists if required.
* Report library/filters/filter_with_transformed_searcharea.xml performance improved.
* Changes to Elasticsearch record details report to support alterations to the layout of
  this control.

# Version 3.0.0
*2019-12-03*

Note that the major version increment reflects the fact that the following
potentially breaking changes exist in this version:

* Some fields have been removed from the users table as described below.
  This change is mitigated by the fact that the removed fields were not used by
  any core Indicia code so could only impact custom developments.
* The REST API for downloading Elasticsearch data has been changed so clients
  using the experimental Elasticsearch client helper code will need to be
  updated in order for downloads to continue working.

These changes are considered low risk for impact on existing sites other than
those which use the experimental Elasticsearch client code.

* Removed unused fields from the `users` table:
  * home_entered_sref
  * home_entered_sref_system
  * interests
  * location_name
  * email_visible
  * view_common_names
* HTML Bootstrap style applied for:
  * User account password fields
  * User website roles.
* Consistency of terminology.
* Fixes for installations where schema name not set to indicia.
* Updating a survey no longer triggers a complete refresh of cache data due to
  performance impact.
* Installation guide updated for recent PostgreSQL/PostGIS versions.

# Version 2.37.0
*2019-11-07*

* Fixes a bug when saving a new survey.
* Fixes import of NBN Record cleaner rules.
* Support for attribute values in Elasticsearch data downloads.
* Minor updates to UKBMS downloads.

# Version 2.36.0
*2019-10-25*

* UI added so that survey datasets will be able to define the taxonomic branches which
  auto-verification will be applied to. https://github.com/BiologicalRecordsCentre/iRecord/issues/486
* Minor wording changes in notification emails.
* Elasticsearch extractions include basic taxonomic info even for taxa who are not on one of the
  officially configured lists.

# Version 2.35.0
*2019-10-03*

* Improved memory consumption when requesting large amounts of data from Data
  Services.
* DwC-A files now include a readme file that explains how to repair the file in
  the event of an error such as a query timeout. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/477.

# Version 2.34.0
*2019-10-01*

* Identification difficulty flags now always raised for benefit of verifiers,
  even if recorder competent with that species. However, the notification is
  only sent if the recorder has insufficient track record for that species.
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/657).
* Removed inadvertant required flag on person title field in edit form.
* Tweaks to UKBMS summary builder calculation optimisations.

# Version 2.33.0
*2019-09-30*

* Higher geography Elasticsearch download fixed.
* Fix taxon search ordering of results
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/669).
* Fixes relating to Elasticsearch scroll mode (pagination) not applying column
  settings.

# Version 2.32.0
*2019-09-03*

* Support for loading dynamic attributes for multiple occurrences in one go (required for species checklist). See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/637.
* Fixes a bug in the Swift mailer class loader which was being too eager on some setups, causing file not found errors.

# Version 2.31.0
*2019-08-29*

* Refactor of the Summary Builder module to use the work_queue for greater efficiency.

# Version 2.30.0
*2019-08-28*

* REST API Elasticsearch CSV downloads now support flexible download column templates.
* When importing against existing taxa, can now match against "Species list and taxon search code".

# Version 2.29.0
*2019-08-04*

* Taxon search API now allows exclusion of taxa or taxon names via options exclude_taxon_meaning_id,
  exclude_taxa_taxon_list_id and exclude_preferred_taxa_taxon_list_id.
* Taxon search API now supports option commonNames=defaults, meaning that non-default common names will be excluded.

# Version 2.28.0
*2019-07-01*

* Set option `do_not_send` to false in `application/config/email.php` to prevent server from attempting to send
  notification emails on development and test servers (https://github.com/Indicia-Team/warehouse/issues/323).
* Improved error handling where vague date ranges are the wrong way round ().
* Improvements to reporting standard parameters handling where there are multiple filters for taxonomic limits
  interacting.
* Fixed bugs filtering against occurrence association reports (https://github.com/Indicia-Team/warehouse/issues/322).
* CSV Importer now supports a mode where it checks and validates all records without committing anything.
* Fixes problems with links between preferred and non-preferred taxa for newly entered taxa via the UI
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/548).
* Output grid ref system no longer uses the input grid ref system as a parameter, ensuring output grid refs are
  consistent in each locality around the world.
* When scheduled tasks run from a browser, the output for cache building is significantlt tidier.
* REST API Sync module now works correctly when run from a URL endpoint.
* Bug fixes to Survey Structure Importer when handling termlist data.
* My sites lookup (for location autocompletes) now trims the search text, preventing errors in the full text lookup.
* Updates to reports used to extract data into Elasticsearch.
* When re-using a location for data entry, more than one location-linked sample attribute's values can be recovered from
  the last submission to provide defaults for the next submission (e.g. to obtain a default for habitat and altitude)
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/321).

# Version 2.27.0
*2019-05-29*

* Elasticsearch extraction reports include map squares data and verification decision source.
* Correct CC licence codes (e.g. CC-BY-AT becomes CC BY-AT).

# Version 2.26.0
*2019-05-13*

* Adds sensitivity precision control to occurrence edit form.
* Data services views for custom attributes include the unit field in the response.
* Spatial services buffer function allows the projection code and number of segments to be passed as parameters.

# Version 2.25.0
*2019-05-03*

* Fixes re-use of previous location related sample data from a site when adding a new sample so that more than one
  value can be copied over.
* Fixes an error when auto_log_determinations is off and a record is redetermined.

# Version 2.22.0
*2019-04-22*

* Updates views for taxon designation data to support new tools for editing taxon designations.

# Version 2.21.0
*2019-04-17*

* Updates the fields available when doing CSV download from Elasticsearch.
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/549)
* New report required for showing recorder email addresses to verifiers using
  Elasticsearch (https://github.com/BiologicalRecordsCentre/iRecord/issues/552).
* A report providing a hierarchical view of a termlist, used for editing trait
  data in the Pantheon system.

# Version 2.20.0
*2019-04-15*

* Data services submission format now allows fkField to override the name of the key linked to a foreign key when
  describing entity relationships in a data submission. Further info at
  https://indicia-docs.readthedocs.io/en/latest/developing/web-services/submission-format.html?highlight=fkfield#super-and-sub-models
* iNaturalist sync method in the `rest_api_sync` module now skips unlicenced photos.
* New report, `reports/library/locations/location_boundary_projected.xml`, provides a simple list of location boundaries
  in a given projection, ideal for use on Leaflet maps.
* New report, `reports/library/taxa/taxon_list.xml`, provides a simple list of taxon names and associated data.

# Version 2.19.0
*2019-04-08*

* Adds support for categorisation of scratchpad lists via new scratchpad_type_id field
  and associated termlist.

# Version 2.18.0
*2019-04-04*

* Request_logging module can now capture additional types of events to track performance
  of save events, imports, verification and taxon searches. See the provided example
  config file under modules/request_logging/config.

# Version 2.17.0
*2019-04-03*

* Fixes required to run on PHP 7.3 (not yet fully tested).
* Import guids are now true GUIDs rather than numeric. Prevents Excel mashing the large
  numbers to scientific notation and therefore preventing re-imports of error files from
  binding to the correct import GUID.
* INaturalist sync now pages data and limits processing per run to cope with larger
  datasets.
* INaturalist sync (and any others built on the Rest_api_sync module) now tolerate naming
  differences where subspecies names either do or don't include the rank.
* New report for scratchpad list species external keys - can be used to drive sensitive
  record suggestions on a client entry form.

# Version 2.16.0
*2019-03-20*

* Changes required to allow tracked correspondance to appear on client where appropriate.
* ES searches which contain {} are no longer broken by converting to [].

# Version 2.15.0
*2019-03-19*

* email.notification_subject and email.notification_intro can both be
  overridden in the application/config/email.php file.
* Workflow mapping reports make verified records at top of z order.
* Variant on workflow records explore report that outputs full precision grid
  ref for download.
* Fix problem in Swift email component for PHP 7 which caused PHP errors.
* Record data for verification uses recorder email rather than inputter email
  where available, so record queries go to the correct location.

# Version 2.14.0

* Spatial index builder supports automatic inclusion of location parents in the index,
  improving performance in the background tasks where layers are hierarchical since only
  the bottom layer needs to be scanned.
* Autofeed reports in the REST API support tracking updates by a date field where the
  cache_occurrences_functional.tracking field is not available.

# Version 2.13.0
*2019-03-09*

* Filters list report loaded onto report pages - improved performance.
* New list_verify web service for verifying against a list of IDs.

# Version 2.12.0
*2019-03-07*

* Fixes importing of constituent date fields into occurrences (#318).
* Installation process fixed in some environments (#317).
* Elasticsearch proxy in REST API and scrolling support for large downloads.

# Version 2.11.0
*2019-02-26*

* CSV files generated for download using the REST API and the Elasticsearch scroll API
  are now zipped.

# Version 2.10.0
*2019-02-22*

* Adds support for importing locations using TM65 Irish Grid projection.

# Version 2.9.0
*2019-02-22*

* Adds a download folder to warehouse for temporary generated download files.
* REST API Elasticsearch proxy supports the Scroll API for generation of large downnload files in chunks.
* REST API Elasticsearch proxy supports formatting output as CSV.
* Fix REST API JSON output so that zeros are not excluded.
* Improvements to Elasticsearch document format.
* Updates to reports for Splash.

# Version 2.8.0
*2019-02-15*

* Elasticsearch output reports now include custom attributes data.
* Fixes a syntax error in spatial indexing SQL statements.

# Version 2.7.0
*2019-02-13*

* Saving a record slightly faster, because ap square updates are done in a single update
  statement rather than one per square size.
* Saving records also slightly faster due to reduced number of update statements that are
  run on the cache tables.
* New tracking field to store an autogenerated sequential system unique update ID in
  cache_occurrences_functional and cache_samples_functional. This makes it easier to
  track any changes to the cache tables, e.g. for feeding changes through to other
  systems such as Elasticsearch. It also makes change tracking of system generated
  changes (such as spatial indexing) seperate to user instigated record changes so that
  notifications are not unnecessarily generated for the former.
* New standard reporting parameters `tracking_from` and `tracking_to` for filtering on
  change tracking IDs.
* REST API uses tracking data rather than updated_on field when using autofeed mode to
  autogenerate a feed of updates.
* Spatial indexing no longer changes cache table updated_on fields therefore does not
  fire notifications.
* Fixes a problem in the update kit with a missing class file
  (https://github.com/Indicia-Team/warehouse/issues/315)

# Version 2.6.0
*2019-02-07*

* New indexed_location_type_list standard parameter for reports. Allows
  filtering to any record which is indexed against a site of the given type(s).
* Uploading locations from SHP file now generates work queue entries correctly
  for updates as well as inserts.
* Spatial indexer updates the cache table updated_on fields when changing the
  location_ids field in the cache. This makes it easier to pass changes through
  to feeds such as Elasticsearch.

# Version 2.5.0
*2019-02-05*

## Database schema changes

* Terms.term field is now unlimited length.

## Other changes

* Fixes a bug saving attribute values that contain ranges.
* Fixes a bug where an existing deleted attribute value prevented others from
  being saved.
* Support for a restricted attribute in report files for reports which are only
  available to RESTful API clients which have been explicitly authorised to use
  them.
* Updated_on flag is updated after a taxonomy update in the associated
  cache_occurrences_functional records. Previuosly a taxon name change or
  similar would not cause the updated_on field to be updated making change
  tracking harder.
* A parameter request in a report result no longer prevents the report output
  from being counted.
* Elasticsearch population reports updated (better performance and document
  structure, restricted access where appropriate).

# Version 2.4.0
*2019-01-21*

* Support for proxy requests to an Elasticsearch cluster, with authentication &
  authorisation support in the RESTful API. See
  https://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/elasticsearch.html and
  https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/README.md.


# Version 2.3.0
*2019-01-09*

## Database schema changes

* Add the following cache fields:
  * cache_occurrences_functional.verification_checks_enabled
  * cache_occurrences_functional.parent_sample_id
  * cache_samples_functional.parent_sample_id
* Update many reports to avoid need to join to websites table since
  verification_checks_enabled now in cache.
* Cache table location_ids field now stores an empty array when the associated
  sample is not linked to any indexed locations rather than null, allowing
  records not yet indexed to be identifiable.

# Version 2.2.0
*2018-12-19*

* Enable use of a scratchpad list of species as a standard filter parameter.

# Version 2.1.0
*2018-12-18*

* Enable import of occurrences where the taxon is identified using a known
  taxa_taxon_list_id.

# Version 2.0.0
*2018-12-14*

Please see [upgrading to version 2.0.0](UPGRADE-v2.md).

## Warehouse user interface changes

* Warehouse client helper and media code libraries updated to use jQuery 3.2.1
  and jQuery UI 1.12.
* Overhaul the warehouse UI with a new Bootstrap 3 based theme and more logical menu
  structure.
* Warehouse home page now has additional help for getting started and diagnosing
  problems.

## Back-end changes

* Support for PostgreSQL version 10.
* Support for PHP 7.2.
* Support for prioritised load aware background task scheduling via a work queue module.

## Database schema changes

* Removed the following:
  * index_locations_samples table
  * fields named location_id_* from cache_occurrences_functional
  * fields named location_id_* from cache_samples_functional
  Replaced the above with cache_occurrences_functional.location_ids (integer[]) and
  cache_samples_functional.location_ids (integer[]). This means there is no need to
  distinguish between uniquely indexed location types (linked via the locaiton_id_*
  fields) and non-uniquely indexed location types (linked in the index_locations_samples
  table). Removes the need for additional join to index_locations_samples so will improve
  performance in many cases.
* Attribute values for taxa, samples and occurrences are now stored in the relevant
  reporting cache tables in a JSON document (attrs_json field). This means that reports
  can output custom attribute values for a record without additional joins for each
  attribute. To enable this functionality, the report needs a parameter of type taxattrs,
  smpattrs or occattrs (allowing attributes to include to be dynamically declared in a
  parameter). Then provide a parameter useJsonAttributes set to a value of '1' to enable
  the new method of accessing attribute values. This has the potential to improve
  performance significantly for reports which include many different attribute values in
  the output.
* New cache_taxon_paths which provides a hierarchical link between taxa and all their
  taxonomic parents.
* Cache_occurrences_functional now has a taxon_path field which links to the parent taxa
  for the record, as defined by the main taxon list configured on the warehouse.
* Reports no longer need to join via users to check the sharing/privacy settings of a
  user. Any sharing task codes which are not available for the user are listed in
  cache_*_functional.blocked_sharing_tasks.
* Support for dynamic attributes, i.e custom sample or occurrence attributes which are
  linked to a taxon. They can then be included on a recording form only when entering a
  taxon that is, or is a descendant of, the linked taxon.

## Report updates

* Updated reports which used the location_id_* fields to output a location for a record
  to now update a list of overlapping locations rather than being limited to a single
  location. For example, if a record is added which overlaps 2 country boundaries then
  the report may include both country names in the output field rather than just one.
* Removed the following unused verification reports. Existing verification
  implementations on client websites should be updated to use the verification_5 prebuilt
  form and its reports:
  * reports/library/occurrences/verification_list.xml
  * reports/library/occurrences/verification_list_2.xml
  * reports/library/occurrences/verification_list_3.xml
  * reports/library/occurrences/verification_list_3_mapping.xml
  * reports/library/occurrences/verification_list_3_mapping_using_spatial_index_builder.xml
  * reports/library/occurrences/verification_list_3_using_spatial_index_builder.xml
* Other unused reports removed:
  * reports/library/locations/filterable_occurrence_counts_mappable_2.xml
  * reports/library/locations/filterable_species_counts_mappable_2.xml
