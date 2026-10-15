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
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

/**
 * Controller class for the taxon groups taxon lists plugin module.
 * Calling modes:
 * index - taxon_list_taxon_group/1 where 1 is the taxon_list_id
 */
class Taxontree_operation_Controller extends Indicia_Controller {

  public function index()
  {
        $view = new View('taxontree');
      $view->title = 'Taxon Tree';
      $this->template->content = $view;
      $this->template->title = 'Taxon Tree';
  }


  public function info($taxonListId = 15) {
    $this->auto_render = FALSE;

    $taxonListId = (int)$taxonListId;
    $db = Database::instance();

    $sql = "
      SELECT
        MAX(t.updated_on) AS last_updated,
        COUNT(t.id) AS count
      FROM taxa t
      JOIN taxa_taxon_lists ttl ON ttl.taxon_id = t.id
      WHERE ttl.taxon_list_id = $taxonListId
        AND NOT t.deleted
        AND NOT ttl.deleted
    ";

    $row = (array)$db->query($sql)->current();

    $row['taxon_list_id'] = $taxonListId;

    header('Content-Type: application/json');
    echo json_encode($row);
  }

  public function taxonlists() {
    $this->auto_render = FALSE;

    $db = Database::instance();

    $sql = "
      SELECT id, title, description
      FROM taxon_lists
      WHERE NOT deleted
      ORDER BY title ASC
    ";

    $rows = $db->query($sql);

    $data = [];

    foreach ($rows as $row) {
      $data[] = (array) $row;
    }

    header('Content-Type: application/json');

    echo json_encode($data);
  }

  public function tree($parentId = null, $taxonListId = 15 , $prefererd = FALSE )
  {

    $this->auto_render = FALSE;

    $db = Database::instance();

    $taxonListId = (int) $taxonListId;

    if ($parentId === null || $parentId == 0) {
      $where = 'parent_id IS NULL';
    } else {
      $parentId = (int) $parentId;
      $where = "parent_id = $parentId";
    }


    //$where = "parent_id IS NOT NULL";

    $sql = "
      WITH RECURSIVE taxon_tree AS (
        SELECT
          t.taxon,
          t.authority,
          t.search_code,
          t.external_key,
          t.organism_key,
          t.name_form,
          t.name_deprecated,
          t.organism_deprecated,
          (t.search_code = t.external_key) AS preferred_name,
          ttl.id,
          ttl.parent_id,
          ttl.taxon_id,
          0 AS level,
          LPAD(ttl.id::text, 10, '0') AS path,
          (t.taxon)::text AS namepath ,
          t.updated_on as updated_on ,
          ttl.taxon_meaning_id as meaning_id
        FROM taxa_taxon_lists ttl
        JOIN taxa t ON t.id = ttl.taxon_id
        WHERE ttl.taxon_list_id = $taxonListId
          AND ttl.parent_id IS NULL
          AND NOT ttl.deleted
          AND NOT t.deleted
          AND t.scientific = true
          AND ttl.preferred = true
        UNION ALL
        SELECT
          t.taxon,
          t.authority,
          t.search_code,
          t.external_key,
          t.organism_key,
          t.name_form,
          t.name_deprecated,
          t.organism_deprecated,
          (t.search_code = t.external_key) AS preferred_name,
          c.id,
          c.parent_id,
          c.taxon_id,
          p.level + 1,
          p.path || '.' || LPAD(c.id::text, 10, '0'),
          (p.namepath || ' > ' || t.taxon)::text  AS namepath,
          t.updated_on as updated_on,
          c.taxon_meaning_id as meaning_id
        FROM taxa_taxon_lists c
        JOIN taxon_tree p
          ON c.parent_id = p.id
        JOIN taxa t
          ON t.id = c.taxon_id
        WHERE c.taxon_list_id = $taxonListId
          AND NOT c.deleted
          AND NOT t.deleted
          AND t.scientific = true
          AND c.preferred = true
      )
    ";
    /*
 ,
          (
              SELECT ct.taxon
              FROM taxa_taxon_lists cttl
              JOIN taxa ct
                ON ct.id = cttl.common_taxon_id
              WHERE cttl.id = p.id 
              LIMIT 1
          ) AS common_name
    */
    $sql = $sql . "
      SELECT
        id::text AS id,
        COALESCE(parent_id::text, '#') AS parent,
        taxon AS text,
        search_code,
        organism_key,
        preferred_name,
        path,
        level, 
        (
          SELECT COUNT(*)
          FROM taxon_tree c
          WHERE c.parent_id = taxon_tree.id
        ) AS child_count,
         namepath,
         updated_on,
         meaning_id
      FROM taxon_tree 
      WHERE $where
      ORDER BY path
        
    ";

// $sql .= " LIMIT 10";   REMEMBER PREFERRED

    $start = microtime(true);
    
    $rows = $db->query($sql);

    $data = [];
    foreach ($rows as $row) {
      $row = (array) $row;

      $row['children'] = ((int)$row['child_count'] > 0);

      if ($row['level'] == 0) {
        $row['icon'] = 'glyphicon glyphicon-globe root-icon';        
      }
      elseif ($row['children']) {
        $row['icon'] = 'glyphicon glyphicon-folder-open branch-icon';
      }
      else {
        $row['icon'] = 'glyphicon glyphicon-leaf leaf-icon';
      }

      $row['a_attr'] = [
        'title' =>
          "TVK: {$row['search_code']}\n" .
          "Organism Key: {$row['organism_key']}\n" . 
          "Updated On: {$row['updated_on'] }\n" . 
          "Preferred: {$row['preferred_name']}\n" . 
          "Path: {$row['namepath']}\n" . 
          "Meaning Id: {$row['meaning_id']}\n"
      ];

      $data[] = $row;
    }

    $count = count($data);

    header('Content-Type: application/json');

    echo json_encode($data);
  }


  public function details($taxonMeaningId = 0, $taxonListId = 15)
  {
    $this->auto_render = FALSE;

    $taxonMeaningId = (int) $taxonMeaningId;
    $taxonListId = (int) $taxonListId;

    $db = Database::instance();

    $sql = "
      SELECT
        t.id,
        t.taxon,
        t.authority,
        t.search_code,
        t.external_key,
        t.organism_key,
        t.updated_on,
        t.name_form,
        l.iso as language_iso,
        l.language as language,
        tr.rank,
        ttl.taxon_meaning_id,
        ttl.preferred::boolean AS preferred,
        t.scientific::boolean AS scientific,
        t.name_deprecated::boolean AS name_deprecated,
        t.organism_deprecated::boolean AS organism_deprecated,
        CASE
          WHEN l.iso = 'lat'
            AND ttl.id = preferred_ttl.id THEN 'Latin'
          WHEN l.iso = 'lat'
            AND ttl.id <> preferred_ttl.id THEN 'Synonym'
          ELSE 'Vernacular'
        END AS name_type        
      FROM taxa t
      JOIN taxa_taxon_lists ttl
        ON ttl.taxon_id = t.id
      LEFT JOIN taxon_ranks tr
        ON tr.id = t.taxon_rank_id
      LEFT JOIN languages l
        ON l.id = t.language_id
      LEFT JOIN taxa_taxon_lists preferred_ttl
        ON preferred_ttl.taxon_meaning_id = ttl.taxon_meaning_id
        AND preferred_ttl.taxon_list_id = ttl.taxon_list_id
        AND preferred_ttl.preferred = true
        AND preferred_ttl.deleted = false   
      WHERE ttl.taxon_meaning_id = $taxonMeaningId
        AND ttl.taxon_list_id = $taxonListId
        AND NOT t.deleted
        AND NOT ttl.deleted
      ORDER BY
        ttl.preferred DESC,
        t.scientific DESC,
        t.taxon ASC
    ";

    $rows = $db->query($sql);

    $occurrenceStats = $this->getOccurrenceStats($taxonMeaningId);
   
    $groups = $this->getGroups($taxonMeaningId);
    $designations = $this->getDesignations($taxonMeaningId); 

    $data = [];

    foreach ($rows as $row) {
      $row = (array) $row;

    // L - Latin S - Synonym V - vernacular  O - other C - Code A - Abbreviation

      $row['deprecated'] =
        $row['name_deprecated'] ||
        $row['organism_deprecated'];       

      $data[] = $row;
    }

    header('Content-Type: application/json');

    echo json_encode([
      'taxon_list_id' => (int)$taxonListId,
      'taxon_meaning_id' => (int)$taxonMeaningId,
      'occurrence_count' => (int)$occurrenceStats['occurrence_count'],   
      'min_date' => $occurrenceStats['min_date'],
      'max_date' => $occurrenceStats['max_date'],         
      'groups' => (array)$groups,
      'designations' => (array)$designations,
      'names' => (array)$data

    ]);
  }

  private function getParent($parentid) {


  }

  private function getOccurrenceStats($taxonMeaningId) {
    $db = Database::instance();
    $sql = "
    SELECT COUNT(o.id) AS occurrence_count ,MIN(o.created_on) AS min_date, MAX(o.created_on) as max_date
      FROM occurrences o
        JOIN taxa_taxon_lists ttl
          ON ttl.id = o.taxa_taxon_list_id
        WHERE ttl.taxon_meaning_id = $taxonMeaningId
          AND NOT o.deleted
          AND NOT ttl.deleted;
    ";

    $row = $db->query($sql)->current();

    return [
      'occurrence_count' => (int)$row->occurrence_count,
      'min_date' => $row->min_date,
      'max_date' => $row->max_date,
    ];
  }

  private function getGroups($taxonMeaningId) {
    $db = Database::instance();

    $sql = "
      SELECT DISTINCT
        tg.id,
        tg.title
      FROM taxon_groups tg
      JOIN taxa t
        ON t.taxon_group_id = tg.id
      JOIN taxa_taxon_lists ttl
        ON ttl.taxon_id = t.id
      WHERE ttl.taxon_meaning_id = $taxonMeaningId
        AND NOT tg.deleted
      ORDER BY tg.title
    ";

    $rows = $db->query($sql);

    $data = [];
    foreach ($rows as $row) {
      $data[] = (array)$row;
    }

    return $data;
  }

  private function getDesignations($taxonMeaningId) {
    $db = Database::instance();

    $sql = "
      SELECT DISTINCT
        td.id,
        td.title
      FROM taxa_taxon_designations ttd
      JOIN taxon_designations td
        ON td.id = ttd.taxon_designation_id
      JOIN taxa_taxon_lists ttl
        ON ttl.taxon_id = ttd.taxon_id
      WHERE ttl.taxon_meaning_id = $taxonMeaningId
        AND NOT ttd.deleted
      ORDER BY td.title
    ";

    $rows = $db->query($sql);

    $data = [];
    foreach ($rows as $row) {
      $data[] = (array)$row;
    }

    return $data;
  }

  public function fetchOccurrences($meaningId) {
    $this->auto_render = FALSE;

    $es = new RestApiElasticsearch('es-occurrences');

    $query = (object) [
      'size' => 100,
      '_source' => [
        'id',
        'event.date_start',
        'event.date_end',
        'location.name',
        'location.output_sref',
        'identification.verification_status',
        'taxon.accepted_name'
      ],
      'sort' => [
        [
          'event.date_start' => [
            'order' => 'desc'
          ]
        ]
      ],
      'query' => (object) [
        'term' => (object) [
          'taxon.taxon_meaning_id' => (int) $meaningId
        ]
      ]
    ];

    header('Content-Type: application/json');

      $data = json_decode(
        $es->elasticRequest($query, 'json', TRUE),
        TRUE
      );

      $results = [];

      foreach ($data['hits']['hits'] as $hit) {
        $source = $hit['_source'];

        $results[] = [
          'id' => $source['id'],
          'date' => $source['event']['date_start'] ?? null,
          'location' => $source['location']['name'] ?? '',
          'gridref' => $source['location']['output_sref'] ?? '',
          'verified' => $source['identification']['verification_status'] ?? '',
          'taxon' => $source['taxon']['accepted_name'] ?? ''
        ];
      }

      echo json_encode($results);  
  }

}