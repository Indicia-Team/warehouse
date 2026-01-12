CREATE OR REPLACE VIEW list_dna_occurrences AS
SELECT
  dnao.id,
  dnao.occurrence_id,
  array_to_json(dnao.associated_sequences) as associated_sequences,
  dnao.dna_sequence,
  dnao.target_gene,
  dnao.pcr_primer_reference,
  dnao.env_medium,
  dnao.env_broad_scale,
  dnao.otu_db,
  dnao.otu_seq_comp_appr,
  dnao.otu_class_appr,
  dnao.env_local_scale,
  dnao.target_subfragment,
  dnao.pcr_primer_name_forward,
  dnao.pcr_primer_forward,
  dnao.pcr_primer_name_reverse,
  dnao.pcr_primer_reverse,
  dnao.created_on,
  dnao.created_by_id,
  dnao.updated_on,
  dnao.updated_by_id,
  o.website_id
FROM dna_occurrences dnao
JOIN occurrences o ON o.id=dnao.occurrence_id AND o.deleted=false
WHERE dnao.deleted=false;

CREATE OR REPLACE VIEW detail_dna_occurrences AS
SELECT
  dnao.id,
  dnao.occurrence_id,
  array_to_json(dnao.associated_sequences) as associated_sequences,
  dnao.dna_sequence,
  dnao.target_gene,
  dnao.pcr_primer_reference,
  dnao.env_medium,
  dnao.env_broad_scale,
  dnao.otu_db,
  dnao.otu_seq_comp_appr,
  dnao.otu_class_appr,
  dnao.env_local_scale,
  dnao.target_subfragment,
  dnao.pcr_primer_name_forward,
  dnao.pcr_primer_forward,
  dnao.pcr_primer_name_reverse,
  dnao.pcr_primer_reverse,
  dnao.created_on,
  dnao.created_by_id,
  c.username AS created_by,
  dnao.updated_on,
  dnao.updated_by_id,
  u.username AS updated_by,
  o.website_id
FROM dna_occurrences dnao
JOIN occurrences o ON o.id=dnao.occurrence_id AND o.deleted=false
JOIN users c ON c.id = dnao.created_by_id
JOIN users u ON u.id = dnao.updated_by_id
WHERE dnao.deleted=false;