ALTER TABLE species_alerts
   ADD COLUMN taxon_list_id integer;
COMMENT ON COLUMN species_alerts.taxon_list_id
  IS 'For alerts created to fire when any species in an entire list is recorded, this identifies the list.';

ALTER TABLE species_alerts
  ADD CONSTRAINT fk_species_alerts_taxon_list FOREIGN KEY (taxon_list_id)
      REFERENCES taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
      
ALTER TABLE species_alerts DROP CONSTRAINT taxon_meaning_or_external_key;

ALTER TABLE species_alerts
  ADD CONSTRAINT some_taxon_filter CHECK (taxon_meaning_id IS NOT NULL OR external_key IS NOT NULL OR taxon_list_id IS NOT NULL);

