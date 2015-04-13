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
        
        /************************************
        * Customization of Resolution       *
        *************************************/
        $s_resolution_enum_string = '10:Intervention programme,20:Extraction données,30:Action exploitation,40:Résolution fonctionnelle,50:A déterminer,60:Autre intervention technique,70:SQL - DFU - Mise à jour directe,80:Formation - Information';
        $s_resolution = 'Action Hotline';
        $s_reopen_resolution = '50:A déterminer';
        $s_by_resolution = 'Par Action Hotline';
        $s_email_resolution = 'Action Hotline';
        $s_reporter_by_resolution = 'Demandeur par action Hotline';
        $s_developer_by_resolution = 'Hotliner par action Hotline';
        $s_resolve_bugs_conf_msg = 'Choisissez l\'action hotline';
    
    
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
        
        /************************************
        * Customization of Resolution       *
        *************************************/
        $s_resolution_enum_string = '10:Program update,20:Data extract,30:Exploitation action,40:Functional solution,50:To be set,60:Other tech. action,70:SQL - direct update,80:Training';
        $s_resolution = 'Hotline Action';
        $s_reopen_resolution = '50:To be set';
        $s_by_resolution = 'By Hotline Action';
        $s_email_resolution = 'Hotline Action';
        $s_reporter_by_resolution = 'Reporter by Hotline Action';
        $s_developer_by_resolution = 'Hotliner by Hotline Action';
        $s_resolve_bugs_conf_msg = 'Choose issues Action';
    
        
    break;
}