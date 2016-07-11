<?php

class CRM_Ibanodoosync_Definition extends CRM_Odoosync_Model_ObjectDefinition implements CRM_Odoosync_Model_ObjectDependencyInterface {
  
  /**
   *
   * @var CRM_Ibanaccounts_Config 
   */
  protected $config;
  
  public function __construct() {
    $this->config = CRM_Ibanaccounts_Config::singleton();
  }
  
  public function isObjectNameSupported($objectName) {
    if ($objectName == $this->config->getIbanCustomGroupValue('table_name')) {
      return true;
    }
    return false;
  }

  public function getWeight($action) {
    return -90;
  }

  public function getName() {
    return $this->config->getIbanCustomGroupValue('table_name');
  }
  
  public function getCiviCRMEntityName() {
    return $this->config->getIbanCustomGroupValue('table_name');
  }
  
  public function getSynchronisatorClass() {
    return 'CRM_Ibanodoosync_Synchronisator';
  }
  
  public function getSyncDependenciesForEntity($entity_id, $data=false) {
    $dep = array();
    try {
      if (is_array($data) && isset($data['contact_id'])) {
         $contact_id = $data['contact_id'];
         $dep[] = new CRM_Odoosync_Model_Dependency('civicrm_contact', $contact_id);
      }
    } catch (Exception $ex) {
       //do nothing
    }
    return $dep;
  }
  
  public function getCiviCRMEntityDataById($id) {
    $table = $this->config->getIbanCustomGroupValue('table_name');
    $ibanField = $this->config->getIbanCustomFieldValue('column_name');
    $bicField = $this->config->getBicCustomFieldValue('column_name');
    
    $sql = "SELECT * FROM `".$table."` WHERE `id` = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($id, 'Integer')));
    $data = array();
    if ($dao->fetch()) {
      $data['contact_id'] = $dao->entity_id;
      $data['id'] = $dao->id;
      $data['iban'] = $dao->$ibanField;
      $data['bic'] = $dao->$bicField;
      
      return $data;
    }
    
    throw new Exception('Could not find Iban data for syncing into Odoo');
  }
  
}

