<?php

$lang = [];
$lang['title'] = 'Indicia RESTful API';
$lang['introduction'] = 'Provides RESTful access to data in the Indicia warehouse database.';
$lang['authenticationTitle'] = 'Authentication';
$lang['authIntroduction'] = <<<HTML
For information on authentication, see the
<a href="http://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/authentication.html">
authentication documentation.</a> The available authentication options are described in the table below.
HTML;
$lang['resourcesTitle'] = 'Resources';
$lang['authMethods'] = 'Allowed authentication methods';
$lang['oauth2User'] = 'oAuth2 as warehouse user';
$lang['oauth2UserHelp'] = 'Use oAuth2 password flow to authenticate as a warehouse user';
$lang['jwtUser'] = 'JWT as warehouse user';
$lang['jwtUserHelp'] = 'Use JWT access token to authenticate as a warehouse user';
$lang['hmacClient'] = 'HMAC as client system';
$lang['hmacClientHelp'] = 'Use HMAC to authenticate as a configured client system.';
$lang['hmacClientHelpHeader'] = 'Set the authorisation header to <em>USER:[client system ID]:HMAC:[hmac]</em>';
$lang['hmacWebsite'] = 'HMAC as website';
$lang['hmacWebsiteHelp'] = 'Use HMAC to authenticate as a website registered on the warehouse.';
$lang['hmacWebsiteHelpHeader'] = 'Set the authorisation header to <em>WEBSITE_ID:[website ID]:HMAC:[hmac]</em>';
$lang['directUser'] = 'Direct authentication as warehouse user';
$lang['directUserHelp'] = 'Directly pass the username and password of a warehouse user account.';
$lang['directClientHelpHeader'] = 'Set the authorisation header to <em>USER_ID:[user ID]:WEBSITE_ID:[website id]:SECRET:[user warehouse password]</em>';
$lang['directClientHelpUrl'] = 'Add the following to the URL: <em>?user_id=[user ID]&website_id=[website ID]&secret=[user warehouse password]</em>';
$lang['directClient'] = 'Direct authentication as client system';
$lang['directClientHelp'] = 'Directly pass the ID and secret of a configured client system.';
$lang['directClientHelpHeader'] = 'Set the authorisation header to <em>USER:[client system ID]:SECRET:[secret]</em>';
$lang['directClientHelpUrl'] = 'Add the following to the URL: <em>?user=[client system ID]&secret=[secret]</em>';
$lang['directWebsite'] = 'Direct authentication as website';
$lang['directWebsiteHelp'] = 'Directly pass the ID and password of a website registered on the warehouse.';
$lang['directWebsiteHelpHeader'] = 'Set the authorisation header to <em>WEBSITE_ID:[website ID]:SECRET:[password]</em>';
$lang['directWebsiteHelpUrl'] = 'Add the following to the URL: <em>?website_id=[website ID]&secret=[password]</em>';
$lang['jwtUser'] = 'Use a Java Web Token (JWT) to authenticate as a user.';
$lang['jwtUserHelp'] = 'To use JWT to authenticate, you need to:<ul>' .
      '<li>Generate a public/private key pair and store the private key in the Warehouse website settings.</li>' .
      '<li>Provide a JWT token signed with the public key which provides the following claims:<ul>' .
      '  <li>iss - the website URL</li>' .
      '  <li>http://indicia.org.uk/user:id</li> set to the warehouse ID of the user issuing the request.</li>' .
      '</ul></ul>';
$lang['jwtUserHelpHeader'] = 'Set the authorisation header to "Bearer <JWT token>"';
$lang['genericHelpHeader'] = 'Specify an authorisation header with a list of token name/value pairs, using colons as a ' .
      'separator, for example <em>TOKEN1:value1:TOKEN2:value2</em>.';
$lang['genericHelpUrl'] = 'Add the tokens to the URL as parameters, using lowercase token names';
$lang['authMethodsHelpHeader'] = 'Provide the authentication tokens using one of the following methods:';
$lang['allowAuthTokensInUrl'] = 'Tokens required for authorisation can be passed in the URL as query parameters or in ' .
        'the Authorization header of the request.';
$lang['dontAllowAuthTokensInUrl'] = 'Tokens required for authorisation must be passed in the Authorization header of ' .
        'the request.';
$lang['onlyAllowHttps'] = 'This authentication method requires you to access the web service via https';
$lang['resourceOptionInfo'] = 'The %s resource: {{ list }}';
$lang['resourceOptionInfo-entities'] = 'Access to data entities is limited to: {{ list }}';
$lang['resourceOptionInfo-elasticsearch'] = 'Elasticsearch is enabled via end-points mapped to Elasticsearch aliases.';
$lang['resourceOptionInfo-reports-featured-true'] = 'is limited to reports which have been vetted and flagged as featured';
$lang['resourceOptionInfo-reports-featured-false'] = 'is not limited to reports which have been vetted and flagged as featured';
$lang['resourceOptionInfo-reports-summary-true'] = 'is limited to reports which show summary data';
$lang['resourceOptionInfo-reports-summary-false'] = 'is not limited to reports which show summary data';
$lang['resourceOptionInfo-reports-cached-true'] = 'returns cached data which may be slightly out of date';
$lang['resourceOptionInfo-reports-cached-false'] = 'returns live, uncached data';
$lang['resourceOptionInfo-reports-limit_to_own_data-true'] = 'is limited to data entered by you';
$lang['resourceOptionInfo-reports-limit_to_own_data-false'] = 'returns data entered by any user';
$lang['format_param_help'] = 'Request a response in this format, either html or json (default). You can also set the ' .
        'response format using the Accept http header, setting it to text/html or application/json as required.';

// Help text for each end-point/method combination.
$lang['resources'] = [];
$lang['resources']['media-queue'] = [];
$lang['resources']['media-queue']['post'] = <<<TXT
Adds a media file such as a photo to the queue of media files available to attach to a subsequent submission. Several
files can be sent in each request but note that server limits on submission size will limit the number possible. Files
can be named with a single field name or an array field name with '[]' appended. The response will be an array, where
each item provides the `name` of the stored file and the `tempPath` where the file is located. For example:
<pre><code>
POST /index.php/services/rest/media-queue
myfirstfile=IMAGE FILE
mysecondfile=IMAGE FILE

Response:
{
  "myfirstfile": {
    "name": "5f3698a2e587b1.59610000.png",
    "tempPath": "http://localhost/warehouse-test/upload-queue/5f3698a2e587b1.59610000.png"
  },
  "mysecondfile": {
    "name": "5f3698a2e587c1.59610000.png",
    "tempPath": "http://localhost/warehouse-test/upload-queue/5f3698a2e587c1.59610000.png"
  }
}
</pre></code>

Or:
<pre><code>
POST /index.php/services/rest/media-queue
myfile[]=IMAGE FILE
myfile[]=IMAGE FILE

Response:
{
  "file[0]": {
    "name": "5f3698a2e587b1.59610000.png",
    "tempPath": "http://localhost/warehouse-test/upload-queue/5f3698a2e587b1.59610000.png"
  },
  "file[1]": {
    "name": "5f3698a2e587c1.59610000.png",
    "tempPath": "http://localhost/warehouse-test/upload-queue/5f3698a2e587c1.59610000.png"
  }
}
</pre></code>

The client must store the name of each queued entry then include that in the subsequent submission when the record data
are posted. For example:
<pre><code>
POST /index.php/services/rest/samples
{
  "values": {
    "survey_id": 1,
    "entered_sref": "SU1234",
    "entered_sref_system": "OSGB",
    "date": "01\/08\/2020"
  },
  "media": [{
    "values": {
      "queued": "5f3698a2e587b1.59610000.png",
      "caption": "Sample image"
    },
  },
  {
    {
      "values": {
        "queued": "5f3698a2e587c1.59610000.png",
        "caption": "2nd sample image"
      }
    }
  }]
}
</pre></code>

Note that queued items will be stored for at least 1 day and attempts to submit record data referring to queued items
that have expired will result in an error. Therefore if a pending submission is stored on the client for more than one
day the media should be re-posted to /media-queue before sending the submission.
TXT;
$lang['resources']['occurrences'] = [];
$lang['resources']['occurrences/{occurrence ID}']['get'] = <<<TXT
Retrieve the fields for a single occurrence. In addition to the database fields, the response values include the
following: <ul>
  <li>taxa_taxon_list_id - recorded taxon's key</li>
  <li>taxon - recorded taxon name</li>
  <li>preferred_taxon - accepted name for the taxon</li>
  <li>default_common_name - common name for the taxon</li>
  <li>taxon_group - group for the taxon</li>
  <li>taxa_taxon_list_external_key - key for the taxon</li>
</ul>
TXT;
$lang['resources']['samples'] = [];
$lang['resources']['samples']['post'] = 'Create a new sample, associated occurrences and media. Posted values should
match database fields in the samples table (or equivalent table for sub-models).

<pre><code>
POST /index.php/services/rest/samples
{
  "values": {
    "survey_id": 1,
    "entered_sref": "SU1234",
    "entered_sref_system": "OSGB",
    "date": "01\/08\/2020"
  },
  "occurrences": [{
    "values": {
      "taxa_taxon_list_id": 2,
      "occAttr:8": "4 adults",
    },
    "media": [{
      "values": {
        "queued": "5f36a6d2b51472.42086512.jpg",
        "caption": "Occurrence image"
      }
    }]
  }]
}

Response:
{
  "values": {
    "id": "3",
    "created_on": "2020-08-14T17:57:32+02:00",
    "updated_on": "2020-08-14T17:57:32+02:00"
  },
  "href": "http:\/\/localhost\/warehouse-test\/index.php\/services\/rest\/samples\/3",
  "occurrences": [{
    "values": {
      "id": "3",
      "created_on": "2020-08-14T17:57:32+02:00",
      "updated_on": "2020-08-14T17:57:32+02:00"
    },
    "href": "http:\/\/localhost\/warehouse-test\/index.php\/services\/rest\/occurrences\/3",
    "media": [{
      "values": {
        "id": "15",
        "created_on": "2020-08-14T17:57:32+02:00",
        "updated_on": "2020-08-14T17:57:32+02:00"
      },
      "href": "http:\/\/localhost\/warehouse-test\/index.php\/services\/rest\/occurrence_media\/15"
    }]
  }]
}
</code></pre>
';
$lang['resources']['samples/{sample ID}']['get'] = <<<TXT
Read the data for a single sample. If using jwtUser or directUser authentication then the sample
must be created by the authenticated user or 404 Not Found will be returned. Response contains a
values entry with a list of key/value pairs including custom attributes. An additional field called
`date` is added with the formatted date string created from the vague date fields. Example:
<pre><code>
GET /index.php/services/rest/samples/3

Response:
200 OK
{
  "values": {
    "id": "3",
    "survey_id": "1",
    "location_id": null,
    "date_start": "2020-08-01",
    "date_end": "2020-08-01",
    "sample_method_id": null,
    "geom": "POLYGON((-362693.424306773 6638740.7043692,-362720.601545824 6640334.45652272,-361130.912140383 6640361.5584498,-361104.043056924 6638767.79238341,-362693.424306773 6638740.7043692))",
    "parent_id": null,
    "group_id": null,
    "privacy_precision": null,
    "verified_by_id": null,
    "verified_on": "1970-01-01T01:00:00+01:00",
    "licence_id": null,
    "created_on": "2020-08-14T15:23:46+02:00",
    "created_by_id": "1",
    "updated_on": "2020-08-14T15:23:46+02:00",
    "updated_by_id": "1",
    "date_type": "D",
    "entered_sref": "ST1234",
    "entered_sref_system": "OSGB",
    "location_name": null,
    "external_key": null,
    "recorder_names": null,
    "record_status": "C",
    "input_form": null,
    "comment": null,
    "lat": "51.10309961727583",
    "lon": "51.10309961727583",
    "smpAttr:1": "150",
    "date": "01\/08\/2020"
  }
}
</code></pre>
TXT;
$lang['resources']['samples/{sample ID}']['put'] = <<<TXT
Update an existing sample by replacing the provided values.
TXT;
$lang['resources']['samples/{sample ID}']['delete'] = <<<TXT
Delete a single sample.  If using jwtUser or directUser authentication then the sample must be
created by the authenticated user or 404 Not Found will be returned. Example:
<pre><code>
DELETE /index.php/services/rest/samples/3

Response:
204 No Content
</code></pre>
TXT;
$lang['resources']['taxa'] = 'Base resource for taxon interactions. Not currently implemented.';
$lang['resources']['taxa/search'] = <<<TXT
Search resource for taxa. Perform full text searches against the taxonomy information held in the
warehouse.
TXT;
$lang['resources']['reports'] = <<<TXT
Retrieves the contents of the top level of the reports directory on the warehouse. Can retrieve the
output for a subfolder in the directory or a specific report by appending the path to the resource
URL.
TXT;
$lang['resources']['reports/{report_path}-xml'] = 'Access the output for a report specified by the supplied path.';
$lang['resources']['reports/{report_path}-xml/params'] = <<<TXT
Get metadata about the list of parameters available to filter this report by.
TXT;
$lang['resources']['reports/{report_path}-xml/columns'] = 'Get metadata about the list of columns available for this report.';
$lang['resources']['projects'] = <<<TXT
Retrieve a list of projects available to this client system ID. Only available when authenticating
as a client system defined in the REST API's configuration file.
TXT;
$lang['resources']['projects/{project ID}'] = <<<TXT
Retrieve the details of a single project where {project id} is replaced by the project ID as
retreived from an earlier request to /projects.
TXT;
$lang['resources']['taxon-observations'] = <<<TXT
Retrieve a list of taxon-observations available to this client ID for a project indicated by a
supplied proj_id parameter.
TXT;
$lang['resources']['taxon-observations/{taxon-observation ID}'] = <<<TXT
Retrieve the details of a single taxon-observation where {taxon-observation ID} is replaced by the
observation ID. A proj_id parameter must be provided and the observation should be available within
that project's records.
TXT;
$lang['resources']['annotations'] = 'Retrieve a list of annotations available to this client ID.';
$lang['resources']['annotations/{annotation ID}'] = <<<TXT
Retrieve the details of a single annotation where {annotation ID} is replaced by the observation
ID.
TXT;

// Lang strings for URL parameters for each end-point.
$lang['occurrences'] = [];
$lang['occurrences']['verbose'] = <<<TXT
Add &verbose to the URL to retrieve attribute values as an array with additional information
including the attribute ID, caption, data type, value ID and raw value information as shown in the
following example response (shortened):
<pre><code>
200 OK
{
  "values": {
    "id": "3",
    ...
    "occAttr:1": [{
      "attribute_id": "1",
      "value_id": "4",
      "caption": "Count",
      "data_type": "I",
      "value": "4 - 6",
      "raw_value": "4",
      "upper_value": 6
    }],
    ...
  }
}
</code></pre>
TXT;
$lang['samples'] = [];
$lang['samples']['verbose'] = <<<TXT
Add &verbose to the URL to retrieve attribute values as an array with additional information
including the attribute ID, caption, data type, value ID and raw value information as shown in the
following example response (shortened):
<pre><code>
200 OK
{
  "values": {
    "id": "3",
    ...
    "smpAttr:1": [{
      "attribute_id": "1",
      "value_id": "4",
      "caption": "Altitude",
      "data_type": "I",
      "value": "150",
      "raw_value": "150",
      "upper_value": null
    }],
    ...
  }
}
</code></pre>
TXT;
$lang['taxon-observations'] = [];
$lang['taxon-observations']['proj_id'] = <<<TXT
Required when authenticated using a client system. Identifier for the project that contains the
observations the client is requesting.
TXT;
$lang['taxon-observations']['filter_id'] = <<<TXT
Optional when authenticated as a warehouse user. Must point to the ID of a filter in the filters
table which has defines_permissions set to true and is linked to the authenticated user. When used,
switches the set of records that are accessible from those created by the current user to the set
of records identified by the filter.
TXT;
$lang['taxon-observations']['page'] = <<<TXT
The page of records to retrieve when there are more records available than page_size. The first
page is page 1. Defaults to 1 if not provided.
TXT;
$lang['taxon-observations']['page_size'] = <<<TXT
The maximum number of records to retrieve. Defaults to 100 if not provided.
TXT;
$lang['taxon-observations']['edited_date_from'] = <<<TXT
Restricts the records to those created or edited on or after the date provided. Format yyyy-mm-dd.
TXT;
$lang['taxon-observations']['edited_date_to'] = <<<TXT
Restricts the records to those created or edited on or before the date provided. Format yyyy-mm-dd.
TXT;
$lang['annotations'] = [];
$lang['annotations']['proj_id'] = <<<TXT
Required when authenticated using a client system. Identifier for the project that contains the
observations the client is requesting.
TXT;
$lang['annotations']['filter_id'] = <<<TXT
Optional when authenticated as a warehouse user. Must point to the ID of a filter in the filters
table which has defines_permissions set to true and is linked to the authenticated user. When used,
switches the set of records that are accessible from those created by the current user to the set
of records identified by the filter.
TXT;
$lang['annotations']['page'] = <<<TXT
The page of records to retrieve when there are more records available than page_size. The first
page is page 1. Defaults to 1 if not provided.
TXT;
$lang['annotations']['page_size'] = <<<TXT
The maximum number of records to retrieve. Defaults to 100 if not provided.
TXT;
$lang['annotations']['edited_date_from'] = <<<TXT
Restricts the annotations to those created or edited on or after the date provided. Format
yyyy-mm-dd.
TXT;
$lang['annotations']['edited_date_to'] = <<<TXT
Restricts the annotations to those created or edited on or before the date provided. Format
yyyy-mm-dd.
TXT;
$lang['taxa'] = [];
$lang['taxa']['taxon_list_id'] = 'ID or list o IDs of taxon list to search against.';
$lang['taxa']['searchQuery'] = <<<TXT
Search text which will be used to look up species and taxon names.
TXT;
$lang['taxa']['taxon_group_id'] = 'ID or array of IDs of taxon groups to limit the search to.';
$lang['taxa']['taxon_group'] = <<<TXT
Taxon group name or array of taxon group names to limit the search to, an alternative to using
taxon_group_id.
TXT;
$lang['taxa']['taxon_meaning_id'] = 'ID or array of IDs of taxon meanings to limit the search to.';
$lang['taxa']['taxa_taxon_list_id'] = <<<TXT
ID or array of IDs of taxa taxon list records to limit the search to.
TXT;
$lang['taxa']['preferred_taxa_taxon_list_id'] = <<<TXT
ID or array of IDs of taxa taxon list records to limit the search to, using the preferred name's ID
to filter against, therefore including synonyms and common names in the search.
TXT;
$lang['taxa']['preferred_taxon'] = <<<TXT
Preferred taxon name or array of preferred names to limit the search to (e.g. limit to a list of
species names). Exact matches required.
TXT;
$lang['taxa']['external_key'] = <<<TXT
External key or array of external keys to limit the search to (e.g. limit to a list of TVKs).
TXT;
$lang['taxa']['parent_id'] = <<<TXT
ID of a taxa_taxon_list record limit the search to children of, e.g. a species when searching the
subspecies. May be set to null to force top level species only.
TXT;
$lang['taxa']['language'] = <<<TXT
Languages of names to include in search results. Pass a 3 character iso code for the language, e.g.
"lat" for Latin names or "eng" for English names. Alternatively set this to "common" to filter for
all common names (i.e. non-Latin names).
TXT;
$lang['taxa']['preferred'] = <<<TXT
Set to true to limit to preferred names, false to limit to non-preferred names.
TXT;
$lang['taxa']['commonNames'] = <<<TXT
Set to true to limit to common names, false to exclude common names.
TXT;
$lang['taxa']['synonyms'] = 'Set to true to limit to syonyms, false to exclude synonyms.';
$lang['taxa']['abbreviations'] = <<<TXT
Set to false to disable searching 2+3 character species name abbreviations.
TXT;
$lang['taxa']['marine_flag'] = <<<TXT
Set to true for only marine associated species, false to exclude marine-associated species.
TXT;
$lang['taxa']['searchAuthors'] = 'Set to true to include author strings in the searched text.';
$lang['taxa']['wholeWords'] = <<<TXT
Set to true to only search whole words in the full text index, otherwise searches the start of
words.
TXT;
$lang['taxa']['min_taxon_rank_sort_order'] = <<<TXT
Set to the minimum sort order of the taxon ranks to include in the results.
TXT;
$lang['taxa']['max_taxon_rank_sort_order'] = <<<TXT
Set to the maximum sort order of the taxon ranks to include in the results.
TXT;
$lang['taxa']['limit'] = 'Limit the number of records in the response.';
$lang['taxa']['offset'] = 'Offset from the start of the dataset that the response will start.';
$lang['taxa']['include'] = <<<TXT
Defines which parts of the response structure to include. If the count and paging data are not
required then exclude them for better performance.
TXT;
$lang['reports'] = [];
$lang['reports']['featured_folder_description'] = <<<TXT
Provides a list of well maintained reports which are recommended as a starting point when exploring
the library of reports.
TXT;
$lang['reports']['filter_id'] = <<<TXT
Optional when authenticated as a warehouse user. Must point to the ID of a filter in the filters
table which has defines_permissions set to true and is linked to the authenticated user. When used,
switches the set of records that are accessible from those created by the current user to the set
of records identified by the filter.
TXT;
$lang['reports']['limit'] = 'Limit the number of records in the response.';
$lang['reports']['offset'] = 'Offset from the start of the dataset that the response will start.';
$lang['reports']['sortby'] = <<<TXT
The field to sort by. Must be compatible with the SQL generated for the report.
TXT;
$lang['reports']['sortdir'] = 'Direction of sort, ASC or DESC';
$lang['reports']['columns'] = <<<TXT
Comma separated list of column fieldnames to include in the report output. Default is all
available in the report.
TXT;
$lang['reports']['cached'] = <<<TXT
Set to true to enable server side caching of the report output. Repeated requests with for the same
report and parameters will be fast but data will not be fully up to date.
TXT;
$lang['reports']['{report parameter}'] = <<<TXT
Supply report parameter values for filtering as defined by the report /params resource.
TXT;
$lang['reports/{report_path}.xml/params'] = [];
$lang['reports/{report_path}.xml/columns'] = [];
