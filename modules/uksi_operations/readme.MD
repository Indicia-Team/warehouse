# UKSI Operations module

A module which allows the spreadsheet of operations being imported into UKSI to be applied directly
to the copy of UKSI held in an Indicia warehouse's taxonomy tables.

Before using this module, UKSI should have been installed on the warehouse (see
https://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/importing-uksi.html).

Then, enable this module and visit /index.php/home/upgrade to update the database tables.

Then run the following script to ensure that the organism_key links to UKSI are present:

```sql
update taxa t
set organism_key=pn.organism_key
from uksi.preferred_names pn
where pn.taxon_version_key=t.external_key
and pn.redundant=false;

update taxa t
set organism_key=pn.organism_key
from uksi.preferred_names pn
where pn.taxon_version_key=t.external_key
and pn.redundant=true
and t.organism_key is null;
```

