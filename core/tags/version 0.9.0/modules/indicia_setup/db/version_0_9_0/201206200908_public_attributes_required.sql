UPDATE locations SET public=false WHERE public IS NULL;

ALTER TABLE locations
   ALTER COLUMN public SET NOT NULL;

UPDATE location_attributes SET public=false WHERE public IS NULL;

ALTER TABLE location_attributes
   ALTER COLUMN public SET NOT NULL;

UPDATE occurrence_attributes SET public=false WHERE public IS NULL;

ALTER TABLE occurrence_attributes
   ALTER COLUMN public SET NOT NULL;

UPDATE sample_attributes SET public=false WHERE public IS NULL;

ALTER TABLE sample_attributes
   ALTER COLUMN public SET NOT NULL;

UPDATE taxa_taxon_list_attributes SET public=false WHERE public IS NULL;

ALTER TABLE taxa_taxon_list_attributes
   ALTER COLUMN public SET NOT NULL;
