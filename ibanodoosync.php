<?php

require_once 'ibanodoosync.civix.php';

/**
 * Implementation of hook_civicrm_odoo_object_definition
 * 
 */
function ibanodoosync_civicrm_odoo_object_definition(&$list) {
  if (!_ibanodoosync_check_reqiurements()) {
    return;
  }
  
  $config = CRM_Ibanaccounts_Config::singleton();
  $table_name = $config->getIbanCustomGroupValue('table_name');
  $list[$table_name] = new CRM_Ibanodoosync_Definition();
}

/** 
 * Implementation of hook_civicrm_custom
 * 
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_custom
 */
function ibanodoosync_civicrm_custom($op,$groupID, $entityID, &$params ) {
  if (!_ibanodoosync_check_reqiurements()) {
    return;
  }
  
  //check if the group if the IBAN account group
  $config = CRM_Ibanaccounts_Config::singleton();
  if ($groupID == $config->getIbanCustomGroupValue('id')) {
    //group is the IBAN account
    //add the iban account for syncing
    if ($op == 'delete') {
      //when deleting the params contains the id
      $objectId = $params;
    } else {
      //first find the id for this custom value pair
      $contactParams = array();
      $contactParams['id'] = $entityID;
      foreach($params as $param) {
        $contactParams['custom_'.$param['custom_field_id']] = $param['value'];
        $contactParams['return.custom_'.$param['custom_field_id']] = 1;
      }
      $contact = civicrm_api3('Contact', 'getsingle', $contactParams);
      //extract the custom value table id
      $objectId = $contact[$config->getIbanCustomGroupValue('table_name').'_id'];
    }

    
    $objects = CRM_Odoosync_Objectlist::singleton();
    $objects->post($op,$config->getIbanCustomGroupValue('table_name'), $objectId);
  }
}

/**
 * Returns true when the requirements for this extension are met
 */
function _ibanodoosync_check_reqiurements() {
  $error = false;
  $requiredExtensions = array('org.civicoop.odoosync', 'org.civicoop.ibanaccounts');
  try {
    $extensions = civicrm_api3('Extension', 'get');  
    foreach($extensions['values'] as $ext) {
      if ($ext['status'] == 'installed') {
        if (in_array($ext['key'], $requiredExtensions)) {
          $key = array_search($ext['key'], $requiredExtensions);
          unset($requiredExtensions[$key]);
        }
      }
    }    
  } catch (Exception $e) {
    $error = true;
  }
  
  if ($error || count($requiredExtensions) > 0) {
    return false;
  }
  
  return true;
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function ibanodoosync_civicrm_config(&$config) {
  _ibanodoosync_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function ibanodoosync_civicrm_xmlMenu(&$files) {
  _ibanodoosync_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function ibanodoosync_civicrm_install() {
  return _ibanodoosync_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function ibanodoosync_civicrm_uninstall() {
  return _ibanodoosync_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function ibanodoosync_civicrm_enable() {
  return _ibanodoosync_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function ibanodoosync_civicrm_disable() {
  return _ibanodoosync_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function ibanodoosync_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _ibanodoosync_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function ibanodoosync_civicrm_managed(&$entities) {
  return _ibanodoosync_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function ibanodoosync_civicrm_caseTypes(&$caseTypes) {
  _ibanodoosync_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function ibanodoosync_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _ibanodoosync_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
