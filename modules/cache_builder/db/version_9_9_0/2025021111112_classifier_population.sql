-- #slow script#

-- Set a default of disagreement for all records with classifier info.
UPDATE cache_occurrences_functional o
SET classifier_agreement=false
FROM occurrence_media m
JOIN classification_results_occurrence_media crom ON crom.occurrence_media_id=m.id
WHERE m.occurrence_id=o.id AND m.deleted=false;

-- For records with classifier info where a suggestion matches the current det,
-- set agreement to true if the classifier chose that suggestion as the best match.
UPDATE cache_occurrences_functional o
SET classifier_agreement=COALESCE(cs.classifier_chosen, false)
FROM occurrence_media m
JOIN classification_results_occurrence_media crom ON crom.occurrence_media_id=m.id
LEFT JOIN (classification_suggestions cs
  JOIN cache_taxa_taxon_lists cttl on cttl.id=cs.taxa_taxon_list_id
) ON cs.classification_result_id=crom.classification_result_id AND cs.deleted=false
WHERE m.occurrence_id=o.id AND m.deleted=false
AND (cttl.external_key=o.taxa_taxon_list_external_key OR cs.id IS NULL);