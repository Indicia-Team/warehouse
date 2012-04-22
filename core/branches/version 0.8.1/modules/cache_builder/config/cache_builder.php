<?php

$config['termlists_terms']['get_changelist_query']="
    select distinct on (tlt.id) tlt.id, tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted as deleted
      from termlists tl
      join termlists_terms tlt on tlt.termlist_id=tl.id 
      join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred='t' 
      join terms t on t.id=tlt.term_id 
      join languages l on l.id=t.language_id 
      join terms tpref on tpref.id=tltpref.term_id 
      join languages lpref on lpref.id=tpref.language_id";
      
$config['termlists_terms']['exclude_existing'] = "
      left join cache_termlists_terms ctlt on ctlt.id=tlt.id 
      left join needs_update_termlists_terms nutlt on nutlt.id=tlt.id 
      where ctlt.id is null and nutlt.id is null
      and (tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted) = false";

$config['termlists_terms']['filter_on_date'] = "
      where tl.created_on>'#date#' or tl.updated_on>'#date#' 
      or tlt.created_on>'#date#' or tlt.updated_on>'#date#' 
      or tltpref.created_on>'#date#' or tltpref.updated_on>'#date#' 
      or t.created_on>'#date#' or t.updated_on>'#date#' 
      or l.created_on>'#date#' or l.updated_on>'#date#' 
      or tpref.created_on>'#date#' or tpref.updated_on>'#date#' 
      or lpref.created_on>'#date#' or lpref.updated_on>'#date#' ";

$config['termlists_terms']['update'] = "update cache_termlists_terms ctlt
    set preferred=tlt.preferred,
      termlist_id=tl.id, 
      termlist_title=tl.title,
      website_id=tl.website_id,
      preferred_termlists_term_id=tltpref.id,
      parent_id=tltpref.parent_id,
      sort_order=tltpref.sort_order,
      term=t.term,
      language_iso=l.iso,
      language=l.language,
      preferred_term=tpref.term,
      preferred_language_iso=lpref.iso,
      preferred_language=lpref.language,
      meaning_id=tltpref.meaning_id,
      cache_updated_on=now()
    from termlists tl
    join termlists_terms tlt on tlt.termlist_id=tl.id 
    join needs_update_termlists_terms nutlt on nutlt.id=tlt.id
    join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred='t' 
    join terms t on t.id=tlt.term_id 
    join languages l on l.id=t.language_id 
    join terms tpref on tpref.id=tltpref.term_id 
    join languages lpref on lpref.id=tpref.language_id 
    where ctlt.id=tlt.id";

$config['termlists_terms']['insert']="insert into cache_termlists_terms (
      id, preferred, termlist_id, termlist_title, website_id,
      preferred_termlists_term_id, parent_id, sort_order,
      term, language_iso, language, preferred_term, preferred_language_iso, preferred_language, meaning_id,
      cache_created_on, cache_updated_on
    )
    select distinct on (tlt.id) tlt.id, tlt.preferred, 
      tl.id as termlist_id, tl.title as termlist_title, tl.website_id,
      tltpref.id as preferred_termlists_term_id, tltpref.parent_id, tltpref.sort_order,
      t.term,
      l.iso as language_iso, l.language,
      tpref.term as preferred_term, 
      lpref.iso as preferred_language_iso, lpref.language as preferred_language, tltpref.meaning_id,
      now(), now()
    from termlists tl
    join termlists_terms tlt on tlt.termlist_id=tl.id 
    join needs_update_termlists_terms nutlt on nutlt.id=tlt.id and nutlt.deleted=false
    left join cache_termlists_terms ctlt on ctlt.id=tlt.id
    join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred='t' 
    join terms t on t.id=tlt.term_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join terms tpref on tpref.id=tltpref.term_id 
    join languages lpref on lpref.id=tpref.language_id
    where ctlt.id is null";
    

$config['taxa_taxon_lists']['get_changelist_query']="
    select distinct on (ttl.id) ttl.id, tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted as deleted
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' 
      join taxa t on t.id=ttl.taxon_id 
      join languages l on l.id=t.language_id 
      join taxa tpref on tpref.id=ttlpref.taxon_id 
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      join languages lpref on lpref.id=tpref.language_id";
      
$config['taxa_taxon_lists']['exclude_existing'] = "
      left join cache_taxa_taxon_lists cttl on cttl.id=ttl.id 
      left join needs_update_taxa_taxon_lists nuttl on nuttl.id=ttl.id 
      where cttl.id is null and nuttl.id is null 
      and (tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or lpref.deleted) = false";

$config['taxa_taxon_lists']['filter_on_date'] = "
      where tl.created_on>'#date#' or tl.updated_on>'#date#' 
      or ttl.created_on>'#date#' or ttl.updated_on>'#date#' 
      or ttlpref.created_on>'#date#' or ttlpref.updated_on>'#date#' 
      or t.created_on>'#date#' or t.updated_on>'#date#' 
      or l.created_on>'#date#' or l.updated_on>'#date#' 
      or tpref.created_on>'#date#' or tpref.updated_on>'#date#' 
      or tg.created_on>'#date#' or tg.updated_on>'#date#' 
      or lpref.created_on>'#date#' or lpref.updated_on>'#date#' ";

$config['taxa_taxon_lists']['update'] = "update cache_taxa_taxon_lists cttl
    set preferred=ttl.preferred,
      taxon_list_id=tl.id, 
      taxon_list_title=tl.title,
      website_id=tl.website_id,
      preferred_taxa_taxon_list_id=ttlpref.id,
      parent_id=ttlpref.parent_id,
      taxonomic_sort_order=ttlpref.taxonomic_sort_order,
      taxon=t.taxon,
      authority=t.authority,
      language_iso=l.iso,
      language=l.language,
      preferred_taxon=tpref.taxon,
      preferred_authority=tpref.authority,
      preferred_language_iso=lpref.iso,
      preferred_language=lpref.language,
      default_common_name=tcommon.taxon,
      search_name=regexp_replace(regexp_replace(lower(t.taxon), 'ae', 'e', 'g'), '[ \''\-_]', '', 'g'),
      external_key=tpref.external_key,
      taxon_meaning_id=ttlpref.taxon_meaning_id,
      taxon_group_id = tpref.taxon_group_id,
      taxon_group = tg.title,
      cache_updated_on=now()
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
    join needs_update_taxa_taxon_lists nuttl on nuttl.id=ttl.id
    join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' 
    join taxa t on t.id=ttl.taxon_id 
    join languages l on l.id=t.language_id 
    join taxa tpref on tpref.id=ttlpref.taxon_id 
    join taxon_groups tg on tg.id=tpref.taxon_group_id
    join languages lpref on lpref.id=tpref.language_id
    left join taxa tcommon on tcommon.id=ttlpref.common_taxon_id
    where cttl.id=ttl.id";

$config['taxa_taxon_lists']['insert']="insert into cache_taxa_taxon_lists (
      id, preferred, taxon_list_id, taxon_list_title, website_id,
      preferred_taxa_taxon_list_id, parent_id, taxonomic_sort_order,
      taxon, authority, language_iso, language, preferred_taxon, preferred_authority, 
      preferred_language_iso, preferred_language, default_common_name, search_name, external_key, 
      taxon_meaning_id, taxon_group_id, taxon_group,
      cache_created_on, cache_updated_on
    )
    select distinct on (ttl.id) ttl.id, ttl.preferred, 
      tl.id as taxon_list_id, tl.title as taxon_list_title, tl.website_id,
      ttlpref.id as preferred_taxa_taxon_list_id, ttlpref.parent_id, ttlpref.taxonomic_sort_order,
      t.taxon, t.authority,
      l.iso as language_iso, l.language,
      tpref.taxon as preferred_taxon, tpref.authority as preferred_authority, 
      lpref.iso as preferred_language_iso, lpref.language as preferred_language,
      tcommon.taxon as default_common_name,
      regexp_replace(regexp_replace(lower(t.taxon), 'ae', 'e', 'g'), '[ \''\-_]', '', 'g'), tpref.external_key, 
      ttlpref.taxon_meaning_id, tpref.taxon_group_id, tg.title,
      now(), now()
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
    join needs_update_taxa_taxon_lists nuttl on nuttl.id=ttl.id and nuttl.deleted=false
    left join cache_taxa_taxon_lists cttl on cttl.id=ttl.id
    join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' 
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join taxa tpref on tpref.id=ttlpref.taxon_id 
    join taxon_groups tg on tg.id=tpref.taxon_group_id
    join languages lpref on lpref.id=tpref.language_id
    left join taxa tcommon on tcommon.id=ttlpref.common_taxon_id
    where cttl.id is null";


$config['occurrences']['get_changelist_query'] = "
  select o.id, o.deleted or s.deleted or su.deleted or (cttl.id is null) as deleted
    from occurrences o
    join samples s on s.id=o.sample_id 
    join surveys su on su.id=s.survey_id 
    join cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id
    left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id";
  
$config['occurrences']['exclude_existing'] = "
      left join cache_occurrences co on co.id=o.id 
      left join needs_update_occurrences nuo on nuo.id=o.id 
      where co.id is null and nuo.id is null
      and (o.deleted or s.deleted or su.deleted or (cttl.id is null)) = false";

$config['occurrences']['filter_on_date'] = "
    where o.created_on>'#date#' or o.updated_on>'#date#' 
      or s.created_on>'#date#' or s.updated_on>'#date#' 
      or su.created_on>'#date#' or su.updated_on>'#date#'
      or cttl.cache_updated_on>'#date#'
      or tmethod.cache_updated_on>'#date#' ";

$config['occurrences']['update'] = "update cache_occurrences co
    set record_status=o.record_status, 
      downloaded_flag=o.downloaded_flag, 
      zero_abundance=o.zero_abundance,
      website_id=su.website_id, 
      survey_id=su.id, 
      sample_id=s.id,
      survey_title=su.title,
      date_start=s.date_start, 
      date_end=s.date_end, 
      date_type=s.date_type,
      public_entered_sref=case when o.confidential=true then null else s.entered_sref end,
      entered_sref_system=s.entered_sref_system,
      public_geom=case when o.confidential=true then null else s.geom end,
      sample_method=tmethod.term,
      taxa_taxon_list_id=cttl.id, 
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id, 
      taxonomic_sort_order=cttl.taxonomic_sort_order, 
      taxon=cttl.taxon, 
      authority=cttl.authority, 
      preferred_taxon=cttl.preferred_taxon, 
      preferred_authority=cttl.preferred_authority, 
      default_common_name=cttl.default_common_name, 
      search_name=cttl.search_name, 
      taxa_taxon_list_external_key=cttl.external_key,
      taxon_meaning_id=cttl.taxon_meaning_id,
      taxon_group_id = cttl.taxon_group_id,
      taxon_group = cttl.taxon_group,
      created_by_id=o.created_by_id,
      cache_updated_on=now(),
      certainty=case when certainty.sort_order is null then null
        when certainty.sort_order <100 then 'C'
        when certainty.sort_order <200 then 'L'
        else 'U'
      end,
      location_name=COALESCE(l.name, s.location_name)
    from occurrences o
    join needs_update_occurrences nuo on nuo.id=o.id
    join samples s on s.id=o.sample_id and s.deleted=false
    left join locations l on l.id=s.location_id and l.deleted=false
    join surveys su on su.id=s.survey_id and su.deleted=false
    left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    join cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id
    left join (occurrence_attribute_values oav
      join termlists_terms certainty on certainty.id=oav.int_value
      join occurrence_attributes oa on oa.id=oav.occurrence_attribute_id and oa.deleted='f' and oa.system_function='certainty'
    ) on oav.occurrence_id=o.id and oav.deleted='f'
    where co.id=o.id";

$config['occurrences']['insert']="insert into cache_occurrences (
      id, record_status, downloaded_flag, zero_abundance,
      website_id, survey_id, sample_id, survey_title,
      date_start, date_end, date_type,
      public_entered_sref, entered_sref_system, public_geom,
      sample_method, taxa_taxon_list_id, preferred_taxa_taxon_list_id, taxonomic_sort_order, 
      taxon, authority, preferred_taxon, preferred_authority, default_common_name, 
      search_name, taxa_taxon_list_external_key, taxon_meaning_id, taxon_group_id, taxon_group,
      created_by_id, cache_created_on, cache_updated_on, certainty, location_name
    )
  select o.id, o.record_status, o.downloaded_flag, o.zero_abundance,
    su.website_id as website_id, su.id as survey_id, s.id as sample_id, su.title as survey_title,
    s.date_start, s.date_end, s.date_type,
    case when o.confidential=true then null else s.entered_sref end as public_entered_sref,
    s.entered_sref_system,
    case when o.confidential=true then null else s.geom end as public_geom,
    tmethod.term as sample_method,
    cttl.id as taxa_taxon_list_id, cttl.preferred_taxa_taxon_list_id, cttl.taxonomic_sort_order, 
    cttl.taxon, cttl.authority, cttl.preferred_taxon, cttl.preferred_authority, cttl.default_common_name, 
    cttl.search_name, cttl.external_key as taxa_taxon_list_external_key, cttl.taxon_meaning_id,
    cttl.taxon_group_id, cttl.taxon_group, o.created_by_id, now(), now(),
    case when certainty.sort_order is null then null
        when certainty.sort_order <100 then 'C'
        when certainty.sort_order <200 then 'L'
        else 'U'
    end,
    COALESCE(l.name, s.location_name)
  from occurrences o
  join needs_update_occurrences nuo on nuo.id=o.id and nuo.deleted=false
  left join cache_occurrences co on co.id=o.id
  join samples s on s.id=o.sample_id 
  left join locations l on l.id=s.location_id and l.deleted=false
  join surveys su on su.id=s.survey_id 
  left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
  join cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id
  left join (occurrence_attribute_values oav
    join termlists_terms certainty on certainty.id=oav.int_value
    join occurrence_attributes oa on oa.id=oav.occurrence_attribute_id and oa.deleted='f' and oa.system_function='certainty'
  ) on oav.occurrence_id=o.id and oav.deleted='f'
  where co.id is null";


?>
