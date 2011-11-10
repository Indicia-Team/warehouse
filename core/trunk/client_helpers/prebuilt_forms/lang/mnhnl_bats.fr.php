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
 * Language terms for the survey_reporting_form_2 form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_Download' => 'Reports and Downloads',
	'LANG_Locations' => 'Sites',
	'LANG_Sites_Download' => 'Run a report to provide information on all the sites used for these surveys, plus their attributes. (CSV Format)',
	'LANG_Conditions_Download' => 'Run a report to provide information on all these surveys, including the conditions and the associated sites. This returns one row per survey, and excludes any species data. (CSV Format)',
	'LANG_Species_Download' => 'Run a report to provide information on species entered for these surveys. It includes the data for the surveys, conditions and the associated sites. This returns one row per occurrence. (CSV Format)',
	'LANG_Download_Button' => 'Download Report',
	'Edit' => 'Éditer',
	// 'Actions' is left unchanged
	// TBD translations for report grid headings.
	'SRef'=>'Coordonnées',
	// TBD Translations for species grid headings, species tab header, species comment header, conditions block headers.
	'LANG_Edit' => 'Éditer',
	'LANG_Add_Sample' => 'Ajouter nouvel échantillon',
	'LANG_Add_Sample_Single' => 'Add Unique',
	'LANG_Add_Sample_Grid' => 'Ajouter plusieurs occurrences',
	'LANG_Save' => 'Enregistrer',
	'save'=>'Enregistrer',
	'LANG_Cancel' => 'Annuler',
	'next step'=>'Suivant',
	'prev step'=>'Précédente',

	// 'Site' tab heading left alone
	'Existing Locations' => 'Sites existants',
	'LANG_Location_Code_Label' => 'Code',
	'LANG_Location_Code_Blank_Text' => 'Choisissez un emplacement existant par le code',
	'LANG_Location_Name_Label' => 'Nom du site',
	'LANG_Location_Name_Blank_Text' => 'Choisissez un site',
	'Create New Location' => 'Créer un nouvel emplacement',
	'village' => 'Village / Lieu-dit',
	'commune' => 'Commune',
	'LANG_PositionOutsideCommune' => 'The position you have choosen is outside the set of allowable Communes. You will not be able to save this position.',
	'site type' => 'Type de gîte',
	'site followup' => 'Pertinence du site pour un suivi régulier',
	'LANG_Georef_Label'=>'Chercher la position sur la carte',
	'LANG_Georef_SelectPlace' => 'Choisissez la bonne parmi les localités suivantes qui correspondent à votre recherche. (Cliquez dans la liste pour voir l\'endroit sur la carte.)',
	'LANG_Georef_NothingFound' => 'Aucun endroit n\'a été trouvé avec ce nom. Essayez avec le nom d\'une localité voisine.',
	'Latitude' => 'Coordonnées : X ',
	'Longitude' => 'Y ',
	'LANG_LatLong_Bumpf' => '(projection géographique LUREF en mètres)',
	'precision' => 'Précision',
	'codegsl' => 'Code GSL',
	'profondeur' => 'Profondeur',
	'development' => 'Développement',
	'search' => 'Chercher',
	'Location Comment' => 'Commentaires',
	'Clear Position' => 'Effacer les coordonnées',
	'View All Luxembourg' => 'Voir tout le Luxembourg',

	'LANG_Tab_otherinformation' => 'Conditions',
	'LANG_Date' => 'Date',
	'Recorder names' => 'Observateur(s)',
	'LANG_RecorderInstructions'=>"(Pour sélectionner plusieurs observateurs, maintenir la touche CTRL enfoncée)",
	'General' => 'Général',
	'Physical' => 'Caractéristiques de la cavité',
	'Microclimate' => 'Conditions microclimatiques',
	'Visit' => 'Visite',
	'Bat Visit' => 'Visite',
	'LANG_Site_Extra' => "(Numéro de passage / Nombre de passages durant l'hiver)",
	'cavity entrance' => 'Entrée de la cavité',
	'disturbances' => 'Perturbations',
	'Human Frequentation' => 'Fréquentation humaine du site',
	'Bats Temp Exterior' => "Température à l'extérieur de la cavité (Celsius)",
	'Bats Humid Exterior' => "Humidité relative hors de la cavité (%)",
	'Bats Temp Int 1' => "Température à l'intérieur de la cavité - A (Celsius)",
	'Bats Humid Int 1' => "Humidité à l'intérieur de la cavité - A (%)",
	'Bats Temp Int 2' => "Température à l'intérieur de la cavité - B (Celsius)",
	'Bats Humid Int 2' => "Humidité à l'intérieur de la cavité - B (%)",
	'Bats Temp Int 3' => "Température à l'intérieur de la cavité - C (Celsius)",
	'Bats Humid Int 3' => "Humidité à l'intérieur de la cavité - C (%)",
	'Positions Marked' => 'Emplacement(s) des prises de mesures indiqué(s) sur le relevé topographique',
	'Reliability' => "Fiabilité (exhaustivité) de l'inventaire",
	'Overall Comment' => 'Commentaires',

	'LANG_Tab_species' => 'Espèces',
	'species_checklist.species'=>'Espèces',
	'Bats Obs Type' => "Type d'observation",
	'SCLabel_Col1' => "Nombre d'individus",
	'SCLabel_Row1' => 'Vivant(s)',
	'SCLabel_Row2' => 'Mort(s)',
	'Excrement' => 'Excréments', 
	'Occurrence Reliability' => "Fiabilité de la determination",
	'No observation' => 'Aucune observation',
	'Comment' => 'Commentaires',
	'LANG_Duplicate_Taxon' => 'Vous avez sélectionné un taxon qui a déjà une entrée.',
	'Are you sure you want to delete this row?' => 'Etes-vous sûr de vouloir supprimer cette ligne?',

	'validation_required' => 'Veuillez entrer une valeur pour ce champ',
	'validation_max' => "S'il vous plaît entrer une valeur inférieure ou égale à {0}.",
	'validation_min' => "S'il vous plaît entrer une valeur supérieure ou égale à {0}.",
	'validation_number' => "S'il vous plaît entrer un numéro valide.",
	'validation_digits' => "S'il vous plaît entrer un nombre entier positif.",
	'validation_integer' => "S'il vous plaît entrer un nombre entier.",
	'validation_no_observation' => "Cette option doit être cochée si et seulement si il n'existe aucun donnée dans le tableau ci-dessus.",
	'validation_fillgroup'=>"S'il vous plaît entrer un de ces deux champs."
);