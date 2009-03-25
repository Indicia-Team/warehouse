CREATE FUNCTION addDeleted() RETURNS integer AS $$
DECLARE
	arrayTables Text[];
	currentTable Text;
BEGIN
	arrayTables[1] = 'core_roles';
	arrayTables[2] = 'languages';
	arrayTables[3] = 'location_attribute_values';
	arrayTables[4] = 'location_attributes';	
	arrayTables[5] = 'location_attributes_websites';	
	arrayTables[6] = 'locations';
	arrayTables[7] = 'locations_websites';	
	arrayTables[8] = 'occurrence_attribute_values';	
	arrayTables[9] = 'occurrence_attributes';
	arrayTables[10] = 'occurrence_attributes_websites';	
	arrayTables[11] = 'occurrence_comments';	
	arrayTables[12] = 'occurrence_images';
	arrayTables[13] = 'occurrences';	
	arrayTables[14] = 'people';	
	arrayTables[15] = 'samples';
	arrayTables[16] = 'sample_attribute_values';	
	arrayTables[17] = 'sample_attributes';
	arrayTables[18] = 'sample_attributes_websites';
	arrayTables[19] = 'site_roles';	
	arrayTables[20] = 'surveys';
	arrayTables[21] = 'taxon_groups';
	arrayTables[22] = 'titles';	
	arrayTables[23] = 'users';
	arrayTables[24] = 'websites';
	
	FOR idx in array_lower(arrayTables, 1)..array_upper(arrayTables, 1) LOOP
		EXECUTE 'ALTER TABLE ' || arrayTables[idx] || ' ADD COLUMN deleted boolean not null default false';
		EXECUTE 'COMMENT ON COLUMN ' || arrayTables[idx]|| '.deleted is ''Has this record been deleted?''';
	END LOOP;
	
	RETURN 1;
END
$$ LANGUAGE plpgsql;

SELECT addDeleted();
DROP FUNCTION addDeleted();