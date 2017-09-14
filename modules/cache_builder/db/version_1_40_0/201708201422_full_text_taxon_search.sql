-- #slow script#

CREATE INDEX ix_cache_taxon_searchterms_fulltext
  ON cache_taxon_searchterms
  USING gin
  (to_tsvector('simple'::regconfig, quote_literal(quote_literal(original::text))))
  WHERE simplified = false;

CREATE INDEX ix_cache_taxon_searchterms_fulltext_with_author
  ON cache_taxon_searchterms
  USING gin
  (to_tsvector('simple'::regconfig, quote_literal(quote_literal((original::text || ' '::text) || COALESCE(authority, ''::character varying)::text))))
  WHERE simplified = false;
