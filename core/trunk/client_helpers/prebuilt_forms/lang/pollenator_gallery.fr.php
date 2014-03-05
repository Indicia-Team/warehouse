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
 * Language terms for the pollenator insect form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Invocation_Error' => "Avertissement: GET valide les paramètres dans l'URL"
	,'LANG_Insufficient_Privileges' => "Vous n'avez pas de privilèges suffisants pour accéder à cette page."
	,'LANG_Please_Refresh_Page' => "Une erreur s'est produite. S'il vous plaît, actualisez la page."
	
	,'LANG_Enter_Filter_Name' => 'Entrer un nom pour ce filtre'
	,'LANG_Save_Filter_Button' => 'Enregistrer'
	,'LANG_Collection' => 'Retour à la Collection'
	,'LANG_Previous' => 'Précédent'
	,'LANG_Next' => 'Suivant'
	,'LANG_Add_Preferred_Insect' => 'Enregistrer dans mes insectes préférées'
	,'LANG_Validate' => 'Valider'
	,'LANG_Submit_delete-collection' => 'Delete this collection'
	,'LANG_Collection_Delete_Confirmation' => 'Are you sure you wish to delete this collection? It will no longer be returned by any searches carried out in the Gallery. If needed you may talk to the Website Administrator to reinstate it.'
	,'LANG_Add_Preferred_Collection'  => 'Enregistrer dans mes collections préférées'
	,'LANG_List' => 'Retour à la Liste'
	,'LANG_No_Collection_Results' => 'Aucune collection ne correspond à cette recherche.'
	,'LANG_No_Insect_Results' => 'Aucun insecte ne correspond à cette recherche.'

	,'LANG_Indentification_Title' => 'Identification'
	,'LANG_Doubt' => "émettre un doute"
	,'LANG_Doubt_Comment' => 'Commentez votre doute :'
	,'LANG_Default_Doubt_Comment' => "J'ai exprimé un doute sur cette identification parce que..."
	,'LANG_New_ID' => 'Proposer une nouvelle identification'
	,'LANG_Launch_ID_Key' => "Lancer la clé d'identification"
	,'LANG_Cancel_ID' => "Abandonner la clé d'identification"
	,'LANG_Taxa_Returned' => "Taxons retourné par la clé d'identification:"
	,'LANG_ID_Unrecognised' => 'Les suivants ne sont pas reconnus: '
	,'LANG_Taxa_Unknown_In_Tool' => 'Taxon inconnu de la clé'
	,'LANG_Det_Type_Label' => 'Identification'
	,'LANG_Det_Type_A' => 'Initial, Unvalidated'
	,'LANG_Det_Type_C' => 'Correct, validé'
	,'LANG_Det_Type_X' => 'Non identifié'
	,'LANG_Choose_Taxon' => "Choisissez un taxon dans la liste"
	,'LANG_Identify_Insect' => 'Indiquer le nom de cet insecte:'
	,'LANG_More_Precise' => 'Dénomination précise'
	,'LANG_ID_Comment' => 'Commentez éventuellement votre identification :'
	,'LANG_Default_ID_Comment' => "Precisions sur ma nouvelle identification..."
	,'LANG_Flower_Species' => "Nom de la Fleur "
	,'LANG_Flower_Name' => "Nom de la Fleur "
	,'LANG_Insect_Species' => "Nom de l'insecte "
	,'LANG_History_Title' => 'Ancienne identification'
	,'LANG_Last_ID' => 'Dernière(s) identification(s)'
	,'LANG_Display' => 'Afficher'
	,'LANG_No_Determinations' => 'Aucune identification enregistrée.'
	,'LANG_No_Comments' => 'Aucun commentaire enregistré.'
	
	,'LANG_Filter_Title' => 'Filtres'
	,'LANG_Name_Filter_Title' => 'Pseudo'
	,'LANG_Name' => "Pseudo"
	,'LANG_Date_Filter_Title' => 'Date'
	,'LANG_Flower_Filter_Title' => 'Fleur'
	,'LANG_ID_Status' => "Statut de l'identification "
	,'LANG_ID_Status_Choose' => "Choisissez un statut dans la liste"
	,'LANG_ID_Status_Unidentified' => "non identifié"
	,'LANG_ID_Status_Initial' => "identifié par l'auteur"
	,'LANG_ID_Status_Doubt' => "doute émis sur l'identification"
	,'LANG_ID_Status_Validated' => "validé par un expert"
	,'LANG_ID_Type' => "Type de l'identification "
	,'LANG_ID_Type_Choose' => "Choisissez un type dans la liste"
	,'LANG_ID_Type_Single' => "un seul taxon"
	,'LANG_ID_Type_Multiple' => "multi-taxon"
	,'LANG_Insect_Filter_Title' => 'Insecte'
	,'LANG_Conditions_Filter_Title' => "Conditions d'observation"
	,'LANG_Location_Filter_Title' => 'Localisation'
	,'LANG_Georef_Label' => '<strong>Localiser la fleur</strong> : utiliser les champs et/ou la carte ci-dessous'
	,'LANG_Georef_Notes' => "(Le nom d'un village, d'une ville, d'une région, d'un département ou un code postal.)"
    ,'msgGeorefSelectPlace' => "Sélectionnez dans les endroits suivants qui correspondent à vos critères de recherche, puis cliquez sur la carte pour indiquer l'emplacement exact"
    ,'msgGeorefNothingFound' => "Aucune ville portant ce nom n'a été trouvée. Essayez le nom d'une ville proche."
	,'LANG_INSEE' => "No/nom de INSEE/Département/Région"
	,'LANG_Search'=>'Chercher par :'
	,'LANG_Enter_Location'=>"Précisez ici votre recherche"
	,'LANG_NO_INSEE' => "Il n'ya pas de zone qui répond à ce critère de recherche."
	,'LANG_Max_INSEE_Features' => 'Vous avez atteint le nombre maximum de zones (<>) qui peut être retourné par cette recherche. La liste est abrégée. Précisez éventuellement votre recherche.'
	,'LANG_INSEE_Search_Limit' => 'Vous ne pouvez pas restreindre une recherche à plus de <> zones.'
	,'LANG_Search_Insects' => 'Rechercher des Insectes'
	,'LANG_Search_Collections' => 'Rechercher des collections'
	,'LANG_Insects_Search_Results' => 'Insectes'
	,'LANG_Collections_Search_Results' => 'Collections'
	,'LANG_Validate_Page' => "Valider l'identification de l'ensemble des photos de cette page"
	,'LANG_Bulk_Validation_Error' => 'An error has occurred during this bulk validation.'
	,'LANG_Bulk_Page_Validation_Completed' => "Votre validation a bien été prise en compte."
	,'LANG_Confirm_Bulk_Page_Validation'=>'Voulez-vous valider toutes les identifications de cette page?'
	,'LANG_Validate_Taxon' => "Valider l'ensemble des identifications pour ce taxon"
	,'LANG_Bulk_Taxon_Validation_Error' => 'An error has occurred during this bulk validation.'
	,'LANG_Bulk_Taxon_Validation_Completed' => "Votre validation a bien été prise en compte."
	,'LANG_Confirm_Bulk_Taxon_Validation'=>'Voulez-vous valider toutes les identifications de ce taxon?'
	,'LANG_Validate_Collection' => "Valider l'ensemble des identifications pour cette collection"
	,'LANG_Bulk_Collection_Validation_Error' => 'An error has occurred during this bulk validation.'
	,'LANG_Bulk_Collection_Validation_Completed' => "Votre validation a bien été prise en compte."
	,'LANG_Confirm_Bulk_Collection_Validation'=>'Voulez-vous valider toutes les identifications de cette collection?'
	,'LANG_Bulk_Validation_Comment'=>"(validation par lot)."
	,'LANG_Bulk_Page_Nothing_To_Do'=>"Il n'y a aucune identification à valider dans cette page."
	,'LANG_Bulk_Taxon_Nothing_To_Do'=>"Il n'y a aucune identification à valider pour ce taxon."
	,'LANG_Bulk_Collection_Nothing_To_Do'=>"Il n'y a aucune identification à valider pour cette collection."
	,'LANG_Cancel' => 'Annuler'
	,'LANG_Bulk_Validation_Canceled'=>'Cette validation a été annulée mi-chemin. Certains changements peuvent avoir déjà été appliquée à la base de données.'
	,'LANG_ClearTooltip' => 'Effacer polygones'
	,'LANG_User_Link' => 'Toutes ses collections dans les Galeries'
	,'LANG_Additional_Info_Title' => 'Informations Complémentaires'
	,'LANG_Date' => 'Date'
	,'LANG_Time' => 'Heure'
	,'LANG_To' => ' à '
	,'Sky' => 'Ciel (couverture nuageuse) '
	,'Temperature' => 'Température '
	,'Wind' => 'Vent '
	,'Shade' => "Fleur à l'ombre "
	,'Flower Type' => "Il s'agit d'une fleur "
	,'Habitat' => "Il s'agit d'un habitat "
	,'Foraging'=> "L'insecte a été photographié ailleurs que sur la fleur "
	,'Latitude' => 'Latitude '
	,'Longitude' => 'Longitude '
	
	,'LANG_Comments_Title' => 'COMMENTAIRES DES INTERNAUTES'
	,'LANG_New_Comment' => 'Ajouter un commentaire'
	,'LANG_Username' => 'Pseudo'
	,'LANG_Email' => 'EMAIL'
	,'LANG_Comment' => 'Commentaire'
	,'LANG_Submit_Comment' => 'Ajouter'
	,'LANG_Comment_By' => "Par : "
	,'LANG_Reset_Filter' => 'Réinitialiser'
	,'LANG_Submit_Location' => 'Modifier'
	,'LANG_Comment_Delete_Confirmation' => 'Are you sure you wish to delete this comment?'
	,'LANG_Insect_Delete_Confirmation' => 'Are you sure you wish to delete this insect?'
	,'LANG_Determination_Delete_Confirmation' => 'Are you sure you wish to delete this identification?'
	,'LANG_Submit_delete-insect' => 'Delete this insect'
	
	,'validation_required' => "Ce champ est obligatoire"
	,'Yes' => 'Oui'
	,'No' => 'Non'
	,'close'=>'Fermer'	
  	,'search'=>'Chercher'
  	,'click here'=>'Cliquez ici'
	,'LANG_Unknown' => '?'
	,'LANG_Dubious' => '!'
	,'LANG_Confirm_Express_Doubt' => 'Etes-vous sûr de vouloir émettre un doute au sujet de cette identification?'
	,'LANG_Doubt_Expressed' => "Cette personne a exprimé des doutes au sujet de cette identification."
	,'LANG_Determination_Valid' => "L'identification de ce taxon, ayant été confirmée par un expert, est validée."
	,'LANG_Determination_Corrected' => "L'identification de ce taxon, ayant été corrigée par un expert, est désormais validée."
	,'LANG_Determination_Incorrect' => 'Cette identification a été signalée comme incorrecte.'
	,'LANG_Determination_Unconfirmed' => 'Cette identification a été marquée comme non confirmées.'
	,'LANG_Determination_Unknown' => "Le taxon n'est pas connu de la clé d'identification."
	,'LANG_Max_Collections_Reached' => "Du fait du grand nombre de collections enregistrées sur le site du SPIPOLL, seules les 1000 dernières collections saisies vous sont présentées. Utilisez la géolocalisation et/ou le filtre date pour voir l'ensemble des collections au sein d'une zone et/ou d'une période d'observation données."
	,'LANG_Max_Insects_Reached' => "Du fait du grand nombre de collections enregistrées sur le site du SPIPOLL, seules les 1000 dernières insectes saisies vous sont présentées. Utilisez la géolocalisation et/ou le filtre date pour voir l'ensemble des insectes au sein d'une zone et/ou d'une période d'observation données."
	,'LANG_General' => 'Général'
	,'LANG_Created_Between' => 'Créé entre '
	,'LANG_And' => 'et'
	,'LANG_Or' => 'ou'
	,'LANG_Indicia_Warehouse_Error' => 'Erreur renvoyée par Indicia Warehouse'
	,'loading' => 'Chargement'
	,'LANG_INSEE_Localisation' => 'Localisation '
	,'LANG_Localisation_Confirm' => 'Etes-vous sûr de vouloir modifier la géolocalisation de votre collection?'
	,'LANG_Localisation_Desc' => "Si la géolocalisation de votre collection est incorrecte, vous pouvez la modifier en cliquant sur la carte ou en modifiant ses coordonnées."
	,'LANG_Front Page' => "Inclure cette collection à la page d'accueil"
	,'LANG_Submit_Front_Page' => 'Enregistrer'
	,'LANG_Included_In_Front_Page' => "Cette collection a été inclue à la page d'accueil."
	,'LANG_Removed_From_Front_Page' => "Cette collection a été retiré de la page d'accueil"
	,'LANG_Number_In_Front_Page' => "Nombre de collections dans la liste page d'accueil: "
	,'LANG_Location_Updated' => 'La localisation de cette collection a été mise à jour.'
	,'LANG_Locality_Commune' => 'Commune'
	,'LANG_Locality_Department' => 'Département'
	,'LANG_Locality_Region' => 'Région'
	
	,'LANG_Bad_Collection_ID' => "Vous avez essayé de charger une session comme une collection: ce ID n'est pas une collection."
	,'LANG_Bad_Insect_ID' => "Vous avez essayé de charger une fleur comme un insecte: cette ID n'est pas un insect."
	,'LANG_Bad_Flower_ID' => "Vous avez essayé de charger un insecte comme une fleur: cet ID n'est pas une fleur."
	
);