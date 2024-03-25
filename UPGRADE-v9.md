# Upgrading to version 9 of the warehouse.

Version 9 adds fields to the reporting cache tables and the code that populates these fields will
error if the upgrade scripts which add the fields have not been run. In order to avoid errors for
posted records during the upgrade process, you have 2 options. Either take all client sites
offline during the upgrade (e.g. by putting Drupal in maintenance mode), or by running the
following script before the upgrade so that the fields are ready in the database. Note that the
UPDATE statements in particular may take a long time, depending on the number of records and
samples in your database.

```sql
ALTER TABLE cache_samples_functional
ADD COLUMN IF NOT EXISTS hide_sample_as_private boolean;

ALTER TABLE cache_occurrences_functional
ADD COLUMN IF NOT EXISTS hide_sample_as_private boolean;

-- Disable tracking increments, so doesn't force a complete ES refresh.
SET application_name = 'skiptrigger';

ALTER TABLE cache_occurrences_functional
ALTER COLUMN hide_sample_as_private SET DEFAULT false;

ALTER TABLE cache_samples_functional
ALTER COLUMN hide_sample_as_private SET DEFAULT false;

UPDATE cache_samples_functional SET hide_sample_as_private=false;

UPDATE cache_occurrences_functional SET hide_sample_as_private=false;

ALTER TABLE cache_samples_functional
ALTER COLUMN hide_sample_as_private SET NOT NULL;

ALTER TABLE cache_occurrences_functional
ALTER COLUMN hide_sample_as_private SET NOT NULL;
```

After the upgrade the warehouse will ask you to run the 2nd part of this script using pgAdmin, if
you have already run it there is no need to run it a second time.

## Elasticsearch

If you are using Elasticsearch, then before upgrading you should add the mappings required for new
fields. You can run the following using the Dev tools in Kibana, replacing your index name:

```
PUT /my_occurrence_index/_mapping
{
  "properties": {
    "metadata.hide_sample_as_private": {
      "type": "boolean"
    }
  }
}
```

Repeat this step for your samples index if you are also storing samples in Elasticsearch.

You also need to add information about this new field to each of your occurrence *.conf files used
by Logstash. Edit the files and search for a comment which starts `# Convert our list of fields`
which should be just above a `mutate` block. Insert the following code before the comment:

```yaml
  mutate {
    add_field => {
      "hide_sample_as_private" => false
    }
  }
  # Set hide_sample_as_private using privacy_precision value.
  translate {
    source => "[privacy_precision]"
    target => "[hide_sample_as_private]"
    override => true
    dictionary => {
      "0" => true
    }
    fallback => false
  }
```

Also, in the list of rename operations in the mutate block just below, add the following after the
rename operation for `privacy_precision`:

```yaml
  "hide_sample_as_private" => "[metadata][hide_sample_as_private]"
```

Now save the config file and repeat for any other pipeline configuration files that you have set
up. Finally, restart the Logstash process or service as appropriate.

One the above steps have been completed, it is safe to update your warehouse code then visit the
home page in order to follow the link to upgrade the database.