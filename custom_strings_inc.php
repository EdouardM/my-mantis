<?php
# Translation for Custom Status Code: testing
switch( $g_active_language ) {

	case 'french':
    
        /****************************
        * Customization of Access   *
        ****************************/
    
        #Translation of access level:
        $s_access_levels_enum_string = '10:invité,25:utilisateur,40:key user,55:hotliner,70:resp domaine,90:administrateur';

        /***********************************
        * Customization of Reproducibility *
        ************************************/
        
        $s_reproducibility_enum_string = '10:Nouveau besoin,20:Erreur de saisie,30:Problème exploitation,40:Données de base,50:Manque formation,60:Pas de problème,70:A déterminer,80:Problème sécurité,90:Erreur de paramétrage,100:Bug programme';
        $s_reproducibility = 'Cause';
        $s_select_reproducibility = 'Sélectionner la cause de DI';
        $s_must_enter_reproducibility = 'Vous devez sélectionner une cause.';
        $$s_email_reproducibility = 'Cause';
    
    default: # english
        /****************************
        * Customization of Access   *
        ****************************/

        #Translation of access levels:
        $s_access_levels_enum_string = '10:viewer,25:reporter,40:key user,55:hotliner,70:manager,90:administrator';
        
        /***********************************
        * Customization of Reproducibility *
        ************************************/
        $s_reproducibility_enum_string = '10:New need,20:Mistake in entry,30:System exploitation,40:Data,50:Lack training,60:No problem,70:To be set,80:Security issue,90:Setup issue,100:Bug';
        $s_reproducibility = 'Cause';
        $s_select_reproducibility = 'Select Cause';
        $s_must_enter_reproducibility = 'You must select a cause.';
        $$s_email_reproducibility = 'Cause';
        
    break;
}