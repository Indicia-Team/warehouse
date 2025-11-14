<?php

/**
 * @file
 * Configuration for cache table building queries.
 *
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

$config['termlists_terms']['get_missing_items_query'] = "
    select distinct on (tlt.id) tlt.id, tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted as deleted
      from termlists tl
      join termlists_terms tlt on tlt.termlist_id=tl.id
      join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred=true
      join terms t on t.id=tlt.term_id
      join languages l on l.id=t.language_id
      join terms tpref on tpref.id=tltpref.term_id
      join languages lpref on lpref.id=tpref.language_id
      left join cache_termlists_terms ctlt on ctlt.id=tlt.id
      left join needs_update_termlists_terms nu on nu.id=tlt.id
      where ctlt.id is null and nu.id is null
      and (tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted) = false";

$config['termlists_terms']['get_changed_items_query'] = "
    select distinct on (tlt.id) tlt.id, tl.deleted or tlt.deleted or tltpref.deleted or t.deleted or l.deleted or tpref.deleted or lpref.deleted as deleted
      from termlists tl
      join termlists_terms tlt on tlt.termlist_id=tl.id
      join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred=true
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
      preferred_image_path=tltpref.image_path,
      cache_updated_on=now(),
      allow_data_entry=tlt.allow_data_entry
    from termlists tl
    join termlists_terms tlt on tlt.termlist_id=tl.id
    #join_needs_update#
    join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred=true
    join terms t on t.id=tlt.term_id
    join languages l on l.id=t.language_id
    join terms tpref on tpref.id=tltpref.term_id
    join languages lpref on lpref.id=tpref.language_id
    where ctlt.id=tlt.id";

$config['termlists_terms']['insert'] = "insert into cache_termlists_terms (
      id, preferred, termlist_id, termlist_title, website_id,
      preferred_termlists_term_id, parent_id, sort_order,
      term, language_iso, language, preferred_term, preferred_language_iso, preferred_language, meaning_id,
      preferred_image_path, cache_created_on, cache_updated_on, allow_data_entry
    )
    select distinct on (tlt.id) tlt.id, tlt.preferred,
      tl.id as termlist_id, tl.title as termlist_title, tl.website_id,
      tltpref.id as preferred_termlists_term_id, tltpref.parent_id, tltpref.sort_order,
      t.term,
      l.iso as language_iso, l.language,
      tpref.term as preferred_term,
      lpref.iso as preferred_language_iso, lpref.language as preferred_language, tltpref.meaning_id,
      tltpref.image_path, now(), now(), tlt.allow_data_entry
    from termlists tl
    join termlists_terms tlt on tlt.termlist_id=tl.id
    #join_needs_update#
    left join cache_termlists_terms ctlt on ctlt.id=tlt.id
    join termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.preferred=true
    join terms t on t.id=tlt.term_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join terms tpref on tpref.id=tltpref.term_id
    join languages lpref on lpref.id=tpref.language_id
    where ctlt.id is null";

$config['termlists_terms']['join_needs_update'] = 'join needs_update_termlists_terms nu on nu.id=tlt.id and nu.deleted=false';
$config['termlists_terms']['key_field'] = 'tlt.id';

//-----------------------------------------------------------------------------

$config['taxa_taxon_lists']['get_missing_items_query'] = "
    select distinct on (ttl.id) ttl.id, tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted as deleted
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      join languages lpref on lpref.id=tpref.language_id
      left join cache_taxa_taxon_lists cttl on cttl.id=ttl.id and cttl.cache_updated_on > ttl.updated_on and cttl.cache_updated_on > ttlpref.updated_on
      left join needs_update_taxa_taxon_lists nu on nu.id=ttl.id
      where cttl.id is null and nu.id is null
      and (tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted
        or l.deleted or tpref.deleted or tg.deleted or lpref.deleted) = false ";

$config['taxa_taxon_lists']['get_changed_items_query'] = "
      select sub.id, cast(max(cast(deleted as int)) as boolean) as deleted
      from (
      select ttl.id, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      where ttl.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      where tl.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      where t.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      where l.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where tc.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id and ttlpref.deleted=false
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where ttlpref.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id and ttlpref.deleted=false
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where tpref.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id and ttlpref.deleted=false
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where lpref.updated_on>'#date#'
      union
      select ttl.id, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id and ttlpref.deleted=false
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where tg.updated_on>'#date#'
      ) as sub
      group by id";

$config['taxa_taxon_lists']['delete_query']['taxa'] = "
  delete from cache_taxa_taxon_lists where id in (select id from needs_update_taxa_taxon_lists where deleted=true)";

$config['taxa_taxon_lists']['delete_query']['paths'] = "
  delete from cache_taxon_paths ctp
  using needs_update_taxa_taxon_lists nu
  join taxa_taxon_lists ttl on ttl.id=nu.id and ttl.preferred=true
  where nu.deleted=true
  and ttl.taxon_meaning_id=ctp.taxon_meaning_id and ttl.taxon_list_id=ctp.taxon_list_id";

$config['taxa_taxon_lists']['update'] = "update cache_taxa_taxon_lists cttl
    set preferred=ttl.preferred,
      taxon_list_id=tl.id,
      taxon_list_title=tl.title,
      website_id=tl.website_id,
      preferred_taxa_taxon_list_id=coalesce(ttlpref.id, ttlprefredundant.id),
      parent_id=coalesce(ttlpref.parent_id, ttlprefredundant.parent_id),
      taxonomic_sort_order=coalesce(ttlpref.taxonomic_sort_order, ttlprefredundant.taxonomic_sort_order),
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
      organism_key=tpref.organism_key,
      taxon_meaning_id=coalesce(ttlpref.taxon_meaning_id, ttlprefredundant.taxon_meaning_id),
      taxon_group_id = tpref.taxon_group_id,
      taxon_group = tg.title,
      taxon_rank_id = tr.id,
      taxon_rank = tr.rank,
      taxon_rank_sort_order = tr.sort_order,
      cache_updated_on=now(),
      allow_data_entry=ttl.allow_data_entry,
      marine_flag=t.marine_flag,
      freshwater_flag=t.freshwater_flag,
      terrestrial_flag=t.terrestrial_flag,
      non_native_flag=t.non_native_flag,
      taxon_id=t.id,
      search_code=t.search_code
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id
    #join_needs_update#
    -- Select the preferred name which isn't redundant (allow_data_entry=true)
    left join taxa_taxon_lists ttlpref
      on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id
      and ttlpref.preferred=true
      and ttlpref.taxon_list_id=ttl.taxon_list_id
      and ttlpref.deleted=false
      and ttlpref.allow_data_entry=true
    -- A fallback preferred name which is redundant (allow_data_entry=false)
    left join taxa_taxon_lists ttlprefredundant
      on ttlprefredundant.taxon_meaning_id=ttl.taxon_meaning_id
      and ttlprefredundant.preferred=true
      and ttlprefredundant.taxon_list_id=ttl.taxon_list_id
      and ttlprefredundant.deleted=false
      and ttlprefredundant.allow_data_entry=false
    join taxa t on t.id=ttl.taxon_id and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false
    join taxa tpref on tpref.id=coalesce(ttlpref.taxon_id, ttlprefredundant.taxon_id) and tpref.deleted=false
    join taxon_groups tg on tg.id=tpref.taxon_group_id and tg.deleted=false
    left join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false
    join languages lpref on lpref.id=tpref.language_id and lpref.deleted=false
    left join taxa tcommon on tcommon.id=coalesce(ttlpref.common_taxon_id, ttlprefredundant.common_taxon_id) and tcommon.deleted=false
    where cttl.id=ttl.id";

$config['taxa_taxon_lists']['insert'] = "insert into cache_taxa_taxon_lists (
      id, preferred, taxon_list_id, taxon_list_title, website_id,
      preferred_taxa_taxon_list_id, parent_id, taxonomic_sort_order,
      taxon, authority, language_iso, language, preferred_taxon, preferred_authority,
      preferred_language_iso, preferred_language, default_common_name, search_name,
      external_key, organism_key, taxon_meaning_id, taxon_group_id, taxon_group,
      taxon_rank_id, taxon_rank, taxon_rank_sort_order,
      cache_created_on, cache_updated_on, allow_data_entry,
      marine_flag, freshwater_flag, terrestrial_flag, non_native_flag,
      taxon_id, search_code
    )
    select distinct on (ttl.id) ttl.id, ttl.preferred,
      tl.id as taxon_list_id, tl.title as taxon_list_title, tl.website_id,
      coalesce(ttlpref.id, ttlprefredundant.id) as preferred_taxa_taxon_list_id,
      coalesce(ttlpref.parent_id, ttlprefredundant.parent_id) as parent_id,
      coalesce(ttlpref.taxonomic_sort_order, ttlprefredundant.taxonomic_sort_order) as taxonomic_sort_order,
      t.taxon || coalesce(' ' || t.attribute, ''), t.authority,
      l.iso as language_iso, l.language,
      tpref.taxon || coalesce(' ' || tpref.attribute, '') as preferred_taxon, tpref.authority as preferred_authority,
      lpref.iso as preferred_language_iso, lpref.language as preferred_language,
      tcommon.taxon as default_common_name,
      regexp_replace(regexp_replace(regexp_replace(lower(t.taxon), E'\\\\(.+\\\\)', '', 'g'), 'ae', 'e', 'g'), E'[^a-z0-9\\\\?\\\\+]', '', 'g'),
      tpref.external_key, tpref.organism_key,
      coalesce(ttlpref.taxon_meaning_id, ttlprefredundant.taxon_meaning_id) as taxon_meaning_id,
      tpref.taxon_group_id, tg.title,
      tr.id, tr.rank, tr.sort_order,
      now(), now(), ttl.allow_data_entry,
      t.marine_flag, t.freshwater_flag, t.terrestrial_flag, t.non_native_flag,
      t.id, t.search_code
    from taxon_lists tl
    join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id and ttl.deleted=false
    #join_needs_update#
    left join cache_taxa_taxon_lists cttl on cttl.id=ttl.id
    -- Select the preferred name which isn't redundant (allow_data_entry=true)
    left join taxa_taxon_lists ttlpref
      on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id
      and ttlpref.preferred=true
      and ttlpref.taxon_list_id=ttl.taxon_list_id
      and ttlpref.deleted=false
      and ttlpref.allow_data_entry=true
    -- A fallback preferred name which is redundant (allow_data_entry=false)
    left join taxa_taxon_lists ttlprefredundant
      on ttlprefredundant.taxon_meaning_id=ttl.taxon_meaning_id
      and ttlprefredundant.preferred=true
      and ttlprefredundant.taxon_list_id=ttl.taxon_list_id
      and ttlprefredundant.deleted=false
      and ttlprefredundant.allow_data_entry=false
    join taxa t on t.id=ttl.taxon_id and t.deleted=false and t.deleted=false
    join languages l on l.id=t.language_id and l.deleted=false and l.deleted=false
    join taxa tpref on tpref.id=coalesce(ttlpref.taxon_id, ttlprefredundant.taxon_id) and tpref.deleted=false
    join taxon_groups tg on tg.id=tpref.taxon_group_id and tg.deleted=false
    left join taxon_ranks tr on tr.id=t.taxon_rank_id and tr.deleted=false
    join languages lpref on lpref.id=tpref.language_id and lpref.deleted=false
    left join taxa tcommon on tcommon.id=coalesce(ttlpref.common_taxon_id, ttlprefredundant.common_taxon_id) and tcommon.deleted=false
    where cttl.id is null and tl.deleted=false
    -- Must have one or other preferred name available.
    and (ttlpref.id is not null or ttlprefredundant.id is not null)";

$config['taxa_taxon_lists']['join_needs_update'] = 'join needs_update_taxa_taxon_lists nu on nu.id=ttl.id and nu.deleted=false';
$config['taxa_taxon_lists']['key_field'] = 'ttl.id';

$config['taxa_taxon_lists']['extra_multi_record_updates'] = [
  'setup' => "
    -- Find children of updated taxa to ensure they are also changed.
    WITH RECURSIVE q AS (
      SELECT ttl.id
      FROM taxa_taxon_lists ttl
      JOIN needs_update_taxa_taxon_lists nu ON nu.id=ttl.id
      WHERE ttl.deleted=false
      UNION ALL
      SELECT ttl.id
      FROM q
      JOIN taxa_taxon_lists ttl ON ttl.parent_id=q.id and ttl.deleted=false AND ttl.preferred=true
    )
    SELECT DISTINCT *
    INTO TEMPORARY descendants FROM q;

    WITH RECURSIVE q AS (
      SELECT distinct ttlpref.id AS child_pref_ttl_id, ttlpref.allow_data_entry as child_pref_allow_data_entry,
      ttlpref.taxon_meaning_id as child_pref_taxon_meaning_id, ttlpref.taxon_list_id as child_pref_taxon_list_id,
      ttlpref.parent_id, ttlpref.taxon_meaning_id AS rank_taxon_meaning_id, ttlpref.taxon_list_id, 0 as distance
      FROM taxa_taxon_lists ttlpref
      JOIN taxa t ON t.id=ttlpref.taxon_id and t.deleted=false
      JOIN descendants d ON d.id=ttlpref.id
      WHERE ttlpref.preferred=true
      AND ttlpref.deleted=false
      UNION ALL
      SELECT q.child_pref_ttl_id, q.child_pref_allow_data_entry, q.child_pref_taxon_meaning_id, q.child_pref_taxon_list_id,
          ttl.parent_id, ttl.taxon_meaning_id AS rank_taxon_meaning_id, ttl.taxon_list_id, q.distance+1
      FROM q
      JOIN taxa_taxon_lists ttl ON ttl.id=q.parent_id and ttl.deleted=false and ttl.taxon_list_id=q.taxon_list_id
      JOIN taxa t ON t.id=ttl.taxon_id and t.deleted=false
    )
    SELECT child_pref_ttl_id, child_pref_allow_data_entry, child_pref_taxon_meaning_id, child_pref_taxon_list_id,
	    array_agg(rank_taxon_meaning_id order by distance desc) as path
    INTO TEMPORARY ttl_path
    FROM q
    GROUP BY child_pref_ttl_id, child_pref_allow_data_entry, child_pref_taxon_meaning_id, child_pref_taxon_list_id
    ORDER BY child_pref_ttl_id;

    -- Remove any for redundant taxa where path covered by a non-redundant taxa.
    DELETE FROM ttl_path t1
    USING ttl_path t2
    WHERE t1.child_pref_allow_data_entry=false
    AND t2.child_pref_allow_data_entry=true
    AND t2.child_pref_taxon_meaning_id=t1.child_pref_taxon_meaning_id
    AND t2.child_pref_taxon_list_id=t1.child_pref_taxon_list_id;

    SELECT DISTINCT ON (cttl.external_key) cttl.external_key, cttlall.id, tp.path
    INTO TEMPORARY master_list_paths
    FROM ttl_path tp
    JOIN cache_taxa_taxon_lists cttl ON cttl.id=tp.child_pref_ttl_id
    JOIN cache_taxa_taxon_lists cttlall ON cttlall.external_key=cttl.external_key
    WHERE cttl.taxon_list_id=COALESCE(#master_list_id#, cttlall.taxon_list_id)
    AND cttlall.preferred=true
    ORDER BY cttl.external_key, cttl.allow_data_entry DESC;",
  'Taxon paths' => "
    UPDATE cache_taxon_paths ctp
    SET path=tp.path, external_key=t.external_key
    FROM taxa_taxon_lists ttl
    JOIN taxa t ON t.id=ttl.taxon_id AND t.deleted=false
    JOIN ttl_path tp ON tp.child_pref_ttl_id=ttl.id
    WHERE ctp.taxon_meaning_id=ttl.taxon_meaning_id AND ctp.taxon_list_id=ttl.taxon_list_id
    AND (ctp.path<>tp.path OR COALESCE(ctp.external_key, '')<>COALESCE(t.external_key, ''))
    AND ttl.deleted=false;

    INSERT INTO cache_taxon_paths (taxon_meaning_id, taxon_list_id, external_key, path)
    SELECT DISTINCT ON (ttl.taxon_meaning_id, ttl.taxon_list_id) ttl.taxon_meaning_id, ttl.taxon_list_id, t.external_key, tp.path
    FROM taxa_taxon_lists ttl
    JOIN taxa t ON t.id=ttl.taxon_id AND t.deleted=false
    JOIN ttl_path tp ON tp.child_pref_ttl_id=ttl.id
    LEFT JOIN cache_taxon_paths ctp ON ctp.taxon_meaning_id=ttl.taxon_meaning_id AND ctp.taxon_list_id=ttl.taxon_list_id
    WHERE ctp.taxon_meaning_id IS NULL
    AND ttl.deleted=false;

    DELETE FROM cache_taxon_paths
    USING cache_taxon_paths ctp
    LEFT JOIN taxa_taxon_lists ttl ON ttl.taxon_meaning_id=ctp.taxon_meaning_id AND ttl.taxon_list_id=ctp.taxon_list_id AND ttl.deleted=false
    WHERE ttl.id IS NULL
    AND cache_taxon_paths.taxon_meaning_id=ctp.taxon_meaning_id AND cache_taxon_paths.taxon_list_id=ctp.taxon_list_id;",
  'Ranks' => "
    UPDATE cache_taxa_taxon_lists u
    SET family_taxa_taxon_list_id=cttlf.id, family_taxon=cttlf.taxon,
        order_taxa_taxon_list_id=cttlo.id, order_taxon=cttlo.taxon,
        kingdom_taxa_taxon_list_id=cttlk.id, kingdom_taxon=cttlk.taxon
    FROM master_list_paths mlp
    JOIN descendants nu ON nu.id=mlp.id
    LEFT JOIN cache_taxa_taxon_lists cttlf ON cttlf.taxon_meaning_id=ANY(mlp.path) and cttlf.taxon_rank='Family'
      AND cttlf.taxon_list_id=#master_list_id# AND cttlf.preferred=true AND cttlf.allow_data_entry=true
    LEFT JOIN cache_taxa_taxon_lists cttlo ON cttlo.taxon_meaning_id=ANY(mlp.path) and cttlo.taxon_rank='Order'
      AND cttlo.taxon_list_id=#master_list_id# AND cttlo.preferred=true AND cttlo.allow_data_entry=true
    LEFT JOIN cache_taxa_taxon_lists cttlk ON cttlk.taxon_meaning_id=ANY(mlp.path) and cttlk.taxon_rank='Kingdom'
      AND cttlk.taxon_list_id=#master_list_id# AND cttlk.preferred=true AND cttlk.allow_data_entry=true
    WHERE mlp.external_key=u.external_key
    AND (COALESCE(u.family_taxa_taxon_list_id, 0)<>COALESCE(cttlf.id, 0)
        OR COALESCE(u.family_taxon, '')<>COALESCE(cttlf.taxon, '')
        OR COALESCE(u.order_taxa_taxon_list_id, 0)<>COALESCE(cttlo.id, 0)
        OR COALESCE(u.order_taxon, '')<>COALESCE(cttlo.taxon, '')
        OR COALESCE(u.kingdom_taxa_taxon_list_id, 0)<>COALESCE(cttlk.id, 0)
        OR COALESCE(u.kingdom_taxon, '')<>COALESCE(cttlk.taxon, '')
    );

    UPDATE cache_occurrences_functional u
    SET family_taxa_taxon_list_id=cttl.family_taxa_taxon_list_id,
      taxon_path=mlp.path
    FROM cache_taxa_taxon_lists cttl
    -- Ensure only changed taxon concepts are updated
    JOIN descendants nu ON nu.id=cttl.preferred_taxa_taxon_list_id
    JOIN master_list_paths mlp ON mlp.external_key=cttl.external_key
    WHERE cttl.id=u.taxa_taxon_list_id
    AND (COALESCE(u.family_taxa_taxon_list_id, 0)<>COALESCE(cttl.family_taxa_taxon_list_id, 0)
    OR COALESCE(u.taxon_path, ARRAY[]::integer[])<>COALESCE(mlp.path, ARRAY[]::integer[]));",
  "teardown" => "
    DROP TABLE descendants;
    DROP TABLE ttl_path;
    DROP TABLE master_list_paths;",
];

// --------------------------------------------------------------------------------------------------------------------------

// No cache_updated_on in cache_taxon_searchterms.
$config['taxon_searchterms']['get_missing_items_query'] = "
    select distinct on (ttl.id) ttl.id, ttl.allow_data_entry,
		tl.deleted or ttl.deleted or ttlpref.deleted or t.deleted or l.deleted or tpref.deleted or tg.deleted or lpref.deleted as deleted
      from taxon_lists tl
      join taxa_taxon_lists ttl on ttl.taxon_list_id=tl.id
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id
        and ttlpref.preferred=true
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

$config['taxon_searchterms']['get_changed_items_query'] = "
      select sub.id, sub.allow_data_entry, cast(max(cast(deleted as int)) as boolean) as deleted
      from (
      select ttl.id, ttl.allow_data_entry, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where ttl.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where tl.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where t.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where l.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or tl.deleted or t.deleted or l.deleted as deleted
      from taxa_taxon_lists ttl
      join taxon_lists tl on tl.id=ttl.taxon_list_id
      join taxa t on t.id=ttl.taxon_id
      join languages l on l.id=t.language_id
      left join taxa tc on tc.id=ttl.common_taxon_id
      where tc.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where ttlpref.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where tpref.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where lpref.updated_on>'#date#'
      union
      select ttl.id, ttl.allow_data_entry, ttl.deleted or ttlpref.deleted or tpref.deleted or lpref.deleted or tg.deleted
      from taxa_taxon_lists ttl
      join taxa_taxon_lists ttlpref on ttlpref.taxon_meaning_id=ttl.taxon_meaning_id and ttlpref.preferred=true and ttlpref.taxon_list_id=ttl.taxon_list_id
      join taxa tpref on tpref.id=ttlpref.taxon_id
      join languages lpref on lpref.id=tpref.language_id
      join taxon_groups tg on tg.id=tpref.taxon_group_id
      where tg.updated_on>'#date#'
      ) as sub
      group by sub.id, sub.allow_data_entry";

$config['taxon_searchterms']['delete_query']['taxa'] = "
  delete from cache_taxon_searchterms where taxa_taxon_list_id in (select id from needs_update_taxon_searchterms where deleted=true or allow_data_entry=false)";

$config['taxon_searchterms']['delete_query']['codes'] = "
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
      freshwater_flag=cttl.freshwater_flag,
      terrestrial_flag=cttl.terrestrial_flag,
      non_native_flag=cttl.non_native_flag,
      external_key=cttl.external_key,
      organism_key=cttl.organism_key,
      authority=cttl.authority,
      search_code=cttl.search_code,
      taxonomic_sort_order=cttl.taxonomic_sort_order,
      taxon_rank=cttl.taxon_rank
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
      freshwater_flag=cttl.freshwater_flag,
      terrestrial_flag=cttl.terrestrial_flag,
      non_native_flag=cttl.non_native_flag,
      external_key=cttl.external_key,
      organism_key=cttl.organism_key,
      authority=cttl.authority,
      search_code=cttl.search_code,
      taxonomic_sort_order=cttl.taxonomic_sort_order,
      taxon_rank=cttl.taxon_rank
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
      taxon_rank_sort_order=cttl.taxon_rank_sort_order,
      marine_flag=cttl.marine_flag,
      freshwater_flag=cttl.freshwater_flag,
      terrestrial_flag=cttl.terrestrial_flag,
      non_native_flag=cttl.non_native_flag,
      external_key=cttl.external_key,
      organism_key=cttl.organism_key,
      authority=cttl.authority,
      search_code=cttl.search_code,
      taxonomic_sort_order=cttl.taxonomic_sort_order,
      taxon_rank=cttl.taxon_rank
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
      freshwater_flag=cttl.freshwater_flag,
      terrestrial_flag=cttl.terrestrial_flag,
      non_native_flag=cttl.non_native_flag,
      external_key=cttl.external_key,
      organism_key=cttl.organism_key,
      authority=cttl.authority,
      search_code=cttl.search_code,
      taxonomic_sort_order=cttl.taxonomic_sort_order,
      taxon_rank=cttl.taxon_rank
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    join taxon_codes tc on tc.taxon_meaning_id=cttl.taxon_meaning_id
    join termlists_terms tlttype on tlttype.id=tc.code_type_id
    join termlists_terms tltcategory on tltcategory.id=tlttype.parent_id
    join terms tcategory on tcategory.id=tltcategory.term_id and tcategory.term='searchable'
    where cttl.id=cttl.preferred_taxa_taxon_list_id and cts.taxa_taxon_list_id=cttl.id and cts.name_type = 'C' and cts.source_id=tc.id";

/* Note id_diff verification_rule_data.key forced uppercase by rule postprocessor. */
$config['taxon_searchterms']['update']['id_diff'] = "update cache_taxon_searchterms cts
    set identification_difficulty=extkey.value::integer, id_diff_verification_rule_id=vr.id
      from cache_taxa_taxon_lists cttl
      #join_needs_update#
      join verification_rule_data extkey ON extkey.key=LOWER(cttl.external_key) AND extkey.header_name='Data' AND extkey.deleted=false
      join verification_rules vr ON vr.id=extkey.verification_rule_id AND vr.test_type='IdentificationDifficulty' AND vr.deleted=false
      where cttl.id=cts.taxa_taxon_list_id";

$config['taxon_searchterms']['insert']['standard terms'] = "insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id,
      marine_flag, freshwater_flag, terrestrial_flag, non_native_flag,
      external_key, organism_key, authority, search_code, taxonomic_sort_order, taxon_rank
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, cttl.taxon || coalesce(' ' || cttl.authority, ''),
      cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id,
      cttl.preferred_taxon, cttl.default_common_name, cttl.preferred_authority, cttl.language_iso,
      case
        when cttl.language_iso='lat' and cttl.id=cttl.preferred_taxa_taxon_list_id then 'L'
        when cttl.language_iso='lat' and cttl.id<>cttl.preferred_taxa_taxon_list_id then 'S'
        else 'V'
      end, false, null, cttl.preferred, length(cttl.taxon), cttl.parent_id, cttl.preferred_taxa_taxon_list_id,
      cttl.marine_flag, cttl.freshwater_flag, cttl.terrestrial_flag, cttl.non_native_flag,
      cttl.external_key, cttl.organism_key, cttl.authority, cttl.search_code, cttl.taxonomic_sort_order, cttl.taxon_rank
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified=false
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['abbreviations'] = "insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id,
      marine_flag, freshwater_flag, terrestrial_flag, non_native_flag, external_key, organism_key, authority, search_code, taxonomic_sort_order, taxon_rank
    )
    select distinct on (cttl.id) cttl.id, cttl.taxon_list_id, taxon_abbreviation(cttl.taxon), cttl.taxon, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, cttl.language_iso,
      'A', null, null, cttl.preferred, length(taxon_abbreviation(cttl.taxon)), cttl.parent_id, cttl.preferred_taxa_taxon_list_id,
      cttl.marine_flag, cttl.freshwater_flag, cttl.terrestrial_flag, cttl.non_native_flag,
      cttl.external_key, cttl.organism_key, cttl.authority, cttl.search_code, cttl.taxonomic_sort_order, cttl.taxon_rank
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='A'
    where cts.taxa_taxon_list_id is null and cttl.language_iso='lat' and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['simplified terms'] = "insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, preferred, searchterm_length, parent_id, preferred_taxa_taxon_list_id,
      marine_flag, freshwater_flag, terrestrial_flag, non_native_flag, external_key, organism_key, authority, search_code, taxonomic_sort_order, taxon_rank
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
      cttl.parent_id, cttl.preferred_taxa_taxon_list_id,
      cttl.marine_flag, cttl.freshwater_flag, cttl.terrestrial_flag, cttl.non_native_flag, cttl.external_key, cttl.organism_key, cttl.authority,
      cttl.search_code, cttl.taxonomic_sort_order, cttl.taxon_rank
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type in ('L','S','V') and cts.simplified=true
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=true";

$config['taxon_searchterms']['insert']['codes'] = "insert into cache_taxon_searchterms (
      taxa_taxon_list_id, taxon_list_id, searchterm, original, taxon_group_id, taxon_group, taxon_meaning_id, preferred_taxon,
      default_common_name, preferred_authority, language_iso,
      name_type, simplified, code_type_id, source_id, preferred, searchterm_length,
      parent_id, preferred_taxa_taxon_list_id,
      marine_flag, freshwater_flag, terrestrial_flag, non_native_flag,
      external_key, organism_key, authority, search_code, taxonomic_sort_order, taxon_rank
    )
    select distinct on (tc.id) cttl.id, cttl.taxon_list_id, tc.code, tc.code, cttl.taxon_group_id, cttl.taxon_group, cttl.taxon_meaning_id, cttl.preferred_taxon,
      cttl.default_common_name, cttl.authority, null, 'C', null, tc.code_type_id, tc.id, cttl.preferred, length(tc.code),
      cttl.parent_id, cttl.preferred_taxa_taxon_list_id,
      cttl.marine_flag, cttl.freshwater_flag, cttl.terrestrial_flag, cttl.non_native_flag,
      cttl.external_key, cttl.organism_key, cttl.authority, cttl.search_code, cttl.taxonomic_sort_order, cttl.taxon_rank
    from cache_taxa_taxon_lists cttl
    #join_needs_update#
    join taxon_codes tc on tc.taxon_meaning_id=cttl.taxon_meaning_id and tc.deleted=false
    left join cache_taxon_searchterms cts on cts.taxa_taxon_list_id=cttl.id and cts.name_type='C' and cts.source_id=tc.id
    join termlists_terms tlttype on tlttype.id=tc.code_type_id and tlttype.deleted=false
    join termlists_terms tltcategory on tltcategory.id=tlttype.parent_id and tltcategory.deleted=false
    join terms tcategory on tcategory.id=tltcategory.term_id and tcategory.term='searchable' and tcategory.deleted=false
    where cts.taxa_taxon_list_id is null and cttl.allow_data_entry=false";

/* Note id_diff verification_rule_data.key forced uppercase by rule postprocessor. */
$config['taxon_searchterms']['insert']['id_diff'] = "update cache_taxon_searchterms cts
    set identification_difficulty=extkey.value::integer, id_diff_verification_rule_id=vr.id
      from cache_taxa_taxon_lists cttl
      #join_needs_update#
      join verification_rule_data extkey ON extkey.key=UPPER(cttl.external_key) AND extkey.header_name='Data' AND extkey.deleted=false
      join verification_rules vr ON vr.id=extkey.verification_rule_id AND vr.test_type='IdentificationDifficulty' AND vr.deleted=false
      where cttl.id=cts.taxa_taxon_list_id";

$config['taxon_searchterms']['join_needs_update'] = 'join needs_update_taxon_searchterms nu on nu.id=cttl.id and nu.deleted=false';
$config['taxon_searchterms']['key_field'] = 'cttl.preferred_taxa_taxon_list_id';

$config['taxon_searchterms']['count'] = '
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

// --------------------------------------------------------------------------------------------------------------------------

$config['samples']['get_missing_items_query'] = "
  select distinct s.id, s.deleted or su.deleted as deleted
    from samples s
    join surveys su on su.id=s.survey_id
    left join samples sp on sp.id=s.parent_id
    left join cache_termlists_terms tmethod on tmethod.id=s.sample_method_id
    left join cache_samples_functional cs on cs.id=s.id
    left join needs_update_samples nu on nu.id=s.id
    where cs.id is null and nu.id is null
    and (s.deleted or coalesce(sp.deleted, false) or su.deleted) = false
";
$config['samples']['get_changed_items_query'] = "
  select sub.id, cast(max(cast(deleted as int)) as boolean) as deleted
    from (
    -- don't pick up changes to samples at this point, as they are updated immediately
    -- but do pick up edits of other tables that can affect the sample cache
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

$config['samples']['delete_query'] = [<<<SQL
    DELETE FROM cache_samples_functional WHERE id IN (SELECT id FROM needs_update_samples WHERE deleted=true);
    DELETE FROM cache_samples_nonfunctional WHERE id IN (SELECT id FROM needs_update_samples WHERE deleted=true);
    DELETE FROM cache_samples_sensitive WHERE id IN (SELECT id FROM needs_update_samples WHERE deleted=true);
  SQL,
];

$config['samples']['update']['functional'] = "
UPDATE cache_samples_functional s_update
SET website_id=su.website_id,
  survey_id=s.survey_id,
  input_form=COALESCE(sp.input_form, s.input_form),
  location_id= s.location_id,
  location_name=CASE
    WHEN s.privacy_precision IS NOT NULL OR (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL THEN NULL
    ELSE COALESCE(l.name, s.location_name, lp.name, sp.location_name)
  END,
  public_geom=reduce_precision(
    coalesce(s.geom, l.centroid_geom),
    false,
    greatest(
      case s.privacy_precision when 0 then 10000 else s.privacy_precision end,
      (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id)
    )
  ),
  date_start=s.date_start,
  date_end=s.date_end,
  date_type=s.date_type,
  created_on=s.created_on,
  updated_on=s.updated_on,
  verified_on=s.verified_on,
  created_by_id=s.created_by_id,
  group_id=coalesce(s.group_id, sp.group_id),
  record_status=s.record_status,
  training=s.training,
  import_guid=s.import_guid,
  query=case
    when sc1.id is null then null
    when sc2.id is null and s.updated_on<=sc1.created_on then 'Q'
    else 'A'
  end,
  parent_sample_id=s.parent_id,
  media_count=(SELECT COUNT(sm.*) FROM sample_media sm WHERE sm.sample_id=s.id AND sm.deleted=false),
  external_key=s.external_key,
  sensitive=(SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL,
  private=s.privacy_precision IS NOT NULL,
  hide_sample_as_private=(s.privacy_precision IS NOT NULL AND s.privacy_precision=0)
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

$config['samples']['update']['functional_sensitive'] = "
UPDATE cache_samples_functional u
SET location_id=null, location_name=null
FROM samples s
#join_needs_update#
JOIN occurrences o ON o.sample_id=s.id AND o.deleted=false AND o.sensitivity_precision IS NOT NULL
WHERE s.id=u.id
AND (u.location_id IS NOT NULL OR u.location_name IS NOT NULL)
";

$config['samples']['update']['nonfunctional'] = "
WITH full_name_smp_attrs AS (SELECT id FROM sample_attributes WHERE system_function='full_name' AND deleted=false),
  biotope_smp_attrs AS (SELECT id, data_type FROM sample_attributes WHERE system_function='biotope' AND deleted=false)
UPDATE cache_samples_nonfunctional
SET website_title=w.title,
  survey_title=su.title,
  group_title=g.title,
  public_entered_sref=case
    when s.privacy_precision is not null OR (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL then
      get_output_sref(
        greatest(
          round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
          (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
          case s.privacy_precision when 0 then 10000 else s.privacy_precision end,
          -- work out best square size to reflect a lat long's true precision
          case
            when coalesce(v_sref_precision.int_value, v_sref_precision.float_value)>=50001 then 1000000
            when coalesce(v_sref_precision.int_value, v_sref_precision.float_value)>=5001 then 100000
            when coalesce(v_sref_precision.int_value, v_sref_precision.float_value)>=501 then 10000
            when coalesce(v_sref_precision.int_value, v_sref_precision.float_value) between 51 and 500 then 1000
            when coalesce(v_sref_precision.int_value, v_sref_precision.float_value) between 6 and 50 then 100
          else 10
          end,
          10 -- default minimum square size
        ), reduce_precision(
          coalesce(s.geom, l.centroid_geom),
          (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id),
          greatest(
            (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
            case s.privacy_precision when 0 then 10000 else s.privacy_precision end
          )
        )
      )
   else
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
  output_sref=get_output_sref(
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
      case s.privacy_precision when 0 then 10000 else s.privacy_precision end,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=50001 then 1000000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=5001 then 100000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=501 then 10000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value) between 51 and 500 then 1000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id),
      greatest(
        (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
  output_sref_system=get_output_system(
    reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id),
      greatest(
        (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
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
  attr_full_name=(
    SELECT STRING_AGG(v.text_value, '; ')
    FROM sample_attribute_values v
    JOIN full_name_smp_attrs fa on fa.id=v.sample_attribute_id
    WHERE v.sample_id=s.id
    AND v.deleted=false
    AND v.text_value IS NOT NULL
  ),
  attr_biotope=(
    SELECT STRING_AGG(COALESCE(t.term, v.text_value), '; ')
    FROM sample_attribute_values v
    JOIN biotope_smp_attrs fa on fa.id=v.sample_attribute_id
    LEFT JOIN cache_termlists_terms t on fa.data_type='L' and t.id=v.int_value
    WHERE v.sample_id=s.id
    AND v.deleted=false
    AND COALESCE(v.text_value, t.term) IS NOT NULL
  ),
  attr_sref_precision=CASE a_sref_precision.data_type
      WHEN 'I'::bpchar THEN v_sref_precision.int_value::double precision
      WHEN 'F'::bpchar THEN v_sref_precision.float_value
      WHEN 'L'::bpchar THEN t_sref_precision.sort_order
      ELSE NULL::double precision
  END,
  attr_sample_method=COALESCE(CASE a_sample_method.data_type
      WHEN 'T'::bpchar THEN v_sample_method.text_value
      WHEN 'L'::bpchar THEN t_sample_method.term
      ELSE NULL::text
  END, t_sample_method_id.term),
  attr_linked_location_id=v_linked_location_id.int_value,
  verifier=pv.surname || ', ' || pv.first_name
FROM samples s
#join_needs_update#
LEFT JOIN samples sp ON sp.id=s.parent_id and sp.deleted=false
JOIN surveys su on su.id=s.survey_id and su.deleted=false
JOIN websites w on w.id=su.website_id and w.deleted=false
LEFT JOIN groups g on g.id=coalesce(s.group_id, sp.group_id) and g.deleted=false
LEFT JOIN locations l on l.id=s.location_id and l.deleted=false
LEFT JOIN licences li on li.id=s.licence_id and li.deleted=false
LEFT JOIN (sample_attribute_values v_email
  JOIN sample_attributes a_email on a_email.id=v_email.sample_attribute_id and a_email.deleted=false and a_email.system_function='email'
) on v_email.sample_id=s.id and v_email.deleted=false
LEFT JOIN (sample_attribute_values v_cms_user_id
  JOIN sample_attributes a_cms_user_id on a_cms_user_id.id=v_cms_user_id.sample_attribute_id and a_cms_user_id.deleted=false and a_cms_user_id.system_function='cms_user_id'
) on v_cms_user_id.sample_id=s.id and v_cms_user_id.deleted=false
LEFT JOIN (sample_attribute_values v_cms_username
  JOIN sample_attributes a_cms_username on a_cms_username.id=v_cms_username.sample_attribute_id and a_cms_username.deleted=false and a_cms_username.system_function='cms_username'
) on v_cms_username.sample_id=s.id and v_cms_username.deleted=false
LEFT JOIN (sample_attribute_values v_first_name
  JOIN sample_attributes a_first_name on a_first_name.id=v_first_name.sample_attribute_id and a_first_name.deleted=false and a_first_name.system_function='first_name'
) on v_first_name.sample_id=s.id and v_first_name.deleted=false
LEFT JOIN (sample_attribute_values v_last_name
  JOIN sample_attributes a_last_name on a_last_name.id=v_last_name.sample_attribute_id and a_last_name.deleted=false and a_last_name.system_function='last_name'
) on v_last_name.sample_id=s.id and v_last_name.deleted=false
LEFT JOIN (sample_attribute_values v_sref_precision
  JOIN sample_attributes a_sref_precision on a_sref_precision.id=v_sref_precision.sample_attribute_id and a_sref_precision.deleted=false and a_sref_precision.system_function='sref_precision'
  LEFT JOIN cache_termlists_terms t_sref_precision on a_sref_precision.data_type='L' and t_sref_precision.id=v_sref_precision.int_value
) on v_sref_precision.sample_id=s.id and v_sref_precision.deleted=false
LEFT JOIN (sample_attribute_values v_sample_method
  JOIN sample_attributes a_sample_method on a_sample_method.id=v_sample_method.sample_attribute_id and a_sample_method.deleted=false and a_sample_method.system_function='sample_method'
  LEFT JOIN cache_termlists_terms t_sample_method on a_sample_method.data_type='L' and t_sample_method.id=v_sample_method.int_value
) on v_sample_method.sample_id=s.id and v_sample_method.deleted=false
LEFT JOIN cache_termlists_terms t_sample_method_id ON t_sample_method_id.id=s.sample_method_id
LEFT JOIN (sample_attribute_values v_linked_location_id
  JOIN sample_attributes a_linked_location_id on a_linked_location_id.id=v_linked_location_id.sample_attribute_id
    and a_linked_location_id.deleted=false and a_linked_location_id.system_function='linked_location_id'
) ON v_linked_location_id.sample_id=s.id and v_linked_location_id.deleted=false
LEFT JOIN users uv on uv.id=s.verified_by_id and uv.deleted=false
LEFT JOIN people pv on pv.id=uv.person_id and pv.deleted=false
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

$config['samples']['insert']['functional'] = "
INSERT INTO cache_samples_functional(
            id, website_id, survey_id, input_form, location_id, location_name,
            public_geom, date_start, date_end, date_type, created_on, updated_on, verified_on, created_by_id,
            group_id, record_status, training, import_guid, query, parent_sample_id, media_count, external_key,
            sensitive, private, hide_sample_as_private)
SELECT distinct on (s.id) s.id, su.website_id, s.survey_id, COALESCE(sp.input_form, s.input_form), s.location_id,
  CASE
    WHEN s.privacy_precision IS NOT NULL OR (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL THEN NULL
    ELSE COALESCE(l.name, s.location_name, lp.name, sp.location_name)
  END,
  reduce_precision(coalesce(s.geom, l.centroid_geom), false, greatest(case s.privacy_precision when 0 then 10000 else s.privacy_precision end, (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id))),
  s.date_start, s.date_end, s.date_type, s.created_on, s.updated_on, s.verified_on, s.created_by_id,
  coalesce(s.group_id, sp.group_id), s.record_status, s.training, s.import_guid,
  case
    when sc1.id is null then null
    when sc2.id is null and s.updated_on<=sc1.created_on then 'Q'
    else 'A'
  end,
  s.parent_id,
  (SELECT COUNT(sm.*) FROM sample_media sm WHERE sm.sample_id=s.id AND sm.deleted=false),
  s.external_key,
  (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL,
  s.privacy_precision IS NOT NULL, s.privacy_precision IS NOT NULL AND s.privacy_precision=0
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

$config['samples']['insert']['functional_sensitive'] = "
UPDATE cache_samples_functional
SET location_id=null, location_name=null
FROM samples s
#join_needs_update#
JOIN occurrences o ON o.sample_id=s.id AND o.deleted=false AND o.sensitivity_precision IS NOT NULL
WHERE s.id=cache_samples_functional.id
";

$config['samples']['insert']['sensitive'] = "
INSERT INTO cache_samples_sensitive(id)
SELECT s.id
FROM samples s
#join_needs_update#
LEFT JOIN cache_samples_sensitive cs on cs.id=s.id
WHERE s.deleted=false
AND cs.id IS NULL
";

$config['samples']['insert']['nonfunctional'] = "
INSERT INTO cache_samples_nonfunctional(
            id, website_title, survey_title, group_title, public_entered_sref,
            entered_sref_system, recorders, comment, privacy_precision, licence_code,
            attr_sref_precision, output_sref, output_sref_system, verifier)
SELECT distinct on (s.id) s.id, w.title, su.title, g.title,
  case
    when s.privacy_precision is not null OR (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id) IS NOT NULL then
      get_output_sref(
        greatest(
          round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
          (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
          case s.privacy_precision when 0 then 10000 else s.privacy_precision end,
          -- work out best square size to reflect a lat long's true precision
          case
            when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=50001 then 1000000
            when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=5001 then 100000
            when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=501 then 10000
            when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value) between 51 and 500 then 1000
            when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value) between 6 and 50 then 100
            else 10
          end,
          10 -- default minimum square size
        ), reduce_precision(
          coalesce(s.geom, l.centroid_geom),
          (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id),
          greatest(
            (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
            case s.privacy_precision when 0 then 10000 else s.privacy_precision end
          )
        )
      )
   else
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
  s.recorder_names, s.comment, s.privacy_precision, li.code,
  CASE a_sref_precision.data_type
    WHEN 'I'::bpchar THEN v_sref_precision.int_value::double precision
    WHEN 'F'::bpchar THEN v_sref_precision.float_value
    WHEN 'L'::bpchar THEN t_sref_precision.sort_order
    ELSE NULL::double precision
  END,
  get_output_sref(
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
      case s.privacy_precision when 0 then 10000 else s.privacy_precision end,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=50001 then 1000000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=5001 then 100000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value)>=501 then 10000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value) between 51 and 500 then 1000
        when coalesce(t_sref_precision.sort_order, v_sref_precision.int_value, v_sref_precision.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id),
      greatest(
        (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
  get_output_system(
    reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      (SELECT bool_or(confidential) FROM occurrences WHERE sample_id=s.id),
      greatest(
        (SELECT max(sensitivity_precision) FROM occurrences WHERE sample_id=s.id),
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
  pv.surname || ', ' || pv.first_name
FROM samples s
#join_needs_update#
LEFT JOIN samples sp ON sp.id=s.parent_id and sp.deleted=false
LEFT JOIN cache_samples_nonfunctional cs on cs.id=s.id
JOIN surveys su on su.id=s.survey_id and su.deleted=false
JOIN websites w on w.id=su.website_id and w.deleted=false
LEFT JOIN groups g on g.id=coalesce(s.group_id, sp.group_id) and g.deleted=false
LEFT JOIN locations l on l.id=s.location_id and l.deleted=false
LEFT JOIN (sample_attribute_values v_sref_precision
  JOIN sample_attributes a_sref_precision on a_sref_precision.id=v_sref_precision.sample_attribute_id and a_sref_precision.deleted=false and a_sref_precision.system_function='sref_precision'
  LEFT JOIN cache_termlists_terms t_sref_precision on a_sref_precision.data_type='L' and t_sref_precision.id=v_sref_precision.int_value
) on v_sref_precision.sample_id=s.id and v_sref_precision.deleted=false
LEFT JOIN licences li on li.id=s.licence_id and li.deleted=false
LEFT JOIN users uv on uv.id=s.verified_by_id and uv.deleted=false
LEFT JOIN people pv on pv.id=uv.person_id and pv.deleted=false
WHERE s.deleted=false
AND cs.id IS NULL";


$config['samples']['insert']['nonfunctional_attrs'] = "
WITH full_name_smp_attrs AS (SELECT id FROM sample_attributes WHERE system_function='full_name' AND deleted=false),
  biotope_smp_attrs AS (SELECT id, data_type FROM sample_attributes WHERE system_function='biotope' AND deleted=false)
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
  attr_full_name=(
    SELECT STRING_AGG(DISTINCT v.text_value, '; ')
    FROM sample_attribute_values v
    JOIN full_name_smp_attrs fa on fa.id=v.sample_attribute_id
    WHERE v.sample_id in (s.id, s.parent_id)
    AND v.deleted=false
    AND v.text_value IS NOT NULL
  ),
  attr_biotope=(
    SELECT STRING_AGG(DISTINCT COALESCE(t.term, v.text_value), '; ')
    FROM sample_attribute_values v
    JOIN biotope_smp_attrs fa on fa.id=v.sample_attribute_id
    LEFT JOIN cache_termlists_terms t on fa.data_type='L' and t.id=v.int_value
    WHERE v.sample_id in (s.id, s.parent_id)
    AND v.deleted=false
    AND COALESCE(v.text_value, t.term) IS NOT NULL
  ),
  attr_sample_method=COALESCE(t_sample_method_id.term, CASE a_sample_method.data_type
      WHEN 'T'::bpchar THEN v_sample_method.text_value
      WHEN 'L'::bpchar THEN t_sample_method.term
      ELSE NULL::text
  END),
  attr_linked_location_id=v_linked_location_id.int_value
FROM samples s
#join_needs_update#
LEFT JOIN (sample_attribute_values v_email
  JOIN sample_attributes a_email on a_email.id=v_email.sample_attribute_id and a_email.deleted=false and a_email.system_function='email'
) on v_email.sample_id=s.id and v_email.deleted=false
LEFT JOIN (sample_attribute_values v_cms_user_id
  JOIN sample_attributes a_cms_user_id on a_cms_user_id.id=v_cms_user_id.sample_attribute_id and a_cms_user_id.deleted=false and a_cms_user_id.system_function='cms_user_id'
) on v_cms_user_id.sample_id=s.id and v_cms_user_id.deleted=false
LEFT JOIN (sample_attribute_values v_cms_username
  JOIN sample_attributes a_cms_username on a_cms_username.id=v_cms_username.sample_attribute_id and a_cms_username.deleted=false and a_cms_username.system_function='cms_username'
) on v_cms_username.sample_id=s.id and v_cms_username.deleted=false
LEFT JOIN (sample_attribute_values v_first_name
  JOIN sample_attributes a_first_name on a_first_name.id=v_first_name.sample_attribute_id and a_first_name.deleted=false and a_first_name.system_function='first_name'
) on v_first_name.sample_id=s.id and v_first_name.deleted=false
LEFT JOIN (sample_attribute_values v_last_name
  JOIN sample_attributes a_last_name on a_last_name.id=v_last_name.sample_attribute_id and a_last_name.deleted=false and a_last_name.system_function='last_name'
) on v_last_name.sample_id=s.id and v_last_name.deleted=false
LEFT JOIN (sample_attribute_values v_sample_method
  JOIN sample_attributes a_sample_method on a_sample_method.id=v_sample_method.sample_attribute_id and a_sample_method.deleted=false and a_sample_method.system_function='sample_method'
  LEFT JOIN cache_termlists_terms t_sample_method on a_sample_method.data_type='L' and t_sample_method.id=v_sample_method.int_value
) on v_sample_method.sample_id=s.id and v_sample_method.deleted=false
LEFT JOIN cache_termlists_terms t_sample_method_id ON t_sample_method_id.id=s.sample_method_id
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

$config['samples']['join_needs_update'] = 'join needs_update_samples nu on nu.id=s.id and nu.deleted=false';
$config['samples']['key_field'] = 's.id';


// Additional update statements to pick up the recorder name from various possible custom attribute places. Faster than
// loads of left joins. These should be in priority order - i.e. ones where we have recorded the inputter rather than
// specifically the recorder should come after ones where we have recorded the recorder specifically.
$config['samples']['extra_multi_record_updates'] = [
  // s.recorder_names is filled in as a starting point. The rest only proceed if this is null.
  // full recorder name
  // or surname, firstname.
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
  // Sample recorder names in parent sample.
  'Parent sample recorder names' => 'update cache_samples_nonfunctional cs
    set recorders=sp.recorder_names
    from needs_update_samples nu, samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false and sp.recorder_names is not null
    where cs.recorders is null and nu.id=cs.id
    and s.id=cs.id and s.deleted=false;',
  // Full recorder name in parent sample.
  'Parent full name' => 'update cache_samples_nonfunctional cs
    set recorders=sav.text_value
    from needs_update_samples nu, samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> \', \'
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
    where cs.recorders is null and nu.id=cs.id
    and s.id=cs.id and s.deleted=false;',
  // Firstname and surname in parent sample.
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
  // Warehouse surname, first name.
  'Warehouse surname, first name' => 'update cache_samples_nonfunctional cs
    set recorders = p.surname || coalesce(\', \' || p.first_name, \'\')
    from needs_update_samples nu, people p, users u
    join cache_samples_functional csf on csf.created_by_id=u.id
    where cs.recorders is null and nu.id=cs.id
    and csf.id=cs.id and p.id=u.person_id and p.deleted=false
    and u.id<>1;',
  // CMS username.
  'CMS Username' => 'update cache_samples_nonfunctional cs
    set recorders = sav.text_value
    from needs_update_samples nu, sample_attribute_values sav
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
    where cs.recorders is null and nu.id=cs.id
    and sav.sample_id=cs.id and sav.deleted=false;',
  // CMS username in parent sample.
  'Parent CMS Username' => 'update cache_samples_nonfunctional cs
    set recorders = sav.text_value
    from needs_update_samples nu, samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
    where cs.recorders is null and nu.id=cs.id
    and s.id=cs.id and s.deleted=false;',
  // Warehouse username.
  'Warehouse username' => 'update cache_samples_nonfunctional cs
    set recorders=u.username
    from needs_update_samples nu, users u
    join cache_samples_functional csf on csf.created_by_id=u.id
    where cs.recorders is null and nu.id=cs.id
    and cs.id=csf.id and u.id<>1;',
];

// Final statements to pick up after an insert of a single record.
$config['samples']['extra_single_record_updates'] = [
  // Sample recorder names
  // Or, full recorder name
  // Or, surname, firstname.
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
  // Sample recorder names in parent sample.
  'Parent sample recorder names' => "update cache_samples_nonfunctional cs
    set recorders = sp.recorder_names
    from samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and s.id=cs.id and s.deleted=false and sp.recorder_names is not null and sp.recorder_names<>'';",
  // Full recorder name in parent sample.
  'Parent full name' => 'update cache_samples_nonfunctional cs
    set recorders = sav.text_value
    from samples s
    join samples sp on sp.id=s.parent_id and sp.deleted=false
    join sample_attribute_values sav on sav.sample_id=sp.id and sav.deleted=false and sav.text_value <> \', \'
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'full_name\' and sa.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and s.id=cs.id and s.deleted=false;',
  // Surname, firstname in parent sample.
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
  // Warehouse surname, firstname.
  'Warehouse first name/surname' => 'update cache_samples_nonfunctional cs
    set recorders=p.surname || coalesce(\', \' || p.first_name, \'\')
    from users u
    join cache_samples_functional csf on csf.created_by_id=u.id
    join people p on p.id=u.person_id and p.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and cs.id=csf.id and u.id<>1;',
  // CMS username.
  'CMS Username' => 'update cache_samples_nonfunctional cs
    set recorders=sav.text_value
    from sample_attribute_values sav
    join sample_attributes sa on sa.id=sav.sample_attribute_id and sa.system_function = \'cms_username\' and sa.deleted=false
    where cs.recorders is null and cs.id in (#ids#)
    and sav.sample_id=cs.id and sav.deleted=false;',
  // CMS username in parent sample.
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
    and cs.id=csf.id and u.id<>1;',
];

// ---------------------------------------------------------------------------------------------------------------------

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
    select om.occurrence_id, false
    from occurrence_media om
    where om.updated_on>'#date#' and om.created_on<om.updated_on
    union
    select oc.occurrence_id, false
    from occurrence_comments oc
    where oc.auto_generated=false and oc.updated_on>'#date#'
    union
    select dnao.occurrence_id, false
    from dna_occurrences dnao
    where dnao.updated_on>'#date#'
    ) as sub
    group by id";

$config['occurrences']['delete_query'] = [
  "delete from cache_occurrences_functional where id in (select id from needs_update_occurrences where deleted=true);
delete from cache_occurrences_nonfunctional where id in (select id from needs_update_occurrences where deleted=true);"
];

$config['occurrences']['update']['functional'] = "
UPDATE cache_occurrences_functional u
SET sample_id=o.sample_id,
  website_id=o.website_id,
  survey_id=s.survey_id,
  input_form=COALESCE(sp.input_form, s.input_form),
  location_id=CASE
    WHEN o.confidential=true OR o.sensitivity_precision IS NOT NULL OR s.privacy_precision IS NOT NULL THEN NULL
    ELSE l.id
  END,
  location_name=CASE
    WHEN o.confidential=true OR o.sensitivity_precision IS NOT NULL OR s.privacy_precision IS NOT NULL THEN NULL
    ELSE COALESCE(l.name, s.location_name, lp.name, sp.location_name)
  END,
  public_geom=reduce_precision(
    coalesce(s.geom, l.centroid_geom),
    o.confidential,
    greatest(
      o.sensitivity_precision,
      case s.privacy_precision when 0 then 10000 else s.privacy_precision end
    )
  ),
  date_start=s.date_start,
  date_end=s.date_end,
  date_type=s.date_type,
  created_on=o.created_on,
  updated_on=greatest(o.updated_on, cttl.cache_updated_on),
  verified_on=o.verified_on,
  created_by_id=o.created_by_id,
  group_id=coalesce(s.group_id, sp.group_id),
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
    when oc1.id is not null and oc2.id is not null then 'A'
    when oc1.id is not null and oc2.id is null then 'Q'
    else null
  end,
  sensitive=o.sensitivity_precision is not null,
  private=s.privacy_precision is not null,
  hide_sample_as_private=(s.privacy_precision IS NOT NULL AND s.privacy_precision=0),
  release_status=o.release_status,
  marine_flag=cttl.marine_flag,
  freshwater_flag=cttl.freshwater_flag,
  terrestrial_flag=cttl.terrestrial_flag,
  non_native_flag=cttl.non_native_flag,
  data_cleaner_result=case when o.last_verification_check_date is null then null else dc.id is null end,
  applied_verification_rule_types=case when o.last_verification_check_date is null then null else u.applied_verification_rule_types end,
  training=o.training,
  zero_abundance=o.zero_abundance,
  licence_id=s.licence_id,
  import_guid=o.import_guid,
  confidential=o.confidential,
  external_key=o.external_key,
  taxon_path=ctp.path,
  parent_sample_id=s.parent_id,
  verification_checks_enabled=w.verification_checks_enabled,
  media_count=(SELECT COUNT(om.*) FROM occurrence_media om WHERE om.occurrence_id=o.id AND om.deleted=false),
  identification_difficulty=(SELECT cts.identification_difficulty FROM cache_taxon_searchterms cts where cts.taxa_taxon_list_id=o.taxa_taxon_list_id AND cts.simplified=false),
  dna_derived=dnao.id IS NOT NULL AND dnao.deleted=false
FROM occurrences o
#join_needs_update#
LEFT JOIN cache_occurrences_functional co on co.id=o.id
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
JOIN websites w ON w.id=o.website_id AND w.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
LEFT JOIN cache_taxon_paths ctp ON ctp.external_key=cttl.external_key AND ctp.taxon_list_id=#master_list_id#
LEFT JOIN (occurrence_attribute_values oav
    JOIN termlists_terms certainty ON certainty.id=oav.int_value
    JOIN occurrence_attributes oa ON oa.id=oav.occurrence_attribute_id and oa.deleted=false and oa.system_function='certainty'
  ) ON oav.occurrence_id=o.id AND oav.deleted=false
LEFT JOIN occurrence_comments oc1 ON oc1.occurrence_id=o.id AND oc1.deleted=false AND oc1.auto_generated=false
    AND oc1.query=true AND (o.verified_on IS NULL OR oc1.created_on>o.verified_on)
LEFT JOIN occurrence_comments oc2 ON oc2.occurrence_id=o.id AND oc2.deleted=false AND oc2.auto_generated=false
    AND oc2.query=false AND oc2.generated_by IS NULL
    AND (o.verified_on IS NULL OR oc2.created_on>o.verified_on) AND oc2.id>oc1.id
LEFT JOIN occurrence_comments dc
    ON dc.occurrence_id=o.id
    AND dc.implies_manual_check_required=true
    AND dc.deleted=false
LEFT JOIN dna_occurrences dnao
    ON dnao.occurrence_id=o.id
WHERE u.id=o.id
";

// Fill in taxon_path if it was unable to be populated from the master list.
$config['occurrences']['update']['functional_taxon_path'] = <<<SQL
  UPDATE cache_occurrences_functional u
  SET taxon_path=ctp.path
  FROM occurrences o
  #join_needs_update#
  JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
  JOIN cache_taxon_paths ctp ON ctp.external_key=cttl.external_key AND ctp.taxon_list_id=cttl.taxon_list_id
  WHERE u.id=o.id
  AND u.taxon_path IS NULL
SQL;

// Fill in classifier agreement.
$config['occurrences']['update']['functional_classification_defaults'] = <<<SQL
  -- Set a default of disagreement for all records with classifier info.
  UPDATE cache_occurrences_functional u
  SET classifier_agreement=false
  FROM occurrences o
  #join_needs_update#
  JOIN occurrence_media m ON m.occurrence_id=o.id AND m.deleted=false
  JOIN classification_results_occurrence_media crom ON crom.occurrence_media_id=m.id
  WHERE u.id=o.id
SQL;

// For records with classifier info where a suggestion matches the current det,
// set agreement to true if the classifier chose that suggestion as the best match.
$config['occurrences']['update']['functional_classification'] = <<<SQL
  UPDATE cache_occurrences_functional u
  SET classifier_agreement=COALESCE(cs.classifier_chosen, false)
  FROM occurrences o
  #join_needs_update#
  JOIN occurrence_media m ON m.occurrence_id=o.id AND m.deleted=false
  JOIN classification_results_occurrence_media crom ON crom.occurrence_media_id=m.id
  LEFT JOIN (classification_suggestions cs
    JOIN cache_taxa_taxon_lists cttl on cttl.id=cs.taxa_taxon_list_id
  ) ON cs.classification_result_id=crom.classification_result_id AND cs.deleted=false
  WHERE u.id=o.id
  AND (cttl.external_key=u.taxa_taxon_list_external_key OR cs.id IS NULL)
SQL;

// Ensure occurrence sensitivity changes apply to parent sample cache data.
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
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      o.sensitivity_precision,
      case s.privacy_precision when 0 then 10000 else s.privacy_precision end,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(spv.int_value, spv.float_value)>=50001 then 1000000
        when coalesce(spv.int_value, spv.float_value)>=5001 then 100000
        when coalesce(spv.int_value, spv.float_value)>=501 then 10000
        when coalesce(spv.int_value, spv.float_value) between 51 and 500 then 1000
        when coalesce(spv.int_value, spv.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      o.confidential,
      greatest(
        o.sensitivity_precision,
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
  output_sref_system=get_output_system(
    reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      o.confidential,
      greatest(
        o.sensitivity_precision,
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
  verifier=pv.surname || ', ' || pv.first_name,
  licence_code=li.code,
  attr_behaviour=CASE a_behaviour.data_type
    WHEN 'T'::bpchar THEN v_behaviour.text_value
    WHEN 'L'::bpchar THEN t_behaviour.term
    ELSE NULL::text
  END,
  attr_reproductive_condition=CASE a_reproductive_condition.data_type
    WHEN 'T'::bpchar THEN v_reproductive_condition.text_value
    WHEN 'L'::bpchar THEN t_reproductive_condition.term
    ELSE NULL::text
  END,
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
  attr_det_first_name=COALESCE(CASE a_det_first_name.data_type
      WHEN 'T'::bpchar THEN v_det_first_name.text_value
      ELSE NULL::text
  END, CASE WHEN a_det_full_name.data_type='T' AND v_det_full_name.text_value IS NOT NULL THEN null ELSE pd.first_name END),
  attr_det_last_name=COALESCE(CASE a_det_last_name.data_type
      WHEN 'T'::bpchar THEN v_det_last_name.text_value
      ELSE NULL::text
  END, CASE WHEN a_det_full_name.data_type='T' AND v_det_full_name.text_value IS NOT NULL THEN null ELSE pd.surname END),
  attr_det_full_name=COALESCE(CASE a_det_full_name.data_type
      WHEN 'T'::bpchar THEN v_det_full_name.text_value
      ELSE NULL::text
  END, pd.surname || ', ' || pd.first_name)
FROM occurrences o
#join_needs_update#
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
LEFT JOIN users uv on uv.id=o.verified_by_id and uv.deleted=false
LEFT JOIN people pv on pv.id=uv.person_id and pv.deleted=false
LEFT JOIN people pd on pd.id=o.determiner_id and pd.deleted=false
LEFT JOIN licences li on li.id=s.licence_id
LEFT JOIN (sample_attribute_values spv
  JOIN sample_attributes spa on spa.id=spv.sample_attribute_id and spa.deleted=false
      and spa.system_function='sref_precision'
) on spv.sample_id=s.id and spv.deleted=false
LEFT JOIN (occurrence_attribute_values v_behaviour
  JOIN occurrence_attributes a_behaviour on a_behaviour.id=v_behaviour.occurrence_attribute_id and a_behaviour.deleted=false and a_behaviour.system_function='behaviour'
  LEFT JOIN cache_termlists_terms t_behaviour on a_behaviour.data_type='L' and t_behaviour.id=v_behaviour.int_value
) on v_behaviour.occurrence_id=o.id and v_behaviour.deleted=false
LEFT JOIN (occurrence_attribute_values v_reproductive_condition
  JOIN occurrence_attributes a_reproductive_condition on a_reproductive_condition.id=v_reproductive_condition.occurrence_attribute_id and a_reproductive_condition.deleted=false and a_reproductive_condition.system_function='reproductive_condition'
  LEFT JOIN cache_termlists_terms t_reproductive_condition on a_reproductive_condition.data_type='L' and t_reproductive_condition.id=v_reproductive_condition.int_value
) on v_reproductive_condition.occurrence_id=o.id and v_reproductive_condition.deleted=false
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

$config['occurrences']['insert']['functional'] = "INSERT INTO cache_occurrences_functional(
            id, sample_id, website_id, survey_id, input_form, location_id,
            location_name, public_geom,
            date_start, date_end, date_type, created_on, updated_on, verified_on,
            created_by_id, group_id, taxa_taxon_list_id, preferred_taxa_taxon_list_id,
            taxon_meaning_id, taxa_taxon_list_external_key, family_taxa_taxon_list_id,
            taxon_group_id, taxon_rank_sort_order, record_status, record_substatus,
            certainty, query, sensitive, private, hide_sample_as_private, release_status,
            marine_flag, freshwater_flag, terrestrial_flag, non_native_flag, data_cleaner_result,
            training, zero_abundance, licence_id, import_guid, confidential, external_key,
            taxon_path, blocked_sharing_tasks, parent_sample_id, verification_checks_enabled,
            media_count, identification_difficulty, dna_derived)
SELECT distinct on (o.id) o.id, o.sample_id, o.website_id, s.survey_id, COALESCE(sp.input_form, s.input_form), s.location_id,
    case when o.confidential=true or o.sensitivity_precision is not null or s.privacy_precision is not null
        then null else coalesce(l.name, s.location_name, lp.name, sp.location_name) end,
    reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      o.confidential,
      greatest(
        o.sensitivity_precision,
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    ) as public_geom,
    s.date_start, s.date_end, s.date_type, o.created_on, o.updated_on, o.verified_on,
    o.created_by_id, coalesce(s.group_id, sp.group_id), o.taxa_taxon_list_id, cttl.preferred_taxa_taxon_list_id,
    cttl.taxon_meaning_id, cttl.external_key, cttl.family_taxa_taxon_list_id,
    cttl.taxon_group_id, cttl.taxon_rank_sort_order, o.record_status, o.record_substatus,
    case when certainty.sort_order is null then null
        when certainty.sort_order <100 then 'C'
        when certainty.sort_order <200 then 'L'
        else 'U'
    end,
    null,
    o.sensitivity_precision is not null, s.privacy_precision is not null, s.privacy_precision IS NOT NULL AND s.privacy_precision=0, o.release_status,
    cttl.marine_flag, cttl.freshwater_flag, cttl.terrestrial_flag, cttl.non_native_flag, null,
    o.training, o.zero_abundance, s.licence_id, o.import_guid, o.confidential, o.external_key,
    ctp.path,
    CASE WHEN u.allow_share_for_reporting
      AND u.allow_share_for_peer_review AND u.allow_share_for_verification
      AND u.allow_share_for_data_flow AND u.allow_share_for_moderation
      AND u.allow_share_for_editing
    THEN null
    ELSE
      ARRAY_REMOVE(ARRAY[
        CASE WHEN u.allow_share_for_reporting=false THEN 'R' ELSE NULL END,
        CASE WHEN u.allow_share_for_peer_review=false THEN 'P' ELSE NULL END,
        CASE WHEN u.allow_share_for_verification=false THEN 'V' ELSE NULL END,
        CASE WHEN u.allow_share_for_data_flow=false THEN 'D' ELSE NULL END,
        CASE WHEN u.allow_share_for_moderation=false THEN 'M' ELSE NULL END,
        CASE WHEN u.allow_share_for_editing=false THEN 'E' ELSE NULL END
      ], NULL)
    END,
    s.parent_id,
    w.verification_checks_enabled,
    (SELECT COUNT(om.*) FROM occurrence_media om WHERE om.occurrence_id=o.id AND om.deleted=false),
    (SELECT cts.identification_difficulty FROM cache_taxon_searchterms cts where cts.taxa_taxon_list_id=o.taxa_taxon_list_id AND cts.simplified=false),
    dnao.id IS NOT NULL
FROM occurrences o
#join_needs_update#
LEFT JOIN cache_occurrences_functional co on co.id=o.id
JOIN websites w ON w.id=o.website_id AND w.deleted=false
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN samples sp ON sp.id=s.parent_id AND  sp.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
LEFT JOIN locations lp ON lp.id=sp.location_id AND lp.deleted=false
JOIN users u ON u.id=o.created_by_id -- deleted users records still included.
JOIN cache_taxa_taxon_lists cttl ON cttl.id=o.taxa_taxon_list_id
LEFT JOIN cache_taxon_paths ctp ON ctp.external_key=cttl.external_key AND ctp.taxon_list_id=#master_list_id#
LEFT JOIN (occurrence_attribute_values oav
    JOIN termlists_terms certainty ON certainty.id=oav.int_value
    JOIN occurrence_attributes oa ON oa.id=oav.occurrence_attribute_id and oa.deleted=false and oa.system_function='certainty'
  ) ON oav.occurrence_id=o.id AND oav.deleted=false
LEFT JOIN dna_occurrences dnao
    ON dnao.occurrence_id=o.id AND dnao.deleted=false
WHERE o.deleted=false
AND co.id IS NULL
";

// Insert can use same query as update to fill in the classifier agreement and
// taxon paths.
$config['occurrences']['insert']['functional_taxon_path'] = $config['occurrences']['update']['functional_taxon_path'];
$config['occurrences']['insert']['functional_classification'] = $config['occurrences']['update']['functional_classification'];

$config['occurrences']['insert']['functional_sensitive'] = <<<SQL
  UPDATE cache_samples_functional cs
  SET location_id=null, location_name=null
  FROM occurrences o
  #join_needs_update#
  WHERE o.sample_id=cs.id
  AND o.deleted=false
  AND o.sensitivity_precision IS NOT NULL
SQL;

$config['occurrences']['insert']['nonfunctional'] = "
INSERT INTO cache_occurrences_nonfunctional(
            id, comment, sensitivity_precision, privacy_precision, output_sref, output_sref_system, verifier, licence_code)
SELECT o.id,
  o.comment, o.sensitivity_precision,
  s.privacy_precision,
  get_output_sref(
    greatest(
      round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer,
      o.sensitivity_precision,
      case s.privacy_precision when 0 then 10000 else s.privacy_precision end,
      -- work out best square size to reflect a lat long's true precision
      case
        when coalesce(spv.int_value, spv.float_value)>=50001 then 1000000
        when coalesce(spv.int_value, spv.float_value)>=5001 then 100000
        when coalesce(spv.int_value, spv.float_value)>=501 then 10000
        when coalesce(spv.int_value, spv.float_value) between 51 and 500 then 1000
        when coalesce(spv.int_value, spv.float_value) between 6 and 50 then 100
        else 10
      end,
      10 -- default minimum square size
    ), reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      o.confidential,
      greatest(
        o.sensitivity_precision,
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
  get_output_system(
    reduce_precision(
      coalesce(s.geom, l.centroid_geom),
      o.confidential,
      greatest(
        o.sensitivity_precision,
        case s.privacy_precision when 0 then 10000 else s.privacy_precision end
      )
    )
  ),
  pv.surname || ', ' || pv.first_name,
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
LEFT JOIN users uv on uv.id=o.verified_by_id and uv.deleted=false
LEFT JOIN people pv on pv.id=uv.person_id and pv.deleted=false
WHERE o.deleted=false
AND co.id IS NULL
";

$config['occurrences']['insert']['nonfunctional_attrs'] = "
UPDATE cache_occurrences_nonfunctional
SET
  attr_behaviour=CASE a_behaviour.data_type
      WHEN 'T'::bpchar THEN v_behaviour.text_value
      WHEN 'L'::bpchar THEN t_behaviour.term
      ELSE NULL::text
  END,
  attr_reproductive_condition=CASE a_reproductive_condition.data_type
      WHEN 'T'::bpchar THEN v_reproductive_condition.text_value
      WHEN 'L'::bpchar THEN t_reproductive_condition.term
      ELSE NULL::text
  END,
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
LEFT JOIN (occurrence_attribute_values v_behaviour
  JOIN occurrence_attributes a_behaviour on a_behaviour.id=v_behaviour.occurrence_attribute_id and a_behaviour.deleted=false and a_behaviour.system_function='behaviour'
  LEFT JOIN cache_termlists_terms t_behaviour on a_behaviour.data_type='L' and t_behaviour.id=v_behaviour.int_value
) on v_behaviour.occurrence_id=o.id and v_behaviour.deleted=false
LEFT JOIN (occurrence_attribute_values v_reproductive_condition
  JOIN occurrence_attributes a_reproductive_condition on a_reproductive_condition.id=v_reproductive_condition.occurrence_attribute_id and a_reproductive_condition.deleted=false and a_reproductive_condition.system_function='reproductive_condition'
  LEFT JOIN cache_termlists_terms t_reproductive_condition on a_reproductive_condition.data_type='L' and t_reproductive_condition.id=v_reproductive_condition.int_value
) on v_reproductive_condition.occurrence_id=o.id and v_reproductive_condition.deleted=false
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

$config['occurrences']['join_needs_update'] = 'join needs_update_occurrences nu on nu.id=o.id and nu.deleted=false';
$config['occurrences']['key_field'] = 'o.id';
