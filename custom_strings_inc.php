<?php
# Translation for Custom Status Code: testing
switch( $g_active_language ) {

	case 'french':
    
        /****************************
        * Customization of Access   *
        ****************************/
    
        #Translation of access level:
        $s_access_levels_enum_string = '10:invit�,25:utilisateur,40:key user,55:hotliner,70:resp domaine,90:administrateur';

        /***********************************
        * Customization of Reproducibility *
        ************************************/
        
        $s_reproducibility_enum_string = '10:Nouveau besoin,20:Erreur de saisie,30:Probl�me exploitation,40:Donn�es de base,50:Manque formation,60:Pas de probl�me,70:A d�terminer,80:Probl�me s�curit�,90:Erreur de param�trage,100:Bug programme';
        $s_reproducibility = 'Cause';
        $s_select_reproducibility = 'S�lectionner la cause de DI';
        $s_must_enter_reproducibility = 'Vous devez s�lectionner une cause.';
        $$s_email_reproducibility = 'Cause';
        
        /************************************
        * Customization of Resolution       *
        *************************************/
        $s_resolution_enum_string = '10:Intervention programme,20:Extraction donn�es,30:Action exploitation,40:R�solution fonctionnelle,50:A d�terminer,60:Autre intervention technique,70:SQL - DFU - Mise � jour directe,80:Formation - Information';
        $s_resolution = 'Action Hotline';
        $s_reopen_resolution = '50:A d�terminer';
        $s_by_resolution = 'Par Action Hotline';
        $s_email_resolution = 'Action Hotline';
        $s_reporter_by_resolution = 'Demandeur par action Hotline';
        $s_developer_by_resolution = 'Hotliner par action Hotline';
        $s_resolve_bugs_conf_msg = 'Choisissez l\'action hotline';
    
        /****************************************
        * Customization of Severity             *
        *****************************************/
        $s_severity_enum_string = '10:Anomalie,20:Demande assistance,40:Demande am�lioration';
        $s_severity = 'Type Demande';
        $s_select_severity = 'S�lectionner le type de demande';
        $s_email_severity = 'Type Demande';
        $s_by_severity = 'Par Type Demande';
        $s_must_enter_severity  = 'Vous devez renseigner le type de Demande';
        $s_with_minimum_severity = 'A partir du type de demande';
        $s_update_severity_title = 'Mise � jour du type de demande';
        $s_update_severity_msg = 'Choisissez le type de demande';
        $s_update_severity_button = 'Mettre � jour le type de demande';
        $s_actiongroup_menu_update_severity = 'Mettre � jour le type de demande';
        
    
    
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
    
        /****************************************
        * Customization of Severity             *
        *****************************************/
        $s_severity_enum_string = '10:Incident,20:Service request,40:Improvement';
        $s_severity = 'Request Type';
        $s_select_severity = 'Select request type';
        $s_email_severity = 'Request Type';
        $s_by_severity = 'By Request Type';
        $s_must_enter_severity  = 'You must select Request Type';
        $s_with_minimum_severity = 'From Request Type';
        $s_update_severity_title = 'Update Request Type';
        $s_update_severity_msg = 'Choose Request Type';
        $s_update_severity_button = 'Update Request Type';
        $s_actiongroup_menu_update_severity = 'Update Request Type';
        
    break;
}