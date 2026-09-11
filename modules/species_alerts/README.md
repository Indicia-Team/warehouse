# Species alerts

The species alerts module stores subscriptions for users who want notifications
when matching occurrence records are entered or verified.

## Matching

The scheduled task scans recently updated, non-training, non-confidential
occurrences. An alert matches when all of its supplied filters match:

- `external_key`, `taxon_meaning_id`, and `taxon_list_id` identify the taxon.
- `location_id` limits the alert to records whose indexed locations contain it.
- `survey_id` limits the alert to one survey.
- `website_id` is matched through the reporting-sharing index. The alert's
  website must be allowed to receive reporting data from the occurrence's
  website; records belonging to the same website are included automatically.

A taxon selector is required. Alerts using `taxon_list_id` must also include
at least one additional taxon, survey, or location filter.

## Notification timing

Entry notifications use the occurrence `created_on` timestamp. They are still
generated if the record has already become verified by the time the scheduled
task runs. Verification notifications use `verified_on` and require status `V`.
When both events fall within the processing window and both options are enabled,
both notifications are generated.

The task rescans a 12-hour period before the previous task checkpoint to allow
spatial indexing to catch up. It also permits a two-day entry/verification event
window for retries. Existing notifications for the same user and occurrence are
excluded. Duplicate alert rows for one user therefore do not create duplicate
notifications.

## Updating alerts

New alerts can be registered through the service, called by the "Subscribe to a
species alert" prebuilt form. Updating an existing alert requires the alert
owner and website to remain unchanged. User-authenticated requests must use the
owning user's token; site-level credentials cannot update existing alerts.

Model validation is applied before persistence. The test coverage for this
module is in `tests/SpeciesAlertTest.php`.
