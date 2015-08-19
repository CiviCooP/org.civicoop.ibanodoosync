<?php

class CRM_Ibanodoosync_Synchronisator extends CRM_Odoosync_Model_ObjectSynchronisator {
  
  /**
   *
   * @var CRM_Ibanaccounts_Config
   */
  protected $config;
  
  public function __construct(CRM_Odoosync_Model_ObjectDefinitionInterface $objectDefinition) {
    $this->config = CRM_Ibanaccounts_Config::singleton();
    parent::__construct($objectDefinition);
  }
  
  /**
   * Retruns wether this item is syncable
   * By default false. 
   * 
   * subclasses should implement this function to make items syncable
   */
  public function isThisItemSyncable(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    return true;
  }
  
  /**
   * Insert a civicrm entity into Odoo
   * 
   */
  public function performInsert(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    $data = $this->getIban($sync_entity->getEntityId());
    $odoo_partner_id = $sync_entity->findOdooIdByEntity('civicrm_contact', $data['contact_id']);
    $parameters = $this->getOdooParameters($data, $odoo_partner_id, $sync_entity->getEntity(), $sync_entity->getEntityId(), 'create');
    $odoo_id = $this->connector->create($this->getOdooResourceType(), $parameters);
    if ($odoo_id) {
      return $odoo_id;
    }
    throw new Exception('Could not insert bank account into Odoo');
  }
  
  /**
   * Update an Odoo resource with civicrm data
   * 
   */
  public function performUpdate($odoo_id, CRM_Odoosync_Model_OdooEntity $sync_entity) {
    $data = $this->getIban($sync_entity->getEntityId());
    $odoo_partner_id = $sync_entity->findOdooIdByEntity('civicrm_contact', $data['contact_id']);
    $parameters = $this->getOdooParameters($data, $odoo_partner_id, $sync_entity->getEntity(), $sync_entity->getEntityId(), 'create');
    if ($this->connector->write($this->getOdooResourceType(), $odoo_id, $parameters)) {
      return $odoo_id;
    }
    throw new Exception("Could not update bank account in Odoo");
  }
  
  public function getSyncData(\CRM_Odoosync_Model_OdooEntity $sync_entity, $odoo_id) {
    $data = $this->getIban($sync_entity->getEntityId());
    $odoo_partner_id = $sync_entity->findOdooIdByEntity('civicrm_contact', $data['contact_id']);
    $parameters = $this->getOdooParameters($data, $odoo_partner_id, $sync_entity->getEntity(), $sync_entity->getEntityId(), 'create');
    return $parameters;
  }
  
  /**
   * Delete an item from Odoo
   * 
   */
  function performDelete($odoo_id, CRM_Odoosync_Model_OdooEntity $sync_entity) {
    if ($this->connector->unlink($this->getOdooResourceType(), $odoo_id)) {
      return -1;
    }
    throw new Exception('Could not delete bank account from Odoo');
  }
  
  /**
   * Find item in Odoo and return odoo_id
   * 
   */
  public function findOdooId(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    $data = $this->getIban($sync_entity->getEntityId());
    $odoo_partner_id = $sync_entity->findOdooIdByEntity('civicrm_contact', $data['contact_id']);
        
    $key = array(
      new xmlrpcval(array(
        new xmlrpcval('partner_id', 'string'),
        new xmlrpcval('=', 'string'),
        new xmlrpcval($odoo_partner_id, 'int'),
      ), "array"),
      new xmlrpcval(array(
        new xmlrpcval('iban', 'string'),
        new xmlrpcval('=', 'string'),
        new xmlrpcval($data['iban'], 'int'),
      ), "array")
    );
    
    
    $result = $this->connector->search($this->getOdooResourceType(), $key);
    foreach($result as $id_element) {
        $id = $id_element->scalarval();
    }
    return false;
  }
  
  /**
   * Checks if an entity still exists in CiviCRM.
   * 
   * This is used to check wether a civicrm entity is soft deleted or hard deleted. 
   * In the first case we have to update the entity in odoo 
   * In the second case we have to delete the entity from odoo 
   */
  public function existsInCivi(CRM_Odoosync_Model_OdooEntity $sync_entity) {
    $table = $this->config->getIbanCustomGroupValue('table_name');
    $dao = CRM_Core_DAO::executeQuery("SELECT * FROM `".$table."` WHERE `id` = %1", array(
      1 => array($sync_entity->getEntityId(), 'Integer'),
    ));
    
    if ($dao->fetch()) {
      return true;
    }
    return false;
  }
  
  /**
   * Returns the name of the Odoo resource e.g. res.partner
   * 
   * @return string
   */
  public function getOdooResourceType() {
    return 'res.partner.bank';
  }
  
  protected function getIban($entity_id) {
    $table = $this->config->getIbanCustomGroupValue('table_name');
    $sql = "SELECT * FROM `".$table."` WHERE `id` = %1";
    $params[1] = array($entity_id, 'Integer');
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $data = array();
    if ($dao->fetch()) {
      $iban_field = $this->config->getIbanCustomFieldValue('column_name');
      $bic_field = $this->config->getBicCustomFieldValue('column_name');
      $tnv_field = $this->config->getTnvCustomFieldValue('column_name');
      $data['contact_id'] = $dao->entity_id;
      $data['iban'] = $dao->$iban_field;
      $data['bic'] = $dao->$bic_field;
      $data['tnv'] = $dao->$tnv_field;
    }
    return $data;
  }
  
  /**
   * Returns the parameters to update/insert an Odoo object
   * 
   * @param type $contact
   * @return \xmlrpcval
   */
  protected function getOdooParameters($data, $odoo_partner_id, $entity, $entity_id, $action) {
    $parameters = array(
      'acc_number' => new xmlrpcval($data['iban'], 'string'),
      'partner_id' => new xmlrpcval($odoo_partner_id, 'int'),
      'bank_bic' => new xmlrpcval($data['bic'], 'string'),
      'owner_name' => new xmlrpcval($data['tnv'], 'string'),
      'state' => new xmlrpcval('bank', 'string'),
    );
    
    $this->alterOdooParameters($parameters, $this->getOdooResourceType(), $entity, $entity_id, $action);
    
    return $parameters;
  }
  
}
