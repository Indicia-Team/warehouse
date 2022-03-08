This module is based on the code in https://wiki.postgresql.org/wiki/Audit_trigger_91plus
As such it assumes postgresql 9.1 or greater.

Notes from that page:
"
Audited data. Lots of information is available, it's just a matter of how much
you really want to record. See:

  http://www.postgresql.org/docs/9.1/static/functions-info.html

Remember, every column you add takes up more audit table space and slows audit
inserts.

Every index you add has a big impact too, so avoid adding indexes to the
audit table unless you REALLY need them. The hstore GIST indexes are
particularly expensive.
"

Requirements (copied from email from John vB):
1)	An audit trail mechanism that will be able to (at least) capture all updates to the sample & occurrences table, with locations (and potentially others) as nice to haves.
2)	Changes will track the previous record data values, probably in something like a JSON field.
3)	Changes will capture the record ID, table name, transaction ID, datestamp. Nice to have ñ version number for the individual record.
4)	Changes to attribute values will get stored in the same log entry as the parent occurrence/sample/location record if done through the same transaction. 
5)	We’ll provide a UI that allows a table name to be selected and a record ID to be input. Then youíd see a history of that record with changes (even if the changes are just a load of field value pairs). Doesnít need to be too elegant ñ more for working out what went wrong with the data in a record.
6)	It wonít change the existing data tables or queries (i.e. no storing of record versions in situ in the tables). 
7)	We’ll endeavour to ensure that overall performance impact is minimised.
8)	We’ll capture enough information so that code could be written to reconstruct a record at any point in time / version number. This could be added to data services in future but wonít be now.
9)	Weíll capture enough information so that a rollback could be written in future. (I think this is exactly the same as point 6). 

Changes for this implementation:
1) Cant use activate hstore through normal Indicia upgrade mechanisms: has to be done manually, probably
   by postgres user.
     CREATE EXTENSION IF NOT EXISTS hstore;
2) Kohana doesnt understand the oid field type, so not using the relid.
3) there are various places in Indicia that assume the primary key of a table is "id", so using that rather than "event_id"
4) ORM uses the field "table_name" internally, so can't use this as a column name - changed to "event_table_name"
5) not recording the client address/port
6) only really interested in one timestamp (will use transaction timestamp), so not recording statement and wall clock timestamps 
7) not recording the application_name
8) not recording the client_query
9) records the record primary key (Indicia ID) in a separate column "event_record_id"
10) records the indicia user as a separate column
11) records the indicia website in a separate table: locations especially may have more than one website
12) not recording INSERTs on main tables (initially locations, samples, occurrences). The subtables (e.g.
    attributes) have inserts recorded.
13) In order to bundle subtable data with parent table, need extra search fields.
14) point to parents of subtables
15) Add Indicia specific UI.
16) We have assumed that the Indicia Primary key does not change.

TODO
add version number to tables?



