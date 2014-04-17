ALTER TABLE cache_taxa_taxon_lists ADD COLUMN taxon_rank_id integer;
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN taxon_rank_sort_order integer;
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN taxon_rank varchar;
ALTER TABLE cache_taxon_searchterms ADD COLUMN taxon_rank_sort_order integer;

CREATE OR REPLACE VIEW gv_taxon_ranks AS 
 SELECT id, rank, sort_order
  FROM taxon_ranks
  WHERE deleted=false;

CREATE OR REPLACE VIEW list_taxon_ranks AS 
 SELECT id, rank, sort_order, short_name, italicise_taxon
  FROM taxon_ranks
  WHERE deleted=false;