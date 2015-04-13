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
    
        /****************************************
        * Customization of Severity             *
        *****************************************/
        $s_severity_enum_string = '10:Anomalie,20:Demande assistance,40:Demande amélioration';
        $s_severity = 'Type Demande';
        $s_select_severity = 'Sélectionner le type de demande';
        $s_email_severity = 'Type Demande';
        $s_by_severity = 'Par Type Demande';
        $s_must_enter_severity  = 'Vous devez renseigner le type de Demande';
        $s_with_minimum_severity = 'A partir du type de demande';
        $s_update_severity_title = 'Mise à jour du type de demande';
        $s_update_severity_msg = 'Choisissez le type de demande';
        $s_update_severity_button = 'Mettre à jour le type de demande';
        $s_actiongroup_menu_update_severity = 'Mettre à jour le type de demande';
        
        /****************************
        * Customization of Status   *
        ****************************/
        #Translation of status:
		$s_status_enum_string = '10:nouveau,30:accepté,50:en cours,60:ordirope,61:talentia,65:arbitrage,70:à tester,80:à valider,90:fermé';

        #Translation for custom status 60: Ordirope =
		$s_ordirope_bug_title = 'Passage DI chez Ordirope';
		$s_ordirope_bug_button = 'Transférer';
        $s_email_notification_title_for_status_bug_ordirope = 'Le bug suivant est transféré chez ORDIROPE pour résolution.';

        
        #Translation for custom status 61: Talentia =
		$s_talentia_bug_title = 'Passage DI chez Talentia';
		$s_talentia_bug_button = 'Transférer';
        $s_email_notification_title_for_status_bug_talentia = 'Le bug suivant est transféré chez TALENTIA pour résolution.';
        
		
        #Translation for custom status 65: Change Review =
        $s_change_review_bug_title = 'Arbitrage Amélioration';
		$s_change_review_bug_button = 'Passer en Arbitrage';

		$s_email_notification_title_for_status_bug_change_review = 'La demande d\' amélioration est passée en ARBITRAGE.';
        
        #Translation for custom status 65: Change Review =
        $s_testing_bug_title = 'Mettre la DI à tester';
		$s_testing_bug_button = 'A tester';

		$s_email_notification_title_for_status_bug_testing = 'La DI est prête à être testée.';
    
    
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
        
        /****************************
        * Customization of Status   *
        ****************************/
        
        #Translation of status:
		$s_status_enum_string = '10:new,30:acknowledged,50:assigned,60:ordirope,61:talentia,65:change review,70:testing,80:resolved,90:closed';
        
        #Translation for custom status 60: Ordirope =
		$s_ordirope_bug_title = 'Send bug details to Ordirope';
		$s_ordirope_bug_button = 'Send';
		$s_email_notification_title_for_status_bug_ordirope = 'The following issue is under INVESTIGATION with development team.';
        
        #Translation for custom status 61: Talentia =
		$s_talentia_bug_title = 'Send bug details to Talentia';
		$s_talentia_bug_button = 'Send';
		$s_email_notification_title_for_status_bug_talentia = 'The following issue is under INVESTIGATION with development team.';

        #Translation for custom status 65: Change Review =
        $s_change_review_bug_title = 'Change Review';
		$s_change_review_bug_button = 'Change review';

		$s_email_notification_title_for_status_bug_change_review = 'The following change request is under EVALUATION.';
        
        #Translation for custom status 70: Testing =
        $s_testing_bug_title = 'Mark issue Ready for Testing';
		$s_testing_bug_button = 'Ready for Testing';

		$s_email_notification_title_for_status_bug_testing = 'The following issue is ready for TESTING.';
        
    break;
}