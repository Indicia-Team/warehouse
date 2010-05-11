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
 * Language terms for the pollenators form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Insufficient_Privileges' => "Vous n'avez pas de privilèges suffisants pour accéder à la page 'Créer d'un Collection'"
	,'LANG_Collection_Name_Label' => 'Nommer votre collection'
	,'Protocol' => 'Choisir un protocole'
	,'LANG_Modify' => 'MODIFIER'
	,'LANG_Reinitialise' => 'INITIALISER'
	,'LANG_Collection_Details' => 'Détails de la collection'
	,'LANG_Protocol_Title_Label' => 'protocole'
	,'LANG_Validate' => 'VALIDER'
	,'LANG_Unable_To_Reinit' => 'Impossible de réinitialiser parce que les valeurs existantes ne passent pas la validation'
	,'LANG_Confirm_Reinit' => 'Etes-vous sûr de vouloir réinitialiser? Toutes les données contre cette collection sera supprimé.'
	
	,'LANG_Flower_Station' => "VOTRE STATION FLORALE"
	,'LANG_Upload_Flower' => "Charger l'image de la Fleur"
	,'LANG_Identify_Flower' => 'Indiquer le nom de cette fleur'
	,'LANG_ID_Flower_Later' => "Vous preferez l'identifier plus tard:"
	,'LANG_Flower_Species' => "Vous connaissez le taxon correspondant à cette fleur"
	,'LANG_Flower_ID_Key_label' => "Vous ne connaissez pas le nom de cette fleur"
	,'LANG_Launch_ID_Key' => "Lancer la clé d'identification"
	,'LANG_Choose_Taxon' => "Choisissez un taxon dans la Liste"
	,'LANG_Upload_Environment' => "Charger l'image de son environnement"
	,'LANG_Environment_Notes' => "Cett image doit rendre compte de l'environnement botanique de la fleur (typiquement un champ de 2 mètre de large)" 
	,'LANG_Georef_Label' => 'Nom'
	,'LANG_Georef_Notes' => '(Ce peut être un village ou ville, région, département ou code postal.)'
	,'LANG_Location_Notes' => 'Localiser la fleur : placer votre repère sur la carte ou utilise les champs ci-dessous :'
	,'LANG_Or' => 'ou :'
	,'LANG_INSEE' => 'INSEE No.'
	,'LANG_Lat' => 'Lat./Long.'
	,'Flower Type' => "Il s'agit d'une fleur"
	,'Habitat' => "Il s'agit d'un habitat"
	,'Nearest House' => "Distance approximative entre votre fleur et la ruche d'abeille domestique la plus proche (mètre)"
	,'LANG_Validate_Flower' => 'VALIDER VOTRE STATION FLORALE'
	,'LANG_Must_Provide_Pictures' => "Les images doivent être téléchargées pour la fleur et de l'environnement"
	,'LANG_Must_Provide_Location' => 'Un emplacement doit être choisi'
	
	,'LANG_Date' => 'Date'
	,'LANG_Sessions_Title' => 'VOS SESSIONS'
	,'LANG_Session' => 'Session'
	,'LANG_Validate_Session' => 'VALIDER LA SESSION'
	,'LANG_Add_Session' => 'AJOUTER UNE SESSION'
	,'LANG_Delete_Session' => 'supprimer'
	,'Start Time' => 'Heure de début'
	,'End Time' => 'Heure de fin'
	,'Sky' => 'Ciel'
	,'Temperature Bands' => 'Température'
	,'Wind' => 'Vent'
	,'In Shade' => "Fleur à l’ombre"
	
	,'LANG_Photos' => "VOS PHOTOS D'INSECTE"
	,'LANG_Photo_Blurb' => 'Télécharger ou modifier vos observations.'
	,'LANG_Upload_Insect' => "Charger l'image d'insecte"
	,'LANG_Identify_Insect' => 'Indiquer le nom de cet insecte:'
	,'LANG_Insect_Species' => "Vous connaissez le taxon correspondant à cet insecte"
	,'LANG_Insect_ID_Key_label' => "Vous ne connaissez pas le nom de cet insecte"
	,'LANG_ID_Insect_Later' => "Vous preferez l'identifier plus tard:"
	,'LANG_Comment' => 'Commentaire'
	,'Number Insects' => "Nombre d'insectes de le même espace au moment précis où vous preniez cette photo"
	,'Foraging'=> "Cochez cette case si vous avez pris en photo cet insecte allieurs que sur la fleur, mais que vous l'y avez vu butiner"
	,'LANG_Validate_Insect' => "VALIDER L'INSECTE"
	,'LANG_Validate_Photos' => 'VALIDER VOS PHOTOS'
	,'LANG_Must_Provide_Insect_Picture' => 'Une image doit être téléchargée pour les insectes'
	,'LANG_Confirm_Insect_Delete' => 'Etes-vous sûr de vouloir supprimer cet insecte?'
	,'LANG_Delete_Insect' => 'Supprimer des insectes'
	
	,'LANG_Can_Complete_Msg' => "Vous avez identifié la fleur et un nombre suffisant d'insectes, vous pouvez maintenant clôturer la collection"
	,'LANG_Cant_Complete_Msg' => "Vous avez une ou l'autre: pas identifié la fleur, et / ou non identifié un nombre suffisant d'insectes. Vous devez corriger avant que vous pouvez clôturer la collection."
	,'LANG_Complete_Collection' => 'Clôturer la collection'
	,'LANG_Trailer_Head' => 'Après clôture'
	,'LANG_Trailer_Point_1' => "vous ne pourrez plus ajouter d'insectes à votre collection ; les avez-vous tous téléversé?"
	,'LANG_Trailer_Point_2' => "vous ne pouvez plus modifier les différentes valeurs décrivant cette station floral, sessions et insectes."
	,'LANG_Trailer_Point_3' => "vous pouvez modifier l'identification des insectes dans «Mes collections»"
	,'LANG_Trailer_Point_4' => "vous pourrez créer une nouvelle collection"
	
	,'validation_required' => "S'il vous plaît entrer une valeur"
	,'Yes' => 'Oui'
	,'No' => 'Non'
	,'LANG_Help_Button' => '?'
	
	,'LANG_Final_1' => 'Cette collection a été enregistrée et ajoutée à votre ensemble de collections'
	,'LANG_Final_2' => "Cette collection peut être consultée par rubrique «Mes collections», où vous pouvez changer l'identification de vos insectes"
	,'LANG_Consult_Collection' => 'Consulter cette collection'
	,'LANG_Create_New_Collection' => 'Créer la nouvelle collection'
	
	,'LANG_Indicia_Warehouse_Error' => 'Erreur renvoyée par Indicia Warehouse'
	
);