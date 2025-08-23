<?php

//If copying this file to be the basis for a new module, remember to change:
//	MENU_XXXX
//	$module_name

//SA_CUSTOMER is probably not the right access level for this module.  It should be the company's bookkeeper etc.

define( 'MENU_AMORTIZATION', 'menu_amortization' );

class hooks_ksf_amortization extends hooks {
    var $module_name = 'ksf_amortization'; 
    var $controller = "/src/Ksfraser/Amortizations/controller.php";

    /*
    * Install additonal menu options provided by module
    */

    function install_options($app) {
	global $path_to_root;


	switch($app->id) {
	    case 'GL':
		$app->add_rapp_function(3, _("Amortization Generic"),
			$path_to_root."/modules/".$this->module_name.$this->controller, 'SA_CUSTOMER', MENU_AMORTIZATION);
		$app->add_rapp_function(3, _("Amortization Admin"),
			$path_to_root."/modules/".$this->module_name.$this->controller."?action=admin", 'SA_CUSTOMER', MENU_AMORTIZATION);
		$app->add_rapp_function(3, _("Amortization Create"),
			$path_to_root."/modules/".$this->module_name.$this->controller."?action=create", 'SA_CUSTOMER', MENU_AMORTIZATION);
		$app->add_rapp_function(3, _("Amortization Reports"),
			$path_to_root."/modules/".$this->module_name.$this->controller."?action=report", 'SA_CUSTOMER', MENU_AMORTIZATION);
		break;
	}
    }


    function activate_extension($company, $check_only=true) {
		//updates array:
		//	foreach($updates as $file => $update) {
		//	$table = @$update[0];
		//	$field = @$update[1];
		//	$properties = @$update[2];
		//	$ok = db_import($path_to_root.'/modules/'.$this->module_name.'/sql/'.$file,

	//$updates = array( 'update.sql' => array($this->module_name) );
	$updates = array( array( 'schema_events.sql' ),
			array( 'schema_selectors.sql' ),
			array( 'schema.sql' )
		 );
	return $this->update_databases($company, $updates, $check_only);
	return true;
    }

    //this is required to cancel bank transactions when a voiding operation occurs
	//@todo refactor to use my eventloop functions
    function db_prevoid($trans_type, $trans_no) {
/**
	    //SET status=0
	$sql = "
	    UPDATE ".TB_PREF."bi_transactions
	    SET status=0, fa_trans_no=0, fa_trans_type=0, created=0, matched=0, g_partner='', g_option=''
	    WHERE
		fa_trans_no=".db_escape($trans_no)." AND
		fa_trans_type=".db_escape($trans_type)." AND
		status = 1";
	display_notification($sql);
	db_query($sql, 'Could not void transaction');
*/

    }


}
?>
