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
// 'Actions' is left unchanged
// TBD translations for report grid headings.
$custom_terms = array(
// Tab Titles
	'LANG_Main_Samples_Tab' => 'Echantillons',
	'LANG_Locations' => 'Sites',
	'LANG_Tab_species' => 'Espèces',
	'LANG_Trailer_Text' => "Coordination du programme de monitoring de la biodiversité au Luxembourg: <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (Département Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Ministère du Développement durable et des Infrastructures</a> (Département de l'environnement)",
// Navigation
	'LANG_Edit' => 'Éditer',
	'Edit' => 'Éditer',
	'LANG_Add_Sample' => 'Ajouter nouvel échantillon',
	'LANG_Add_Sample_Single' => 'Add single occurrence',
	'LANG_Add_Sample_Grid' => 'Ajouter plusieurs occurrences',
	'LANG_Save' => 'Enregistrer',
	'save'=>'Enregistrer',
	'LANG_Cancel' => 'Annuler',
	'LANG_Submit' => 'Enregistrer',
	'next step'=>'Suivant',
	'prev step'=>'Précédent',
// Main grid Selection
	'Site name' => 'Nom du site',
	'Actions' => 'Actions',
	'Delete'=>'Supprimer',
// Reports
	'LANG_Data_Download' => 'These reports provide details of the data entered in the survey.',
// Locations
	'Existing locations' => 'Sites existants',
	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Nom du site',
	'LANG_Multiple_Location_Types' => 'Certains sites sont mis en évidence en rouge car ils ne sont pas encore confirmée par un utilisateur Admin.',
	'Create New Location' => 'Créer un nouvel emplacement',
	'LANG_Location_Name_Blank_Text' => 'Choisissez un site',
	'SRef'=>'Coordonnées',
	'LANG_SRef_Label' => 'Coordonnées',
	'LANG_Location_X_Label' => 'Centre du site coordonnées: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_LatLong_Bumpf' => '(projection géographique LUREF en mètres)',
	'LANG_Location_Code_Label' => 'Code',
	'Location Comment' => 'Commentaires',
	'LANG_CommonInstructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (5x5km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_Instructions2'=>"Pour choisir un site, sélectionnez l'outil de sélection et cliquez sur le site sur la carte ou sélectionnez le site dans la liste ci-dessous. Vous pouvez ensuite modifier des attributs de ce site ou les références spatiales de ce site. Vous pouvez déplacer les points sélectionnés. Pour supprimer un point, placez la souris sur le point et pressez sur la touche « Delete » ou « d » de votre clavier.",
	'LANG_LocModTool_CantCreate' => "Vous ne pouvez pas créer de nouveaux sites via ce formulaire, mais uniquement modifier des sites existant.",
	'LANG_DE_Instructions2'=>"Pour choisir un site, sélectionnez l'outil de sélection et cliquez sur le site sur la carte ou sélectionnez le site dans la liste ci-dessous.<br />Vous pouvez ajouter un nouveau site : cliquez sur le bouton « Créer un nouveau site » sur la carte, sélectionnez l'outil « Ajouter un/des point(s) au site » et dessinez le site sur la carte. Chaque site peut être composé de plusieurs points. Vous pouvez également déplacer les points sélectionnés. Pour supprimer un point, placez la souris sur le point et pressez sur la touche « Delete » ou « d » de votre clavier.<br />Le fait de sélectionner un site existant supprimera toutes les informations relatives à un nouveau site.<br />Il n'est possible de modifier les détails d'un site existant via ce formulaire d'encodage que si vous êtes administrateur ou si vous êtes la seule personne à avoir encodé des données relatives à ce site.",
	'LANG_LocModTool_IDLabel'=>'Ancien nom du site',
	'LANG_DE_LocationIDLabel'=>'Site',
	'LANG_CommonChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_NoSitesInSquare'=>'There are no sites currently associated with this square',
	'LANG_NoSites'=>"Il n'y a actuellement aucune sites définis: s'il vous plaît créer un nouveau.",
	'LANG_CommonEmptyLocationID'=>'Choose an existing site',
	'LANG_CommonLocationNameLabel' => 'Nom du site',
	'LANG_LocModTool_NameLabel'=>'Nouveau nom',
	'LANG_LocModTool_DeleteLabel'=>'Supprimer',
	'LANG_LocModTool_DeleteInstructions'=>'Quand un site est supprimé, toutes les données relatives à ce site seront maintenues dans les rapports.',
	'LANG_TooFewPoints' => 'Il ya trop peu de points dans ce polygone - il doit y avoir au moins 3.',
	'LANG_TooFewLinePoints' => 'There are too few points in this line - there must be at least 2.',
	'LANG_CentreOutsideParent'=>'Il ya trop peu de points dans cette ligne - il doit y avoir au moins 2.',
	'LANG_PointOutsideParent'=>'Warning: the point you have created for your site is outside the square.',
	'LANG_LineOutsideParent'=>'Warning: the line you have created for your site has a centre which is outside the square.',
	'LANG_PolygonOutsideParent'=>'Warning: the polygon you have created for new site has a centre which is outside the square.',
	'LANG_ConfirmRemoveDrawnSite'=> "Cette action supprime le site existant que vous avez créé. Voulez-vous continuer?",
	'LANG_SelectTooltip'=>'Cliquez sur la carte pour sélectionner un site',
	'LANG_PolygonTooltip'=>'Pour dessiner des polygones pour le site',
	'LANG_LineTooltip'=>'Tracez des lignes pour le site',
	'LANG_PointTooltip'=>'Ajouter des points sur le site',
	'LANG_CancelSketchTooltip'=>'Annuler cette esquisse',
	'LANG_UndoSketchPointTooltip'=>'Annuler le dernier sommet créé',
	'LANG_StartNewSite'=>'Créer un nouveau site',
	'LANG_RemoveNewSite'=>'Supprimer le site sélectionné nouvelle',
	'LANG_ZoomToSite'=>'Zoomer sur le site',
	'LANG_ZoomToParent'=>'Zoom to square (5x5km)',
	'LANG_ZoomToCountry'=>'Voir tout le Luxembourg',
	'LANG_Location_Type_Label'=>'Statut du site',
	'LANG_Location_Type_Primary'=>'Submitted',
	'LANG_Location_Type_Secondary'=>'Confirmed',
	'LANG_CommonLocationCodeLabel'=>'Code',
	'LANG_LocationModTool_CommentLabel'=>'Commentaires',
	'LANG_DuplicateName'=>'Attention: il ya un autre endroit de ce nom.',
	'LANG_PointsLegend'=>'Coordonnées des points individuels',
	'LANG_Grid_X_Label'=>'X',
	'LANG_Grid_Y_Label'=>'Y',
	'Latitude' => 'Coordonnées: X',
	'Longitude' => 'Y',
	'LANG_DeletePoint'=>'Supprimer ce point',
	'LANG_AddPoint'=>'Ajouter ce point',
	'LANG_HighlightPoint'=>'Mettez en surbrillance ce point',
	'LANG_SHP_Download_Legend'=> 'Fichiers SHP télécharger',
	'LANG_Shapefile_Download'=> 'Ce téléchargement fournir un zip de fichiers SHP pour les points dans les lieux. Cliquez pour sélectionner:',
// Georeferencing
	'search' => 'Chercher',
	'LANG_Georef_Label'=>'Chercher la position sur la carte',
	'LANG_Georef_SelectPlace' => 'Choisissez la bonne parmi les localités suivantes qui correspondent à votre recherche. (Cliquez dans la liste pour voir l\'endroit sur la carte.)',
	'LANG_Georef_NothingFound' => 'Aucun endroit n\'a été trouvé avec ce nom. Essayez avec le nom d\'une localité voisine.',
	'LANG_PositionOutsideCommune' => "La position que vous avez choisi est en dehors de l'ensemble des communes autorisées. Vous ne pourrez pas enregistrer cette position.",
	'LANG_CommuneLookUpFailed' => 'Commune Lookup Failed',
// Conditions
	'General' => 'Général',
	'Physical' => 'Caractéristiques de la cavité',
	'Microclimate' => 'Conditions microclimatiques',
	'LANG_Date' => 'Date',
	'Recorder names' => 'Observateur(s)',
	'LANG_RecorderInstructions'=>"(Pour sélectionner plusieurs observateurs, maintenir la touche CTRL enfoncée)",
	'LANG_Site_Extra' => "(Numéro de passage / Nombre de passages durant l'hiver)",
	'Overall Comment' => 'Commentaires',
// Species
	'species_checklist.species'=>'Espèces',
	'LANG_Duplicate_Taxon' => 'Vous avez sélectionné un taxon qui a déjà une entrée.',
	'LANG_SpeciesInstructions'=>"Les espèces peuvent être ajoutées en utilisant la case d'entrée ci-dessous. Une seule ligne peut être utilisée par espèce ou complexe d'espèces.<br />Cliquez sur la croix rouge devant une ligne pour la supprimer.",
	'Add species to list'=>'Ajouter une espèce à la liste',
	'Comment' => 'Commentaires',
	'Are you sure you want to delete this row?' => 'Etes-vous sûr de vouloir supprimer cette ligne?',
// Attributes
	'Village' => 'Village / Lieu-dit',
	'Site type' => 'Type de gîte',
	'Site type other' => 'If Others',
	// 'Code GSL' is unchanged in French
	'Depth' => 'Profondeur',
	'Precision' => 'Précision',
	'Development' => 'Développement',
	'Site followup' => 'Pertinence du site pour un suivi régulier',
	'Accompanied By' => 'Personne(s) accompagnante(s)',
	'Visit' => 'Visite',
	'Bat visit' => 'Visite',
	'Cavity entrance' => 'Entrée de la cavité',
	'Cavity entrance comment' => 'If the closure system is defective',
	'Disturbances' => 'Perturbations',
	'Disturbances other comment' => 'If Others',
	'Human frequentation' => 'Fréquentation humaine du site',
	'Temp Exterior' => "Température à l'extérieur de la cavité (Celsius)",
	'Humid Exterior' => "Humidité relative hors de la cavité (%)",
	'Temp Int 1' => "Température à l'intérieur de la cavité - A (Celsius)",
	'Humid Int 1' => "Humidité à l'intérieur de la cavité - A (%)",
	'Temp Int 2' => "Température à l'intérieur de la cavité - B (Celsius)",
	'Humid Int 2' => "Humidité à l'intérieur de la cavité - B (%)",
	'Temp Int 3' => "Température à l'intérieur de la cavité - C (Celsius)",
	'Humid Int 3' => "Humidité à l'intérieur de la cavité - C (%)",
	'Positions marked' => 'Emplacement(s) des prises de mesures indiqué(s) sur le relevé topographique',
	'Reliability' => "Fiabilité (exhaustivité) de l'inventaire",
	'Num alive' => 'Vivant(s)',
	'Num dead' => 'Mort(s)',
	'Excrement' => 'Excréments',
	'Occurrence reliability' => "Fiabilité",
	'No observation' => 'Aucune observation',
// Validation
	'validation_required' => 'Veuillez entrer une valeur pour ce champ',
	'validation_max' => "S'il vous plaît entrer une valeur inférieure ou égale à {0}.",
	'validation_min' => "S'il vous plaît entrer une valeur supérieure ou égale à {0}.",
	'validation_number' => "S'il vous plaît entrer un numéro valide.",
	'validation_digits' => "S'il vous plaît entrer un nombre entier positif.",
	'validation_integer' => "S'il vous plaît entrer un nombre entier.",
	'validation_no_observation' => "Cette option doit être cochée si et seulement si il n'existe aucun donnée dans le tableau ci-dessus.",
	'validation_fillgroup'=>"S'il vous plaît définissez un de ces trois options."
);