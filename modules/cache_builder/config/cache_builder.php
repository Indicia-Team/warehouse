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
    #join_needs_update#
    left join cache_termlists_terms ctlt on ctlt.id=tlt.id
    join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred='t' 
    join terms t on t.id=tlt.term_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join terms tpref on tpref.id=tltpref.term_id 
    join languages lpref on lpref.id=tpref.language_id
    where ctlt.id is null";

$config['termlists_terms']['join_needs_update']='join needs_update_termlists_terms nu on nu.id=tlt.id and nu.deleted=false';
$config['termlists_terms']['key_field']='tlt.id';

$config['taxa_taxon_lists']['get_missing_items_query']="
    select distinct on (ttl.id) ttl.id, tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted 
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted as deleted
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' and ttlpref.taxon_list_id=ttl.taxon_list_id
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
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id and ttlpref.deleted=false
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
      allow_data_entry=ttl.allow_data_entry,
      marine_flag=t.marine_flag
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
    #join_needs_update#
    join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' and ttlpref.taxon_list_id=ttl.taxon_list_id and ttlpref.deleted=false
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join taxa tpref on tpref.id=ttlpref.taxon_id and tpref.deleted=false
    join taxon_groups tg on tg.id=tpref.taxon_group_id and tg.deleted=false
    join languages lpref on lpref.id=tpref.language_id and lpref.deleted=false
    left join taxa tcommon on tcommon.id=ttlpref.common_taxon_id and tcommon.deleted=false
    where cttl.id=ttl.id";

$config['taxa_taxon_lists']['insert']="insert into cache_taxa_taxon_lists (
      id, preferred, taxon_list_id, taxon_list_title, website_id,
      preferred_taxa_taxon_list_id, parent_id, taxonomic_sort_order,
      taxon, authority, language_iso, language, preferred_taxon, preferred_authority, 
      preferred_language_iso, preferred_language, default_common_name, search_name, external_key, 
      taxon_meaning_id, taxon_group_id, taxon_group,
      cache_created_on, cache_updated_on, allow_data_entry, marine_flag
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
      now(), now(), ttl.allow_data_entry, t.marine_flag
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id and ttl.deleted=false
    #join_needs_update#
    left join cache_taxa_taxon_lists cttl on cttl.id=ttl.id
    join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred='t' and ttlpref.taxon_list_id=ttl.taxon_list_id and ttlpref.deleted=false
    join taxa t on t.id=ttl.taxon_id and t.deleted=false and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false and l.deleted=false
    join taxa tpref on tpref.id=ttlpref.taxon_id and tpref.deleted=false
    join taxon_groups tg on tg.id=tpref.taxon_group_id and tg.deleted=false
    join languages lpref on lpref.id=tpref.language_id and lpref.deleted=false
    left join taxa tcommon on tcommon.id=ttlpref.common_taxon_id and tcommon.deleted=false
    where cttl.id is null and tl.deleted=false";

$config['taxa_taxon_lists']['join_needs_update']='join needs_update_taxa_taxon_lists nu on nu.id=ttl.id and nu.deleted=false';
$config['taxa_taxon_lists']['key_field']='ttl.id';

$config['taxa_taxon_lists']['extra_multi_record_updates']=array(
  'Ranks' => "
with recursive q as (
  select ttl1.id, ttl1.id as child_id, ttl1.taxon as child_taxon, ttl2.parent_id, 
      t.taxon as rank_taxon, tr.rank, tr.id as taxon_rank_id, tr.sort_order as taxon_rank_sort_order
  from cache_taxa_taxon_lists ttl1  
  join cache_taxa_taxon_lists ttl2 on ttl2.external_key=ttl1.external_key and ttl2.taxon_list_id=#master_list_id#
  join taxa_taxon_lists ttl2raw on ttl2raw.id=ttl2.id and ttl2raw.deleted=false
  join taxa t on t.id=ttl2raw.taxon_id and t.deleted=false and t.deleted=false
  join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false and tr.deleted=false
  join needs_update_taxa_taxon_lists nu on nu.id=ttl1.id
  union all
  select ttl.id, q.child_id, q.child_taxon, ttl.parent_id, t.taxon as rank_taxon, tr.rank, tr.id as taxon_rank_id, tr.sort_order as taxon_rank_sort_order
  from q
  join taxa_taxon_lists ttl on ttl.id=q.parent_id and ttl.deleted=false
  join taxa t on t.id=ttl.taxon_id and t.deleted=false and t.deleted=false
  join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false and tr.deleted=false
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

update cache_taxa_taxon_lists cttl
set taxon_rank_id=ru.taxon_rank_id, taxon_rank=ru.rank, taxon_rank_sort_order=ru.taxon_rank_sort_order
from rankupdate ru
where ru.id=cttl.id;

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
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id
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
      searchterm=cttl.taxon || coalesce(' ' || cttl.authority, ''),
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
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id,
      taxon_rank_sort_order=cttl.taxon_rank_sort_order,
      marine_flag=cttl.marine_flag,
      external_key=cttl.external_key,
      authority=cttl.authority
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
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id,
      taxon_rank_sort_order=cttl.taxon_rank_sort_order,
      marine_flag=cttl.marine_flag,
      external_key=cttl.external_key,
      authority=cttl.authority
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    where cts.taxa_taxon_list_id=cttl.id and cts.name_type='A' and cttl.language_iso='lat'";

$config['taxon_searchterms']['update']['simplified terms'] = "update cache_taxon_searchterms cts
    set taxa_taxon_list_id=cttl.id,
      taxon_list_id=cttl.taxon_list_id,
      searchterm=regexp_replace(regexp_replace(
          lower( regexp_replace(cttl.taxon, E'\\\\(.+\\\\)', '', 'g') || coalesce(cttl.authority, '') ), 'ae', 'e', 'g'
        ), E'[^a-z0-9\\\\?\\\\+]', '', 'g'),                 
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
      searchterm_length=length(regexp_replace(regexp_replace(
          lower( regexp_replace(cttl.taxon, E'\\\\(.+\\\\)', '', 'g') || coalesce(cttl.authority, '') ), 'ae', 'e', 'g'
        ), E'[^a-z0-9\\\\?\\\\+]', '', 'g')),
      parent_id=cttl.parent_id,
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id,
      marine_flag=cttl.marine_flag,
      external_key=cttl.external_key,
      authority=cttl.authority
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
      preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id,
      marine_flag=cttl.marine_flag,
      external_key=cttl.external_key,
      authority=cttl.authority
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
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id,
      marine_flag, external_key, authority
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, cttl.taxon || coalesce(' ' || cttl.authority, ''),
      cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, 
      cttl.preferred_taxon, cttl.default_common_name, cttl.preferred_authority, cttl.language_iso, 
      case
        when cttl.language_iso='lat' and cttl.id=cttl.preferred_taxa_taxon_list_id then 'L' 
        when cttl.language_iso='lat' and cttl.id<>cttl.preferred_taxa_taxon_list_id then 'S' 
        else 'V'
      end, false, null, cttl.preferred, length(cttl.taxon), cttl.parent_id, cttl.preferred_taxa_taxon_list_id,
      cttl.marine_flag, cttl.external_key, cttl.authority
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified='f'
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['abbreviations']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id,
      marine_flag, external_key, authority
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, taxon_abbreviation(cttl.taxon), cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso, 
      'A', null, null, cttl.preferred, length(taxon_abbreviation(cttl.taxon)), cttl.parent_id, cttl.preferred_taxa_taxon_list_id,
      cttl.marine_flag, cttl.external_key, cttl.authority
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    join taxa_taxon_lists ttlpref 
      on ttlpref.taxon_meaning_id=cttl.taxon_meaning_id 
      and ttlpref.preferred=true and 
      ttlpref.taxon_list_id=cttl.taxon_list_id
      and ttlpref.deleted=false
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='A'
    where cts.taxa_taxon_list_id is null and cttl.language_iso='lat' and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['simplified terms']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id,
      marine_flag, external_key, authority
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, 
      regexp_replace(lower(
          regexp_replace(regexp_replace(cttl.taxon, E'\\\\(.+\\\\)', '', 'g') || coalesce(cttl.authority, ''), 'ae', 'e', 'g')
        ), E'[^a-z0-9\\\\?\\\\+]', '', 'g'), 
      cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso, 
      case
        when cttl.language_iso='lat' and cttl.id=cttl.preferred_taxa_taxon_list_id then 'L' 
        when cttl.language_iso='lat' and cttl.id<>cttl.preferred_taxa_taxon_list_id then 'S' 
        else 'V'
      end, true, null, cttl.preferred, 
      length(regexp_replace(lower(
          regexp_replace(regexp_replace(cttl.taxon, E'\\\\(.+\\\\)', '', 'g') || coalesce(cttl.authority, ''), 'ae', 'e', 'g')
        ), E'[^a-z0-9\\\\?\\\\+]', '', 'g')),
      cttl.parent_id, cttl.preferred_taxa_taxon_list_id, cttl.marine_flag, cttl.external_key, cttl.authority
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified=true
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['codes']="insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, source_id, preferred, searchterm_length,
      parent_id, preferred_taxa_taxon_list_id, marine_flag, external_key, authority
    )
    select distinct on (tc.id) cttl.id, cttl.taxon_list_id, tc.code, tc.code, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, null, 'C', null, tc.code_type_id, tc.id, cttl.preferred, length(tc.code), 
      cttl.parent_id, cttl.preferred_taxa_taxon_list_id, cttl.marine_flag, cttl.external_key, cttl.authority
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    join taxon_codes tc on tc.taxon_meaning_id=cttl.taxon_meaning_id and tc.deleted=false
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='C' and cts.source_id=tc.id
    join termlists_terms tlttype on tlttype.id=tc.code_type_id and tlttype.deleted=false
    join termlists_terms tltcategory on tltcategory.id=tlttype.parent_id and tltcategory.deleted=false
    join terms tcategory on tcategory.id=tltcategory.term_id and tcategory.term='searchable' and tcategory.deleted=false
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=false";

$config['taxon_searchterms']['insert']['id_diff'] = "update cache_taxon_searchterms cts
    set identification_difficulty=extkey.value::integer, id_diff_verification_rule_id=vr.id
      from cache_taxa_taxon_lists cttl
      #join_needs_update#
      join verification_rule_data extkey ON extkey.key=LOWER(cttl.external_key) AND extkey.header_name='Data' AND extkey.deleted=false
      join verification_rules vr ON vr.id=extkey.verification_rule_id AND vr.test_type='IdentificationDifficulty' AND vr.deleted=false
      where cttl.id=cts.taxa_taxon_list_id";

$config['taxon_searchterms']['join_needs_update']='join needs_update_taxon_searchterms nu on nu.id=cttl.id and nu.deleted=false';
$config['taxon_searchterms']['key_field']='cttl.preferred_taxa_taxon_list_id';

$config['taxon_searchterms']['count']='
select sum(count) as count from (
select count(distinct(ttl.id))*2 as count
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id 
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=\'t\' and ttlpref.taxon_list_id=ttl.taxon_list_id
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
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=\'t\' and ttlpref.taxon_list_id=ttl.taxon_list_id
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
$config['samples']['get_missing_items_query'] = "
  select distinct s.id, s.deleted or su.deleted as deleted
    from samples s
    join surveys su on su.id=s.survey_id
    left join samples sp on sp.id=s.parent_id
    left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    left join cache_samples_functional cs on cs.id=s.id
    left join needs_update_samples nu on nu.id=s.id
    where s.id is null and nu.id is null
    and (s.deleted or coalesce(sp.deleted, false) or su.deleted) = false
";
$config['samples']['get_changed_items_query'] = "
  select sub.id, cast(max(cast(deleted as int)) as boolean) as deleted
    from (select s.id, s.deleted
    from samples s
    where s.updated_on>'#date#'
    union
    select s.id, sp.deleted
    from samples s
    join samples sp on sp.id=s.parent_id
    where sp.updated_on>'#date#'
    union
    select s.id, false
    from samples s
    join locations l on l.id=s.location_id
    where l.updated_on>'#date#'
    union
    select s.id, su.deleted
    from samples s
    join surveys su on su.id=s.survey_id
    where su.updated_on>'#date#'
    union
    select s.id, false
    from samples s
    join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    where tmethod.cache_updated_on>'#date#'
    ) as sub
    group by id
";
$config['samples']['update']['functional'] = "
UPDATE cache_samples_functional s_update
SET website_id=su.website_id,
  survey_id=s.survey_id,
  input_form=COALESCE(sp.input_form, s.input_form),
  location_id= s.location_id,
  location_name=CASE WHEN s.privacy_precision IS NOT NULL THEN NULL ELSE COALESCE(l.name, s.location_name, lp.name, sp.location_name) END,
  public_geom=reduce_precision(coalesce(s.geom, l.centroid_geom), false, s.privacy_precision,
        case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end),
  date_start=s.date_start,
  date_end=s.date_end,
  date_type=s.date_type,
  created_on=s.created_on,
  updated_on=s.updated_on,
  verified_on=s.verified_on,
  created_by_id=s.created_by_id,
  group_id=s.group_id,
  record_status=s.record_status,
  query=case
    when sc1.id is null then null
    when sc2.id is null and s.updated_on<=sc1.created_on then 'Q'
    else 'A'
  end
FROM samples s
#join_needs_update#
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN surveys su on su.id=s.survey_id and su.deleted=false
LEFT JOIN sample_comments sc1 ON sc1.sample_id=s.id AND sc1.deleted=false
    AND sc1.query=true AND (s.verified_on IS NULL OR sc1.created_on>s.verified_on)
LEFT JOIN sample_comments sc2 ON sc2.sample_id=s.id AND sc2.deleted=false
    AND sc2.query=false AND (s.verified_on IS NULL OR sc2.created_on>s.verified_on) AND sc2.id>sc1.id
WHERE s.id=s_update.id
";

$config['samples']['update']['functional_media'] = "
UPDATE cache_samples_functional u
SET media_count=(SELECT COUNT(sm.*)
FROM sample_media sm WHERE sm.sample_id=u.id AND sm.deleted=false)
FROM samples s
#join_needs_update#
WHERE s.id=u.id
";

$config['samples']['update']['functional_sensitive'] = "
UPDATE cache_samples_functional
SET location_id=null, location_name=null
FROM samples s
#join_needs_update#
JOIN occurrences o ON o.sample_id=s.id AND o.deleted=false AND o.sensitivity_precision IS NOT NULL
WHERE s.id=cache_samples_functional.id
";

$config['samples']['update']['nonfunctional'] = "
UPDATE cache_samples_nonfunctional
SET website_title=w.title,
  survey_title=su.title,
  group_title=g.title,
  public_entered_sref=case when s.privacy_precision is not null then null else
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
  recorders = s.recorder_names,
  comment=s.comment,
  privacy_precision=s.privacy_precision,
  licence_code=li.code,
  attr_email=CASE a_email.data_type
      WHEN 'T'::bpchar THEN v_email.text_value
      ELSE NULL::text
  END,
  attr_cms_user_id=CASE a_cms_user_id.data_type
      WHEN 'I'::bpchar THEN v_cms_user_id.int_value
      ELSE NULL::integer
  END,
  attr_cms_username=CASE a_cms_username.data_type
      WHEN 'T'::bpchar THEN v_cms_username.text_value
      ELSE NULL::text
  END,
  attr_first_name=CASE a_first_name.data_type
      WHEN 'T'::bpchar THEN v_first_name.text_value
      ELSE NULL::text
  END,
  attr_last_name=CASE a_last_name.data_type
      WHEN 'T'::bpchar THEN v_last_name.text_value
      ELSE NULL::text
  END,
  attr_full_name=CASE a_full_name.data_type
      WHEN 'T'::bpchar THEN v_full_name.text_value
      ELSE NULL::text
  END,
  attr_biotope=CASE a_biotope.data_type
      WHEN 'T'::bpchar THEN v_biotope.text_value
      WHEN 'L'::bpchar THEN t_biotope.term
      ELSE NULL::text
  END,
  attr_sref_precision=CASE a_sref_precision.data_type
      WHEN 'I'::bpchar THEN v_sref_precision.int_value::double precision
      WHEN 'F'::bpchar THEN v_sref_precision.float_value
      ELSE NULL::double precision
  END,
  attr_linked_location_id=v_linked_location_id.int_value
FROM samples s
#join_needs_update#
JOIN surveys su on su.id=s.survey_id and su.deleted=false
JOIN websites w on w.id=su.website_id and w.deleted=false
LEFT JOIN groups g on g.id=s.group_id and g.deleted=false
LEFT JOIN locations l on l.id=s.location_id and l.deleted=false
LEFT JOIN licences li on li.id=s.licence_id and li.deleted=false
LEFT JOIN (sample_attribute_values v_email
  JOIN sample_attributes a_email on a_email.id=v_email.sample_attribute_id and a_email.deleted=false and a_email.system_function='email'
  LEFT JOIN cache_termlists_terms t_email on a_email.data_type='L' and t_email.id=v_email.int_value
) on v_email.sample_id=s.id and v_email.deleted=false
LEFT JOIN (sample_attribute_values v_cms_user_id
  JOIN sample_attributes a_cms_user_id on a_cms_user_id.id=v_cms_user_id.sample_attribute_id and a_cms_user_id.deleted=false and a_cms_user_id.system_function='cms_user_id'
  LEFT JOIN cache_termlists_terms t_cms_user_id on a_cms_user_id.data_type='L' and t_cms_user_id.id=v_cms_user_id.int_value
) on v_cms_user_id.sample_id=s.id and v_cms_user_id.deleted=false
LEFT JOIN (sample_attribute_values v_cms_username
  JOIN sample_attributes a_cms_username on a_cms_username.id=v_cms_username.sample_attribute_id and a_cms_username.deleted=false and a_cms_username.system_function='cms_username'
  LEFT JOIN cache_termlists_terms t_cms_username on a_cms_username.data_type='L' and t_cms_username.id=v_cms_username.int_value
) on v_cms_username.sample_id=s.id and v_cms_username.deleted=false
LEFT JOIN (sample_attribute_values v_first_name
  JOIN sample_attributes a_first_name on a_first_name.id=v_first_name.sample_attribute_id and a_first_name.deleted=false and a_first_name.system_function='first_name'
  LEFT JOIN cache_termlists_terms t_first_name on a_first_name.data_type='L' and t_first_name.id=v_first_name.int_value
) on v_first_name.sample_id=s.id and v_first_name.deleted=false
LEFT JOIN (sample_attribute_values v_last_name
  JOIN sample_attributes a_last_name on a_last_name.id=v_last_name.sample_attribute_id and a_last_name.deleted=false and a_last_name.system_function='last_name'
  LEFT JOIN cache_termlists_terms t_last_name on a_last_name.data_type='L' and t_last_name.id=v_last_name.int_value
) on v_last_name.sample_id=s.id and v_last_name.deleted=false
LEFT JOIN (sample_attribute_values v_full_name
  JOIN sample_attributes a_full_name on a_full_name.id=v_full_name.sample_attribute_id and a_full_name.deleted=false and a_full_name.system_function='full_name'
  LEFT JOIN cache_termlists_terms t_full_name on a_full_name.data_type='L' and t_full_name.id=v_full_name.int_value
) on v_full_name.sample_id=s.id and v_full_name.deleted=false
LEFT JOIN (sample_attribute_values v_biotope
  JOIN sample_attributes a_biotope on a_biotope.id=v_biotope.sample_attribute_id and a_biotope.deleted=false and a_biotope.system_function='biotope'
  LEFT JOIN cache_termlists_terms t_biotope on a_biotope.data_type='L' and t_biotope.id=v_biotope.int_value
) on v_biotope.sample_id=s.id and v_biotope.deleted=false
LEFT JOIN (sample_attribute_values v_sref_precision
  JOIN sample_attributes a_sref_precision on a_sref_precision.id=v_sref_precision.sample_attribute_id and a_sref_precision.deleted=false and a_sref_precision.system_function='sref_precision'
  LEFT JOIN cache_termlists_terms t_sref_precision on a_sref_precision.data_type='L' and t_sref_precision.id=v_sref_precision.int_value
) on v_sref_precision.sample_id=s.id and v_sref_precision.deleted=false
LEFT JOIN (sample_attribute_values v_linked_location_id
  JOIN sample_attributes a_linked_location_id on a_linked_location_id.id=v_linked_location_id.sample_attribute_id 
    and a_linked_location_id.deleted=false and a_linked_location_id.system_function='linked_location_id'
) ON v_linked_location_id.sample_id=s.id and v_linked_location_id.deleted=false
WHERE s.id=cache_samples_nonfunctional.id
";

$config['samples']['update']['nonfunctional_media'] = "
UPDATE cache_samples_nonfunctional
SET media=(SELECT array_to_string(array_agg(sm.path), ',')
FROM sample_media sm WHERE sm.sample_id=s.id AND sm.deleted=false)
FROM samples s
#join_needs_update#
WHERE s.id=cache_samples_nonfunctional.id
";

$config['samples']['update']['nonfunctional_sensitive'] = "
UPDATE cache_samples_nonfunctional
SET public_entered_sref=null
FROM samples s
#join_needs_update#
JOIN occurrences o ON o.sample_id=s.id AND o.deleted=false AND o.sensitivity_precision IS NOT NULL
WHERE s.id=cache_samples_nonfunctional.id
";

$config['samples']['insert']['functional'] = "
INSERT INTO cache_samples_functional(
            id, website_id, survey_id, input_form, location_id, location_name,
            public_geom, date_start, date_end, date_type, created_on, updated_on, verified_on, created_by_id,
            group_id, record_status, query)
SELECT distinct on (s.id) s.id, su.website_id, s.survey_id, COALESCE(sp.input_form, s.input_form), s.location_id,
  CASE WHEN s.privacy_precision IS NOT NULL THEN NULL ELSE COALESCE(l.name, s.location_name, lp.name, sp.location_name) END,
  reduce_precision(coalesce(s.geom, l.centroid_geom), false, s.privacy_precision,
        case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end),
  s.date_start, s.date_end, s.date_type, s.created_on, s.updated_on, s.verified_on, s.created_by_id,
  s.group_id, s.record_status,
  case
    when sc1.id is null then null
    when sc2.id is null and s.updated_on<=sc1.created_on then 'Q'
    else 'A'
  end
FROM samples s
#join_needs_update#
LEFT JOIN cache_samples_functional cs on cs.id=s.id
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN surveys su on su.id=s.survey_id and su.deleted=false
LEFT JOIN sample_comments sc1 ON sc1.sample_id=s.id AND sc1.deleted=false
    AND sc1.query=true AND (s.verified_on IS NULL OR sc1.created_on>s.verified_on)
LEFT JOIN sample_comments sc2 ON sc2.sample_id=s.id AND sc2.deleted=false
    AND sc2.query=false AND (s.verified_on IS NULL OR sc2.created_on>s.verified_on) AND sc2.id>sc1.id
WHERE s.deleted=false
AND cs.id IS NULL
";

$config['samples']['insert']['functional_media'] = "
UPDATE cache_samples_functional u
SET media_count=(SELECT COUNT(sm.*)
FROM sample_media sm WHERE sm.sample_id=u.id AND sm.deleted=false)
FROM samples s
#join_needs_update#
WHERE s.id=u.id
";

$config['samples']['insert']['functional_sensitive'] = "
UPDATE cache_samples_functional
SET location_id=null, location_name=null
FROM samples s
#join_needs_update#
JOIN occurrences o ON o.sample_id=s.id AND o.deleted=false AND o.sensitivity_precision IS NOT NULL
WHERE s.id=cache_samples_functional.id
";

$config['samples']['insert']['nonfunctional'] = "
INSERT INTO cache_samples_nonfunctional(
            id, website_title, survey_title, group_title, public_entered_sref,
            entered_sref_system, recorders, comment, privacy_precision, licence_code)
SELECT distinct on (s.id) s.id, w.title, su.title, g.title,
  case when s.privacy_precision is not null then null else
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
  case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end,
  s.recorder_names, s.comment, s.privacy_precision, li.code
FROM samples s
#join_needs_update#
LEFT JOIN cache_samples_nonfunctional cs on cs.id=s.id
JOIN surveys su on su.id=s.survey_id and su.deleted=false
JOIN websites w on w.id=su.website_id and w.deleted=false
LEFT JOIN groups g on g.id=s.group_id and g.deleted=false
LEFT JOIN locations l on l.id=s.location_id and l.deleted=false
LEFT JOIN licences li on li.id=s.licence_id and li.deleted=false
WHERE s.deleted=false
AND cs.id IS NULL";


$config['samples']['insert']['nonfunctional_attrs'] = "
UPDATE cache_samples_nonfunctional
SET
  attr_email=CASE a_email.data_type
      WHEN 'T'::bpchar THEN v_email.text_value
      ELSE NULL::text
  END,
  attr_cms_user_id=CASE a_cms_user_id.data_type
      WHEN 'I'::bpchar THEN v_cms_user_id.int_value
      ELSE NULL::integer
  END,
  attr_cms_username=CASE a_cms_username.data_type
      WHEN 'T'::bpchar THEN v_cms_username.text_value
      ELSE NULL::text
  END,
  attr_first_name=CASE a_first_name.data_type
      WHEN 'T'::bpchar THEN v_first_name.text_value
      ELSE NULL::text
  END,
  attr_last_name=CASE a_last_name.data_type
      WHEN 'T'::bpchar THEN v_last_name.text_value
      ELSE NULL::text
  END,
  attr_full_name=CASE a_full_name.data_type
      WHEN 'T'::bpchar THEN v_full_name.text_value
      ELSE NULL::text
  END,
  attr_biotope=CASE a_biotope.data_type
      WHEN 'T'::bpchar THEN v_biotope.text_value
      WHEN 'L'::bpchar THEN t_biotope.term
      ELSE NULL::text
  END,
  attr_sref_precision=CASE a_sref_precision.data_type
      WHEN 'I'::bpchar THEN v_sref_precision.int_value::double precision
      WHEN 'F'::bpchar THEN v_sref_precision.float_value
      ELSE NULL::double precision
  END,
  attr_linked_location_id=v_linked_location_id.int_value
FROM samples s
#join_needs_update#
LEFT JOIN (sample_attribute_values v_email
  JOIN sample_attributes a_email on a_email.id=v_email.sample_attribute_id and a_email.deleted=false and a_email.system_function='email'
  LEFT JOIN cache_termlists_terms t_email on a_email.data_type='L' and t_email.id=v_email.int_value
) on v_email.sample_id=s.id and v_email.deleted=false
LEFT JOIN (sample_attribute_values v_cms_user_id
  JOIN sample_attributes a_cms_user_id on a_cms_user_id.id=v_cms_user_id.sample_attribute_id and a_cms_user_id.deleted=false and a_cms_user_id.system_function='cms_user_id'
  LEFT JOIN cache_termlists_terms t_cms_user_id on a_cms_user_id.data_type='L' and t_cms_user_id.id=v_cms_user_id.int_value
) on v_cms_user_id.sample_id=s.id and v_cms_user_id.deleted=false
LEFT JOIN (sample_attribute_values v_cms_username
  JOIN sample_attributes a_cms_username on a_cms_username.id=v_cms_username.sample_attribute_id and a_cms_username.deleted=false and a_cms_username.system_function='cms_username'
  LEFT JOIN cache_termlists_terms t_cms_username on a_cms_username.data_type='L' and t_cms_username.id=v_cms_username.int_value
) on v_cms_username.sample_id=s.id and v_cms_username.deleted=false
LEFT JOIN (sample_attribute_values v_first_name
  JOIN sample_attributes a_first_name on a_first_name.id=v_first_name.sample_attribute_id and a_first_name.deleted=false and a_first_name.system_function='first_name'
  LEFT JOIN cache_termlists_terms t_first_name on a_first_name.data_type='L' and t_first_name.id=v_first_name.int_value
) on v_first_name.sample_id=s.id and v_first_name.deleted=false
LEFT JOIN (sample_attribute_values v_last_name
  JOIN sample_attributes a_last_name on a_last_name.id=v_last_name.sample_attribute_id and a_last_name.deleted=false and a_last_name.system_function='last_name'
  LEFT JOIN cache_termlists_terms t_last_name on a_last_name.data_type='L' and t_last_name.id=v_last_name.int_value
) on v_last_name.sample_id=s.id and v_last_name.deleted=false
LEFT JOIN (sample_attribute_values v_full_name
  JOIN sample_attributes a_full_name on a_full_name.id=v_full_name.sample_attribute_id and a_full_name.deleted=false and a_full_name.system_function='full_name'
  LEFT JOIN cache_termlists_terms t_full_name on a_full_name.data_type='L' and t_full_name.id=v_full_name.int_value
) on v_full_name.sample_id=s.id and v_full_name.deleted=false
LEFT JOIN (sample_attribute_values v_biotope
  JOIN sample_attributes a_biotope on a_biotope.id=v_biotope.sample_attribute_id and a_biotope.deleted=false and a_biotope.system_function='biotope'
  LEFT JOIN cache_termlists_terms t_biotope on a_biotope.data_type='L' and t_biotope.id=v_biotope.int_value
) on v_biotope.sample_id=s.id and v_biotope.deleted=false
LEFT JOIN (sample_attribute_values v_sref_precision
  JOIN sample_attributes a_sref_precision on a_sref_precision.id=v_sref_precision.sample_attribute_id and a_sref_precision.deleted=false and a_sref_precision.system_function='sref_precision'
  LEFT JOIN cache_termlists_terms t_sref_precision on a_sref_precision.data_type='L' and t_sref_precision.id=v_sref_precision.int_value
) on v_sref_precision.sample_id=s.id and v_sref_precision.deleted=false
LEFT JOIN (sample_attribute_values v_linked_location_id
  JOIN sample_attributes a_linked_location_id on a_linked_location_id.id=v_linked_location_id.sample_attribute_id 
    and a_linked_location_id.deleted=false and a_linked_location_id.system_function='linked_location_id'
) ON v_linked_location_id.sample_id=s.id and v_linked_location_id.deleted=false
WHERE s.id=cache_samples_nonfunctional.id";

$config['samples']['insert']['nonfunctional_media'] = "
UPDATE cache_samples_nonfunctional
SET media=(SELECT array_to_string(array_agg(sm.path), ',')
FROM sample_media sm WHERE sm.sample_id=s.id AND sm.deleted=false)
FROM samples s
#join_needs_update#
WHERE s.id=cache_samples_nonfunctional.id
";

$config['samples']['insert']['nonfunctional_sensitive'] = "
UPDATE cache_samples_nonfunctional
SET public_entered_sref=null
FROM samples s
#join_needs_update#
JOIN occurrences o ON o.sample_id=s.id AND o.deleted=false AND o.sensitivity_precision IS NOT NULL
WHERE s.id=cache_samples_nonfunctional.id
";

$config['samples']['join_needs_update']='join needs_update_samples nu on nu.id=s.id and nu.deleted=false';
$config['samples']['key_field']='s.id';


// Additional update statements to pick up the recorder name from various possible custom attribute places. Faster than
// loads of left joins. These should be in priority order - i.e. ones where we have recorded the inputter rather than
// specifically the recorder should come after ones where we have recorded the recorder specifically.
$config['samples']['extra_multi_record_updates']=array(
  // s.recorder_names is filled in as a starting point. The rest only proceed if this is null.
  // full recorder name
  // or surname, firstname
  'Sample attrs' => "update cache_samples_nonfunctional cs
    set recorders = coalesce(
      nullif(cs.attr_full_name, ''),
      cs.attr_last_name || coalesce(', ' || cs.attr_first_name, '')
    )
    from needs_update_samples nu
    where cs.recorders is null and nu.id=cs.id
    and (
      nullif(cs.attr_full_name, '') is not null or
      nullif(cs.attr_last_name, '') is not null
    );",
  // Sample recorder names in parent sample
  'Parent sample recorder names' => 'update cache_samples_nonfunctional cs
    set recorders=sp.recorder_names
    from needs_update_samples nu, samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false and sp.recorder_names is not null
    where cs.recorders is null and nu.id=cs.id
    and s.id=cs.id and s.deleted=false;',
  // full recorder name in parent sample
  'Parent full name' => 'update cache_samples_nonfunctional cs
    set recorders=sav.text_value
    from needs_update_samples nu, samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> \', \'
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
    where cs.recorders is null and nu.id=cs.id
    and s.id=cs.id and s.deleted=false;',
  // firstname and surname in parent sample
  'Parent first name/surname' => 'update cache_samples_nonfunctional cs
    set recorders = coalesce(savf.text_value || \' \', \'\') || sav.text_value
    from needs_update_samples nu, samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'last_name\' and sa.deleted=false
    left join (sample_attribute_values savf
      join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = \'first_name\' and saf.deleted=false
    ) on savf.deleted=false
    where cs.recorders is null and nu.id=cs.id
    and savf.sample_id=sp.id and s.id=cs.id and s.deleted=false;',
  // warehouse surname, first name
  'Warehouse surname, first name' => 'update cache_samples_nonfunctional cs
    set recorders = p.surname || coalesce(\', \' || p.first_name, \'\')
    from needs_update_samples nu, people p, users u
    join cache_samples_functional csf on csf.created_by_id=u.id
    where cs.recorders is null and nu.id=cs.id
    and csf.id=cs.id and p.id=u.person_id and p.deleted=false
    and u.id<>1;',
  // CMS username
  'CMS Username' => 'update cache_samples_nonfunctional cs
    set recorders = sav.text_value
    from needs_update_samples nu, sample_attribute_values sav
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
    where cs.recorders is null and nu.id=cs.id
    and sav.sample_id=cs.id and sav.deleted=false;',
  // CMS username in parent sample
  'Parent CMS Username' => 'update cache_samples_nonfunctional cs
    set recorders = sav.text_value
    from needs_update_samples nu, samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
    where cs.recorders is null and nu.id=cs.id
    and s.id=cs.id and s.deleted=false;',
  // warehouse username
  'Warehouse username' => 'update cache_samples_nonfunctional cs
    set recorders=u.username
    from needs_update_samples nu, users u
    join cache_samples_functional csf on csf.created_by_id=u.id
    where cs.recorders is null and nu.id=cs.id
    and cs.id=csf.id and u.id<>1;'
);

// Final statements to pick up after an insert of a single record.
$config['samples']['extra_single_record_updates']=array(
  // Sample recorder names
  // Or, full recorder name
  // Or, surname, firstname
  'Sample recorder names or attrs' => "update cache_samples_nonfunctional cs
    set recorders=coalesce(
      nullif(s.recorder_names, ''),
      nullif(cs.attr_full_name, ''),
      cs.attr_last_name || coalesce(', ' || cs.attr_first_name, '')
    )
    from samples s
    where s.id=cs.id and s.deleted=false
    and (
      nullif(s.recorder_names, '') is not null or
      nullif(cs.attr_full_name, '') is not null or
      nullif(cs.attr_last_name, '') is not null
    )
    and cs.id in (#ids#);",
  // Sample recorder names in parent sample
  'Parent sample recorder names' => "update cache_samples_nonfunctional cs
    set recorders = sp.recorder_names
    from samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and s.id=cs.id and s.deleted=false and sp.recorder_names is not null and sp.recorder_names<>'';",
  // Full recorder name in parent sample
  'Parent full name' => 'update cache_samples_nonfunctional cs
    set recorders = sav.text_value
    from samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> \', \'
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and s.id=cs.id and s.deleted=false;',
  // surname, firstname in parent sample
  'Parent first name/surname' => 'update cache_samples_nonfunctional cs
    set recorders = sav.text_value || coalesce(\', \' || savf.text_value, \'\')
    from samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'last_name\' and sa.deleted=false
    left join (sample_attribute_values savf
    join sample_attributes saf on saf.id=savf.sample_attribute_id and saf.system_function = \'first_name\' and saf.deleted=false
    ) on savf.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and savf.sample_id=sp.id and s.id=cs.id and s.deleted=false;',
  // warehouse surname, firstname
  'Warehouse first name/surname' => 'update cache_samples_nonfunctional cs
    set recorders=p.surname || coalesce(\', \' || p.first_name, \'\')
    from users u
    join cache_samples_functional csf on csf.created_by_id=u.id
    join people p on p.id=u.person_id and p.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and cs.id=csf.id and u.id<>1;',
  // CMS username
  'CMS Username' => 'update cache_samples_nonfunctional cs
    set recorders=sav.text_value
    from sample_attribute_values sav
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and sav.sample_id=cs.id and sav.deleted=false;',
  // CMS username in parent sample
  'Parent CMS Username' => 'update cache_samples_nonfunctional cs
    set recorders=sav.text_value
    from samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and s.id=cs.id and s.deleted=false;',
  'Warehouse username' => 'update cache_samples_nonfunctional cs
    set recorders=u.username
    from users u
    join cache_samples_functional csf on csf.created_by_id=u.id
    where cs.recorders is null and cs.id in (#ids#)
    and cs.id=csf.id and u.id<>1;'
);

$config['occurrences']['get_missing_items_query'] = "
  select distinct o.id, o.deleted or s.deleted or su.deleted or (cttl.id is null) as deleted
    from occurrences o
    join samples s on s.id=o.sample_id 
    join surveys su on su.id=s.survey_id
    left join cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id
    left join samples sp on sp.id=s.parent_id
    left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    left join cache_occurrences_functional co on co.id=o.id
    left join needs_update_occurrences nu on nu.id=o.id
    where co.id is null and nu.id is null
    and (o.deleted or s.deleted or coalesce(sp.deleted, false) or su.deleted or (cttl.id is null)) = false";

$config['occurrences']['get_changed_items_query'] = "
  select sub.id, cast(max(cast(deleted as int)) as boolean) as deleted 
    from (
    -- don't pick up changes to occurrences at this point, as they are updated immediately
    -- but do pick up edits of samples as this could be done in isolation to the occurrences
    select o.id, s.deleted 
    from occurrences o
    join samples s on s.id=o.sample_id
    where s.updated_on>'#date#' and s.created_on<s.updated_on
    union
    select o.id, sp.deleted 
    from occurrences o
    join samples s on s.id=o.sample_id
    join samples sp on sp.id=s.parent_id
    where sp.updated_on>'#date#' and sp.created_on<sp.updated_on
    union
    select o.id, false
    from occurrences o
    join samples s on s.id=o.sample_id
    join locations l on l.id=s.location_id
    where l.updated_on>'#date#' 
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
    select om.occurrence_id, false
    from occurrence_media om
    where om.updated_on>'#date#' and om.created_on<om.updated_on
    union
    select oc.occurrence_id, false
    from occurrence_comments oc
    where oc.auto_generated=false and oc.updated_on>'#date#'
    ) as sub
    group by id";

$config['occurrences']['delete_query']=array("
delete from cache_occurrences_functional where id in (select id from needs_update_occurrences where deleted=true);
delete from cache_occurrences_nonfunctional where id in (select id from needs_update_occurrences where deleted=true);
");

$config['occurrences']['update']['functional'] = "
UPDATE cache_occurrences_functional
SET sample_id=o.sample_id,
  website_id=o.website_id,
  survey_id=s.survey_id,
  input_form=COALESCE(sp.input_form, s.input_form),
  location_id=s.location_id,
  location_name=case when o.confidential=true or o.sensitivity_precision is not null or s.privacy_precision is not null
      then null else coalesce(l.name, s.location_name, lp.name, sp.location_name) end,
  public_geom=reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
      case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end),
  date_start=s.date_start,
  date_end=s.date_end,
  date_type=s.date_type,
  created_on=o.created_on,
  updated_on=o.updated_on,
  verified_on=o.verified_on,
  created_by_id=o.created_by_id,
  group_id=s.group_id,
  taxa_taxon_list_id=o.taxa_taxon_list_id,
  preferred_taxa_taxon_list_id=cttl.preferred_taxa_taxon_list_id,
  taxon_meaning_id=cttl.taxon_meaning_id,
  taxa_taxon_list_external_key=cttl.external_key,
  family_taxa_taxon_list_id=cttl.family_taxa_taxon_list_id,
  taxon_group_id=cttl.taxon_group_id,
  taxon_rank_sort_order=cttl.taxon_rank_sort_order,
  record_status=o.record_status,
  record_substatus=o.record_substatus,
  certainty=case when certainty.sort_order is null then null
      when certainty.sort_order <100 then 'C'
      when certainty.sort_order <200 then 'L'
      else 'U'
  end,
  query=case
      when oc1.id is null or o.record_status in ('V','R') then null
      when oc2.id is null and o.updated_on<=oc1.created_on then 'Q'
      else 'A'
  end,
  sensitive=o.sensitivity_precision is not null,
  release_status=o.release_status,
  marine_flag=cttl.marine_flag,
  data_cleaner_result=case when o.last_verification_check_date is null then null else dc.id is null end,
  training=o.training,
  zero_abundance=o.zero_abundance,
  licence_id=s.licence_id,
  import_guid=o.import_guid,
  confidential=o.confidential
FROM occurrences o
#join_needs_update#
left join cache_occurrences_functional co on co.id=o.id
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
LEFT JOIN (occurrence_attribute_values oav
    JOIN termlists_terms certainty ON certainty.id=oav.int_value
    JOIN occurrence_attributes oa ON oa.id=oav.occurrence_attribute_id and oa.deleted='f' and oa.system_function='certainty'
  ) ON oav.occurrence_id=o.id AND oav.deleted='f'
LEFT JOIN occurrence_comments oc1 ON oc1.occurrence_id=o.id AND oc1.deleted=false AND oc1.auto_generated=false
    AND oc1.query=true AND (o.verified_on IS NULL OR oc1.created_on>o.verified_on)
LEFT JOIN occurrence_comments oc2 ON oc2.occurrence_id=o.id AND oc2.deleted=false AND oc2.auto_generated=false
    AND oc2.query=false AND (o.verified_on IS NULL OR oc2.created_on>o.verified_on) AND oc2.id>oc1.id
LEFT JOIN occurrence_comments dc
    ON dc.occurrence_id=o.id
    AND dc.implies_manual_check_required=true
    AND dc.deleted=false
WHERE cache_occurrences_functional.id=o.id
";

$config['occurrences']['update']['functional_media'] = "
UPDATE cache_occurrences_functional u
SET media_count=(SELECT COUNT(om.*)
FROM occurrence_media om WHERE om.occurrence_id=o.id AND om.deleted=false)
FROM occurrences o
#join_needs_update#
WHERE o.id=u.id
";

$config['occurrences']['update']['functional_sensitive'] = "
UPDATE cache_samples_functional cs
SET location_id=null, location_name=null
FROM occurrences o
#join_needs_update#
WHERE o.sample_id=cs.id
AND o.deleted=false
AND o.sensitivity_precision IS NOT NULL
";

$config['occurrences']['update']['nonfunctional'] = "
UPDATE cache_occurrences_nonfunctional
SET comment=o.comment,
  sensitivity_precision=o.sensitivity_precision,
  privacy_precision=s.privacy_precision,
  output_sref=get_output_sref(
      case when o.confidential=true or o.sensitivity_precision is not null or s.privacy_precision is not null then null else
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
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end,
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      o.sensitivity_precision,
      s.privacy_precision,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(spv.int_value, spv.float_value)>=501 then 10000
        when coalesce(spv.int_value, spv.float_value) between 51 and 500 then 1000
        when coalesce(spv.int_value, spv.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end)
  ),
  verifier=pv.surname || ', ' || pv.first_name,
  licence_code=li.code,
  attr_sex_stage=CASE a_sex_stage.data_type
      WHEN 'T'::bpchar THEN v_sex_stage.text_value
      WHEN 'L'::bpchar THEN t_sex_stage.term
      ELSE NULL::text
  END,
  attr_sex=CASE a_sex.data_type
      WHEN 'T'::bpchar THEN v_sex.text_value
      WHEN 'L'::bpchar THEN t_sex.term
      ELSE NULL::text
  END,
  attr_stage=CASE a_stage.data_type
      WHEN 'T'::bpchar THEN v_stage.text_value
      WHEN 'L'::bpchar THEN t_stage.term
      ELSE NULL::text
  END,
  attr_sex_stage_count=CASE a_sex_stage_count.data_type
      WHEN 'T'::bpchar THEN v_sex_stage_count.text_value
      WHEN 'L'::bpchar THEN t_sex_stage_count.term
      WHEN 'I'::bpchar THEN v_sex_stage_count.int_value::text
      WHEN 'F'::bpchar THEN v_sex_stage_count.float_value::text
      ELSE NULL::text
  END,
  attr_certainty=CASE a_certainty.data_type
      WHEN 'T'::bpchar THEN v_certainty.text_value
      WHEN 'L'::bpchar THEN t_certainty.term
      WHEN 'I'::bpchar THEN v_certainty.int_value::text
      WHEN 'B'::bpchar THEN v_certainty.int_value::text
      WHEN 'F'::bpchar THEN v_certainty.float_value::text
      ELSE NULL::text
  END,
  attr_det_first_name=CASE a_det_first_name.data_type
      WHEN 'T'::bpchar THEN v_det_first_name.text_value
      ELSE NULL::text
  END,
  attr_det_last_name=CASE a_det_last_name.data_type
      WHEN 'T'::bpchar THEN v_det_last_name.text_value
      ELSE NULL::text
  END,
  attr_det_full_name=CASE a_det_full_name.data_type
      WHEN 'T'::bpchar THEN v_det_full_name.text_value
      ELSE NULL::text
  END
FROM occurrences o
#join_needs_update#
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
LEFT JOIN users uv on uv.id=o.verified_by_id and uv.deleted=false
LEFT JOIN people pv on pv.id=uv.person_id and pv.deleted=false
LEFT JOIN licences li on li.id=s.licence_id
LEFT JOIN (sample_attribute_values spv
  JOIN sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
LEFT JOIN (occurrence_attribute_values v_sex_stage
  JOIN occurrence_attributes a_sex_stage on a_sex_stage.id=v_sex_stage.occurrence_attribute_id and a_sex_stage.deleted=false and a_sex_stage.system_function='sex_stage'
  LEFT JOIN cache_termlists_terms t_sex_stage on a_sex_stage.data_type='L' and t_sex_stage.id=v_sex_stage.int_value
) on v_sex_stage.occurrence_id=o.id and v_sex_stage.deleted=false
LEFT JOIN (occurrence_attribute_values v_sex
  JOIN occurrence_attributes a_sex on a_sex.id=v_sex.occurrence_attribute_id and a_sex.deleted=false and a_sex.system_function='sex'
  LEFT JOIN cache_termlists_terms t_sex on a_sex.data_type='L' and t_sex.id=v_sex.int_value
) on v_sex.occurrence_id=o.id and v_sex.deleted=false
LEFT JOIN (occurrence_attribute_values v_stage
  JOIN occurrence_attributes a_stage on a_stage.id=v_stage.occurrence_attribute_id and a_stage.deleted=false and a_stage.system_function='stage'
  LEFT JOIN cache_termlists_terms t_stage on a_stage.data_type='L' and t_stage.id=v_stage.int_value
) on v_stage.occurrence_id=o.id and v_stage.deleted=false
LEFT JOIN (occurrence_attribute_values v_sex_stage_count
  JOIN occurrence_attributes a_sex_stage_count on a_sex_stage_count.id=v_sex_stage_count.occurrence_attribute_id and a_sex_stage_count.deleted=false and a_sex_stage_count.system_function='sex_stage_count'
  LEFT JOIN cache_termlists_terms t_sex_stage_count on a_sex_stage_count.data_type='L' and t_sex_stage_count.id=v_sex_stage_count.int_value
) on v_sex_stage_count.occurrence_id=o.id and v_sex_stage_count.deleted=false
LEFT JOIN (occurrence_attribute_values v_certainty
  JOIN occurrence_attributes a_certainty on a_certainty.id=v_certainty.occurrence_attribute_id and a_certainty.deleted=false and a_certainty.system_function='certainty'
  LEFT JOIN cache_termlists_terms t_certainty on a_certainty.data_type='L' and t_certainty.id=v_certainty.int_value
) on v_certainty.occurrence_id=o.id and v_certainty.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_first_name
  JOIN occurrence_attributes a_det_first_name on a_det_first_name.id=v_det_first_name.occurrence_attribute_id and a_det_first_name.deleted=false and a_det_first_name.system_function='det_first_name'
  LEFT JOIN cache_termlists_terms t_det_first_name on a_det_first_name.data_type='L' and t_det_first_name.id=v_det_first_name.int_value
) on v_det_first_name.occurrence_id=o.id and v_det_first_name.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_last_name
  JOIN occurrence_attributes a_det_last_name on a_det_last_name.id=v_det_last_name.occurrence_attribute_id and a_det_last_name.deleted=false and a_det_last_name.system_function='det_last_name'
  LEFT JOIN cache_termlists_terms t_det_last_name on a_det_last_name.data_type='L' and t_det_last_name.id=v_det_last_name.int_value
) on v_det_last_name.occurrence_id=o.id and v_det_last_name.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_full_name
  JOIN occurrence_attributes a_det_full_name on a_det_full_name.id=v_det_full_name.occurrence_attribute_id and a_det_full_name.deleted=false and a_det_full_name.system_function='det_full_name'
  LEFT JOIN cache_termlists_terms t_det_full_name on a_det_full_name.data_type='L' and t_det_full_name.id=v_det_full_name.int_value
) on v_det_full_name.occurrence_id=o.id and v_det_full_name.deleted=false
WHERE cache_occurrences_nonfunctional.id=o.id
";

$config['occurrences']['update']['nonfunctional_media'] = "
UPDATE cache_occurrences_nonfunctional onf
SET media=(SELECT array_to_string(array_agg(om.path), ',')
FROM occurrence_media om WHERE om.occurrence_id=onf.id AND om.deleted=false)
FROM occurrences o
#join_needs_update#
WHERE o.id=onf.id
AND o.deleted=false
";

$config['occurrences']['update']['nonfunctional_data_cleaner_info'] = "
UPDATE cache_occurrences_nonfunctional onf
SET data_cleaner_info=
  CASE WHEN o.last_verification_check_date IS NULL THEN NULL ELSE
    COALESCE((SELECT array_to_string(array_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}'),' ')
      FROM occurrence_comments oc
      WHERE oc.occurrence_id=onf.id
         AND oc.implies_manual_check_required=true
         AND oc.deleted=false), 'pass') END
FROM occurrences o
#join_needs_update#
WHERE o.id=onf.id
AND o.deleted=false
";

$config['occurrences']['update']['nonfunctional_sensitive'] = "
UPDATE cache_samples_nonfunctional cs
SET public_entered_sref=null
FROM occurrences o
#join_needs_update#
WHERE o.sample_id=cs.id
AND o.deleted=false
AND o.sensitivity_precision IS NOT NULL
";

$config['occurrences']['insert']['functional'] = "INSERT INTO cache_occurrences_functional(
            id, sample_id, website_id, survey_id, input_form, location_id,
            location_name, public_geom,
            date_start, date_end, date_type, created_on, updated_on, verified_on,
            created_by_id, group_id, taxa_taxon_list_id, preferred_taxa_taxon_list_id,
            taxon_meaning_id, taxa_taxon_list_external_key, family_taxa_taxon_list_id,
            taxon_group_id, taxon_rank_sort_order, record_status, record_substatus,
            certainty, query, sensitive, release_status, marine_flag, data_cleaner_result,
            training, zero_abundance, licence_id, import_guid, confidential)
SELECT distinct on (o.id) o.id, o.sample_id, o.website_id, s.survey_id, COALESCE(sp.input_form, s.input_form), s.location_id,
    case when o.confidential=true or o.sensitivity_precision is not null or s.privacy_precision is not null
        then null else coalesce(l.name, s.location_name, lp.name, sp.location_name) end,
    reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
        case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end) as public_geom,
    s.date_start, s.date_end, s.date_type, o.created_on, o.updated_on, o.verified_on,
    o.created_by_id, s.group_id, o.taxa_taxon_list_id, cttl.preferred_taxa_taxon_list_id,
    cttl.taxon_meaning_id, cttl.external_key, cttl.family_taxa_taxon_list_id,
    cttl.taxon_group_id, cttl.taxon_rank_sort_order, o.record_status, o.record_substatus,
    case when certainty.sort_order is null then null
        when certainty.sort_order <100 then 'C'
        when certainty.sort_order <200 then 'L'
        else 'U'
    end,
    case
        when oc1.id is null or o.record_status in ('R','V') then null
        when oc2.id is null and o.updated_on<=oc1.created_on then 'Q'
        else 'A'
    end,
    o.sensitivity_precision is not null, o.release_status, cttl.marine_flag,
    case when o.last_verification_check_date is null then null else dc.id is null end,
    o.training, o.zero_abundance, s.licence_id, o.import_guid, o.confidential
FROM occurrences o
#join_needs_update#
LEFT JOIN cache_occurrences_functional co on co.id=o.id
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
LEFT JOIN (occurrence_attribute_values oav
    JOIN termlists_terms certainty ON certainty.id=oav.int_value
    JOIN occurrence_attributes oa ON oa.id=oav.occurrence_attribute_id and oa.deleted='f' and oa.system_function='certainty'
  ) ON oav.occurrence_id=o.id AND oav.deleted='f'
LEFT JOIN occurrence_comments oc1 ON oc1.occurrence_id=o.id AND oc1.deleted=false AND oc1.auto_generated=false
    AND oc1.query=true AND (o.verified_on IS NULL OR oc1.created_on>o.verified_on)
LEFT JOIN occurrence_comments oc2 ON oc2.occurrence_id=o.id AND oc2.deleted=false AND oc2.auto_generated=false
    AND oc2.query=false AND (o.verified_on IS NULL OR oc2.created_on>o.verified_on) AND oc2.id>oc1.id
LEFT JOIN occurrence_comments dc
    ON dc.occurrence_id=o.id
    AND dc.implies_manual_check_required=true
    AND dc.deleted=false
WHERE o.deleted=false
AND co.id IS NULL
";

$config['occurrences']['insert']['functional_media'] = "
UPDATE cache_occurrences_functional u
SET media_count=(SELECT COUNT(om.*)
FROM occurrence_media om WHERE om.occurrence_id=u.id AND om.deleted=false)
FROM occurrences o
#join_needs_update#
WHERE o.id=u.id
";

$config['occurrences']['insert']['functional_sensitive'] = "
UPDATE cache_samples_functional cs
SET location_id=null, location_name=null
FROM occurrences o
#join_needs_update#
WHERE o.sample_id=cs.id
AND o.deleted=false
AND o.sensitivity_precision IS NOT NULL
";

$config['occurrences']['insert']['nonfunctional'] = "
INSERT INTO cache_occurrences_nonfunctional(
            id, comment, sensitivity_precision, privacy_precision, output_sref, licence_code)
SELECT o.id,
  o.comment, o.sensitivity_precision,
  s.privacy_precision,
  get_output_sref(
      case when o.confidential=true or o.sensitivity_precision is not null or s.privacy_precision is not null then null else
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
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end,
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      o.sensitivity_precision,
      s.privacy_precision,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(spv.int_value, spv.float_value)>=501 then 10000
        when coalesce(spv.int_value, spv.float_value) between 51 and 500 then 1000
        when coalesce(spv.int_value, spv.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end)
  ),
  li.code
FROM occurrences o
#join_needs_update#
LEFT JOIN cache_occurrences_nonfunctional co ON co.id=o.id
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
LEFT JOIN licences li on li.id=s.licence_id
LEFT JOIN (sample_attribute_values spv
  JOIN sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
WHERE o.deleted=false
AND co.id IS NULL
";

$config['occurrences']['insert']['nonfunctional_attrs'] = "
UPDATE cache_occurrences_nonfunctional
SET
  attr_sex_stage=CASE a_sex_stage.data_type
      WHEN 'T'::bpchar THEN v_sex_stage.text_value
      WHEN 'L'::bpchar THEN t_sex_stage.term
      ELSE NULL::text
  END,
  attr_sex=CASE a_sex.data_type
      WHEN 'T'::bpchar THEN v_sex.text_value
      WHEN 'L'::bpchar THEN t_sex.term
      ELSE NULL::text
  END,
  attr_stage=CASE a_stage.data_type
      WHEN 'T'::bpchar THEN v_stage.text_value
      WHEN 'L'::bpchar THEN t_stage.term
      ELSE NULL::text
  END,
  attr_sex_stage_count=CASE a_sex_stage_count.data_type
      WHEN 'T'::bpchar THEN v_sex_stage_count.text_value
      WHEN 'L'::bpchar THEN t_sex_stage_count.term
      WHEN 'I'::bpchar THEN v_sex_stage_count.int_value::text
      WHEN 'F'::bpchar THEN v_sex_stage_count.float_value::text
      ELSE NULL::text
  END,
  attr_certainty=CASE a_certainty.data_type
      WHEN 'T'::bpchar THEN v_certainty.text_value
      WHEN 'L'::bpchar THEN t_certainty.term
      WHEN 'I'::bpchar THEN v_certainty.int_value::text
      WHEN 'B'::bpchar THEN v_certainty.int_value::text
      WHEN 'F'::bpchar THEN v_certainty.float_value::text
      ELSE NULL::text
  END,
  attr_det_first_name=CASE a_det_first_name.data_type
      WHEN 'T'::bpchar THEN v_det_first_name.text_value
      ELSE NULL::text
  END,
  attr_det_last_name=CASE a_det_last_name.data_type
      WHEN 'T'::bpchar THEN v_det_last_name.text_value
      ELSE NULL::text
  END,
  attr_det_full_name=CASE a_det_full_name.data_type
      WHEN 'T'::bpchar THEN v_det_full_name.text_value
      ELSE NULL::text
  END
FROM occurrences o
#join_needs_update#
LEFT JOIN (occurrence_attribute_values v_sex_stage
  JOIN occurrence_attributes a_sex_stage on a_sex_stage.id=v_sex_stage.occurrence_attribute_id and a_sex_stage.deleted=false and a_sex_stage.system_function='sex_stage'
  LEFT JOIN cache_termlists_terms t_sex_stage on a_sex_stage.data_type='L' and t_sex_stage.id=v_sex_stage.int_value
) on v_sex_stage.occurrence_id=o.id and v_sex_stage.deleted=false
LEFT JOIN (occurrence_attribute_values v_sex
  JOIN occurrence_attributes a_sex on a_sex.id=v_sex.occurrence_attribute_id and a_sex.deleted=false and a_sex.system_function='sex'
  LEFT JOIN cache_termlists_terms t_sex on a_sex.data_type='L' and t_sex.id=v_sex.int_value
) on v_sex.occurrence_id=o.id and v_sex.deleted=false
LEFT JOIN (occurrence_attribute_values v_stage
  JOIN occurrence_attributes a_stage on a_stage.id=v_stage.occurrence_attribute_id and a_stage.deleted=false and a_stage.system_function='stage'
  LEFT JOIN cache_termlists_terms t_stage on a_stage.data_type='L' and t_stage.id=v_stage.int_value
) on v_stage.occurrence_id=o.id and v_stage.deleted=false
LEFT JOIN (occurrence_attribute_values v_sex_stage_count
  JOIN occurrence_attributes a_sex_stage_count on a_sex_stage_count.id=v_sex_stage_count.occurrence_attribute_id and a_sex_stage_count.deleted=false and a_sex_stage_count.system_function='sex_stage_count'
  LEFT JOIN cache_termlists_terms t_sex_stage_count on a_sex_stage_count.data_type='L' and t_sex_stage_count.id=v_sex_stage_count.int_value
) on v_sex_stage_count.occurrence_id=o.id and v_sex_stage_count.deleted=false
LEFT JOIN (occurrence_attribute_values v_certainty
  JOIN occurrence_attributes a_certainty on a_certainty.id=v_certainty.occurrence_attribute_id and a_certainty.deleted=false and a_certainty.system_function='certainty'
  LEFT JOIN cache_termlists_terms t_certainty on a_certainty.data_type='L' and t_certainty.id=v_certainty.int_value
) on v_certainty.occurrence_id=o.id and v_certainty.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_first_name
  JOIN occurrence_attributes a_det_first_name on a_det_first_name.id=v_det_first_name.occurrence_attribute_id and a_det_first_name.deleted=false and a_det_first_name.system_function='det_first_name'
  LEFT JOIN cache_termlists_terms t_det_first_name on a_det_first_name.data_type='L' and t_det_first_name.id=v_det_first_name.int_value
) on v_det_first_name.occurrence_id=o.id and v_det_first_name.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_last_name
  JOIN occurrence_attributes a_det_last_name on a_det_last_name.id=v_det_last_name.occurrence_attribute_id and a_det_last_name.deleted=false and a_det_last_name.system_function='det_last_name'
  LEFT JOIN cache_termlists_terms t_det_last_name on a_det_last_name.data_type='L' and t_det_last_name.id=v_det_last_name.int_value
) on v_det_last_name.occurrence_id=o.id and v_det_last_name.deleted=false
LEFT JOIN (occurrence_attribute_values v_det_full_name
  JOIN occurrence_attributes a_det_full_name on a_det_full_name.id=v_det_full_name.occurrence_attribute_id and a_det_full_name.deleted=false and a_det_full_name.system_function='det_full_name'
  LEFT JOIN cache_termlists_terms t_det_full_name on a_det_full_name.data_type='L' and t_det_full_name.id=v_det_full_name.int_value
) on v_det_full_name.occurrence_id=o.id and v_det_full_name.deleted=false
WHERE cache_occurrences_nonfunctional.id=o.id
";

$config['occurrences']['insert']['nonfunctional_media'] = "
UPDATE cache_occurrences_nonfunctional onf
SET media=(SELECT array_to_string(array_agg(om.path), ',')
FROM occurrence_media om WHERE om.occurrence_id=onf.id AND om.deleted=false)
FROM occurrences o
#join_needs_update#
WHERE o.id=onf.id
AND o.deleted=false
";

$config['occurrences']['insert']['nonfunctional_data_cleaner_info'] = "
UPDATE cache_occurrences_nonfunctional onf
SET data_cleaner_info=
  CASE WHEN o.last_verification_check_date IS NULL THEN NULL ELSE
    COALESCE((SELECT array_to_string(array_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}'),' ')
      FROM occurrence_comments oc
      WHERE oc.occurrence_id=onf.id
         AND oc.implies_manual_check_required=true
         AND oc.deleted=false), 'pass') END
FROM occurrences o
#join_needs_update#
WHERE o.id=onf.id
AND o.deleted=false
";

$config['occurrences']['insert']['nonfunctional_sensitive'] = "
UPDATE cache_samples_nonfunctional cs
SET public_entered_sref=null
FROM occurrences o
#join_needs_update#
WHERE o.sample_id=cs.id
AND o.deleted=false
AND o.sensitivity_precision IS NOT NULL
";

$config['occurrences']['join_needs_update']='join needs_update_occurrences nu on nu.id=o.id and nu.deleted=false';
$config['occurrences']['key_field']='o.id';