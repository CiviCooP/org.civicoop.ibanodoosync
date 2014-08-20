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
  
}

