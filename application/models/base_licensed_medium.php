<?php defined('SYSPATH') or die('No direct script access.');

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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

/**
 * Base class for the models which represent a media file that has a licence.
 *
 * @link http://indicia-docs.readthedocs.io/en/latest/developing/data-model.html
 */
class Base_licensed_medium_Model extends ORM {

  /**
   * Fill in licence link and media type default before submission.
   *
   * If a submission is for an insert and does not contain the licence ID for
   * the data it contains, look it up from the user's settings and apply it to
   * the submission. If the media_type_id is not populated, default to
   * the ID for image:local.
   */
  protected function preSubmit() {
    if (!array_key_exists('id', $this->submission['fields']) || empty($this->submission['fields']['id']['value'])) {
      global $remoteUserId;
      if (isset($remoteUserId)) {
        $userId = $remoteUserId;
      }
      elseif (isset($_SESSION['auth_user'])) {
        $userId = $_SESSION['auth_user']->id;
      }
      if (isset($userId)) {
        $row = $this->db
          ->select('media_licence_id')
          ->from('users_websites')
          ->where([
            'user_id' => $userId,
            'website_id' => $this->identifiers['website_id'],
          ])
          ->get()->current();
        if ($row) {
          $this->submission['fields']['licence_id']['value'] = $row->media_licence_id;
        }
      }
      // Now fill in the media_type_id if not in submission.
      if (!array_key_exists('media_type_id', $this->submission['fields'])
          || empty($this->submission['fields']['media_type_id']['value'])) {
        $cache = Cache::instance();
        if ($cached = $cache->get('image-local-media_type_id')) {
          $mediaTypeId = $cached;
        }
        else {
          $mediaTypeId = $this->db
            ->select('id')
            ->from('cache_termlists_terms')
            ->where([
              'termlist_title' => 'Media types',
              'term' => 'Image:Local',
            ])
            ->get()->current()->id;
          $cache->set('image-local-media_type_id', $mediaTypeId);
        }
        $this->submission['fields']['media_type_id'] = ['value' => $mediaTypeId];
      }
    }
  }

}
