CREATE OR REPLACE function f_add_species_alerts_fk_taxon_meaning (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := FALSE;

BEGIN

ALTER TABLE species_alerts 
ADD CONSTRAINT fk_species_alerts_taxon_meaning FOREIGN KEY (taxon_meaning_id)
REFERENCES taxon_meanings (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

END;

END;
$func$;

SELECT f_add_species_alerts_fk_taxon_meaning();

DROP FUNCTION f_add_species_alerts_fk_taxon_meaning();