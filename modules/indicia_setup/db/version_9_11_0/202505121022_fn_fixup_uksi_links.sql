CREATE OR REPLACE FUNCTION f_fixup_uksi_links()
  RETURNS boolean AS
$BODY$
BEGIN
-- Function which tidies the links to UKSI after an update, e.g. to ensure that
-- attribute values and  designations still point to the accepted version of a name.

-- First, fix the attribute values.
UPDATE taxa_taxon_list_attribute_values av
SET taxa_taxon_list_id=ttlpref.id
FROM taxa_taxon_lists ttl
JOIN taxa_taxon_lists ttlpref
  ON ttlpref.taxon_meaning_id=ttl.taxon_meaning_id
  AND ttlpref.taxon_list_id=ttl.taxon_list_id
  AND ttlpref.preferred=true
JOIN taxa tpref ON tpref.id=ttlpref.taxon_id AND tpref.deleted=false
WHERE ttl.id=av.taxa_taxon_list_id
AND ttl.preferred=false
AND ttl.deleted=false
AND av.deleted=false
AND av.taxa_taxon_list_id<>ttlpref.id;

-- Fix designations.
UPDATE taxa_taxon_designations ttd
SET taxon_id=ttlpref.taxon_id
FROM taxa_taxon_lists ttl
JOIN taxa_taxon_lists ttlpref
  ON ttlpref.taxon_meaning_id=ttl.taxon_meaning_id
  AND ttlpref.taxon_list_id=ttl.taxon_list_id
  AND ttlpref.preferred=true
JOIN taxa tpref ON tpref.id=ttlpref.taxon_id AND tpref.deleted=false
WHERE ttl.taxon_id=ttd.taxon_id
AND ttl.preferred=false
AND ttl.deleted=false
AND ttd.deleted=false
AND ttd.taxon_id<>ttlpref.taxon_id;

return true;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;