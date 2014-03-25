<?php
/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Modules
 * @subpackage Cache builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

$config['termlists_terms']['get_missing_items_query']="
    select distinct on (tlt.id) tlt.id, tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted as deleted
      from termlists tl
      join termlists_terms tlt on tlt.termlist_id=tl.id 
      join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred='t' 
      join terms t on t.id=tlt.term_id 
      join languages l on l.id=t.language_id 
      join terms tpref on tpref.id=tltpref.term_id 
      join languages lpref on lpref.id=tpref.language_id
      left join cache_termlists_terms ctlt on ctlt.id=tlt.id 
      left join needs_update_termlists_terms nu on nu.id=tlt.id 
      where ctlt.id is null and nu.id is null
      and (tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted) = false";
      
$config['termlists_terms']['get_changed_items_query']="
    select distinct on (tlt.id) tlt.id, tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted as deleted
      from termlists tl
      join termlists_terms tlt on tlt.termlist_id=tl.id 
      join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred='t' 
      join terms t on t.id=tlt.term_id 
      join languages l on l.id=t.language_id 
      join terms tpref on tpref.id=tltpref.term_id 
      join languages lpref on lpref.id=tpref.language_id
      where tl.updated_on>'#date#' 
      or tlt.updated_on>'#date#' 
      or tltpref.updated_on>'#date#' 
      or t.updated_on>'#date#' 
      or l.updated_on>'#date#' 
      or tpref.updated_on>'#date#' 
      or lpref.updated_on>'#date#' ";

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
    #join_needs_update#
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
    #join_needs_update#
    where ctlt.id is null";
    
$config['termlists_terms']['join_needs_update']='join needs_update_termlists_terms nu on nu.id=tlt.id';
$config['termlists_terms']['key_field']='tlt.id';

$config['taxa_taxon_lists']['get_missing_items_query']="
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
      left join cache_taxa_taxon_lists cttl on cttl.id=ttl.id 
      left join needs_update_taxa_taxon_lists nu on nu.id=ttl.id 
      where cttl.id is null and nu.id is null 
      and (tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted) = false ";
      
$config['taxa_taxon_lists']['get_changed_items_query']="
      select sub.id, cast(max(cast(deleted as int)) as boolean) as deleted 
      from (
      select ttl.id, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where ttl.updated_on>'#date#' or tl.updated_on>'#date#' or t.updated_on>'#date#' or l.updated_on>'#date#' 
        or tc.updated_on>'#date#' 
      union
      select ttl.id, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where ttlpref.updated_on>'#date#' or tpref.updated_on>'#date#' or lpref.updated_on>'#date#' or tg.updated_on>'#date#'      
      ) as sub
      group by id";
      
$config['taxa_taxon_lists']['delete_query']['taxa']="
  delete from cache_taxa_taxon_lists where id in (select id from needs_update_taxa_taxon_lists where deleted=true)";

$config['taxa_taxon_lists']['update'] = "update cache_taxa_taxon_lists cttl
    set preferred=ttl.preferred,
      taxon_list_id=tl.id, 
      taxon_list_title=tl.title,
      website_id=tl.website_id,
      preferred_taxa_taxon_list_id=ttlpref.id,
      parent_id=ttlpref.parent_id,
      taxonomic_sort_order=ttlpref.taxonomic_sort_order,
      taxon=t.taxon || coalesce(' ' || t.attribute, ''),
      authority=t.authority,
      language_iso=l.iso,
      language=l.language,
      preferred_taxon=tpref.taxon || coalesce(' ' || tpref.attribute, ''),
      preferred_authority=tpref.authority,
      preferred_language_iso=lpref.iso,
      preferred_language=lpref.language,
      default_common_name=tcommon.taxon,
      search_name=regexp_replace(regexp_replace(regexp_replace(lower(t.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
      external_key=tpref.external_key,
      taxon_meaning_id=ttlpref.taxon_meaning_id,
      taxon_group_id = tpref.taxon_group_id,
      taxon_group = tg.title,
      cache_updated_on=now(),
      allow_data_entry=ttl.allow_data_entry
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
    #join_needs_update#
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
      cache_created_on, cache_updated_on, allow_data_entry
    )
    select distinct on (ttl.id) ttl.id, ttl.preferred, 
      tl.id as taxon_list_id, tl.title as taxon_list_title, tl.website_id,
      ttlpref.id as preferred_taxa_taxon_list_id, ttlpref.parent_id, ttlpref.taxonomic_sort_order,
      t.taxon || coalesce(' ' || t.attribute, ''), t.authority,
      l.iso as language_iso, l.language,
      tpref.taxon || coalesce(' ' || tpref.attribute, '') as preferred_taxon, tpref.authority as preferred_authority, 
      lpref.iso as preferred_language_iso, lpref.language as preferred_language,
      tcommon.taxon as default_common_name,
      regexp_replace(regexp_replace(regexp_replace(lower(t.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
      tpref.external_key, ttlpref.taxon_meaning_id, tpref.taxon_group_id, tg.title,
      now(), now(), ttl.allow_data_entry
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
    #join_needs_update#
    where cttl.id is null";

$config['taxa_taxon_lists']['join_needs_update']='join needs_update_taxa_taxon_lists nu on nu.id=ttl.id';
$config['taxa_taxon_lists']['key_field']='ttl.id';

$config['taxa_taxon_lists']['extra_multi_record_updates']=array(
    // nullify the recorders field so it gets an update
    'Ranks' => "with recursive q as (
  select ttl1.id, ttl1.id as child_id, ttl1.taxon as child_taxon, ttl2.parent_id, ''::varchar as rank_taxon, ''::varchar as rank
  from cache_taxa_taxon_lists ttl1  
  join cache_taxa_taxon_lists ttl2 on ttl2.external_key=ttl1.external_key and ttl2.taxon_list_id=#master_list_id#
  join needs_update_taxa_taxon_lists nu on nu.id=ttl1.id
  union all
  select ttl.id, q.child_id, q.child_taxon, ttl.parent_id, t.taxon as rank_taxon, tr.rank
  from q
  join taxa_taxon_lists ttl on ttl.id=q.parent_id
  join taxa t on t.id=ttl.taxon_id and t.deleted=false
  join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false 
) select distinct * into temporary rankupdate from q;

update cache_taxa_taxon_lists cttl
set kingdom_taxa_taxon_list_id=ru.id, kingdom_taxon=rank_taxon
from rankupdate ru
where ru.child_id=cttl.id and ru.rank='Kingdom';

update cache_taxa_taxon_lists cttl
set order_taxa_taxon_list_id=ru.id, order_taxon=rank_taxon
from rankupdate ru
where ru.child_id=cttl.id and ru.rank='Order';

update cache_taxa_taxon_lists cttl
set family_taxa_taxon_list_id=ru.id, family_taxon=rank_taxon
from rankupdate ru
where ru.child_id=cttl.id and ru.rank='Family';

drop table rankupdate;");

$config['taxon_searchterms']['get_missing_items_query']="
    select distinct on (ttl.id) ttl.id, tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted as deleted
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id 
        and ttlpref.preferred='t' 
        and ttlpref.taxon_list_id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id 
      join languages l on l.id=t.language_id 
      join taxa tpref on tpref.id=ttlpref.taxon_id 
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      join languages lpref on lpref.id=tpref.language_id
      left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=ttl.id 
      left join needs_update_taxon_searchterms nu on nu.id=ttl.id 
      where cts.id is null and nu.id is null 
      and ttl.allow_data_entry=true
      and (tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted) = false";
      
$config['taxon_searchterms']['get_changed_items_query']="
      select sub.id, sub.allow_data_entry, cast(max(cast(deleted as int)) as boolean) as deleted       
      from (
      select ttl.id, ttl.allow_data_entry, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where ttl.updated_on>'#date#' or tl.updated_on>'#date#' or t.updated_on>'#date#' or l.updated_on>'#date#' 
        or tc.updated_on>'#date#' 
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where ttlpref.updated_on>'#date#' or tpref.updated_on>'#date#' or lpref.updated_on>'#date#' or tg.updated_on>'#date#'      
      ) as sub
      group by sub.id, sub.allow_data_entry";

$config['taxon_searchterms']['delete_query']['taxa']="
  delete from cache_taxon_searchterms where taxa_taxon_list_id in (select id from needs_update_taxon_searchterms where deleted=true or allow_data_entry=false)";

$config['taxon_searchterms']['delete_query']['codes']="
  delete from cache_taxon_searchterms where name_type='C' and source_id in (
    select tc.id from taxon_codes tc 
    join taxa_taxon_lists ttl on ttl.taxon_meaning_id=tc.taxon_meaning_id
    join needs_update_taxon_searchterms nu on nu.id = ttl.id
    where tc.deleted=true)";

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
      source_id=null,
      preferred=cttl.preferred,
      searchterm_length=length(cttl.taxon),
      parent_id=cttl.parent_id,
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
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
      source_id=null,
      preferred=cttl.preferred,
      searchterm_length=length(taxon_abbreviation(cttl.taxon)),
      parent_id=cttl.parent_id,
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
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
      source_id=null,
      preferred=cttl.preferred,
      searchterm_length=length(regexp_replace(regexp_replace(regexp_replace(lower(cttl.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g')),
      parent_id=cttl.parent_id,
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
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
      source_id=tc.id,
      preferred=cttl.preferred,
      searchterm_length=length(tc.code),
      parent_id=cttl.parent_id,
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    join taxon_codes tc on tc.taxon_meaning_id=cttl.taxon_meaning_id 
    join termlists_terms tlttype on tlttype.id=tc.code_type_id
    join termlists_terms tltcategory on tltcategory.id=tlttype.parent_id
    join terms tcategory on tcategory.id=tltcategory.term_id and tcategory.term='searchable'
    where cttl.id=cttl.preferred_taxa_taxon_list_id and cts.taxa_taxon_list_id=cttl.id and cts.name_type = 'C' and cts.source_id=tc.id";

$config['taxon_searchterms']['update']['id_diff'] = "update cache_taxon_searchterms cts
    set identification_difficulty=extkey.value::integer, id_diff_verification_rule_id=vr.id
      from cache_taxa_taxon_lists cttl
      #join_needs_update#
      join verification_rule_data extkey ON extkey.key=LOWER(cttl.external_key) AND extkey.header_name='Data' AND extkey.deleted=false
      join verification_rules vr ON vr.id=extkey.verification_rule_id AND vr.test_type='IdentificationDifficulty' AND vr.deleted=false
      where cttl.id=cts.taxa_taxon_list_id"; 

$config['taxon_searchterms']['insert']['standard terms']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, cttl.taxon, cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.preferred_authority, cttl.language_iso, 
      case
        when cttl.language_iso='lat' and cttl.id=cttl.preferred_taxa_taxon_list_id then 'L' 
        when cttl.language_iso='lat' and cttl.id<>cttl.preferred_taxa_taxon_list_id then 'S' 
        else 'V'
      end, false, null, cttl.preferred, length(cttl.taxon), cttl.parent_id, cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified='f'
    #join_needs_update#
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['abbreviations']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, taxon_abbreviation(cttl.taxon), cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso, 
      'A', null, null, cttl.preferred, length(taxon_abbreviation(cttl.taxon)), cttl.parent_id, cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    join taxa_taxon_lists ttlpref 
      on ttlpref.taxon_meaning_id=cttl.taxon_meaning_id 
      and ttlpref.preferred=true and 
      ttlpref.taxon_list_id=cttl.taxon_list_id
      and ttlpref.deleted=false
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='A'
    #join_needs_update#
    where cts.taxa_taxon_list_id is null and cttl.language_iso='lat' and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['simplified terms']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, 
      regexp_replace(regexp_replace(regexp_replace(lower(cttl.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
      cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso, 
      case
        when cttl.language_iso='lat' and cttl.id=cttl.preferred_taxa_taxon_list_id then 'L' 
        when cttl.language_iso='lat' and cttl.id<>cttl.preferred_taxa_taxon_list_id then 'S' 
        else 'V'
      end, true, null, cttl.preferred, 
      length(regexp_replace(regexp_replace(regexp_replace(lower(cttl.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g')),
      cttl.parent_id, cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified=true
    #join_needs_update#
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['codes']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, source_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id
    )
    select distinct on (tc.id) cttl.id, cttl.taxon_list_id, tc.code, tc.code, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, null, 'C', null, tc.code_type_id, tc.id, cttl.preferred, length(tc.code), 
      cttl.parent_id, cttl.preferred_taxa_taxon_list_id
    from cache_taxa_taxon_lists cttl
    join taxon_codes tc on tc.taxon_meaning_id=cttl.taxon_meaning_id and tc.deleted=false
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='C' and cts.source_id=tc.id
    join termlists_terms tlttype on tlttype.id=tc.code_type_id and tlttype.deleted=false
    join termlists_terms tltcategory on tltcategory.id=tlttype.parent_id and tltcategory.deleted=false
    join terms tcategory on tcategory.id=tltcategory.term_id and tcategory.term='searchable' and tcategory.deleted=false
    #join_needs_update#
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=false";

$config['taxon_searchterms']['insert']['id_diff'] = "update cache_taxon_searchterms cts
    set identification_difficulty=extkey.value::integer, id_diff_verification_rule_id=vr.id
      from cache_taxa_taxon_lists cttl
      #join_needs_update#
      join verification_rule_data extkey ON extkey.key=LOWER(cttl.external_key) AND extkey.header_name='Data' AND extkey.deleted=false
      join verification_rules vr ON vr.id=extkey.verification_rule_id AND vr.test_type='IdentificationDifficulty' AND vr.deleted=false
      where cttl.id=cts.taxa_taxon_list_id"; 

$config['taxon_searchterms']['join_needs_update']='join needs_update_taxon_searchterms nu on nu.id=cttl.id';
$config['taxon_searchterms']['key_field']='cttl.preferred_taxa_taxon_list_id';

$config['taxon_searchterms']['count']='
select sum(count) as count from (
select count(distinct(ttl.id))*2 as count
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=\'t\' 
      join taxa t on t.id=ttl.taxon_id 
      join languages l on l.id=t.language_id 
      join taxa tpref on tpref.id=ttlpref.taxon_id 
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      join languages lpref on lpref.id=tpref.language_id
      where 
      (tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
      or l.deleted or tpref.deleted or lpref.deleted) = false
union
select count(distinct(ttl.id))
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=\'t\' 
      join taxa t on t.id=ttl.taxon_id 
      join languages l on l.id=t.language_id and l.iso=\'lat\'
      join taxa tpref on tpref.id=ttlpref.taxon_id 
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      join languages lpref on lpref.id=tpref.language_id
      where 
      (tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
      or l.deleted or tpref.deleted or lpref.deleted) = false      
union
select count(distinct(ttl.id)) as count
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id and ttl.preferred=\'t\'
      join taxa t on t.id=ttl.taxon_id 
      join languages l on l.id=t.language_id       
      join taxon_groups tg on tg.id=t.taxon_group_id
      join taxon_codes tc on tc.id=ttl.taxon_meaning_id
      where 
      (tl.deleted or ttl.deleted or t.deleted or l.deleted ) = false
) as countlist
';

$config['occurrences']['get_missing_items_query'] = "
  select distinct o.id, o.deleted or s.deleted or su.deleted or (cttl.id is null) as deleted
    from occurrences o
    join samples s on s.id=o.sample_id 
    join surveys su on su.id=s.survey_id 
    left join cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id
    left join samples sp on sp.id=s.parent_id
    left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    left join cache_occurrences co on co.id=o.id 
    left join needs_update_occurrences nu on nu.id=o.id 
    where co.id is null and nu.id is null
    and (o.deleted or s.deleted or coalesce(sp.deleted, false) or su.deleted or (cttl.id is null)) = false";
    
$config['occurrences']['get_changed_items_query'] = "
  select sub.id, cast(max(cast(deleted as int)) as boolean) as deleted 
    from (select o.id, o.deleted 
    from occurrences o
    where o.updated_on>'#date#' 
    union
    select o.id, s.deleted 
    from occurrences o
    join samples s on s.id=o.sample_id
    where s.updated_on>'#date#' 
    union
    select o.id, sp.deleted 
    from occurrences o
    join samples s on s.id=o.sample_id
    join samples sp on sp.id=s.parent_id
    where sp.updated_on>'#date#' 
    union
    select o.id, su.deleted 
    from occurrences o
    join samples s on s.id=o.sample_id
    join surveys su on su.id=s.survey_id
    where su.updated_on>'#date#' 
    union
    select o.id, ttl.deleted
    from occurrences o
    join taxa_taxon_lists ttl on ttl.id=o.taxa_taxon_list_id
    where ttl.updated_on>'#date#' 
    union
    select o.id, false
    from occurrences o
    join samples s on s.id=o.sample_id
    join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    where tmethod.cache_updated_on>'#date#'
    union
    select o.id, false
    from occurrences o
    join occurrence_media om on om.occurrence_id=o.id
    where om.updated_on>'#date#' 
    ) as sub
    group by id ";

$config['occurrences']['update'] = "update cache_occurrences co
    set record_status=o.record_status, 
      release_status=o.release_status, 
      downloaded_flag=o.downloaded_flag, 
      zero_abundance=o.zero_abundance,
      website_id=su.website_id, 
      survey_id=su.id, 
      sample_id=s.id,
      survey_title=su.title,
      date_start=s.date_start, 
      date_end=s.date_end, 
      date_type=s.date_type,
      public_entered_sref=case when o.confidential=true or o.sensitivity_precision is not null then null else 
        case 
          when s.entered_sref_system = '4326' and coalesce(s.entered_sref, l.centroid_sref) ~ '^-?[0-9]*\.[0-9]*,[ ]*-?[0-9]*\.[0-9]*' then
            abs(round(((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[1])::numeric, 3))::varchar
            || case when ((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[1])::float>0 then 'N' else 'S' end
            || ', '
            || abs(round(((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[2])::numeric, 3))::varchar 
            || case when ((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[2])::float>0 then 'E' else 'W' end
          when s.entered_sref_system = '4326' and coalesce(s.entered_sref, l.centroid_sref) ~ '^-?[0-9]*\.[0-9]*[NS](, |[, ])*-?[0-9]*\.[0-9]*[EW]' then
            abs(round(((regexp_split_to_array(coalesce(s.entered_sref, l.centroid_sref), '([NS](, |[, ]))|[EW]'))[1])::numeric, 3))::varchar
            || case when coalesce(s.entered_sref, l.centroid_sref) like '%N%' then 'N' else 'S' end
            || ', '
            || abs(round(((regexp_split_to_array(coalesce(s.entered_sref, l.centroid_sref), '([NS](, |[, ]))|[EW]'))[2])::numeric, 3))::varchar 
            || case when coalesce(s.entered_sref, l.centroid_sref) like '%E%' then 'E' else 'W' end
        else 
          coalesce(s.entered_sref, l.centroid_sref) 
        end
      end,
      entered_sref_system=case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end,
      public_geom=reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, o.sensitivity_precision,
          case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end),
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
      location_name=case when o.confidential=true or o.sensitivity_precision is not null then null else coalesce(l.name, s.location_name) end,
      recorders = s.recorder_names,
      verifier = pv.surname || ', ' || pv.first_name,
      verified_on = o.verified_on,
      images=images.list,
      training=o.training,
      location_id=s.location_id,
      input_form=s.input_form,
      data_cleaner_info=case when o.last_verification_check_date is null then null else case sub.info when '' then 'pass' else sub.info end end,
      sensitivity_precision=o.sensitivity_precision
    from occurrences o
    #join_needs_update#
    join (
      select o.id, o.last_verification_check_date, 
        array_to_string(array_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}'),' ') as info
      from occurrences o
      #join_needs_update#
      left join occurrence_comments oc 
            on oc.occurrence_id=o.id 
            and oc.implies_manual_check_required=true 
            and oc.deleted=false
      group by o.id, o.last_verification_check_date
    ) sub on sub.id=o.id
    join samples s on s.id=o.sample_id and s.deleted=false
    left join locations l on l.id=s.location_id and l.deleted=false
    join surveys su on su.id=s.survey_id and su.deleted=false
    left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    join cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id
    left join (occurrence_attribute_values oav
      join termlists_terms certainty on certainty.id=oav.int_value
      join occurrence_attributes oa on oa.id=oav.occurrence_attribute_id and oa.deleted='f' and oa.system_function='certainty'
    ) on oav.occurrence_id=o.id and oav.deleted='f'
    left join users uv on uv.id=o.verified_by_id and uv.deleted=false
    left join people pv on pv.id=uv.person_id and pv.deleted=false
    left join (select occurrence_id, 
    array_to_string(array_agg(path), ',') as list
    from occurrence_media
    where deleted=false
    group by occurrence_id) as images on images.occurrence_id=o.id
    where co.id=o.id";

$config['occurrences']['insert']="insert into cache_occurrences (
      id, record_status, release_status, downloaded_flag, zero_abundance,
      website_id, survey_id, sample_id, survey_title,
      date_start, date_end, date_type,
      public_entered_sref, entered_sref_system, public_geom,
      sample_method, taxa_taxon_list_id, preferred_taxa_taxon_list_id, taxonomic_sort_order, 
      taxon, authority, preferred_taxon, preferred_authority, default_common_name, 
      search_name, taxa_taxon_list_external_key, taxon_meaning_id, taxon_group_id, taxon_group,
      created_by_id, cache_created_on, cache_updated_on, certainty, location_name, recorders, 
      verifier, verified_on, images, training, location_id, input_form, sensitivity_precision
    )
  select distinct on (o.id) o.id, o.record_status, o.release_status, o.downloaded_flag, o.zero_abundance,
    su.website_id as website_id, su.id as survey_id, s.id as sample_id, su.title as survey_title,
    s.date_start, s.date_end, s.date_type,
    case when o.confidential=true or o.sensitivity_precision is not null then null else 
      case 
        when s.entered_sref_system = '4326' and coalesce(s.entered_sref, l.centroid_sref) ~ '^-?[0-9]*\.[0-9]*,[ ]*-?[0-9]*\.[0-9]*' then
          abs(round(((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[1])::numeric, 3))::varchar
          || case when ((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[1])::float>0 then 'N' else 'S' end
          || ', '
          || abs(round(((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[2])::numeric, 3))::varchar 
          || case when ((string_to_array(coalesce(s.entered_sref, l.centroid_sref), ','))[2])::float>0 then 'E' else 'W' end
        when s.entered_sref_system = '4326' and coalesce(s.entered_sref, l.centroid_sref) ~ '^-?[0-9]*\.[0-9]*[NS](, |[, ])*-?[0-9]*\.[0-9]*[EW]' then
          abs(round(((regexp_split_to_array(coalesce(s.entered_sref, l.centroid_sref), '([NS](, |[, ]))|[EW]'))[1])::numeric, 3))::varchar
          || case when coalesce(s.entered_sref, l.centroid_sref) like '%N%' then 'N' else 'S' end
          || ', '
          || abs(round(((regexp_split_to_array(coalesce(s.entered_sref, l.centroid_sref), '([NS](, |[, ]))|[EW]'))[2])::numeric, 3))::varchar 
          || case when coalesce(s.entered_sref, l.centroid_sref) like '%E%' then 'E' else 'W' end
      else 
        coalesce(s.entered_sref, l.centroid_sref) 
      end
    end as public_entered_sref,
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end as entered_sref_system,
    reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, o.sensitivity_precision,
        case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end) as public_geom,
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
    case when o.confidential=true or o.sensitivity_precision is not null then null else coalesce(l.name, s.location_name) end,
    s.recorder_names,
    pv.surname || ', ' || pv.first_name,
    o.verified_on,
    images.list,
    o.training,
    s.location_id,
    s.input_form,
    o.sensitivity_precision
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
  left join users uv on uv.id=o.verified_by_id and uv.deleted=false
  left join people pv on pv.id=uv.person_id and pv.deleted=false
  left join (select occurrence_id, 
    array_to_string(array_agg(path), ',') as list
    from occurrence_media
    where deleted=false
    group by occurrence_id) as images on images.occurrence_id=o.id
  #join_needs_update#
  where co.id is null";
  
  $config['occurrences']['join_needs_update']='join needs_update_occurrences nu on nu.id=o.id';
  $config['occurrences']['key_field']='o.id';
  
  // Additional update statements to pick up the recorder name from various possible custom attribute places. Faster than 
  // loads of left joins. These should be in priority order - i.e. ones where we have recorded the inputter rather than
  // specifically the recorder should come after ones where we have recorded the recorder specifically.
  $config['occurrences']['extra_multi_record_updates']=array(
    // nullify the recorders field so it gets an update
    'Nullify recorders' => 'update cache_occurrences co
      set recorders=null
      from needs_update_occurrences nu
      where nu.id=co.id;',
    // Sample recorder names
    'Sample recorder names' => 'update cache_occurrences co
      set recorders=s.recorder_names
      from samples s, needs_update_occurrences nu
      where co.recorders is null and s.id=co.sample_id and s.deleted=false
      and nu.id=co.id;',
    // full recorder name
    'Full name' => 'update cache_occurrences co
      set recorders=sav.text_value
      from needs_update_occurrences nu, sample_attribute_values sav
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
      where co.recorders is null  
      and sav.sample_id=co.sample_id and sav.deleted=false and sav.text_value <> \', \' 
      and nu.id=co.id;',
    // surname, firstname
    'First name/surname' => 'update cache_occurrences co
      set recorders=sav.text_value || coalesce(\', \' || savf.text_value, \'\')
      from needs_update_occurrences nu, sample_attribute_values sav
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'last_name\' and sa.deleted=false
      left join (sample_attribute_values savf 
      join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = \'first_name\' and saf.deleted=false
      ) on savf.deleted=false
      where co.recorders is null and savf.sample_id=co.sample_id
      and sav.sample_id=co.sample_id and sav.deleted=false
      and nu.id=co.id;',
    // Sample recorder names in parent sample
    'Parent sample recorder names' => 'update cache_occurrences co
      set recorders=sp.recorder_names
      from needs_update_occurrences nu, samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      where co.recorders is null and s.id=co.sample_id and s.deleted=false
      and nu.id=co.id;',
    // full recorder name in parent sample
    'Parent full name' => 'update cache_occurrences co
      set recorders=sav.text_value
      from needs_update_occurrences nu, samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> \', \' 
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
      where co.recorders is null
      and s.id=co.sample_id and s.deleted=false
      and nu.id=co.id;',
    // firstname and surname in parent sample
    'Parent first name/surname' => 'update cache_occurrences co
      set recorders=coalesce(savf.text_value || \' \', \'\') || sav.text_value
      from needs_update_occurrences nu, samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'last_name\' and sa.deleted=false
      left join (sample_attribute_values savf 
      join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = \'first_name\' and saf.deleted=false
      ) on savf.deleted=false
      where co.recorders is null and savf.sample_id=sp.id
      and s.id=co.sample_id and s.deleted=false
      and nu.id=co.id;',
    // warehouse surname, first name
    'Warehouse surname, first name' => 'update cache_occurrences co
      set recorders=p.surname || coalesce(\', \' || p.first_name, \'\')
      from needs_update_occurrences nu, users u, people p
      where co.recorders is null and u.id=co.created_by_id and p.id=u.person_id and p.deleted=false
      and nu.id=co.id and u.id<>1;',
    // CMS username
    'CMS Username' => 'update cache_occurrences co
      set recorders=sav.text_value
      from needs_update_occurrences nu, sample_attribute_values sav
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
      where co.recorders is null and sav.sample_id=co.sample_id and sav.deleted=false
      and nu.id=co.id;',
    // CMS username in parent sample
    'Parent CMS Username' => 'update cache_occurrences co
      set recorders=sav.text_value
      from needs_update_occurrences nu, samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
      where co.recorders is null and s.id=co.sample_id and s.deleted=false
      and nu.id=co.id;',
    // warehouse username
    'Warehouse username' => 'update cache_occurrences co
      set recorders=case u.id when 1 then null else u.username end
      from needs_update_occurrences nu, users u
      where co.recorders is null and u.id=co.created_by_id
      and nu.id=co.id;'
  );
  
  // Final statements to pick up after an insert of a single record.
  $config['occurrences']['extra_single_record_updates']=array(
    // Sample recorder names
    'Sample recorder names' => "update cache_occurrences co
      set recorders=s.recorder_names
      from samples s
      where s.id=co.sample_id and s.deleted=false and s.recorder_names is not null and s.recorder_names<>''
      and co.id in (#ids#);",
    // Full recorder name
    'full name' => 'update cache_occurrences co
      set recorders=sav.text_value
      from sample_attribute_values sav 
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
      where sav.sample_id=co.sample_id and sav.deleted=false and sav.text_value <> \', \' 
      and co.id in (#ids#);',
    // surname, firstname
    'First name/surname' => 'update cache_occurrences co
      set recorders=sav.text_value || coalesce(\', \' || savf.text_value, \'\')
      from sample_attribute_values sav 
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'last_name\' and sa.deleted=false
      left join (sample_attribute_values savf 
      join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = \'first_name\' and saf.deleted=false
      ) on savf.deleted=false
      where savf.sample_id=co.sample_id
      and sav.sample_id=co.sample_id and sav.deleted=false
      and co.id in (#ids#);',    
    // Sample recorder names in parent sample
    'Parent sample recorder names' => "update cache_occurrences co
      set recorders=sp.recorder_names
      from samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      where s.id=co.sample_id and s.deleted=false and sp.recorder_names is not null and sp.recorder_names<>''
      and co.id in (#ids#);",
    // Full recorder name in parent sample
    'Parent full name' => 'update cache_occurrences co
      set recorders=sav.text_value
      from samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> \', \' 
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
      where s.id=co.sample_id and s.deleted=false
      and co.id in (#ids#);',
    // surname, firstname in parent sample
    'Parent first name/surname' => 'update cache_occurrences co
      set recorders=sav.text_value || coalesce(\', \' || savf.text_value, \'\')
      from samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'last_name\' and sa.deleted=false
      left join (sample_attribute_values savf 
      join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = \'first_name\' and saf.deleted=false
      ) on savf.deleted=false
      where savf.sample_id=sp.id
      and s.id=co.sample_id and s.deleted=false
      and co.id in (#ids#);',    
    // warehouse surname, firstname
    'Warehouse first name/surname' => 'update cache_occurrences co
      set recorders=p.surname || coalesce(\', \' || p.first_name, \'\')
      from users u
      join people p on p.id=u.person_id and p.deleted=false
      where u.id=co.created_by_id and u.id<>1
      and co.id in (#ids#);',
    // CMS username
    'CMS Username' => 'update cache_occurrences co
      set recorders=sav.text_value
      from sample_attribute_values sav
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
      where sav.sample_id=co.sample_id and sav.deleted=false
      and co.id in (#ids#);',
    // CMS username in parent sample
    'Parent CMS Username' => 'update cache_occurrences co
      set recorders=sav.text_value
      from samples s
      join samples sp on sp.id=s.parent_id and sp.deleted=false
      join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
      join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
      where s.id=co.sample_id and s.deleted=false
      and co.id in (#ids#);',
    'Warehouse username' => 'update cache_occurrences co
      set recorders=u.username
      from users u
      where u.id=co.created_by_id and u.id<>1
      and co.id in (#ids#);',
  );

?>
