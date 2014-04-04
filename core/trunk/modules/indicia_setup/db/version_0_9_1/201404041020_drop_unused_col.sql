-- This column not required as any change would alter the updated date.
ALTER TABLE occurrences DROP COLUMN last_verification_check_taxa_taxon_list_id;
ALTER TABLE occurrences DROP COLUMN last_verification_check_version;
ALTER TABLE occurrences DROP COLUMN last_Verification_check_date;
