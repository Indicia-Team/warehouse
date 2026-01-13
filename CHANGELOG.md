# Changelog

Notable changes to the Indicia warehouse are documented here.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Version 9.17.0
*2026-01-13*

* Adds support for CSV attachments containing data in trigger emails. See the example in
  `reports/trigger_templates/csv_test.xml`.

## Version 9.16.0
*2026-01-12*

* Adds support for DNA-derived occurrence data. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1935:
  * New dna_occurrences database table and a dna_derived flag in the occurrences table.
  * Update the UI to show DNA data on a tab when viewing an occurrence.
  * Update report standard parameter filters to support filtering to show or hide DNA occurrences.
  * Support for importing DNA-derived occurrence data.
  * Add support for the dna_occurrence entity to the REST API.
* Add support for hectad quadrant grid references for OSGB and OSIE references. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1737.
* Improve error message when corrupt image submitted. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1942.
* Adds a flag for manually added taxa, so that they can be detected and handled differently by the
  UKSI sync script. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1953.
* Improve error handling in the importer when it discovers invalid spatial reference systems in the
  imported data. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1972.
* Importer - removed unnecessary defined in file option for some of the global option dropdowns.
* Fix out of memory errors when editing existing custom attributes to set dynamic taxonn
  restrictions.
* Add an option to log emails to a database table for analysis. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1952.
* Bugfix to the devLogger email handler.
* Update the function used to fixup data links after a UKSI update to also update
  `cache_taxa_taxon_lists.applicable_verification_rule_types`.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1982.

## Version 9.15.0
*2025-10-09*

### Changes

* Adds support for alternative architectures for sending emails, including Microsoft Graph.
  See Warehouse installation notes for further info:
  https://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/warehouse-installation.html#email-configuration.
* Adds an email helper that just logs the email details for development.
* Upgrades to the v2 importer for handling large import files - see
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1921. To use this option enable the
  "Enable background imports" on the import page's Edit tab. Also allows multiple files with the
  same structure to be combined into a single import.

## Version 9.14.0
*2025-08-06*

### Changes

* Adds a new option to the spatial_index_builder module to limit the indexed samples by grid square
  size, so large impreceise samples are not indexed against small site boundaries. See
  https://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/modules/spatial-index-builder.html.
* Invalid Elasticsearch query string searches now provide more information in the error response.

### Bugfixes

* Fixes an issue where large imports would exclude the second half of the records.
* Bugfix to the detection of unique group titles (activity names).

## Version 9.13.0
*2025-08-01*

### Action required

* If using Elasticsearch, update your Logstash configuration files to ensure that the new
  `location.higher_geography_blurred` are processed correctly. Add the sections relating to the
  indexed_location_ids_blurred field as per the copy on GitHub - see the `mutate`, `translate` and `ruby` blocks
  starting at this line of the configuration:
  https://github.com/Indicia-Team/support_files/blob/5fde0f91e75a6cd98fe15be0c0872f5bec25763a/Elasticsearch/logstash-config/occurrences-http-indicia.template#L136.
* If using Elasticsearch, add a mapping each index for the `location.higher_geography_blurred` nested field. You will
  typically need to do this once for occurrences and once for the samples index.
  ```
  PUT <index name>/_mapping
  {
    "properties": {
      "location.higher_geography_blurred": {
        "type": "nested",
        "properties": {
          "id": { "type": "integer" },
          "code": { "type": "keyword" }
        }
      }
    }
  }
  ```

### Changes

* The REST API now allows a user to view all comments for an occurrence or sample they created when limited to their
  own data, previously they were restricted to comments which they created themselves.
* The group title field now has a unique constraint and error reporting of uniqueness violations has been improved. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1893.
* The feed to Elasticsearch for full-precision copies of sensitive records now includes the higher geography
  recalculated using the full-precision copy of the record. There is an additional field containing the blurred version
  of the higher geography. For example, a record in a Vice County with a 100km sensitivity blur will be indexed against
  all Vice Counties intersecting the blurred version of the map reference for the blurred version of the record. The
  full-precision copy of the record will now have just the one precise Vice County in the higher geography, with an
  additional blurred version reflecting all the Vice Counties intersecting the blurred record location. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1714.
* Significant performance improvement in the code which runs in the background to generate record owner notifications.

### Bugfixes

* Fixes an issue where the spatial reference system needs to be re-selected when editing an existing sample.
* Fixes the output map reference and map reference system selected for records in the western part of Guernsey so that
  they use the Channel Islands Grid system.
* Fix Channel Island Grid recording of a 100km map reference, e.g. "WV".

## Version 9.12.0
*2025-07-07*

### Action required

* If using Elasticsearch, update your Logstash configuration files to ensure that the new
  `location.output_sref_blurred`, `location.output_sref_system_blurred` and `identification.verifier_comment` fields
  are located correctly in the documents as described in the first point under **Changes** below.
* If using Elasticsearch, add a mapping to the index for the `identification.verifier_comment` so that it is a `text`
  field type.
* If using Elasticsearch, run the following request in Kibana (replace your_index_name with the name of your index):
  ```
  POST your_index_name/_update_by_query?wait_for_completion=false&requests_per_second=500
  {
    "script": {
      "source": "ctx._source.location.output_sref_blurred = ctx._source.location.output_sref",
      "lang": "painless"
    },
    "query": {
      "bool": {
        "must": [
          {
            "term": {
              "metadata.sensitive": false
            }
          },
          {
            "exists": {
              "field": "location.output_sref"
            }
          }
        ]
      }
    }
  }
  ```

### Changes

* Elasticsearch extraction reports now contain additional fields `output_sref_blurred` and `output_sref_system_blurred`
  which hold the blurred version of any sensitive record's map reference, even if viewing the full precision version of
  the record. These fields can be used to access a "safe" version of the spatial reference even when viewing a full
  precision record copy.
  If using Logstash to populate Elasticsearch, then please ensure that updates to the template for the fields
  `output_sref_blurred` and `output_sref_system_blurred` in the `mutate` section are applied before upgrading
  (see https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/logstash-config/occurrences-http-indicia.template
  and https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/logstash-config/samples-http-indicia.template).
* Adds the comment given at the time of a verification decision to the fields output for
  indexing in Elasticsearch. See https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/docs/occurrences.md
  for information on the additional mapping required on the `identification.verifier_comment` field.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1873. If using Logstash to populate Elasticsearch, then please
  ensure that updates to the template for the field `identification.verifier_comment` in the `mutate` section is applied before
  upgrading (see https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/logstash-config/occurrences-http-indicia.template).
* The standard download format for Elasticsearch records (easy-download) now blurs the "Output map ref" field for sensitive
  records but includes a "Sensitive output map ref" field which is only populated for sensitive records if the user has
  access to the full-precision copy (e.g. a verifier). See https://github.com/BiologicalRecordsCentre/iRecord/issues/1714.
* The simple download format for Elasticsearch records (mapmate) now blurs the "Gridref" field for sensitive
  records but includes a "Sensitive gridref" field which is only populated for sensitive records if the user has access to
  the full-precision copy (e.g. a verifier). See https://github.com/BiologicalRecordsCentre/iRecord/issues/1714.
* The REST API has been updated to allow a GET for a user's notifications, or PUT to acknowledge a notification.
* The REST API has been updated to allow GET, POST, PUT and DELETE for occurrence_comments.
* The REST API has been updated to allow GET, POST, PUT and DELETE for sample_comments.
* Removes some project specific UKBMS reports.

### Bugfixes

* Warehouse log browser now displays HTML characters correctly.

## Version 9.11.0
*2025-05-28*

This version includes updates to packages managed by Composer which require PHP version 8.2+. To
update the Composer packages run the following command from the warehouse's root folder after
installing the files:

```bash
$ composer update
```

### Bugfixes

* Fix to editing of terms and other warehouse values which contain HTML special characters so they
  are not double encoded.
* Fix scheduled tasks problems that occurred on a brand new install.


### Changes

* Add new field `samples.forced_spatial_indexer_location_ids` which can be used to allow verifiers
  to override the higher geography location assiged to a record (e.g. a vice county). See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/827.
* PostgreSQL standard filters against taxa no longer expect the taxa to exist on the master taxon
  list. This allows filtering against taxa on other lists such as EBMS species.
* Update PHPSpreadsheet library to version 3.9.
* String data values are cleaned on saving to the database.
* Updating the title of a group (activity) now automatically propogates to the associated reporting
  cache data.
* Changed the position of the website control on the survey edit form to make it more prominent.
* Docker updated to PostgreSQL 17 and PHP 8.3.

## Version 9.10.0
*2025-03-19*

### Bugfixes

* Newly installed warehouses now fixed on PHP 8.3+ when running scheduled tasks.
* Validation of custom attribute data entry form on the warehouse UI improved if a caption over 50
  characters long is attempted.
* Bugfix on PostgreSQL standard report parameters with preprocessed parameter values; if a value
  supplied that was not found by the preprocessing query it resulted in an error.
* Taxonomic hierarchy (taxon paths) is now calculated for taxa which are not on the master
  checklist, e.g. UKSI.

### Changes

* REST API changes to GET groups - can request "view=pending" to fetch groups the user is pending
  membership of. See https://github.com/NERC-CEH/irecord-app/issues/290.
* REST API changes to GET groups - supply a parameter "page=path" with the path of a group page
  (such as a recording form) to limit the response to groups which have that page linked to them.
  This can be used to fetch groups which are enabled for the iRecord App for exanmple. See
  https://github.com/NERC-CEH/irecord-app/issues/290.
* New occurrence attribute option "Auto-handle zero abundance flag" which can be set on abundance
  attributes to automatically flag zero-abundance occurrence server-side when records are set with
  this attribute's value set to 0, none, absent or similar. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1693.
* Adds a "commenting" URL parameter to links generated for replying to comments via a record
  details page, allowing the page to show a message if the user follows the link when not already
  logged in. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1776.



## Version 9.9.0
*2025-02-25*

### Bugfixes

* Fix general_errors. being incorrectly prepended to error messages.
* Saving a custom attribute with incorrectly formatted content in the description or caption
  internationalisation boxes now shows a validation message against the correct control.
* Bugfixes to the website edit form relating to the Staging URLs input being empty but showing
  incorrectly escaped content, preventing any future update.
* Bugfixes to the import of local custom verification rules. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1787.
* Fix a bug that prevented accounts from deleting properly.

### Changes

* Improved the way that scheduled tasks decide which order to run, allowing modules to declare
  a weight to sort them correctly in the list to run.
* Reply links now included in individual messages in notification emails and they go to the record
  details page. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1776 and
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1243.
* Add a new Record Cleaner API module for integration with the new API - see
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1796.
* Metadata stored for an import now tracks if the import was done in training mode.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1294.
* Add a column indicating image classifier agreement to the occurrence cache data tables.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1786.
* Add support for image classifier metadata output to the information sent to Elasticsearch. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1786.
* Clearer error message returned if attempting to update an email associated with an account but
  the email is already associated with another account.
* Add a `Sensitive` field to the Easy Download and MapMate CSV templates for Elasticsearch
  downloads. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1714.
* The sync with BTO BirdTrack records can now be configured to not overwrite the record status of
  previously imported records when receiving an updated record. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1714.
* Ability to rescan previous failed iNat record imports. E.g. if a taxon mapping is now available,
  it is possible to rescan the previous failures. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1585 and
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1222.
* It is no longer possible to create a species alert against a full taxonomic checklist without any
  additional filters, preventing the possibility of generating huge numbers of notifications.

## Version 9.8.3
*2025-01-27*

### Bugfixes

* Unhandled errors logged rather than returned to the user when submitting records.
* Query code parameter type checking to improve security.

## Version 9.8.2
*2025-01-07*

### Bugfixes

* Fixed REST API connections which use peer review or data flow sharing mode.

## Version 9.8.1
*2025-01-07*

### Bugfixes

* Fixes an error calling the REST API to access data using peer review or data flow sharing modes.

## Version 9.8.1
*2025-01-07*

### Bugfixes

* Rewritten the mechanism which ensures that REST API autofeed requests don't run the same feed
  twice in parallel (if the previous request has not yet completed) to ensure that timeouts do not
  leave the process locked indefinitely. This fixes issues where the Logstash feed to Elasticsearch
  can get blocked after a query timeout.
* Reduced default autofeed page size from 10000 records to 3000 records, reducing the chances of a
  timeout.

## Version 9.8.0
*2025-01-03*

### Changes

* Adds the verbatim location name given for parent samples to the output sent to Logstash for
  indexing into Elasticsearch.
* On the Warehouse admin Diagnostics & Maintenance page, the automatic repair tool now detects Work
  Queue tasks which have started but never completed and resets them.
* Clarification of the terms used to describe import fields for associated occurrences.
* Improvements to the report which lists a user's previous imports, see
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1764:
  * Default sort is recent reports first.
  * Parameter allowing exclusion of zero row imports.
  * Allow sorting by using the filter row at the top of each column.

### Bugfixes

* Fixes the handling of errors in imported vague dates which use individual date components - see
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1768.
* Fixes to the V2 importer - see https://github.com/BiologicalRecordsCentre/iRecord/issues/1736:
  * Ensures the shown error row is correct in error spreadsheet downloads.
  * Don't attempt to match missing taxon names - they are now handled as normal validation
    failures.
  * Fix potential for shown error count to be incorrect.
* UKSI incremental updates - fixes errors caused when UKSI promote name operation used to move a
  name.

## Version 9.7.3
*2024-12-1*

### Fixes

* The background task for populating summary tables for UKBMS annual summaries have been optimised
  to reduce the chance of out of memory errors when processing lots of changed samples.

## Version 9.7.2
*2024-12-09*

### Fixes

* Editing the external key (TVK) of a taxon now correctly updates the occurrences cache data.

## Version 9.7.1
*2024-12-02*

### Fixes

* Updates PHPSpreadsheet library to version 3.5 which includes bugfixes and security fixes.

## Version 9.7.0
*2024-11-29*

### Changes

* Adds a field `locations.higher_location_ids` which can capture an array of parent locations which
  the location's boundary intersects with. This uses the `spatial_index_builder` module and the
  layers which will be indexed, and the layers they are indexed against, must be configured in the
  modules' configuration file (as demonstrated in the example config file).

### Bugfixes

* Fixes to the `work_queue` processes which ensure that deleted records are processed correctly,
  for example deleted attribute values get correctly removed from cached/Elasticsearch data.
  See https://github.com/BiologicalRecordsCentre/UKBMS-online/issues/296.
* Saving any data entity via the warehouse user interface which had checkbox controls would cause
  metadata to be updated, even if there were no actual changes. This has been fixed.
* Updating existing records based on just `external_key` or other non-ID fields when importing now
  fixed.
* Updating the preferred name of a taxon now correctly updates cached occurrence data
  automatically.

## Version 9.6.0
*2024-11-14*

### Changes

* Adds a field `locations.higher_location_ids` which can capture an array of parent locations which
  the location's boundary intersects with. This uses the `spatial_index_builder` module and the
  layers which will be indexed, and the layers they are indexed against, must be configured in the
  modules' configuration file (as demonstrated in the example config file).

## Version 9.6.0
*2024-10-30*

### Changes

* Adds fields describing the linked location supplied by the recorder when resolving a record that
  intersects 2 or more higher geography boundaries (e.g. Vice Counties). See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1741.

## Version 9.5.0
*2024-10-08*

### Changes

* The scheduled tasks process will now skip any attempts to run if there is a previous attempt
  which is still running that has the same configuration. This prevents multiple processes running
  simultaneously and competing for resources. See https://github.com/Indicia-Team/warehouse/issues/527.

### Bugfixes

* The page for quick replies to record comments that is linked to by verification query emails is
  now fixed - previously there was an error causing a blank page. See
  https://github.com/Indicia-Team/warehouse/issues/526.

## Version 9.4.2
*2024-10-08*

### Changes

* Upgrade phpoffice/phpspreadsheet dependency to 3.3.
* Upgrade firebase/php-jwt dependency to 6.10.

## Version 9.4.0
*2024-09-23*

### Changes

* Adds features to trigger reports that allow them to directly send emails, e.g. for replying to
  partially complete reports of Asian Hornets.
* Adds fields to the `groups` entity (for iRecord Activities) for the following:
  * Controlling if blogs are enabled and, if so, whether any member can post a blog or only admins.
    See https://github.com/BiologicalRecordsCentre/iRecord/issues/1703.
  * Defining if a group is a container for other groups, e.g. a project divided by years. See
    https://github.com/BiologicalRecordsCentre/iRecord/issues/1678.
  * Defining if a group is contained by another group.
* Update the `library/groups/find_group_by_url` report to include information about container and
  contained groups. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1678.
* Updated download field formats to support sensitive record download requirements. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1714 and the columns documentation at
  https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-datagrid.
* Update the `library/groups/group_list` report to include a full text search parameter and also so
  that setting the parameter `userFilterMode=joinable` excludes groups you are already a member of.
* The Elasticsearch special field handler for "sitename" now supports additional options -
  `obscureifsensitive` - shows a warning message instead of the site name if sensitive and
  `showifsensitive` - displays the full site name for sensitive records (only if the user has
  access to full precision sensitive data).
* In Elasticsearch, sensitive or private records have their site names replaced by '!' to
  distinguish them from records where there is no site name given. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1714.

### Bugfixes

* Bugfixes for the new bulk edit tool. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1673.
* Bugfix for the handling of the current common name when AddSynonym operations are processed by
  the UKSI History processor. See https://github.com/Indicia-Team/warehouse/pull/522.

## Version 9.3.0
*2024-08-19*

* Adds `search_code` to parameters of Rest endpoint, `services/rest/taxa/search`
  and includes it in the response.

## Version 9.2.0
*2024-06-17*

* Adds support for plugins for the v2 importer - see
  https://indicia-docs.readthedocs.io/en/latest/developing/warehouse/plugins.html#import-plugins-hook.
* Adds an optional custom import plugin that adds an import field for importing into sample
  attribute values where the system function is `linked_location_id`, but using a code for the
  location in the `locations` table rather than the database primary key (id). This can be used
  to specify the location using a Vice County code or similar.
* Reports and cache table definitions to support the new Group Discovery prebuilt form on the
  client-side. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1681.
* Also for Group Discovery, the `index_groups_locations` table now allows each location type to be
  selected separately.
* Web-services updated to support the new Bulk Editor functionality on the client. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1673 and
  https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-bulkeditor.
* Fixes use of sample attributes of type 'linked_location_id' in order to specify which location
  to index against a sample (https://github.com/Indicia-Team/warehouse/issues/518).
* The spatial indexer now uses a location selected by the recorder in a sample attribute of type
  `linked_location_id` to resolve records which overlap a boundary to the correct location.
* Removed `hierarchical_location_types` config option from the spatial_index_builder module as not
  being used and made the code more complex. It is possible to list all hierarchical layers
  directly in the `location_types` option to achieve the same outcome.
* Fix Irish Grid ref SRID from 29901 to 29903.
* Some PHP 8.2 compatibility fixes and other minor bugfixes.

## Version 9.1.0
*2024-03-29*

* Groups (e.g. activities or projects) can be indexed against locations, making it easier to
  write reports for discovering groups on a location basis. Now allows an optional
  `group_location_types` setting to be added to the Spatial Index Builder module's config to
  control exactly which group types to index - for example it may be appropriate to only index
  against countries and counties.
* Adds an `indexed_location_ids` field to the `/services/rest/groups` end-point so that REST API
  clients can discover the list of locations indexed against a group. This field is filterable
  to allow discovering the groups whose boundaries intersect a location.
* The `/services/rest/groups` end-point is now sorted by group title.

## Version 9.0.0
*2024-03-26*

See the [Version 9 upgrade notes](UPGRADE-v9.md) for notes on the upgrade process.

* Setting `samples.privacy_precision` to 0 is now treated as a special value which causes the
  sample to be hidden in default report behaviour. See https://github.com/BiologicalRecordsCentre/ABLE/issues/468.
  There is a new field in the cache tables, `hide_sample_as_private`, to support this.
* New features in the REST API for authorisation:
  * New user interface in the warehouse under Admin -> REST API Clients, which allows new clients
    for the REST API to be added without having to edit the config files. Clients are stored in the
    database and define the keys required for JWT authentication. Clients contain one or more
    available connections for that connection which define the set of records and actions which are
    authorised for use by that connection. Using the config files to define clients is still
    supported but is deprecated so may be dropped in a future version.
  * Configuration for a project or connection in the REST API list of clients can specify a filter
    ID in order to define the accessible records. This applies to connections configured in the
    warehouse UI and stored in the database as well as projects configured in the config files.
  * Ability to use a JWT token to authenticate as a client register in the warehouse UI.
* Some fixes to the HTTP status response from the REST API, for example returning 401 Forbidden
  rather than 404 Unauthorized when the user account is authorised but access to the requested
  resource or operation is denied.
* Add a Record Status control to the Edit Sample form on the warehouse.
* Adds Elastic Stack containers to the Docker development system. Security
  features have been enabled by way of a demonstration though these are not
  needed in a dev environment.
    - Elasticsearch indexes are configured for samples and occurrences.
    - Logstash pipelines are configured to populate the indexes.
    - Kibana can be used to explore the indexes.

## Version 8.26.0
*2024-02-19*

* Adds REST API support for groups (recording groups, sometimes called activities or projects).
  Includes the ability to retrieve the linked pages (reports and recording forms) as well as the
  list of recording sites for groups. More information at /index.php/services/rest on the warehouse
  installation.

## Version 8.25.0
*2024-02-15*

* Adds a staging URLS option to website configuration, allowing additional websites used for
  development and testing to connect to the REST API.
* Fixes a bug causing the index_groups_locations table to be populated with a full list of
  locations that intersect a group's area of interest rather than just indexed ones.

## Version 8.24.0
*2024-01-11*

* Slight improvement to the algorithm for sorting taxa in taxon search results.
  Taxa between the ranks of species aggregate, species and species hybrid are
  prioritised. See application/config/indicia_dist.php for an explanation of
  2 new configuration values that can be specified to define the range of taxon
  ranks that are given precedence, `preferred_taxon_rank_from` and
  `preferred_taxon_rank_to`.
* Updates to standard reporting parameters for occurrences to support new features in the client
  filter builder tool. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1493.
* The quality standard reporting parameter now allows multi-select, to support enhancements to the
  user interface of the report filter builder.
* New database field for tagging which licences are broadly open vs closed.
* Add summary occurrences module to the unit testing done on Travis CI.
* REST API permissions updated to meet needs of iform_layout_builder module. When using JwtUser
  authentication, survey and associated attribute data can be overwritten by a user other than
  the original creator if the user has editor permissions to the relevant website.
* Bug fix in the dates returned by the determinations and comments reports for verification. Fixes
  determination dates < 2018.
* Bugfixes to handling of non-standard UTF8 characters in the warehouse UI forms.
* Bugfixes for PHP 8.2.

## Version 8.23.0
*2023-09-20*

* Fixes for PHP 8.1 & 8.2 (8.2 requires further testing).
* Fix some issues in the sort order of taxa returned when searching. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1511.
* Enhanced support for dates in standard parameter filters. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1491.
* Fixes issue with determiner name not being stored against a record after a redetermination. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/899.
* Tables added for linkage between recording schemes and their taxonomic coverage. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/876.
* Provides a standard PostgreSQL function for tidying taxonomic data after a UKSI update -
  `f_fixup_uksi_links`.
* PostgreSQL function for cloning surveys now includes location attribute links - `f_clone_survey`.
* Database triggers added to ensure consistency in the training flag between samples and
  occurrences.
* Improves logging of errors in the REST API.
* Support for an Elasticsearch special field type #template# that uses an HTML template with token
  replacements to generate output.
* Fix the #associations# Elasticsearch special field type so that associations data can be
  downloaded.
* Adds a new table `rest_api_sync_taxon_mappings` which can be populated to allow unknown taxon
  names in remote systems to be mapped to local taxa when synchronising data. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1222.
* UTM 30N (ED50) grid system renamed to Channel Islands Grid (UTM ED50) for clarity.
* Modify reports used for verification comments to clarify difference between determinations and
  comments.

## Version 8.22.0
*2023-08-03*

* Adds a special field handler for Elasticsearch data that supports a coalesce function, returning
  the value of the first field that has a non-empty value, selected from a list of provided fields.

## Version 8.21.0
*2023-06-28*

* Allow termlists_term_attribute_value submissions from websites.

## Version 8.20.0
*2023-06-16*

* New image organiser warehouse module for restructuring the upload folder into sub-folders based
  on timestamp.
* Queued images added via the REST API are now automatically placed in the new sub-folder image
  structure.

## Version 8.19.0
*2023-05-04*

* New process checker warehouse module to ensure that records missing spatial indexing get
  picked up.

## Version 8.18.0
*2023-04-27*

* New module for automatic verification rule checks for phenology of records by biogeographic
  region.
* Fixes action columns for the occurrences list page.
* Improves captions provided for some columns available for import using the v2 importer.
* Fixes v2 importer when importing a licence for a record - see
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1445/.
* Fixes for PHP 8.2 (experimental).

## Version 8.17.0
*2023-03-30*

* Adds organism_key to the cache_taxa_taxon_lists and cache_taxon_searchterms tables to provide
  a more reliable option for filtering species lists when taxonomy is dynamic. See
  https://github.com/BiologicalRecordsCentre/NPMS/issues/268.

## Version 8.16.0
*2023-03-17*

* Changed menu item "Admin -> Website agreements" to "Admin -> Website data sharing agreements".
* Changed "Agreements" tab title to "Data sharing" for the website details page.
* New database tables and APIs added to support custom verification rules (aka local rules). These
  allow verifiers to define their own verification check rules, e.g. to flag records outside a
  known grid square, or outside a particular time of year.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1403.
* Some fixes relating to PHP 8.1.
* Notifications are no longer generated for training records.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/108.
* A description is no longer required when editing a term in a termlist using the warehouse UI.
* API support added allowing a set of records to be moved between a publically shared website and a
  private website and vice versa, effectively allowing control of their publication status.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1396.
* Updated categories for notifications, so that queries and redeterminations can be properly
  tracked. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1362.
* The verification API now supports applying a verification decision to all records within a parent
  sample of the same taxon in one step. See https://github.com/BiologicalRecordsCentre/iRecord/issues/1274.

## Version 8.15.0
*2023-01-24*

* Adds import_guid field to list of fields extracted to Elasticsearch for an occurrrence document.

## Version 8.14.0
*2023-01-20*

* Permissions changes relating to the ability to use the importer to import records into a
  different website to the one authorised. The other website must provide editing rights
  via a sharing agreement. Relates to https://github.com/BiologicalRecordsCentre/iRecord/issues/1396.
* Add new endpoints to the data_utils service for bulk redetermination using the verification tools.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/674.
* Improves search performance for taxon groups in the filter builder tool.

## Version 8.13.0
*2023-01-13*

* Improved support for PHP 8.1, though it is not yet fully tested.
* The way that taxon names are written in notifications has been improved. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/1395.
* When a taxon is deleted, if there are existing occurrences then the user is able to nominate an
  alternative taxa which the occurrences will be mapped to.
* When notifications for trigger templates are sent as emails, the notification.email_sent field is
  set to 't'.
* Fixes the incorrect updating of verification metadata in occurrences. See
  https://github.com/Indicia-Team/warehouse/issues/466.
* Support for warehouse configuration of the default user email notification settings for a website.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1247.
* Fixes setting the verifier field in the cache_occurrences_nonfunctional table when importing
  already imported occurrences. See https://github.com/Indicia-Team/warehouse/issues/452.
* Improvements to Importer v2:
  * Skips empty rows
  * Provides better feedback in the UI if a template selected.
* When using website based authentication for Elasticsearch, verification mode only retrieves full
  precision occurrence data for reporting. For data downloads, the data are blurred if the record
  is sensitive.
* New fields for tracking the status of names provided by UKSI, for future improvements in the UKSI
  synchronisation process.
* Significant revision and improvements to the code for updating the UKSI species list data from
  UKSI using the UKSI_History table (as opposed to full synchronisation).
* Fixes a bug where certain characters in cache keys could not be saved to the cache due to invalid
  file names being generated.
* Changed detail_occurrences view to allow verifier information to be displayed on species grid.

## Version 8.12.0
*2022-10-25*

* Moves the taxon search form into the core warehouse code.
* Enhances the taxon search form to allow search by taxon name and external key (TVK) as well as
  organism key.

## Version 8.11.0
*2022-10-24*

* Adds `taxon_rank` field to the `cache_taxon_searchterms` table, allowing taxon search controls to
  use the taxon rank name as one of the fields available for separating out taxa.
* The version 2 importer includes taxon rank and other taxon information in the match info returned
  to the client when there are duplicate possible taxon name matches. This allows the client to
  display a user interface for the user to choose the correct taxon to match.

## Version 8.10.0
*2022-10-19*

* Adds new fields for term code and description of terms in lookup lists.
* Implements changes required for PHP 8.1 but not yet fully tested.

## Version 8.9.0
*2022-10-03*

* Adds missing Comments tab to UI for locations.
* Database & API changes to add a reply_to_id so that comments can refer to the comment they reply
  to.

## Version 8.8.0
*2022-09-22*

* Adds a location_comments table.
* Adds a report for retrieving summary data for location comments that contain structured voting or
  review data.
* Updates the occurrences edit form sensitivity control so that all precision levels are supported.

## Version 8.7.0
*2022-09-11*

* Adds reports which support the record, sample and location details pages to allow a single
  attribute value to be output in a block (rather than a data list of all attribute values).

## Version 8.6.0
*2022-09-09"

* Support for new output_formatting option in reports for details pages (occurrences, samples,
  locations) with auto-formatting of hyperlinks for text attribute data.
* Improvements to the REST API's auto-generated documentation.

## Version 8.5.0
*2022-09-09*

* Adds a new standard filter parameter for filtering occurrences by sample ID (smp_id).
* Adds reports required to support a new recording_system_links Drupal module.

## Version 8.4.0
*2022-08-10*

* Change to authorisation so that user ID in authorisation token takes precedence over user ID in
  request parameters.
* Escaping of email addresses fixed when checking for duplicates.
* Fixes purging of import files when scheduled tasks run from Cron with the working directory not
  set to web root.
* Support for configuration templates for the v2 importer.
* Adds website_id to the data captured in the imports table (v2 importer).
## Version 8.3.0
*2022-07-13*

Scheduled tasks called from the command-line can now have the `tasks` parameter set from the
command-line. Previously this parameter was only available when called from a browser URL via a
query parameter.

## Version 8.2.0
*2022-06-29*

* Supports anonymisation of data for deleted user accounts.

## Version 8.1.0
*2022-05-17*

* Adds range of record IDs to the report which lists a user's imports.
  See https://github.com/BiologicalRecordsCentre/iRecord/issues/1294.
* Adds report library/taxa/taxa_list_for_app to aid in extracting lists of taxa to use in apps.
* Removes an unused field groups.published.
* Docker image includes correct URL for GBIF backbone taxonomy.
* Fix to verbose responses in REST API, with support for attributes across multiple records.
## Version 8.0.0
*2022-04-29*

* Support for authenticating on the REST API using anonymous JWT tokens for posting anonymous
  records.

## Version 7.2.0
*2022-04-28*

* ORM extension plugins can now declare table entities that are accessed directly using the
  `table_without_views` option in the plugin metadata.
* Original import code fixes an exception that occurred when importing empty CSV lines.
* Model fields used in the mappings page of the new importer now have human-friendly captions.
* The new importer now purges old files uploaded into the /import folder.
* Adds a report library/classification_events/classification_event_results to summarise the results
  of image classification.
* Project specific reports removed.

## Version 7.1.0
*2022-03-10*

* Add an alternative user_id parameter to the user_users_websites_list report, as user_id fires
  some code automatically that we don't always want fired.
## Version 7.0.0
*2021-03-08*

* The data model includes tables that allow capturing of information on the results of using
  external image classification services to provide suggested identifications for photographed
  occurrences.
* Adds support for a new Excel or CSV spreadsheet upload facility. Differs from the previous one
  because records are imported into a temporary table first and can therefore be processed in bulk
  there before import into the main database, bringing several advantages.
* The new import tool tracks import metadata in a new database table.
* The new and old import tools now use PhpSpreadsheet for CSV file parsing, for more reliable UTF-8
  handling.
* Cached taxon data now includes information on the verification rule types that apply to records
  of this taxon. Cached occurrence data in PostgreSQL and Elasticsearch now includes information
  on exactly which types of rules have been applied. See https://github.com/Indicia-Team/warehouse/issues/422.
* Support for filters that limit records to those which have failed a specific type of verification
  rule.
* Filters on pass/fail of verification rules now only brings back records that actually have rules.
* Fixes a missing samples check in the cache builder code.
* Improves escaping of text values displayed in custom attribute fields.

## Version 6.12.0
*2021-12-02*

* Import of sample and media file licences by licence code field supported.
* Improvements and bug-fixes for the json_occurrences API used to sync between systems.
* Occurrence comments list report uses logged in person name for the comment in preference to
  person_name field as latter may contain email.
* Output_sref fields now support up to 100km grid square sizes with imprecision indicator prefix
  (~) where even less precise references are being displayed.


## Version 6.11.0
*2021-11-23*

* Minor feature release to support hierarchical termlist usage in complex_attr_grid control.
* Support for multi-value attribute editing on the warehouse.

## Version 6.10.0
*2021-11-08*

* Minor feature release to add reports required for AJAX loading of species checklists.

## Version 6.9.0
*2021-10-15*

* Configurable precision for latitude and longitude fields extracted from Elasticsearch.

## Version 6.8.0
*2021-10-11*

* Bugfixes.
* Adds a custom synchronisation handler for Odonata data synchronised from BTO Birdtrack, to meet
  the needs of the recording scheme.
* Update taxon associations module tests to new PHPUnit version.

## Version 6.7.0
*2021-09-30*

* Allow Users to be searched using an autocomplete when linking users to a location.

## Version 6.6.0
*2021-09-28*

* Added language_iso column to the taxon_list report. This is needed when using this report
  to drive a species_autocomplete, it does not work without this information.
## Version 6.5.0
*2021-09-25*

* Adds cache_occurrences_functional.private field to reflect site privacy status.
* Updates the extraction to Elasticsearch to include additional privacy metadata.

## Version 6.4.0
*2021-09-24*

* Occurrence Elasticsearch extraction updated to include sample (event) media.

## Version 6.3.0
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

### Deprecation notice

* The previously provided taxon-observations and annotations end-points in the REST API (which were
  based on the defunct NBN Gateway Exchange Format) are now deprecated and may be removed in a
  future version.

## Version 6.2.0
*2021-08-02*

* Support for life stages in period-within-year verification rules
* Support for csv import of survey attributes to a website.
* Additional iNat fields can be mapped to custom attributes.
* Allow alert filters to include survey.
* Import/Export of surveys now includes survey attributes.
* Updates to unit test harness.
* Allow dependencies to be maintained with Composer.

## Version 6.1.0
*2021-07-27*

* Reporting updates to support the sample details page when showing parent/child samples, e.g.
  transect walks.

## Version 6.0.0
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

## Version 5.1.0
*2021-03-22*

* Adds reports to support a new sample_details prebuilt form.

## Version 5.0.0
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

## Version 4.12.0
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

## Version 4.11.0
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
## Version 4.10.0
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

## Version 4.5.0
*2020-09-16*

* Bugfix for saving an occurrence via the warehouse UI.
* Refactoring of way entities are configured in the REST API.
* REST API now supports creation of surveys, sample and occurrence attributes for users with site
  admin role.
* Several bugfixes relating to RESTful API behaviour.
* Support for creating DINTY tetrad references from server-side database code (e.g. in reports).

## Version 4.4.0
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

## Version 4.3.0

* Adds `taxon_id` and `search_code` to cache_taxa_taxon_lists.
* Performance improvements by removing joins that are no longer necessary.
* Adds a performance diagnostics dashboard to the admin menu.
* Fixes validation of float attribute values so that negative numbers with zero
  at end of numbers after decimal point are not rejected.

## Version 4.2.0
*2020-08-03*

* Attributes display additional info for termlists in lookup to help disambiguate similar names.
* Addition of freshwater_flag, terrestrial_flag, non_native_flag to taxonomic data model.
* Support for a new easy-download format in Elasticsearch downloads.
* Summariser module integration with work queue.

## Version 4.1.0
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

## Version 4.0.0
*2020-05-20*

Major version update due to breaking changes in the Elasticsearch REST API:

  * Format for the addColumns parameter in calls to the Elasticsearch REST API
    endpoints (for CSV downloads) now changed to match the format of the client
    [dataGrid] control's columns configuration. Therefore custom ES download
    formats will need reconfiguring on the client.

* PHP minimum version supported now 5.6.

## Version 3.4.0
*2020-05-04*

* Ability to import into the `locations` table whilst referencing the
  location's parent by `id`.
* Ability to import into the `samples` table whilst looking up the
  associated location by `id`.
* If location ID provided when importing a sample, then the sample's
  `entered_sref` and `entered_sref_system` fields are not required in the
  import data as they can be extracted from the location.

## Version 3.3.0
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

## Version 3.2.0
*2020-03-29*

* Report `reports/library/locations/locations_list_from_search_location.xml`
  allows multiple location_type_ids to be selected.

## Version 3.1.0
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

## Version 3.0.0
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

## Version 2.37.0
*2019-11-07*

* Fixes a bug when saving a new survey.
* Fixes import of NBN Record cleaner rules.
* Support for attribute values in Elasticsearch data downloads.
* Minor updates to UKBMS downloads.

## Version 2.36.0
*2019-10-25*

* UI added so that survey datasets will be able to define the taxonomic branches which
  auto-verification will be applied to. https://github.com/BiologicalRecordsCentre/iRecord/issues/486
* Minor wording changes in notification emails.
* Elasticsearch extractions include basic taxonomic info even for taxa who are not on one of the
  officially configured lists.

## Version 2.35.0
*2019-10-03*

* Improved memory consumption when requesting large amounts of data from Data
  Services.
* DwC-A files now include a readme file that explains how to repair the file in
  the event of an error such as a query timeout. See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/477.

## Version 2.34.0
*2019-10-01*

* Identification difficulty flags now always raised for benefit of verifiers,
  even if recorder competent with that species. However, the notification is
  only sent if the recorder has insufficient track record for that species.
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/657).
* Removed inadvertant required flag on person title field in edit form.
* Tweaks to UKBMS summary builder calculation optimisations.

## Version 2.33.0
*2019-09-30*

* Higher geography Elasticsearch download fixed.
* Fix taxon search ordering of results
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/669).
* Fixes relating to Elasticsearch scroll mode (pagination) not applying column
  settings.

## Version 2.32.0
*2019-09-03*

* Support for loading dynamic attributes for multiple occurrences in one go (required for species checklist). See
  https://github.com/BiologicalRecordsCentre/iRecord/issues/637.
* Fixes a bug in the Swift mailer class loader which was being too eager on some setups, causing file not found errors.

## Version 2.31.0
*2019-08-29*

* Refactor of the Summary Builder module to use the work_queue for greater efficiency.

## Version 2.30.0
*2019-08-28*

* REST API Elasticsearch CSV downloads now support flexible download column templates.
* When importing against existing taxa, can now match against "Species list and taxon search code".

## Version 2.29.0
*2019-08-04*

* Taxon search API now allows exclusion of taxa or taxon names via options exclude_taxon_meaning_id,
  exclude_taxa_taxon_list_id and exclude_preferred_taxa_taxon_list_id.
* Taxon search API now supports option commonNames=defaults, meaning that non-default common names will be excluded.

## Version 2.28.0
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

## Version 2.27.0
*2019-05-29*

* Elasticsearch extraction reports include map squares data and verification decision source.
* Correct CC licence codes (e.g. CC-BY-AT becomes CC BY-AT).

## Version 2.26.0
*2019-05-13*

* Adds sensitivity precision control to occurrence edit form.
* Data services views for custom attributes include the unit field in the response.
* Spatial services buffer function allows the projection code and number of segments to be passed as parameters.

## Version 2.25.0
*2019-05-03*

* Fixes re-use of previous location related sample data from a site when adding a new sample so that more than one
  value can be copied over.
* Fixes an error when auto_log_determinations is off and a record is redetermined.

## Version 2.22.0
*2019-04-22*

* Updates views for taxon designation data to support new tools for editing taxon designations.

## Version 2.21.0
*2019-04-17*

* Updates the fields available when doing CSV download from Elasticsearch.
  (https://github.com/BiologicalRecordsCentre/iRecord/issues/549)
* New report required for showing recorder email addresses to verifiers using
  Elasticsearch (https://github.com/BiologicalRecordsCentre/iRecord/issues/552).
* A report providing a hierarchical view of a termlist, used for editing trait
  data in the Pantheon system.

## Version 2.20.0
*2019-04-15*

* Data services submission format now allows fkField to override the name of the key linked to a foreign key when
  describing entity relationships in a data submission. Further info at
  https://indicia-docs.readthedocs.io/en/latest/developing/web-services/submission-format.html?highlight=fkfield#super-and-sub-models
* iNaturalist sync method in the `rest_api_sync` module now skips unlicenced photos.
* New report, `reports/library/locations/location_boundary_projected.xml`, provides a simple list of location boundaries
  in a given projection, ideal for use on Leaflet maps.
* New report, `reports/library/taxa/taxon_list.xml`, provides a simple list of taxon names and associated data.

## Version 2.19.0
*2019-04-08*

* Adds support for categorisation of scratchpad lists via new scratchpad_type_id field
  and associated termlist.

## Version 2.18.0
*2019-04-04*

* Request_logging module can now capture additional types of events to track performance
  of save events, imports, verification and taxon searches. See the provided example
  config file under modules/request_logging/config.

## Version 2.17.0
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

## Version 2.16.0
*2019-03-20*

* Changes required to allow tracked correspondance to appear on client where appropriate.
* ES searches which contain {} are no longer broken by converting to [].

## Version 2.15.0
*2019-03-19*

* email.notification_subject and email.notification_intro can both be
  overridden in the application/config/email.php file.
* Workflow mapping reports make verified records at top of z order.
* Variant on workflow records explore report that outputs full precision grid
  ref for download.
* Fix problem in Swift email component for PHP 7 which caused PHP errors.
* Record data for verification uses recorder email rather than inputter email
  where available, so record queries go to the correct location.

## Version 2.14.0

* Spatial index builder supports automatic inclusion of location parents in the index,
  improving performance in the background tasks where layers are hierarchical since only
  the bottom layer needs to be scanned.
* Autofeed reports in the REST API support tracking updates by a date field where the
  cache_occurrences_functional.tracking field is not available.

## Version 2.13.0
*2019-03-09*

* Filters list report loaded onto report pages - improved performance.
* New list_verify web service for verifying against a list of IDs.

## Version 2.12.0
*2019-03-07*

* Fixes importing of constituent date fields into occurrences (#318).
* Installation process fixed in some environments (#317).
* Elasticsearch proxy in REST API and scrolling support for large downloads.

## Version 2.11.0
*2019-02-26*

* CSV files generated for download using the REST API and the Elasticsearch scroll API
  are now zipped.

## Version 2.10.0
*2019-02-22*

* Adds support for importing locations using TM65 Irish Grid projection.

## Version 2.9.0
*2019-02-22*

* Adds a download folder to warehouse for temporary generated download files.
* REST API Elasticsearch proxy supports the Scroll API for generation of large downnload files in chunks.
* REST API Elasticsearch proxy supports formatting output as CSV.
* Fix REST API JSON output so that zeros are not excluded.
* Improvements to Elasticsearch document format.
* Updates to reports for Splash.

## Version 2.8.0
*2019-02-15*

* Elasticsearch output reports now include custom attributes data.
* Fixes a syntax error in spatial indexing SQL statements.

## Version 2.7.0
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

## Version 2.6.0
*2019-02-07*

* New indexed_location_type_list standard parameter for reports. Allows
  filtering to any record which is indexed against a site of the given type(s).
* Uploading locations from SHP file now generates work queue entries correctly
  for updates as well as inserts.
* Spatial indexer updates the cache table updated_on fields when changing the
  location_ids field in the cache. This makes it easier to pass changes through
  to feeds such as Elasticsearch.

## Version 2.5.0
*2019-02-05*

### Database schema changes

* Terms.term field is now unlimited length.

### Other changes

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

## Version 2.4.0
*2019-01-21*

* Support for proxy requests to an Elasticsearch cluster, with authentication &
  authorisation support in the RESTful API. See
  https://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/elasticsearch.html and
  https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/README.md.


## Version 2.3.0
*2019-01-09*

### Database schema changes

* Add the following cache fields:
  * cache_occurrences_functional.verification_checks_enabled
  * cache_occurrences_functional.parent_sample_id
  * cache_samples_functional.parent_sample_id
* Update many reports to avoid need to join to websites table since
  verification_checks_enabled now in cache.
* Cache table location_ids field now stores an empty array when the associated
  sample is not linked to any indexed locations rather than null, allowing
  records not yet indexed to be identifiable.

## Version 2.2.0
*2018-12-19*

* Enable use of a scratchpad list of species as a standard filter parameter.

## Version 2.1.0
*2018-12-18*

* Enable import of occurrences where the taxon is identified using a known
  taxa_taxon_list_id.

## Version 2.0.0
*2018-12-14*

Please see [upgrading to version 2.0.0](UPGRADE-v2.md).

### Warehouse user interface changes

* Warehouse client helper and media code libraries updated to use jQuery 3.2.1
  and jQuery UI 1.12.
* Overhaul the warehouse UI with a new Bootstrap 3 based theme and more logical menu
  structure.
* Warehouse home page now has additional help for getting started and diagnosing
  problems.

### Back-end changes

* Support for PostgreSQL version 10.
* Support for PHP 7.2.
* Support for prioritised load aware background task scheduling via a work queue module.

### Database schema changes

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

### Report updates

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
