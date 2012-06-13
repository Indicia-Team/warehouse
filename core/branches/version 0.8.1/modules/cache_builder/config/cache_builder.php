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
    left join cache_termlists_terms ctlt on ctlt.id=tlt.id
    join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred='t' 
    join terms t on t.id=tlt.term_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join terms tpref on tpref.id=tltpref.term_id 
    join languages lpref on lpref.id=tpref.language_id
    #insert_join_needs_update#
    where ctlt.id is null";
    
$config['termlists_terms']['insert_join_needs_update']='join needs_update_termlists_terms nutlt on nutlt.id=tlt.id and nutlt.deleted=false';
$config['termlists_terms']['insert_key_field']='tlt.id';

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
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted) = false";

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
      search_name=regexp_replace(regexp_replace(regexp_replace(lower(t.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
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
      regexp_replace(regexp_replace(regexp_replace(lower(t.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
      tpref.external_key, ttlpref.taxon_meaning_id, tpref.taxon_group_id, tg.title,
      now(), now()
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
    left join cache_taxa_taxon_lists cttl on cttl.id=ttl.id
    join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' 
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join taxa tpref on tpref.id=ttlpref.taxon_id 
    join taxon_groups tg on tg.id=tpref.taxon_group_id
    join languages lpref on lpref.id=tpref.language_id
    left join taxa tcommon on tcommon.id=ttlpref.common_taxon_id
    #insert_join_needs_update#
    where cttl.id is null";

$config['taxa_taxon_lists']['insert_join_needs_update']='join needs_update_taxa_taxon_lists nuttl on nuttl.id=ttl.id and nuttl.deleted=false';
$config['taxa_taxon_lists']['insert_key_field']='ttl.id';

$config['taxon_searchterms']['get_changelist_query']="
    select distinct on (ttl.id) ttl.id, tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted as deleted
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' 
      join taxa t on t.id=ttl.taxon_id 
      join languages l on l.id=t.language_id 
      join taxa tpref on tpref.id=ttlpref.taxon_id 
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      join languages lpref on lpref.id=tpref.language_id
      left join taxon_codes tc on tc.taxon_meaning_id=ttl.taxon_meaning_id";

$config['taxon_searchterms']['delete_query']['taxa']="
  delete from cache_taxon_searchterms where taxa_taxon_list_id in (select id from needs_update_taxon_searchterms where deleted=true)";

$config['taxon_searchterms']['delete_query']['codes']="
  delete from cache_taxon_searchterms where name_type='C' and source_id in (
    select tc.id from taxon_codes tc 
    join taxa_taxon_lists ttl on ttl.taxon_meaning_id=tc.taxon_meaning_id
    join needs_update_taxon_searchterms nuts on nuts.id = ttl.id
    where tc.deleted=true)";

$config['taxon_searchterms']['exclude_existing'] = "
      left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=ttl.id 
      left join needs_update_taxon_searchterms nuts on nuts.id=ttl.id 
      where cts.id is null and nuts.id is null 
      and (tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or lpref.deleted) = false";

$config['taxon_searchterms']['filter_on_date'] = "
      where tl.created_on>'#date#' or tl.updated_on>'#date#' 
      or ttl.created_on>'#date#' or ttl.updated_on>'#date#' 
      or ttlpref.created_on>'#date#' or ttlpref.updated_on>'#date#' 
      or t.created_on>'#date#' or t.updated_on>'#date#' 
      or l.created_on>'#date#' or l.updated_on>'#date#' 
      or tpref.created_on>'#date#' or tpref.updated_on>'#date#' 
      or tg.created_on>'#date#' or tg.updated_on>'#date#' 
      or lpref.created_on>'#date#' or lpref.updated_on>'#date#' 
      or ((tc.created_on>'#date#' or tc.updated_on>'#date#') and ttl.preferred=true)";

$config['taxon_searchterms']['update']['standard terms'] = "update cache_taxon_searchterms cts
    set taxa_taxon_list_id=cttl.id,
      taxon_list_id=cttl.taxon_list_id,
      searchterm=cttl.taxon,
      original=cttl.taxon,
      taxon_group_id=cttl.taxon_group_id,
      taxon_group=cttl.taxon_group,
      taxon_meaning_id=cttl.taxon_meaning_id,
      preferred_taxon=cttl.preferred_taxon,
      default_common_name=cttl.default_common_name,
      preferred_authority=cttl.preferred_authority,
      language_iso=cttl.language_iso,
      name_type=case
        when cttl.language_iso='lat' and cttl.preferred_taxa_taxon_list_id=cttl.id then 'L' 
        when cttl.language_iso='lat' and cttl.preferred_taxa_taxon_list_id<>cttl.id then 'S' 
        else 'V'
      end,
      simplified=false, 
      code_type_id=null,
      source_id=null
    from cache_taxa_taxon_lists cttl
    join needs_update_taxon_searchterms nuts on nuts.id=cttl.id
    where cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified=false";

/*
 * 3+2 letter abbreviations are created for all latin names. 
 */
$config['taxon_searchterms']['update']['abbreviations'] = "update cache_taxon_searchterms cts
    set taxa_taxon_list_id=cttl.id,
      taxon_list_id=cttl.taxon_list_id,
      searchterm=taxon_abbreviation(cttl.taxon),
      original=cttl.taxon,
      taxon_group_id=cttl.taxon_group_id,
      taxon_group=cttl.taxon_group,
      taxon_meaning_id=cttl.taxon_meaning_id,
      preferred_taxon=cttl.preferred_taxon,
      default_common_name=cttl.default_common_name,
      preferred_authority=cttl.preferred_authority,
      language_iso=cttl.language_iso,
      name_type='A',
      simplified=null, 
      code_type_id=null,
      source_id=null
    from cache_taxa_taxon_lists cttl
    join needs_update_taxon_searchterms nuts on nuts.id=cttl.id
    where cts.taxa_taxon_list_id=cttl.id and cts.name_type='A' and cttl.language_iso='lat'";

$config['taxon_searchterms']['update']['simplified terms'] = "update cache_taxon_searchterms cts
    set taxa_taxon_list_id=cttl.id,
      taxon_list_id=cttl.taxon_list_id,
      searchterm=regexp_replace(regexp_replace(regexp_replace(lower(cttl.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
      original=cttl.taxon,
      taxon_group_id=cttl.taxon_group_id,
      taxon_group=cttl.taxon_group,
      taxon_meaning_id=cttl.taxon_meaning_id,
      preferred_taxon=cttl.preferred_taxon,
      default_common_name=cttl.default_common_name,
      preferred_authority=cttl.preferred_authority,
      language_iso=cttl.language_iso,
      name_type=case
        when cttl.language_iso='lat' and cttl.preferred_taxa_taxon_list_id=cttl.id then 'L' 
        when cttl.language_iso='lat' and cttl.preferred_taxa_taxon_list_id<>cttl.id then 'S' 
        else 'V'
      end,
      simplified=true,
      code_type_id=null,
      source_id=null
    from cache_taxa_taxon_lists cttl
    join needs_update_taxon_searchterms nuts on nuts.id=cttl.id
    where cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified=true";

$config['taxon_searchterms']['update']['codes'] = "update cache_taxon_searchterms cts
  set taxa_taxon_list_id=cttl.id,
    taxon_list_id=cttl.taxon_list_id,
      searchterm=tc.code,
      original=tc.code,
      taxon_group_id=cttl.taxon_group_id,
      taxon_group=cttl.taxon_group,
      taxon_meaning_id=cttl.taxon_meaning_id,
      preferred_taxon=cttl.preferred_taxon,
      default_common_name=cttl.default_common_name,
      preferred_authority=cttl.preferred_authority,
      language_iso=null,
      name_type='C',
      simplified=null,
      code_type_id=tc.code_type_id,
      source_id=tc.id
    from cache_taxa_taxon_lists cttl
    join needs_update_taxon_searchterms nuts on nuts.id=cttl.id
    join taxon_codes tc on tc.taxon_meaning_id=cttl.taxon_meaning_id 
    join termlists_terms tlttype on tlttype.id=tc.code_type_id
    join termlists_terms tltcategory on tltcategory.id=tlttype.parent_id
    join terms tcategory on tcategory.id=tltcategory.term_id and tcategory.term='searchable'
    where cttl.id=cttl.preferred_taxa_taxon_list_id and cts.taxa_taxon_list_id=cttl.id and cts.name_type = 'C' and cts.source_id=tc.id";

$config['taxon_searchterms']['insert']['standard terms']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, cttl.taxon, cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso, 
      case
        when cttl.language_iso='lat' and cttl.id=cttl.preferred_taxa_taxon_list_id then 'L' 
        when cttl.language_iso='lat' and cttl.id<>cttl.preferred_taxa_taxon_list_id then 'S' 
        else 'V'
      end, false, null
    from cache_taxa_taxon_lists cttl
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified='f'
    #insert_join_needs_update#
    where cts.taxa_taxon_list_id is null";

$config['taxon_searchterms']['insert']['abbreviations']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, taxon_abbreviation(cttl.taxon), cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso, 
      'A', null, null
    from cache_taxa_taxon_lists cttl
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='A'
    #insert_join_needs_update#
    where cts.taxa_taxon_list_id is null and cttl.language_iso='lat'";

$config['taxon_searchterms']['insert']['simplified terms']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, 
      regexp_replace(regexp_replace(regexp_replace(lower(cttl.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
      cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso, 
      case
        when cttl.language_iso='lat' and cttl.id=cttl.preferred_taxa_taxon_list_id then 'L' 
        when cttl.language_iso='lat' and cttl.id<>cttl.preferred_taxa_taxon_list_id then 'S' 
        else 'V'
      end, true, null
    from cache_taxa_taxon_lists cttl
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified=true
    #insert_join_needs_update#
    where cts.taxa_taxon_list_id is null";

$config['taxon_searchterms']['insert']['codes']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, source_id
    )
    select distinct on (tc.id) cttl.id, cttl.taxon_list_id, tc.code, tc.code, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, null, 'C', null, tc.code_type_id, tc.id
    from cache_taxa_taxon_lists cttl
    join taxon_codes tc on tc.taxon_meaning_id=cttl.taxon_meaning_id and tc.deleted=false
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='C' and cts.source_id=tc.id
    join termlists_terms tlttype on tlttype.id=tc.code_type_id and tlttype.deleted=false
    join termlists_terms tltcategory on tltcategory.id=tlttype.parent_id and tltcategory.deleted=false
    join terms tcategory on tcategory.id=tltcategory.term_id and tcategory.term='searchable' and tcategory.deleted=false
    #insert_join_needs_update#
    where cts.taxa_taxon_list_id is null";

$config['taxon_searchterms']['insert_join_needs_update']='join needs_update_taxon_searchterms nuts on nuts.id=cttl.preferred_taxa_taxon_list_id and nuts.deleted=false';
$config['taxon_searchterms']['insert_key_field']='cttl.preferred_taxa_taxon_list_id';

$config['taxon_searchterms']['count']='select sum(count) as count from (
    -- count of codes
    select count(*) as count from taxon_codes where deleted=false and code_type_id in (149 /*%searchable_terms%*/)
    union
    -- count of taxon names, * 2 to allow for simplified versions as well
    select count(*)*2 as count from taxa_taxon_lists where deleted=false
    union 
    -- additional count of latin names because these get abbreviations added
    select count(ttl.id) as count
    from taxa_taxon_lists ttl
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join languages l on l.id=t.language_id and l.iso=\'lat\'
  ) as countlist';

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
        else 'P'
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
  select distinct on (o.id) o.id, o.record_status, o.downloaded_flag, o.zero_abundance,
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
        else 'P'
    end,
    COALESCE(l.name, s.location_name)
  from occurrences o
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
  #insert_join_needs_update#
  where co.id is null";
  
  $config['occurrences']['insert_join_needs_update']='join needs_update_occurrences nuo on nuo.id=o.id and nuo.deleted=false';
  $config['occurrences']['insert_key_field']='o.id';

?>
