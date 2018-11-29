-- #slow script#
UPDATE cache_samples_functional u
SET attr_sample_method=COALESCE(CASE a_sample_method.data_type
      WHEN 'T'::bpchar THEN v_sample_method.text_value
      WHEN 'L'::bpchar THEN t_sample_method.term
      ELSE NULL::text
  END, t_sample_method_id.term)
FROM samples s
LEFT JOIN (sample_attribute_values v_sample_method
  JOIN sample_attributes a_sample_method on a_sample_method.id=v_sample_method.sample_attribute_id and a_sample_method.deleted=false and a_sample_method.system_function='sample_method'
  LEFT JOIN cache_termlists_terms t_sample_method on a_sample_method.data_type='L' and t_sample_method.id=v_sample_method.int_value
) on v_sample_method.sample_id=s.id and v_sample_method.deleted=false
LEFT JOIN cache_termlists_terms t_sample_method_id ON t_sample_method_id.id=s.sample_method_id
WHERE s.id=u.id
AND COALESCE(u.attr_sample_method, '') <> COALESCE(CASE a_sample_method.data_type
      WHEN 'T'::bpchar THEN v_sample_method.text_value
      WHEN 'L'::bpchar THEN t_sample_method.term
      ELSE NULL::text
  END, t_sample_method_id.term, '');