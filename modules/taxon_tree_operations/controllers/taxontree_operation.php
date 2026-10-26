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

/**
 * Displays the Taxon Tree user interface.
 *
 * Loads the Taxon Tree view and sets the page title.
 *
 * @return void
 *   Renders the Taxon Tree page.
 */
  public function index() {
        $view = new View('taxontree');
      $view->title = 'Taxon Tree';
      $this->template->content = $view;
      $this->template->title = 'Taxon Tree';
  }

 /**
 * Returns summary information for a taxon list.
 *
 * Retrieves the total number of taxa and the most recent update date
 * for taxa associated with the supplied taxon list. Results are
 * returned as a JSON response for use by the Taxon Tree user interface.
 *
 * @param int $taxonListId
 *   The taxon list identifier. Defaults to 15.
 *
 * @return void
 *   Outputs a JSON response containing:
 *   - taxon_list_id: The taxon list identifier.
 *   - last_updated: Most recent taxon update timestamp.
 *   - count: Number of taxa in the taxon list.
 */
  public function info($taxonListId = 15) {
    $this->auto_render = FALSE;

    $taxonListId = (int) $taxonListId;
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

    $row = (array) $db->query($sql)->current();

    $row['taxon_list_id'] = $taxonListId;

    header('Content-Type: application/json');
    echo json_encode($row);
  }

 /**
 * Returns a list of available taxon lists.
 *
 * Retrieves all non-deleted taxon lists and returns their identifiers,
 * titles and descriptions as a JSON response. The results are ordered
 * alphabetically by title for display within the Taxon Tree interface.
 *
 * @return void
 *   Outputs a JSON array containing:
 *   - id: Taxon list identifier.
 *   - title: Taxon list title.
 *   - description: Taxon list description.
 */
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

 /**
 * Returns taxon tree nodes for display in the Taxon Tree browser.
 *
 * Builds a hierarchical view of scientific taxa within a taxon list,
 * returning either the root nodes or the immediate children of a
 * specified parent node. Results are formatted for consumption by
 * the jsTree control, including child node indicators, icons and
 * tooltip attributes.
 *
 * Optionally restricts the hierarchy to preferred taxa only.
 *
 * @param int|NULL $parentId
 *   Parent taxon list node identifier. Set to NULL or 0 to retrieve
 *   root-level taxa.
 * @param int $taxonListId
 *   Taxon list identifier. Defaults to 15.
 * @param bool $preferred
 *   If TRUE, only preferred taxa are returned. If FALSE, both
 *   preferred and non-preferred taxa are included.
 *
 * @return void
 *   Outputs a JSON array of jsTree node objects containing:
 *   - id: Taxon list node identifier.
 *   - parent: Parent node identifier or '#'.
 *   - text: Taxon name.
 *   - children: Indicates whether child nodes exist.
 *   - icon: CSS class used to display the node icon.
 *   - a_attr: Additional node attributes including tooltip text.
 */
  public function tree($parentId = NULL, $taxonListId = 15, $preferred = TRUE) {
    $this->auto_render = FALSE;
    $db = Database::instance();
    $taxonListId = (int) $taxonListId;
    if ($parentId === NULL || $parentId == 0) {
      $where = 'parent_id IS NULL';
    } else {
      $parentId = (int) $parentId;
      $where = "parent_id = $parentId";
    }

    $preferredWhereA = "";
    $preferredWhereB = "";
    if ($preferred) {
      $preferredWhereA = " AND ttl.preferred = true";
      $preferredWhereB = " AND c.preferred = true";
    }
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
        WHERE ttl.taxon_list_id = ?
          AND ttl.parent_id IS NULL
          AND NOT ttl.deleted
          AND NOT t.deleted
          AND t.scientific = true
          $preferredWhereA
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
        WHERE c.taxon_list_id = ?
          AND NOT c.deleted
          AND NOT t.deleted
          AND t.scientific = true
          $preferredWhereB
      )
    ";
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
    $rows = $db->query($sql,
      $taxonListId,
      $taxonListId
    );
    $data = [];
    foreach ($rows as $row) {
      $row = (array) $row;
      $row['children'] = ((int) $row['child_count'] > 0);
      if ($row['level'] == 0) {
        $row['icon'] = 'glyphicon glyphicon-globe root-icon';        
      }
      else if ($row['children']) {
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
    header('Content-Type: application/json');
    echo json_encode($data);
  }


 /**
 * Returns detailed information for a taxon meaning.
 *
 * Retrieves taxon names associated with the supplied taxon meaning,
 * together with occurrence statistics, taxonomic groups,
 * conservation designations and the taxonomic hierarchy path within
 * the selected taxon list.
 *
 * @param int $taxonMeaningId
 *   The taxon meaning identifier.
 * @param int $taxonListId
 *   The taxon list identifier. Defaults to 15.
 *
 * @return void
 *   Outputs a JSON response containing:
 *   - taxon_list_id: Taxon list identifier.
 *   - taxon_meaning_id: Taxon meaning identifier.
 *   - occurrence_count: Number of associated occurrences.
 *   - min_date: Earliest occurrence date.
 *   - max_date: Latest occurrence date.
 *   - groups: Associated taxon groups.
 *   - designations: Associated taxon designations.
 *   - names: Taxon names, synonyms and vernacular names.
 *   - path: Hierarchical path ids for the requested taxon
 *   - taxon_path: Hierarchical path from the root taxon to the
 *     requested taxon.
 */
  public function details($taxonMeaningId = 0, $taxonListId = 15) {
    $this->auto_render = FALSE;
    $taxonMeaningId = (int) $taxonMeaningId;
    $taxonListId = (int) $taxonListId;
    $db = Database::instance();
    $where = " WHERE ttl.taxon_meaning_id = ?
        AND ttl.taxon_list_id = ?
        AND NOT t.deleted
        AND NOT ttl.deleted ";
    $sql = $this->detailSQL($where);
    $rows = $db->query($sql,
      $taxonMeaningId,
      $taxonListId
    );
    $occurrenceStats = $this->getOccurrenceStats($taxonMeaningId);
    $groups = $this->getGroups($taxonMeaningId);
    $designations = $this->getDesignations($taxonMeaningId); 
    $parentRow=$this->getParents(
          $taxonMeaningId,
          $taxonListId
    );
    if ($parentRow) {
        $path = explode(',', trim($parentRow->path, '{}'));
        $taxonPath = explode(',', trim($parentRow->taxon_path, '{}'));
    } else {
        $path = [];
        $taxonPath = [];
    }
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
      'taxon_list_id' => (int) $taxonListId,
      'taxon_meaning_id' => (int) $taxonMeaningId,
      'occurrence_count' => (int) $occurrenceStats['occurrence_count'],   
      'min_date' => $occurrenceStats['min_date'],
      'max_date' => $occurrenceStats['max_date'],         
      'groups' => (array) $groups,
      'designations' => (array) $designations,
      'names' => (array) $data,
      'path' => $path,
      'taxon_path' => $taxonPath,
    ]);
  }

 /**
 * Searches for taxa within a taxon list.
 *
 * Searches taxon names, TVKs and organism keys for records matching
 * the supplied search term. Matching taxa are returned together with
 * their taxonomic hierarchy to allow the client to locate and display
 * results within the Taxon Tree.
 *
 * Expected POST parameters:
 * - taxon: Search term.
 * - taxon_list_id: Taxon list identifier.
 *
 * @return void
 *   Outputs a JSON response containing:
 *   - taxon_list_id: Taxon list identifier.
 *   - searchterm: The submitted search term.
 *   - data: Array of matching taxa.
 *     - taxon_meaning_id: Taxon meaning identifier.
 *     - taxon: Taxon name.
 *     - preferred: Indicates whether the name is preferred.
 *     - deprecated: Indicates whether the name or organism is
 *       deprecated.
 *     - path: Taxonomic path expressed as a hierarchy of node IDs.
 *     - taxonPath: Taxonomic path expressed as a hierarchy of taxon
 *       names.
 */
  public function search() {
    $this->auto_render = FALSE;
    $db = Database::instance();
    $taxon = $this->input->post('taxon');
    $taxonListId = (int) $this->input->post('taxon_list_id');
    $where = "WHERE (
          t.taxon ILIKE ? 
          OR t.search_code ILIKE ?
          OR t.organism_key ILIKE ?
        )
        AND ttl.taxon_list_id = ?
        AND NOT t.deleted
        AND NOT ttl.deleted";
    $sql = $this->detailSQL($where);
    $rows = $db->query($sql, 
      $taxon,
      $taxon,
      $taxon,
      $taxonListId
    );
    $data = [];
    foreach ($rows as $row) {
      $row = (array) $row;
    // L - Latin S - Synonym V - vernacular  O - other C - Code A - Abbreviation
      $row['deprecated'] =
        $row['name_deprecated'] ||
        $row['organism_deprecated'];       
        $parentRow=$this->getParents(
          $row['taxon_meaning_id'],
          $taxonListId
        );
        if ($parentRow) {
            $row['path'] = explode(',', trim($parentRow->path, '{}'));
            $row['taxonPath'] = explode(',', trim($parentRow->taxon_path, '{}'));
        } else {
            $row['path'] = [];
            $row['taxonPath'] = [];
        }
        $data[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode([
      'taxon_list_id' =>  $taxonListId,
      'searchterm' => $taxon,
      'data' => $data
    ]);
  }

 /**
 * Retrieves the taxonomic hierarchy for a taxon meaning.
 *
 * Builds the ancestry of the preferred taxon within the specified
 * taxon list, returning both the hierarchy of taxon list node IDs
 * and the corresponding hierarchy of taxon names from the root taxon
 * to the selected taxon.
 *
 * @param int $taxonMeaningId
 *   The taxon meaning identifier.
 * @param int $taxonListId
 *   The taxon list identifier.
 *
 * @return object|NULL
 *   Database record containing:
 *   - path: Array of taxon list node identifiers representing the
 *     hierarchy from the root taxon to the selected taxon.
 *   - taxon_path: Array of taxon names representing the hierarchy
 *     from the root taxon to the selected taxon.
 *
 *   Returns NULL if no matching preferred taxon is found.
 */
  private function getParents($taxonMeaningId, $taxonListId) {
    $db = Database::instance();
    $sql = "
        WITH RECURSIVE parents AS (
          SELECT
            ttl.id,
            ttl.parent_id,
            t.taxon,
            0 AS level
          FROM taxa_taxon_lists ttl
          JOIN taxa t
            ON t.id = ttl.taxon_id
          WHERE ttl.taxon_meaning_id = $taxonMeaningId
            AND ttl.taxon_list_id = $taxonListId
            AND ttl.preferred = true
            AND NOT ttl.deleted
          UNION ALL
          SELECT
            p.id,
            p.parent_id,
            t.taxon,
            parents.level + 1
          FROM taxa_taxon_lists p
          JOIN taxa t
            ON t.id = p.taxon_id
          JOIN parents
            ON parents.parent_id = p.id
          WHERE NOT p.deleted
        )
        SELECT
          array_agg(id ORDER BY level DESC) AS path,
          array_agg(taxon ORDER BY level DESC) AS taxon_path
        FROM parents;
    ";
    $row = $db->query($sql)->current();
    return $row;
  }

 /**
 * Builds the SQL query used to retrieve taxon names and metadata.
 *
 * Constructs the base query used by the details and search methods
 * to retrieve taxon names, synonyms and vernacular names associated
 * with a taxon meaning. The supplied WHERE clause is appended to
 * apply context-specific filtering.
 *
 * The query also determines:
 * - Whether a name is preferred.
 * - Whether data entry is permitted.
 * - Whether a name is scientific.
 * - Whether a name is deprecated.
 * - The name type (Latin, Synonym or Vernacular).
 *
 * @param string $where
 *   SQL WHERE clause used to filter the result set.
 *
 * @return string
 *   The completed SQL query.
 */
  private function detailSQL($where) {
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
        ttl.allow_data_entry::boolean AS allow_data_entry,
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
      $where
      ORDER BY
        ttl.preferred DESC,
        t.scientific DESC,
        t.taxon ASC
    ";
    return $sql;
  }

 /**
 * Retrieves occurrence statistics for a taxon meaning.
 *
 * Calculates the number of associated occurrences together with the
 * earliest and latest occurrence creation dates for the supplied
 * taxon meaning.
 *
 * @param int $taxonMeaningId
 *   The taxon meaning identifier.
 *
 * @return array
 *   An associative array containing:
 *   - occurrence_count: Total number of occurrences.
 *   - min_date: Earliest occurrence creation date.
 *   - max_date: Latest occurrence creation date.
 */
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
      'occurrence_count' => (int) $row->occurrence_count,
      'min_date' => $row->min_date,
      'max_date' => $row->max_date,
    ];
  }

 /**
 * Retrieves the taxon groups associated with a taxon meaning.
 *
 * Identifies all non-deleted taxon groups linked to taxa within the
 * supplied taxon meaning and returns them in alphabetical order.
 *
 * @param int $taxonMeaningId
 *   The taxon meaning identifier.
 *
 * @return array
 *   An array of taxon groups, where each item contains:
 *   - id: Taxon group identifier.
 *   - title: Taxon group title.
 */
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
      $data[] = (array) $row;
    }
    return $data;
  }

 /**
 * Retrieves taxon designations associated with a taxon meaning.
 *
 * Identifies all distinct designations linked to taxa within the
 * supplied taxon meaning and returns them in alphabetical order.
 * Examples of designations include conservation, legal or status
 * classifications applied to a taxon.
 *
 * @param int $taxonMeaningId
 *   The taxon meaning identifier.
 *
 * @return array
 *   An array of taxon designations, where each item contains:
 *   - id: Taxon designation identifier.
 *   - title: Taxon designation title.
 */
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
      $data[] = (array) $row;
    }
    return $data;
  }

 /**
 * Retrieves occurrence records for a taxon meaning.
 *
 * Queries the Elasticsearch occurrence index for records associated
 * with the supplied taxon meaning and returns a simplified list of
 * occurrence details ordered by observation date, most recent first.
 *
 * Results are limited to the first 100 matching records.
 *
 * @param int $meaningId
 *   The taxon meaning identifier.
 *
 * @return void
 *   Outputs a JSON array of occurrence records containing:
 *   - id: Occurrence identifier.
 *   - date: Occurrence start date.
 *   - location: Location name.
 *   - gridref: Grid reference.
 *   - verified: Verification status.
 *   - taxon: Accepted taxon name.
 */
  public function fetchOccurrences($meaningId) {
    $this->auto_render = FALSE;
    $es = new RestApiElasticsearch('es-occurrences');
    $query = (object) [
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
          'date' => $source['event']['date_start'] ?? NULL,
          'location' => $source['location']['name'] ?? '',
          'gridref' => $source['location']['output_sref'] ?? '',
          'verified' => $source['identification']['verification_status'] ?? '',
          'taxon' => $source['taxon']['accepted_name'] ?? ''
        ];
      }
      echo json_encode($results);  
  }

}