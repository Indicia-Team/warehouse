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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

global $custom_terms;

/**
 * Language terms for the mnhnl_citizen_science_1 form.
 *
 * @package	Client
 */
$custom_terms = array(
  'abundance' => 'Zahl',
  'close'=>'Schliessen',
  'email'=>'Email',
  'first name'=>'Vorname',
  'happy for contact'=>'Ich bin einverstanden gegebenenfalls zu dieser Beobachtung kontaktiert zu werden',
  'next step'=>'Weiter',
  'occurrence:taxa_taxon_list_id'=>'Art',
  'phone number'=>'Telefonnummer',
  'prev step'=>'Zurück',
  'save'=>'Speichern',
  'sample:comment'=>'Bemerkung',
  'sample:date' => 'Datum',
  'sample:entered_sref'=>'Koordinaten',
  'search'=>'Suchen',
  'LANG_Georef_Label'=>'Standort auf der Karte suchen',
  'LANG_Georef_SelectPlace' => 'Wählen Sie zwischen den Ortschaften die Ihrer Suche entsprechen die Richtige aus. (Klicken Sie in die Liste um die Ortschaft auf der Karte zu sehen.)',
  'LANG_Georef_NothingFound' => 'Es wurde kein Ort mit diesem Namen gefunden. Versuchen Sie es mit dem Namen einer benachbarten Ortschaft.',
  'surname'=>'Nachname',
  'you are recording a'=>'Sie sind dabei eine Beobachtung einzugeben von: {1}. Bitte benutzen Sie das unten stehende Formular um die Details einzugeben.',

  // Tab titles (only visible if interface tabbed
  'about you'=>'Wer ich bin',
  'what did you see'=>'Was ich gesehen habe',
  'where was it'=>'Wo es war',
  'other information'=>'Andere Informationen',
  // Tab instructions
  'about you tab instructions'=>'<strong>Wer ich bin</strong><br/>Sagen Sie uns bitte wer Sie sind. Dies könnte wichtig sein, damit wir Sie kontaktieren können, falls ihre Beobachtung besonders interessant ist.',
  'species tab instructions'=>'<strong>Was ich gesehen habe</strong><br/>Klicken Sie auf die Blume, die Sie gesehen haben, danach auf Weiter.',
  'place tab instructions'=>'<strong>Wo es war</strong><br/>1. Suchen Sie Bitte auf der Karte den genauen Ort Ihrer Beobachtung. Sie können dazu die Ortschaften Suche benutzen.'.
      '<br/>2. Klicken Sie auf den genauen Standort der Beobachtung um die Koordinaten zu übernehmen.',
  'other tab instructions'=>'<strong>Aner Informationen</strong><br/>Sagen Sie uns bitte wann Sie die Blume gesehen haben, wie viele es ungefähr waren und wenn nötig weitere Bemerkungen.',

  'validation_required' => 'Bitte geben sie einen Wert ein für: %s',
  'validation_dateISO' => 'Bitte geben Sie ein gültiges Datum im Format JJJJ-MM-TT an.',
);