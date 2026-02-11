<?php

$lang = [];
$lang['title'] = 'Indicia RESTful API';
$lang['introduction'] = 'Provides RESTful access to data in the Indicia warehouse database.';
$lang['authenticationTitle'] = 'Authentication';
$lang['authIntroduction'] = <<<HTML
For information on authentication, see the
<a href="http://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/authentication.html">
authentication documentation.</a> The available authentication options are described in the table below.<br/>
Where the details below refer to the scope of the request, the possible options are:
<ul>
  <li>userInWebsite - default. Returns the user's records which have been input into this specific website.</li>
  <li>user - returns the user's records input into any website.</li>
  <li>reporting - returns records where the source website has elected to share its data to the authenticated
  website for reporting purposes.</li>
  <li>verification - returns records where the source website has elected to share its data to the authenticated
  website for verification purposes.</li>
  <li>data_flow - returns records where the source website has elected to share its data to the authenticated
  website for data flow purposes, e.g. onward transfer to GBIF or the NBN Atlas.</li>
  <li>moderation - returns records where the source website has elected to share its data to the authenticated
  website for moderation purposes.</li>
  <li>peer_review - returns records where the source website has elected to share its data to the authenticated
  website for peer review purposes.</li>
  <li>editing - returns records where the source website has elected to share its data to the authenticated
  website for editing purposes, e.g. to allow an expert to correct a record during the verification process.</li>
 </ul>
HTML;
$lang['filterTitle'] = 'List filtering';
$lang['filterText'] = <<<HTML
<p>When calling any of the endpoints to GET a list of entities, you can include
parameters in a query string to limit the response. You can filter on any of
the fields in the entity. Only filtering on equality is supported. E.g. to list
all public locations with a location_type_id of 123, use</p>
<pre>services/rest/locations?public=true&location_type_id=123</pre>
<p>(NB. the list of location types is in a term list with title 'Location
types'. Terms are not available from the REST API and need to be looked up in
the warehouse user interface.)</p>
HTML;
$lang['submissionFormatTitle'] = 'Submission format';
$lang['submissionFormatText'] = <<<HTML
<p>In order to POST data to insert, or PUT data to submit, values must be provided in the correct
structure. Fields and their values must be provided inside a `values` property and attribute values
can be included by creating a field called "locAttr:n", "smpAttr:n" or "occAttr:n" for samples,
occurrences and location data respectively, where <em>n</em> is the attribute ID. This is
illustrated in the following simple sample record submission:</p>
<pre><code>
POST /index.php/services/rest/samples
{
  "values": {
    "survey_id": 1,
    "entered_sref": "SU1234",
    "entered_sref_system": "OSGB",
    "date": "01\/08\/2020",
    "smpAttr:4": 14
  }
}
</code></pre>
<p>Nested records (where there is a one to many relationship) are provided by naming the entity
in a property alongside the values array, then listing the child records in an array beneath.
Nested records can contain multiple levels of nesting.</p>
<pre><code>
POST /index.php/services/rest/samples
{
  "values": {
    "survey_id": 1,
    "entered_sref": "SU1234",
    "entered_sref_system": "OSGB",
    "date": "01\/08\/2020"
  },
  "occurrences": [
    {
      "values": {
        "taxa_taxon_list_id": 2,
        "occAttr:8": "4 adults",
      }
    },
    {
      "values": {
        "taxa_taxon_list_id": 2,
        "occAttr:8": "1 juvenile",
      }
    }
  ]
}
</code></pre>
<p>In some cases, a many-to-one relationship can be included in the submission. In these cases, the
nested entity is described using the singular form of the entity name and there is no need to
wrap the child object in an array (as only one is possible). The nested object's primary key is
then saved into the current record as a foreign key.</p>
HTML;
$lang['resourcesTitle'] = 'Resources';
$lang['resourcesIntroduction'] = <<<TXT
Resources available through the REST API are listed below. Reload the page with ?deprecated in the
URL to include deprecated resources in the list.
TXT;
$lang['deprecatedEndpoint'] = 'This endpoint is deprecated and may be removed in a future API version.';
$lang['authMethods'] = 'Allowed authentication methods';
$lang['jwtUser'] = 'JWT as warehouse user';
$lang['jwtUserHelp'] = 'Use JWT access token to authenticate as a warehouse user';
$lang['hmacClient'] = 'HMAC as client system';
$lang['hmacClientHelp'] = <<<HTML
Use HMAC to authenticate as a configured client system. The configuration must be specified in the
<code>\$config['clients']</code> section of the REST API's configuration file on the warehouse.
See <a href="https://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/authentication.html#hmac">
  the Indicia HMAC documentation</a> for more info.
HTML;
$lang['hmacClientHelpHeader'] = 'Set the authorisation header to <em>USER:[client system ID]:HMAC:[hmac]</em>';
$lang['hmacWebsite'] = 'HMAC as website';
$lang['hmacWebsiteHelp'] = <<<HTML
Use HMAC to authenticate as a website registered on the warehouse. The scope of the request defaults to "reporting"
which includes records from all websites which share their records to the authenticated website for public reports.
This can be overridden by setting the URL parameter <em>scope</em>, e.g. <em>scope=verification</em> and optionally
<em>user_id=<warehouse user ID></em> where the scope requires a known user.
See <a href="https://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/authentication.html#hmac">
  the Indicia HMAC documentation</a> for more info.
HTML;
$lang['hmacWebsiteHelpHeader'] = 'Set the authorisation header to <em>WEBSITE_ID:[website ID]:HMAC:[hmac]</em>';
$lang['directUser'] = 'Direct authentication as warehouse user';
$lang['directUserHelp'] = <<<HTML
Directly pass the user ID, website ID and password of a warehouse user account to authenticate. The scope of the
request defaults to <em>userWithinWebsite</em> but can be overridden by including the required scope in the
authentication header.
HTML;
$lang['directUserHelpHeader'] = <<<HTML
Set the authorisation header to <em>USER_ID:[user ID]:WEBSITE_ID:[website id]:SECRET:[user warehouse password]</em>.
Optionally append <em>:SCOPE:[scope name]</em> to override the default scope of the request.
HTML;
$lang['directUserHelpUrl'] = <<<HTML
Add the following to the URL: <em>?user_id=[user ID]&website_id=[website ID]&secret=[user warehouse password]</em> and,
optionally, <em>&scope=[scope name]</em>
HTML;
$lang['directClient'] = 'Direct authentication as client system';
$lang['directClientHelp'] = <<<HTML
Directly pass the ID and secret of a configured client system. The configuration must be specified in the
<code>\$config['clients']</code> section of the REST API's configuration file on the warehouse.
HTML;
$lang['directClientHelpHeader'] = 'Set the authorisation header to <em>USER:[client system ID]:SECRET:[secret]</em>';
$lang['directClientHelpUrl'] = 'Add the following to the URL: <em>?user=[client system ID]&secret=[secret]</em>';
$lang['directWebsite'] = 'Direct authentication as website';
$lang['directWebsiteHelp'] = 'Directly pass the ID and password of a website registered on the warehouse.';
$lang['directWebsiteHelpHeader'] = <<<HTML
Set the authorisation header to <em>WEBSITE_ID:[website ID]:SECRET:[password]</em>. Optionally append
<em>:SCOPE:[scope name]</em> to override the default scope of the request and <em>:USER_ID:[user's warehouse ID]</em>
where the scope requires a user for it's definition.
HTML;
$lang['directWebsiteHelpUrl'] = <<<HTML
Add the following to the URL: <em>?website_id=[website ID]&secret=[password]</em> and, optionally,
<em>&scope=[scope name]</em> to override the default scope of the request, plus <em>&user_id=[user's warehouse ID]</em>
where the scope requires a user for it's definition.
HTML;
$lang['jwtUser'] = 'Use a JSON Web Token (JWT) to authenticate as a user.';
$lang['jwtUserHelp'] = <<<HTML
To use JWT to authenticate, you need to:<ul>
  <li>Generate a public/private key pair and store the public key in the Warehouse website settings.</li>
  <li>Provide a JWT token signed with the private key which provides the following claims:<ul>
    <li>iss - the website URL. This will be used to lookup the website entry in the warehouse's websites table so
      ensure that the iss claim and the website URL match exactly and that only one website with a public token is
      registered on the warehouse for this URL. For development and test environments, the website staging_urls field
      can be used to store alternative URLs that can be used in the iss claim.
    </li>
    <li>http://indicia.org.uk/user:id - set to the warehouse ID of the user issuing the request, or skip this claim if
    the token is issued on behalf of the website rather than a specific user.</li>
    <li>scope - Optional scopes available for requests made using this token. Multiple scopes can be specified
    separated by a space in which case the scope used by a request can be specified in a URL parameter called "scope".
    Each scope defines the list of websites which make their data available to this request as well as the user filter.
    If multiple scopes are claimed by a token, then a request can provide `scope=<scope name>` in the URL parameters to
    select the active scope for a request. Default is "userInWebsite", or "reporting" if the
    `http://indicia.org.uk/user:id` claim is excluded from the JWT token.
    </li>
  </ul>
</ul>
HTML;
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
$lang['resourceOptionInfo-entities-"locations"'] = 'locations';
$lang['resourceOptionInfo-entities-"occurrence_attributes"'] = 'occurrence_attributes';
$lang['resourceOptionInfo-entities-"occurrence_comments"'] = 'occurrence_comments';
$lang['resourceOptionInfo-entities-"occurrence_media"'] = 'occurrence_media';
$lang['resourceOptionInfo-entities-"occurrences"'] = 'occurrences';
$lang['resourceOptionInfo-entities-"sample_attributes"'] = 'sample_attributes';
$lang['resourceOptionInfo-entities-"sample_comments"'] = 'sample_comments';
$lang['resourceOptionInfo-entities-"sample_media"'] = 'sample_media';
$lang['resourceOptionInfo-entities-"samples"'] = 'samples';
$lang['resourceOptionInfo-entities-"surveys"'] = 'surveys';
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
$lang['resources']['annotations'] = <<<TXT
A list of comments and verification decisions attached to taxon-observation resources. Described fully in the
<a href="http://indicia-online-recording-rest-api.readthedocs.io/en/latest/">online recording REST API documentation</a>.
TXT;
$lang['resources']['GET annotations'] = 'Retrieve a list of annotations available to this client ID.';
$lang['resources']['GET annotations/{id}'] = <<<TXT
Retrieve the details of a single annotation where {id} is replaced by the observation
ID.
TXT;
$lang['resources']['custom-verification-rulesets'] = <<<TXT
Endpoints for functionality relating to a user's sets of custom verification rules. Custom verification rulesets are
a feature that allows a verifier to upload their own rule definitions to apply checks to the list of species records
they can verify. For example, they can flag records which are outside an expected region or time of year, or have an
unusually high abundance count.
TXT;
$lang['resources']['POST custom-verification-rulesets/{id}/run-request'] = <<<TXT
<p>POST a filter definition to this endpoint to run the custom verification ruleset {id} against the filtered set of records.</p>
<p>Example:</p>
<pre><code>
POST /index.php/services/rest/custom-verification-rulesets/123/run-request
{
	"query": {
		"bool": {
			"must": [{
				"query_string": {
					"query": "identification.verification_status:C AND identification.verification_substatus:0 AND NOT identification.query:Q"
				}
			}, {
				"term": {
					"metadata.trial": false
				}
			}, {
				"term": {
					"metadata.confidential": false
				}
			}, {
				"term": {
					"metadata.release_status": "R"
				}
			}, {
				"term": {
					"event.year": 2023
				}
			}]
		}
	}
}
</code></pre>
TXT;
$lang['resources']['POST custom-verification-rulesets/clear-flags'] = <<<TXT
<p>POST a filter definition to this endpoint to clear any custom verification rule flags from the filtered list of records. Only affects rule flags created by this user.</p>
<p>Example:</p>
<pre><code>
POST /index.php/services/rest/custom-verification-rulesets/clear-flags
{
	"query": {
		"bool": {
			"must": [{
				"query_string": {
					"query": "identification.verification_status:C AND identification.verification_substatus:0 AND NOT identification.query:Q"
				}
			}, {
				"term": {
					"metadata.trial": false
				}
			}, {
				"term": {
					"metadata.confidential": false
				}
			}, {
				"term": {
					"metadata.release_status": "R"
				}
			}, {
				"term": {
					"event.year": 2023
				}
			}]
		}
	}
}
</code></pre>
TXT;
$lang['resources']['locations'] = 'Provides access to persistent saved sites and other public locations, either created by the user or flagged as public.';
$lang['resources']['GET locations'] = 'Retrieves a list of a user\'s saved sites and other public locations.';
$lang['resources']['GET locations/{id}'] = <<<TXT
Retrieves details of a single location. Users are allowed to access public locations or locations
they created; users with site editor or admin rights to the authenticated website are allowed to
access details of any location belonging to the website.
TXT;
$locationsPostExample = <<<TXT
<p>Example:</p>
<pre><code>
POST {{ url }}
{
"values": {
  "name": "Test location 2",
  "centroid_sref": "SU345678",
  "centroid_sref_system": "OSGB",
  "external_key": "textexternalkey",
}
</code></pre>

<p>If specified, the external_key field must be unique. For this reason, a UUID is preferable, or if the key is only
unique within the system that supplied it, add a suitable prefix to make it unique.</p>
TXT;
$lang['resources']['POST locations'] = '<p>Creates a saved site or other type of location.</p>' . str_replace('{{ url }}', '/index.php/services/rest/locations', $locationsPostExample);
$lang['resources']['PUT locations/{id}'] = <<<HTML
<p>Updates the details of a location identified by {id}. Users are allowed to update locations they
created; users with site editor or admin rights to the authenticated website are allowed to update
details of any location belonging to the website.</p>
<p>Example:</p>
<pre><code>
POST /index.php/services/rest/locations/2
{
"values": {
  "name": "Corrected name"
}
</code></pre>

<p>If specified, the external_key field must be unique. For this reason, a UUID is preferable, or if the key is only
unique within the system that supplied it, add a suitable prefix to make it unique.</p>
HTML;
$lang['resources']['DELETE locations/{id}'] = <<<TXT
Deletes a location identified by {id}. Users are allowed to delete locations they created; users
with site editor or admin rights to the authenticated website are allowed to delete details of any
location belonging to the website.
TXT;

$lang['resources']['GET location-media'] = <<<TXT
Retrieve list of a user's location media. In addition to the database fields, the response values
include the following: <ul>
  <li>media_type - the term describing the type of media, e.g. 'Image:Local'.</li>
</ul>
TXT;
$lang['resources']['GET location-media/{id}'] = <<<TXT
Retrieve details of a location media item. Users are allowed to access details of media they
created; users with site editor or admin rights to the authenticated website are allowed to access
details of any media belonging to the website.
TXT;
$lang['resources']['POST location-media'] = "Create a single location media belonging to the user, for an existing sample.";
$lang['resources']['PUT location-media/{id}'] = <<<TXT
Updates a single location media record either created by the user, or any location media record
belonging to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['DELETE location-media/{id}'] = "Deletes a single location media belonging to the user.";

$lang['resources']['media-queue'] = <<<TXT
Endpoint which allows media files such as record photos to be cached on the server prior to submitting the associated
records. This allows files to be sent to the server during data entry, reducing the time a user has to wait for image
uploads.
TXT;
$lang['resources']['POST media-queue'] = <<<TXT
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
    "name": "18/60/23/5f3698a2e587b1.59610000.png",
    "tempPath": "http://localhost/warehouse-test/upload-queue/5f3698a2e587b1.59610000.png"
  },
  "file[1]": {
    "name": "18/60/23/5f3698a2e587c1.59610000.png",
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
  "media": [
    {
      "values": {
        "queued": "18/60/23/5f3698a2e587b1.59610000.png",
        "caption": "Sample image"
      },
    },
    {
      "values": {
        "queued": "18/60/23/5f3698a2e587c1.59610000.png",
        "caption": "2nd sample image"
      }
    }
  ]
}
</pre></code>

Note that queued items will be stored for at least 1 day and attempts to submit record data referring to queued items
that have expired will result in an error. Therefore if a pending submission is stored on the client for more than one
day the media should be re-posted to /media-queue before sending the submission.
TXT;
$lang['resources']['notifications'] = <<<TXT
  Provides access to a user's list of notifications.
TXT;
$lang['resources']['GET notifications'] = <<<TXT
  Retrieves a user's list of notifications. The default behaviour is to include only unacknowledged notifications, set
  a filter parameter called "acknowledged" to "true" to include acknowledged notifications. Example:
  <pre><code>
  GET /index.php/services/rest/notifications
  </code>
  Response:
  <code>
  [
    {
      "values": {
        "id": "1",
        "source": "Verifications and comments",
        "source_type": "V",
        "data": "{\"username\":\"user_x\",\"occurrence_id\":\"57554\",\"comment\":\"Your record of Fen Raft Spider (&lt;em&gt;Dolomedes plantarius&lt;/em&gt;) at ST9623 on 03\/01\/2024 was examined by an expert.&lt;br/&gt;&lt;em&gt;Accepted as correct&lt;/em&gt;\",\"taxon\":\"Fen Raft Spider (&lt;em&gt;Dolomedes plantarius&lt;/em&gt;)\",\"date\":\"03\/01\/2024\",\"entered_sref\":\"ST9623\",\"auto_generated\":\"f\",\"record_status\":\"V\",\"record_substatus\":\"1\",\"updated_on\":\"2025-02-05 13:31:04.758571\"}",
        "user_id": "1",
        "triggered_on": "2016-07-22T15:00:00+00:00",
        "occurrence_id": "1",
        "source_detail": null,
        "acknowledged": "f",
        "email_sent": "f"
      }
    }
  ]
  </code>
  </pre>
TXT;
$lang['resources']['GET notifications/{id}'] = <<<TXT
  Retrieves a single notification by its ID. The notification must be for the current user. Example:
  <pre><code>
  GET /index.php/services/rest/notifications/1
  </code>
  Response:
  <code>
  {
    "values": {
      "id": "1",
      "source": "Verifications and comments",
      "source_type": "V",
      "data": "{\"username\":\"user_x\",\"occurrence_id\":\"57554\",\"comment\":\"Your record of Fen Raft Spider (&lt;em&gt;Dolomedes plantarius&lt;/em&gt;) at ST9623 on 03\/01\/2024 was examined by an expert.&lt;br/&gt;&lt;em&gt;Accepted as correct&lt;/em&gt;\",\"taxon\":\"Fen Raft Spider (&lt;em&gt;Dolomedes plantarius&lt;/em&gt;)\",\"date\":\"03\/01\/2024\",\"entered_sref\":\"ST9623\",\"auto_generated\":\"f\",\"record_status\":\"V\",\"record_substatus\":\"1\",\"updated_on\":\"2025-02-05 13:31:04.758571\"}",
      "user_id": "1",
      "triggered_on": "2016-07-22T15:00:00+00:00",
      "occurrence_id": "1",
      "source_detail": null,
      "acknowledged": "f",
      "email_sent": "f"
    }
  }
  </code>
  </pre>
  The response can also be HTTP 400 Not Found if the notification does not exist or is not visible to the user (i.e.
  for another user).
TXT;
$lang['resources']['PUT notifications/{id}'] = <<<TXT
  Updates a single notification identified by its ID. The notification must be for the current user and the only field
  that can be updated is the "acknowledged" field, which is set to true to acknowledge the notification.
TXT;
$lang['resources']['occurrence-attributes'] = <<<TXT
  A list of custom attributes defined to capture information about occurrences.
TXT;
$lang['resources']['GET occurrence-attributes'] = <<<TXT
  Retrieves a list of custom attributes defined to capture information about occurrences.
TXT;
$lang['resources']['GET occurrence-attributes/{id}'] = <<<TXT
  Retrieves a single custom attribute defined to capture information about occurrences. Lookup attributes include a
  "terms" element in the response containing an ordered array of terms, excluding any that are allow_data_entry=false.
TXT;
$lang['resources']['POST occurrence-attributes'] = <<<TXT
Creates a custom attribute defined to capture information about occurrences. If the attribute is a lookup
(data_type=L) then provide "terms" as a sibling of the values to auto-generate a termlist. For example:
<pre><code>
POST /index.php/services/rest/occurrence-attributes
{
  "values": {
    "caption": "Stage",
    "data_type": "L",
  },
  "terms": [
    "Egg",
    "Larva",
    "Adult"
  ]
}
</code></pre>

The user must have editor permissions for the authorised client website.
TXT;
$lang['resources']['PUT occurrence-attributes/{id}'] = <<<TXT
Updates a single occurrence custom attribute. Lookups can update the termlist content by passing a "terms" element in
the same way as a POST. The user must have edit rights to the authorised client website.
TXT;
$lang['resources']['DELETE occurrence-attributes/{id}'] = <<<TXT
Deletes a single occurrence custom attribute. User must have editor permissions to the website the
attribute is associated with and the attribute must have no values already stored for it in the
database.
TXT;
$lang['resources']['dna-occurrences'] = <<<TXT
Provides access to DNA data attached to DNA-derived occurrences. When an occurrence is DNA derived, DNA metadata sits
in a separate record alongside the occurrence record, linked by the occurrence_id field, so this resource is solely
for the DNA specific elements of a record.
TXT;
$lang['resources']['GET dna-occurrences'] = <<<TXT
Retrieve a list of DNA occurrence data attached to any occurrences belonging to the user. In typical usage, specify a parameter
called occurrence_id to return DNA metadata a single record. Example:
<code><pre>
GET /index.php/services/rest/dna-occurrencess?occurrence_id=234
</code>
Response:
<code>
200 OK
[
  {
    "values": {
      "id": "1",
      "occurrence_id": "234",
      "associated_sequences": "https://www.ncbi.nlm.nih.gov/nuccore/U34853.1;https://www.ncbi.nlm.nih.gov/nuccore/U53564.2",
      "dna_sequence": "GTGGGTTTGGAGCACCGCCAAGTCCTTAGAGTTTTAAGCGTTTGTGCTCGTAGTTCTCAGGCGAATACTTTGGTGGGGAGAAGTATTTAGATTTAAGGCCAA",
      "target_gene": "CO1",
      "pcr_primer_reference": "https://doi.org/10.1186/1742-9994-10-34",
      "env_medium": "liquid water [ENVO:00002006]",
      "env_broad_scale": "terrestrial biome [ENVO:00000446]",
      "otu_db": "NCBI",
      "otu_seq_comp_appr": "blast version 2.12.0+",
      "otu_class_appr": "standard Linux tools",
      "env_local_scale": "alpine biome",
      "target_subfragment": "V5",
      "pcr_primer_name_forward": "Riaz_12S_V5F",
      "pcr_primer_forward": "TAGAACAGGCTCCTCTAG",
      "pcr_primer_name_reverse": "Riaz_12S_V5R",
      "pcr_primer_reverse": "pcr_primer_reverse",
      "created_on": "2025-10-20T15:38:45+01:00",
      "created_by_id": "1",
      "updated_on": "2025-10-20T15:38:45+01:00",
      "updated_by_id": "1",
      "website_id": "1"
    }
  }
]
</code></pre>
TXT;
$lang['resources']['GET dna-occurrences/{id}'] = <<<TXT
Retrieve details of a single DNA ooccurrence record attached to an occurrence belonging to the user.
TXT;
$lang['resources']['POST dna-occurrences'] = <<<TXT
Create a single DNA occurrence record for an existing occurrence.

Note that is is also possible and valid to post DNA occurrence data as a submodel of an occurrence when posting
occurrence data or occurrence data within a parent sample.

Example:
<pre><code>
POST /index.php/services/rest/dna-occurrences
{
  "values": {
    "occurrence_id": 1,
    "associated_sequences": "https://www.ncbi.nlm.nih.gov/nuccore/U34853.1;https://www.ncbi.nlm.nih.gov/nuccore/U53564.2",
    "dna_sequence": "GTGGGTTTGGAGCACCGCCAAGTCCTTAGAGTTTTAAGCGTTTGTGCTCGTAGTTCTCAGGCGAATACTTTGGTGGGGAGAAGTATTTAGATTTAAGGCCAA",
    "target_gene": "CO1",
    "pcr_primer_reference": "https://doi.org/10.1186/1742-9994-10-34",
  }
}
</code>
Response:
<code>
HTTP 201 Created
{
  "values": {
    "id": "123",
    "created_on": "2025-06-10T10:56:02+00:00",
    "updated_on": "2025-06-10T10:56:02+00:00"
  },
  "href": "http:\/\/warehousetest.test\/index.php\/services\/rest\/dna_occurrences\/123"
}
</code>
</pre>
TXT;
$lang['resources']['PUT dna-occurrences/{id}'] = <<<TXT
Updates a single DNA occurrence record either created by the user, or any DNA occurrence record
belonging to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['DELETE dna-occurrences/{id}'] = <<<TXT
Deletes a single DNA occurrence record belonging to the user. Example:
<code><pre>
DELETE /index.php/services/rest/dna-occurrences/123

Response:
204 No Content
</pre></code>

Alternative responses:
<ul>
  <li>HTTP 404 Not Found response is returned if the DNA occurrence does not exist .</li>
  <li>HTTP 403 Forbidden response is returned if the DNA occurrence does not belong to the user.</li>
</ul>
TXT;

$lang['resources']['occurrence-comments'] = <<<TXT
Provides access to comments attached to occurrences after initial submission.
TXT;
$lang['resources']['GET occurrence-comments'] = <<<TXT
Retrieve list of comments attached to any records belonging to the user. In typical usage, specify a parameter
called occurrence_id to return a list of comments for a single record. Example:
<code><pre>
GET /index.php/services/rest/occurrence-comments?occurrence_id=234
</code>
Response:
<code>
200 OK
[
  {
    "values": {
      "id": "1",
      "occurrence_id": "234",
      "comment": "Occurrence comment for testing",
      "person_name": null,
      "auto_generated": "f",
      "generated_by": null,
      "implies_manual_check_required": "f",
      "query": "f",
      "record_status": null,
      "record_substatus": null,
      "external_key": null,
      "reply_to_id": null,
      "redet_taxa_taxon_list_id": null,
      "created_on": "2016-07-22T16:00:00+00:00",
      "created_by_id": "1",
      "updated_on": "2016-07-22T16:00:00+00:00",
      "updated_by_id": "1",
      "website_id": "1"
    }
  },
  {
    "values": {
      "id": "2",
      "occurrence_id": "234",
      "comment": "A test comment.",
      "person_name": "Foo bar",
      "auto_generated": "f",
      "generated_by": null,
      "implies_manual_check_required": "f",
      "query": "f",
      "record_status": null,
      "record_substatus": null,
      "external_key": null,
      "reply_to_id": null,
      "redet_taxa_taxon_list_id": null,
      "created_on": "2025-06-10T10:35:32+00:00",
      "created_by_id": "1",
      "updated_on": "2025-06-10T10:35:32+00:00",
      "updated_by_id": "1",
      "website_id": "1"
    }
  }
]
</code></pre>
TXT;
$lang['resources']['GET occurrence-comments/{id}'] = <<<TXT
Retrieve details of a single comment attached to an occurrence belonging to the user.
TXT;
$lang['resources']['POST occurrence-comments'] = <<<TXT
Create a single occurrence comment belonging to the user, for an existing occurrence. Example:
<pre><code>
POST /index.php/services/rest/occurrence-comments
{
  "values": {
    "occurrence_id": 1,
    "comment": "A test comment for an occurrence."
  }
}
</code>
Response:
<code>
HTTP 201 Created
{
  "values": {
    "id": "123",
    "created_on": "2025-06-10T10:56:02+00:00",
    "updated_on": "2025-06-10T10:56:02+00:00"
  },
  "href": "http:\/\/warehousetest.test\/index.php\/services\/rest\/occurrence_comments\/123"
}
</code>
</pre>
TXT;
$lang['resources']['PUT occurrence-comments/{id}'] = <<<TXT
Updates a single occurrence comment record either created by the user, or any occurrence comment record
belonging to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['DELETE occurrence-comments/{id}'] = <<<TXT
Deletes a single occurrence comment record belonging to the user. Example:
<code><pre>
DELETE /index.php/services/rest/occurrence-comments/123

Response:
204 No Content
</pre></code>

Alternative responses:
<ul>
  <li>HTTP 404 Not Found response is returned if the comment does not exist .</li>
  <li>HTTP 403 Forbidden response is returned if the comment does not belong to the user.</li>
</ul>
TXT;
$lang['resources']['occurrence-media'] = "Provides access to photos and other media attached to occurrences.";
$lang['resources']['GET occurrence-media'] = <<<TXT
Retrieve list of a user's occurrence media. In addition to the database fields, the response values
include the following: <ul>
  <li>media_type - the term describing the type of media, e.g. 'Image:Local'.</li>
</ul>
TXT;
$lang['resources']['GET occurrence-media/{id}'] = <<<TXT
Retrieve details of an occurrence media item. Users are allowed to access details of media
they created; users with site editor or admin rights to the authenticated website are allowed to
access details of any media belonging to the website.
TXT;
$lang['resources']['POST occurrence-media'] = "Create a single occurrence media item belonging to the user, for an existing occurrence.";
$lang['resources']['PUT occurrence-media/{id}'] = <<<TXT
Updates a single occurrence media record either belonging to the user, or any occurrence media
record belonging to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['DELETE occurrence-media/{id}'] = <<<TXT
Deletes a single occurrence media either belonging to the user, or any occurrence media record
belonging to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['occurrences'] = "Provides access to the list of occurrences linked to the authenticated website.";
$lang['resources']['GET occurrences'] = <<<TXT
Retrieve a list of occurrences owned by the logged in user. In addition to the database fields, the response values
include the following: <ul>
  <li>taxa_taxon_list_id - recorded taxon's key</li>
  <li>taxon - recorded taxon name</li>
  <li>preferred_taxon - accepted name for the taxon</li>
  <li>default_common_name - common name for the taxon</li>
  <li>taxon_group - group for the taxon</li>
  <li>taxa_taxon_list_external_key - key for the taxon</li>
</ul>
TXT;
$lang['resources']['GET occurrences/{id}'] = <<<TXT
Retrieve the fields for a single occurrence. Users are allowed to access details of occurrences
they created; users with site editor or admin rights to the authenticated website are allowed to
access details of any occurrence belonging to the website. In addition to the database fields, the
response values include the following: <ul>
  <li>taxa_taxon_list_id - recorded taxon's key</li>
  <li>taxon - recorded taxon name</li>
  <li>preferred_taxon - accepted name for the taxon</li>
  <li>default_common_name - common name for the taxon</li>
  <li>taxon_group - group for the taxon</li>
  <li>taxa_taxon_list_external_key - key for the taxon</li>
</ul>
TXT;
$lang['resources']['POST occurrences'] = <<<HTML
<p>Creates an occurrence on the system within an existing sample.</p>
<p>A posted occurrence can include a many-to-one relationship to a single classification_event,
which itself can contain nested results, suggestions and links to media. This is illustrated in the
following example:</p>
<pre><code>
POST /index.php/services/rest/occurrences
{
  "values": {
    "taxa_taxon_list_id": 2,
    "machine_involvement": 3
  },
  "media": [
    {
      "values": {
        "queued": "18/60/23/abcdefg.jpg",
        "caption": "Occurrence image"
      }
    }
  ],
  "classification_event": {
    "values": {
      "created_by_id": 123
    },
    "classification_results": [
      {
        "values": {
          "classifier_id": 2,
          "classifier_version": "1.0"
        },
        "classification_suggestions": [
          {
            "values": {
              "taxon_name_given": "A suggested name",
              "taxa_taxon_list_id": 1,
              "probability": 0.9
            }
          },
          {
            "values": {
              "taxon_name_given": "An alternative name",
              "taxa_taxon_list_id": 2,
              "probability": 0.4
            }
          }
        ],
        "metaFields": {
          "mediaPaths": ["abcdefg.jpg"]
        }
      }
    ]
  }
}
</code></pre>

<p>If specified, the external_key field must be unique. For this reason, a UUID is preferable, or if the key is only
unique within the system that supplied it, add a suitable prefix to make it unique.</p>
HTML;
$lang['resources']['POST occurrences/list'] = <<<TXT
Allows posting of a list of occurrences to create multiple in one request. Identical to the POST
occurrences endpoint but the request body should be an array containing the list of occurrences
to create. The response will similarly have an outer array wrapping the response for each sample in
the same order.
TXT;
$lang['resources']['PUT occurrences/{id}'] = <<<TXT
Updates a single occurrence record either created by the user, or any occurrence record belonging
to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['DELETE occurrences/{id}'] = 'Deletes a single occurrence belonging to the user.';
$lang['resources']['GET occurrences/check-newness'] = <<<TXT
Checks if a wildlife record is new to the species, new to a specific grid square, new in a
specific year, or new within a specific group. Useful for displaying "new record" badges to
users after they save a record.

Parameters:
<ul>
  <li><strong>external_key</strong> (required): The accepted taxon ID (taxon.accepted_taxon_id) for
  the species being recorded</li>
  <li><strong>lat</strong> (optional): Latitude of the record location in WGS84 (decimal degrees).
  Must be provided if grid_square_size is specified, otherwise must not be provided.</li>
  <li><strong>lon</strong> (optional): Longitude of the record location in WGS84 (decimal degrees).
  Must be provided if grid_square_size is specified, otherwise must not be provided.</li>
  <li><strong>grid_square_size</strong> (optional): Size of the grid square for location-based newness
  checks. One of: '1km', '2km', or '10km'. Must be provided if lat/lon are specified, otherwise
  must not be provided.</li>
  <li><strong>year</strong> (optional): Year to check for annual newness. If provided, response
  includes is_new_for_year badge.</li>
  <li><strong>group_id</strong> (optional): Group ID to filter by, which corresponds to an activity
  ID or project ID depending on the terminology used on the client website. If provided, response
  includes is_new_for_group badge.</li>
</ul>

Example request:
<pre><code>
GET /index.php/services/rest/occurrences/check-newness?external_key=NBNSYS0000385&lat=51.6243&lon=-3.4123&grid_square_size=1km&year=2024

Response:
200 OK
{
  "is_new_global": true,
  "is_new_for_year": false,
  "is_new_for_grid": true
}
</code></pre>

Response will conditionally include:
<ul>
  <li><strong>is_new_global</strong>: True if the species has not been recorded before (always included)</li>
  <li><strong>is_new_for_year</strong>: True if the species has not been recorded in the specified
  year (only if year parameter provided)</li>
  <li><strong>is_new_for_grid</strong>: True if the species has not been recorded in the specified
  grid square (only if grid_square_size and lat/lon provided)</li>
  <li><strong>is_new_for_group</strong>: True if the species has not been recorded in the specified
  group (only if group_id provided)</li>
  <li><strong>grid_square</strong>: The WKT POINT geometry for the grid square centre (only if
  grid_square_size and lat/lon provided)</li>
</ul>
TXT;
$lang['resources']['reports'] = <<<TXT
Provides access to data generated by predefined report queries and metadata about the reports.
TXT;
$lang['resources']['sample-attributes'] = <<<TXT
A list of custom attributes defined to capture information about samples.
TXT;
$lang['resources']['GET sample-attributes'] = <<<TXT
Retrieves a list of custom attributes defined to capture information about samples.
TXT;
$lang['resources']['GET sample-attributes/{id}'] = <<<TXT
Retrieves a single custom attribute defined to capture information about samples. Lookup attributes include a
"terms" element in the response containing an ordered array of terms, excluding any that are allow_data_entry=false.
TXT;
$lang['resources']['POST sample-attributes'] = <<<TXT
Creates a custom attribute defined to capture information about samples. If the attribute is a lookup
(data_type=L) then provide "terms" as a sibling of the values to auto-generate a termlist. For example:
<pre><code>
POST /index.php/services/rest/sample-attributes
{
  "values": {
    "caption": "Site features",
    "data_type": "L",
  },
  "terms": [
    "Pond",
    "Wildflower patch",
    "Wood pile"
  ]
}
</code></pre>

The user must have editor permissions for the authorised client website.
TXT;
$lang['resources']['PUT sample-attributes/{id}'] = <<<TXT
Updates a single sample custom attribute. Lookups can update the termlist content by passing a "terms" element in
the same way as a POST. User must have editor permissions to the website the survey belongs to.
TXT;
$lang['resources']['DELETE sample-attributes/{id}'] = <<<TXT
Deletes a single sample custom attribute. User must have editor permissions to the website the
attribute is associated with and the attribute must have no values already stored for it in the
database.
TXT;
$lang['resources']['sample-comments'] = <<<TXT
Provides access to comments attached to samples after initial submission.
TXT;
$lang['resources']['GET sample-comments'] = <<<TXT
Retrieve list of comments attached to any records belonging to the user. In typical usage, specify a parameter called
sample_id to retrieve the comments for a single sample. Example:
<code><pre>
GET /index.php/services/rest/sample-comments?sample_id=567
</code>
Response:
<code>
200 OK
[
  {
    "values": {
      "id": "45",
      "sample_id": "567",
      "comment": "A test comment for a sample.",
      "person_name": "Foo bar",
      "query": "f",
      "record_status": null,
      "external_key": null,
      "reply_to_id": null,
      "created_on": "2025-06-10T10:37:47+00:00",
      "created_by_id": "1",
      "updated_on": "2025-06-10T10:37:47+00:00",
      "updated_by_id": "1",
      "website_id": "1"
    }
  }
]
</code></pre>
TXT;
$lang['resources']['GET sample-comments/{id}'] = <<<TXT
Retrieve details of a single comment attached to a records belonging to the user.
TXT;
$lang['resources']['POST sample-comments'] = <<<TXT
Create a single sample comment belonging to the user, for an existing sample. Example:
<pre><code>
POST /index.php/services/rest/sample-comments
{
  "values": {
    "sample_id": 1,
    "comment": "A test comment for a sample."
  }
}
</code>
Response:
<code>
HTTP 201 Created
{
  "values": {
    "id": "123",
    "created_on": "2025-06-10T10:56:02+00:00",
    "updated_on": "2025-06-10T10:56:02+00:00"
  },
  "href": "http:\/\/warehousetest.test\/index.php\/services\/rest\/sample_comments\/123"
}
</code>
</pre>

TXT;
$lang['resources']['PUT sample-comments/{id}'] = <<<TXT
Updates a single sample comment record either created by the user, or any sample comment record
belonging to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['DELETE sample-comments/{id}'] = <<<TXT
Deletes a single sample comment record belonging to the user. Example:
<code><pre>
DELETE /index.php/services/rest/sample-comments/123

Response:
204 No Content
</pre></code>

Alternative responses:
<ul>
  <li>HTTP 404 Not Found response is returned if the comment does not exist .</li>
  <li>HTTP 403 Forbidden response is returned if the comment does not belong to the user.</li>
</ul>
TXT;
$lang['resources']['sample-media'] = <<<TXT
Provides access to photos and other media associated with samples.
TXT;
$lang['resources']['GET sample-media'] = <<<TXT
Retrieve list of a user's sample media. In addition to the database fields, the response values
include the following: <ul>
  <li>media_type - the term describing the type of media, e.g. 'Image:Local'.</li>
</ul>
TXT;
$lang['resources']['GET sample-media/{id}'] = <<<TXT
Retrieve details of a sample media item. Users are allowed to access details of media they
created; users with site editor or admin rights to the authenticated website are allowed to access
details of any media belonging to the website.
TXT;
$lang['resources']['POST sample-media'] = "Create a single sample media item belonging to the user, for an existing sample.";
$lang['resources']['PUT sample-media/{id}'] = <<<TXT
Updates a single sample media record either created by the user, or any sample media record
belonging to the website if the user has site editor or admin access to the website.
TXT;
$lang['resources']['DELETE sample-media/{id}'] = "Deletes a single sample media record belonging to the user.";
$lang['resources']['samples'] = 'A list of the user\'s samples data, each of which can contain any number of occurrences.';
$lang['resources']['GET samples'] = <<<TXT
Retrieve a list of the user's samples data. In addition to the database fields, the response values
include the following: <ul>
  <li>date - formatted date or vague date string.</li>
  <li>survey_title - title of the survey it belongs to.</li>
</ul>
TXT;
$lang['resources']['GET samples/{id}'] = <<<TXT
Read the data for a single sample. Users are allowed to access details of samples they created;
users with site editor or admin rights to the authenticated website are allowed to access details
of any sample belonging to the website. Response contains a values entry with a list of key/value
pairs including custom attributes. In addition to the database fields, the response values
include the following: <ul>
  <li>date - formatted date or vague date string.</li>
  <li>survey_title - title of the survey it belongs to.</li>
</ul>
Example:
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
    "date": "01\/08\/2020",
    "survey_title": "Woodland monitoring"
  }
}
</code></pre>
TXT;
$lang['resources']['POST samples'] = 'Create a new sample, associated occurrences and media. Posted values should
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
        "queued": "18/60/23/5f36a6d2b51472.42086512.jpg",
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

<p>If specified, the external_key field must be unique. For this reason, a UUID is preferable, or if the key is only
unique within the system that supplied it, add a suitable prefix to make it unique.</p>
';
$lang['resources']['POST samples/list'] = <<<TXT
Allows posting of a list of samples to create multiple in one request. Identical to the POST samples endpoint but
the request body should be an array containing the list of samples to create. The response will similarly have an
outer array wrapping the response for each sample in the same order.
TXT;
$lang['resources']['PUT samples/{id}'] = <<<TXT
<p>Updates a single sample record either created by the user, or any occurrence record belonging to
the website if the user has site editor or admin access to the website.</p>

<p>If specified, the external_key field must be unique. For this reason, a UUID is preferable, or if the key is only
unique within the system that supplied it, add a suitable prefix to make it unique.</p>
TXT;
$lang['resources']['DELETE samples/{id}'] = <<<TXT
Deletes a single sample record either created by the user, or any occurrence record belonging to
the website if the user has site editor or admin access to the website. Example:
<pre><code>
DELETE /index.php/services/rest/samples/3

Response:
204 No Content
</code></pre>
TXT;
$lang['resources']['surveys'] = 'A list of metadata about available survey datasets.';
$lang['resources']['GET surveys'] = 'Retrieves a list of metadata about available survey datasets.';
$lang['resources']['GET surveys/{id}'] = 'Retrieves the metadata for a single survey dataset.';
$lang['resources']['POST surveys'] = <<<TXT
Creates a survey dataset. The user must have editor permissions for the authorised client website.
TXT;
$lang['resources']['PUT surveys/{id}'] = <<<TXT
Updates the metadata for a survey dataset. User must have editor permissions to the website the
survey belongs to.
TXT;
$lang['resources']['DELETE surveys/{id}'] = <<<TXT
Deletes a survey dataset. User must have editor permissions to the website the survey belongs to.
TXT;

$lang['resources']['groups'] = 'Recording groups resource endpoint (sometimes called activities or projects).';
$lang['resources']['GET groups'] = 'A list of recording groups including the authorised user membership info. Default behaviour is to return groups the current user is a member of.';
$lang['resources']['GET groups/{id}'] = <<<HTML
<p>Retrieves the information for a single recording group. The user must either be a member of the
group, or the group must be public or membership by request.</p>
<p>Note, if a group has a boundary associated with its filter (either freehand or a saved location)
then the IDs of indexed locations which intersect that boundary are saved in the value
`indexed_location_ids` which is returned as part of the group's record. This is different to the
list of recording locations returned by <code>GET groups/{id}/locations</code>.</p>
HTML;
$lang['resources']['GET groups/{id}/locations'] = <<<TXT
Retrieves the locations that have been linked to a group, e.g. a project's list of recording
sites. Note that the `id` value in the response is the ID of the join record between locations and
groups, refer to the `location_id` value for the locations's ID.
TXT;
$postExample = str_replace('{{ url }}', '/index.php/services/rest/groups/5/locations', $locationsPostExample);
$lang['resources']['POST groups/{id}/locations'] = <<<HTML
<p>Saves a new recording location to the group. The payload format is the same as when POSTing a
  standalone location.</p>
$postExample
<p>Alternatively an existing location may be posted into the group by supplying a single 'id' value
  for the location ID in the list of values.</p>
<p>Users must be members of the group before using this end-point.</p>
HTML;
$lang['resources']['GET groups/{id}/users'] = <<<TXT
Retrieves the list of users that are members of a group. Unless the requesting user is admin of the
group, the members included in the response will be limited to the current user. Note that the `id`
value in the response is the ID of the join record between users and groups, refer to the `user_id`
value for the user's ID.
TXT;
$lang['resources']['POST groups/{id}/users'] = <<<TXT
<p>Adds the user to the list of members for the group. A group admin can add any users, other users
can only add themselves (400 Bad Request will be returned if they attempt to add another user).
Non-admin users can only add themselves to public groups; requests to join by-request groups will
result in the user being flagged as pending and requests to join other group types will be denied.
<p>Provide the following values in the submission:</p>
<ul>
  <li>id - user ID (required).</li>
  <li>pending - optionally set to 't' or 'f' if the authenticated user is group admin and is adding
    another user. Will default to 't' if the group joining method is by request, otherwise 'f'.
    When adding themselves to a public or by-request group, normal users cannot control this flag.
    </li>
  <li>administrator - optionally set to 't' or 'f' if the authenticated user is group admin and is
    adding another user. Defaults to 'f'. When adding themselves to a public or by-request group,
    normal users cannot control this flag.</li>
</ul>

<p>Example submission:</p>
<pre><code>
POST /index.php/services/rest/groups/3/users
{
  "values": {
    "id": 5,
    "pending": "f",
    "admin": "f",
  }
}
</code></pre>
TXT;
$lang['resources']['DELETE groups/{id}/users/{id}'] = <<<TXT
Removes the user ID from the list of members for the group. A group admin can remove any users,
other users can only remove themselves (400 Bad Request will be returned if they attempt to remove
another user).
TXT;

$lang['resources']['taxa'] = 'Provides search for taxonomy data.';
$lang['resources']['GET taxa/search'] = <<<TXT
Search resource for taxa. Perform full text searches against the taxonomy information held in the
warehouse.
TXT;
$lang['resources']['taxon-observations'] = <<<TXT
Occurrence data provided in the deprecated NBN Gateway exchange format. Described fully in the
<a href="http://indicia-online-recording-rest-api.readthedocs.io/en/latest/">online recording REST API documentation</a>.
TXT;
$lang['resources']['POST taxon-observations'] = 'Creates an occurrence using the deprecated NBN Gateway exchange format.';
$lang['resources']['GET reports'] = <<<TXT
Retrieves the contents of the top level of the reports directory on the warehouse. Can retrieve the
output for a subfolder in the directory or a specific report by appending the path to the resource
URL.
TXT;
$lang['resources']['GET reports/{path}'] = <<<TXT
Retrieves the contents of the folder specified by {path} of the reports directory on the warehouse.
URL.
TXT;
$lang['resources']['GET reports/{path}/{file-xml}'] = <<<TXT
Access the output for a report specified by the supplied path. As well as the system parameters
defined here, each report defines a list of it's own parameters available via the /params endpoint
described below.
TXT;
$lang['resources']['GET reports/{path}/{file-xml}/params'] = <<<TXT
Get metadata about the list of parameters available to filter this report by.
TXT;
$lang['resources']['GET reports/{path}/{file-xml}/columns'] = 'Get metadata about the list of columns available for this report.';
$lang['resources']['projects'] = <<<TXT
A pre-configured list of projects available to the authenticated client. Each project defines permissions including the
filtered set of records available.
TXT;
$lang['resources']['GET projects'] = <<<TXT
Retrieve a list of projects available to this client system ID. Only available when authenticating
as a client system defined in the REST API's configuration file.
TXT;
$lang['resources']['GET projects/{id}'] = <<<TXT
Retrieve the details of a single project where {id} is replaced by the project ID as
retreived from an earlier request to /projects.
TXT;
$lang['resources']['GET taxon-observations'] = <<<TXT
Retrieve a list of taxon-observations available to this client ID for a project indicated by a
supplied proj_id parameter.
TXT;
$lang['resources']['GET taxon-observations/{id}'] = <<<TXT
Retrieve the details of a single taxon-observation where {id} is replaced by the
observation ID. A proj_id parameter must be provided and the observation should be available within
that project's records.
TXT;

// Lang strings for URL parameters for each end-point.
$lang['GET groups'] = [];
$lang['GET groups']['page'] = <<<TXT
Optionally pass the stored path for a group page to limit to groups that are associated with that
page. For example 'record/list' to limit to groups that have a page at the alias record/list. Can
be used to limit to groups associated with an app's data entry page for example.
TXT;
$lang['GET groups']['view'] = <<<TXT
Optionally pass one of the following in the view parameter to control the list of groups returned:
member - default, returns a list of groups the current user is a member of; joinable - returns a
list of groups the user is not a member of but are available to join (public or join by request);
all_available - returns all groups the user can see, including those they are a member of and those
which are joinable; pending - return groups the user has requested to join but where they are
pending approval.
TXT;
$lang['GET groups']['verbose'] = <<<TXT
Add &verbose to the URL to request a more verbose response which includes the pages (forms and/or
reports) that are associated with the group and which the user has permissions to view.
TXT;
$lang['GET groups/{id}']['verbose'] = $lang['GET groups']['verbose'];

$lang['GET occurrences'] = [];
$lang['GET occurrences']['verbose'] = <<<TXT
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
$lang['GET occurrences/{id}']['verbose'] = $lang['GET occurrences']['verbose'];

$lang['GET occurrences/check-newness'] = [];
$lang['GET occurrences/check-newness']['external_key'] = <<<TXT
Required. The accepted taxon ID (taxon.accepted_taxon_id) of the species to check.
TXT;
$lang['GET occurrences/check-newness']['lat'] = <<<TXT
Optional. Latitude of the record location in WGS84 (decimal degrees). Must be provided if
grid_square_size is specified, otherwise must not be provided.
TXT;
$lang['GET occurrences/check-newness']['lon'] = <<<TXT
Optional. Longitude of the record location in WGS84 (decimal degrees). Must be provided if
grid_square_size is specified, otherwise must not be provided.
TXT;
$lang['GET occurrences/check-newness']['grid_square_size'] = <<<TXT
Optional. Size of the grid square for location-based newness checks. Must be one of: '1km', '2km',
or '10km'. Must be provided if lat/lon are specified, otherwise must not be provided. If provided,
the response will include an is_new_for_grid badge and a grid_square field containing the WKT
POINT geometry for the grid square centre.
TXT;
$lang['GET occurrences/check-newness']['year'] = <<<TXT
Optional. Year (as an integer) to check for annual newness. If provided, the response will include
an is_new_for_year badge indicating whether the species has been recorded in that year.
TXT;
$lang['GET occurrences/check-newness']['group_id'] = <<<TXT
Optional. Group ID to filter by. If provided, the response will include an is_new_for_group badge
indicating whether the species has been recorded within that group.
TXT;

$lang['GET samples'] = [];
$lang['GET samples']['verbose'] = <<<TXT
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
$lang['GET samples/{id}']['verbose'] = $lang['GET samples']['verbose'];

$lang['GET surveys'] = [];
$lang['GET surveys']['verbose'] = <<<TXT
Add &verbose to the URL to retrieve attribute values as an array with additional information
including the attribute ID, caption, data type, value ID and raw value information as shown in the
following example response (shortened):
<pre><code>
200 OK
{
  "values": {
    "id": "3",
    ...
    "srvAttr:1": [{
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
$lang['GET surveys/{id}']['verbose'] = $lang['GET surveys']['verbose'];
$lang['GET taxon-observations'] = [];
$lang['GET taxon-observations']['proj_id'] = <<<TXT
Required when authenticated using a client system. Identifier for the project that contains the
observations the client is requesting.
TXT;
$lang['GET taxon-observations']['filter_id'] = <<<TXT
Optional when authenticated as a warehouse user. Must point to the ID of a filter in the filters
table which has defines_permissions set to true and is linked to the authenticated user. When used,
switches the set of records that are accessible from those created by the current user to the set
of records identified by the filter.
TXT;
$lang['GET taxon-observations']['page'] = <<<TXT
The page of records to retrieve when there are more records available than page_size. The first
page is page 1. Defaults to 1 if not provided.
TXT;
$lang['GET taxon-observations']['page_size'] = <<<TXT
The maximum number of records to retrieve. Defaults to 100 if not provided.
TXT;
$lang['GET taxon-observations']['edited_date_from'] = <<<TXT
Restricts the records to those created or edited on or after the date provided. Format yyyy-mm-dd.
TXT;
$lang['GET taxon-observations']['edited_date_to'] = <<<TXT
Restricts the records to those created or edited on or before the date provided. Format yyyy-mm-dd.
TXT;
$lang['GET taxon-observations/{id}']['proj_id'] = $lang['GET taxon-observations']['proj_id'];
$lang['GET taxon-observations/{id}']['filter_id'] = $lang['GET taxon-observations']['filter_id'];
$lang['GET annotations'] = [];
$lang['GET annotations']['proj_id'] = <<<TXT
Required when authenticated using a client system. Identifier for the project that contains the
observations the client is requesting.
TXT;
$lang['GET annotations']['filter_id'] = <<<TXT
Optional when authenticated as a warehouse user. Must point to the ID of a filter in the filters
table which has defines_permissions set to true and is linked to the authenticated user. When used,
switches the set of records that are accessible from those created by the current user to the set
of records identified by the filter.
TXT;
$lang['GET annotations']['page'] = <<<TXT
The page of records to retrieve when there are more records available than page_size. The first
page is page 1. Defaults to 1 if not provided.
TXT;
$lang['GET annotations']['page_size'] = <<<TXT
The maximum number of records to retrieve. Defaults to 100 if not provided.
TXT;
$lang['GET annotations']['edited_date_from'] = <<<TXT
Restricts the annotations to those created or edited on or after the date provided. Format
yyyy-mm-dd.
TXT;
$lang['GET annotations']['edited_date_to'] = <<<TXT
Restricts the annotations to those created or edited on or before the date provided. Format
yyyy-mm-dd.
TXT;
$lang['GET annotations/{id}']['proj_id'] = <<<TXT
Required when authenticated using a client system. Identifier for the project that contains the
observations the client is requesting.
TXT;
$lang['GET annotations/{id}']['filter_id'] = <<<TXT
Optional when authenticated as a warehouse user. Must point to the ID of a filter in the filters
table which has defines_permissions set to true and is linked to the authenticated user. When used,
switches the set of records that are accessible from those created by the current user to the set
of records identified by the filter.
TXT;
$lang['GET locations']['verbose'] = <<<TXT
Add &verbose to the URL to retrieve attribute values as an array with additional information
including the attribute ID, caption, data type, value ID and raw value information as shown in the
following example response (shortened):
<pre><code>
200 OK
{
  "values": {
    "id": "3",
    ...
    "locAttr:1": [{
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
$lang['GET locations/{id}']['verbose'] = $lang['GET locations']['verbose'];
$lang['GET taxa/search'] = [];
$lang['GET taxa/search']['taxon_list_id'] = 'ID or list o IDs of taxon list to search against.';
$lang['GET taxa/search']['searchQuery'] = <<<TXT
Search text which will be used to look up species and taxon names.
TXT;
$lang['GET taxa/search']['taxon_group_id'] = 'ID or array of IDs of taxon groups to limit the search to.';
$lang['GET taxa/search']['taxon_group'] = <<<TXT
Taxon group name or array of taxon group names to limit the search to, an alternative to using
taxon_group_id.
TXT;
$lang['GET taxa/search']['scratchpad_list_id'] = <<<TXT
ID of a taxa_taxon_list related scratchpad list to limit the search to.
TXT;
$lang['GET taxa/search']['taxon_meaning_id'] = 'ID or array of IDs of taxon meanings to limit the search to.';
$lang['GET taxa/search']['taxa_taxon_list_id'] = <<<TXT
ID or array of IDs of taxa taxon list records to limit the search to.
TXT;
$lang['GET taxa/search']['preferred_taxa_taxon_list_id'] = <<<TXT
ID or array of IDs of taxa taxon list records to limit the search to, using the preferred name's ID
to filter against, therefore including synonyms and common names in the search.
TXT;
$lang['GET taxa/search']['preferred_taxon'] = <<<TXT
Preferred taxon name or array of preferred names to limit the search to (e.g. limit to a list of
species names). Exact matches required.
TXT;
$lang['GET taxa/search']['external_key'] = <<<TXT
External key or array of external keys to limit the search to (e.g. limit to a list of taxa having the same accepted name TVK).
TXT;
$lang['GET taxa/search']['search_code'] = <<<TXT
Search code or array of search codes to limit the search to (e.g. limit to a list of taxa having the exact TVKs).
TXT;
$lang['GET taxa/search']['parent_id'] = <<<TXT
ID of a taxa_taxon_list record limit the search to children of, e.g. a species when searching the
subspecies. May be set to null to force top level species only.
TXT;
$lang['GET taxa/search']['language'] = <<<TXT
Languages of names to include in search results. Pass a 3 character iso code for the language, e.g.
"lat" for Latin names or "eng" for English names. Alternatively set this to "common" to filter for
all common names (i.e. non-Latin names).
TXT;
$lang['GET taxa/search']['preferred'] = <<<TXT
Set to true to limit to preferred names, false to limit to non-preferred names.
TXT;
$lang['GET taxa/search']['commonNames'] = <<<TXT
Set to true to limit to common names, false to exclude common names.
TXT;
$lang['GET taxa/search']['synonyms'] = 'Set to true to limit to syonyms, false to exclude synonyms.';
$lang['GET taxa/search']['abbreviations'] = <<<TXT
Set to false to disable searching 2+3 character species name abbreviations.
TXT;
$lang['GET taxa/search']['marine_flag'] = <<<TXT
Set to true for only marine associated species, false to exclude marine-associated species.
TXT;
$lang['GET taxa/search']['freshwater_flag'] = <<<TXT
Set to true for only freshwater associated species, false to exclude freshwater-associated species.
TXT;
$lang['GET taxa/search']['terrestrial_flag'] = <<<TXT
Set to true for only terrestrial associated species, false to exclude terrestrial-associated species.
TXT;
$lang['GET taxa/search']['non_native_flag'] = <<<TXT
Set to true for only non-native species, false to exclude non-native species.
TXT;
$lang['GET taxa/search']['searchAuthors'] = 'Set to true to include author strings in the searched text.';
$lang['GET taxa/search']['wholeWords'] = <<<TXT
Set to true to only search whole words in the full text index, otherwise searches the start of
words.
TXT;
$lang['GET taxa/search']['min_taxon_rank_sort_order'] = <<<TXT
Set to the minimum sort order of the taxon ranks to include in the results.
TXT;
$lang['GET taxa/search']['max_taxon_rank_sort_order'] = <<<TXT
Set to the maximum sort order of the taxon ranks to include in the results.
TXT;
$lang['GET taxa/search']['limit'] = 'Limit the number of records in the response.';
$lang['GET taxa/search']['offset'] = 'Offset from the start of the dataset that the response will start.';
$lang['GET taxa/search']['include'] = <<<TXT
Defines which parts of the response structure to include. If the count and paging data are not
required then exclude them for better performance.
TXT;

$lang['reports']['featured_folder_description'] = <<<TXT
Provides a list of well maintained reports which are recommended as a starting point when exploring
the library of reports.
TXT;
$lang['GET reports/{path}/{file-xml}'] = [];
$lang['GET reports/{path}/{file-xml}']['autofeed'] = <<<TXT
Set to 't' to enable autofeed mode when populating an Elasticsearch instance by connecting Logstash
to a warehouse via the REST API, using one of the "list_for_elastic" reports which supports this
mode. The request must authenticate using a client project ID which then enables the API to
automatically feed through all new and changed records since the last request, up to a limit
(normally 10,000).
TXT;
$lang['GET reports/{path}/{file-xml}']['filter_id'] = <<<TXT
Optional when authenticated as a warehouse user. Must point to the ID of a filter in the filters
table which has defines_permissions set to true and is linked to the authenticated user. When used,
switches the set of records that are accessible from those created by the current user to the set
of records identified by the filter.
TXT;
$lang['GET reports/{path}/{file-xml}']['limit'] = 'Limit the number of records in the response.';
$lang['GET reports/{path}/{file-xml}']['max_time'] = <<<TXT
Use in conjuction with `autofeed` mode when populating an Elasticsearch instance by connecting
Logstash to a warehouse via the REST API, using one of the "list_for_elastic" reports which
supports this mode. Defines a maximum number of seconds for the request to take before autofeed
tracking is cancelled. This is because Logstash will always timeout a request that takes too long
so the `max_time` should be set to a lower value in order to cause the next request to retrieve the
same set of records again and avoid skipping a block of records.
TXT;
$lang['GET reports/{path}/{file-xml}']['offset'] = 'Offset from the start of the dataset that the response will start.';
$lang['GET reports/{path}/{file-xml}']['sortby'] = <<<TXT
The field to sort by. Must be compatible with the SQL generated for the report.
TXT;
$lang['GET reports/{path}/{file-xml}']['sortdir'] = 'Direction of sort, ASC or DESC';
$lang['GET reports/{path}/{file-xml}']['columns'] = <<<TXT
Comma separated list of column fieldnames to include in the report output. Default is all
available in the report.
TXT;
$lang['GET reports/{path}/{file-xml}']['cached'] = <<<TXT
Set to true to enable server side caching of the report output. Repeated requests with for the same
report and parameters will be fast but data will not be fully up to date.
TXT;
$lang['GET reports/{path}/{file-xml}']['{report parameter}'] = <<<TXT
Supply report parameter values for filtering as defined by the report /params resource.
TXT;
$lang['GET reports/{path}/{file-xml}/{path}/{file-xml}/params'] = [];
$lang['GET reports/{path}/{file-xml}/{path}/{file-xml}/columns'] = [];
