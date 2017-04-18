<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * APIKeyHandler
 * 
 * @package aggregator
 * @author  Stefano Beccalli
 * @copyright Copyright (c) 2016
 * @link  http://www.jlbbooks.it
 * @since Version 1.0.0
 */
class APIKeyHandler
{
  protected $_CI=NULL;
  
  public function __construct()
  {
    $this->_CI = &get_instance();
  }
  
  private function _generate_uuid() 
  {
    return sprintf( '%04x%04x%04x%04x',
      mt_rand( 0, 0xffff ),
    	mt_rand( 0, 0xffff ),
      mt_rand( 0, 0x0fff ) | 0x4000,
      mt_rand( 0, 0x3fff ) | 0x8000,
      mt_rand( 0, 0xffff )
    );
  }
  
  public function save($application_name)
  {    
    if(empty($application_name)) throw new Exception(__METHOD__.' - Attenzione la variabile $application_name risulta vuota. Valore: '.var_export($application_name,TRUE), 1);
    
    $uuid=$this->_generate_uuid();
    date_default_timezone_set("Europe/Rome"); 
    $created=time();
    $data=array('key' => $uuid, 'application' => $application_name, 'status' => '1.0', 'created' => $created);
    $id=$this->_CI->mongo_db->insert('apikey', $data);
    return $id;    
  }

  public function keyCheck($key)
  {
    if(empty($key)) throw new Exception(__METHOD__.' - Attenzione la variabile $key risulta vuota. Valore: '.var_export($key,TRUE), 1);
    
    $data=$this->_CI->mongo_db->where(array('key' => $key, 'status' => 1))->get('apikey');
    if(!empty($data)) return $data;
    else return FALSE;
  }
  
  public function loadKey($id)
  {
    if(empty($id)) throw new Exception(__METHOD__.' - Attenzione la variabile $id risulta vuota. Valore: '.var_export($id,TRUE), 1);
    
    $data=$this->_CI->mongo_db->where(array('_id' => new MongoId($id)))->limit(1)->get('apikey');
    if(!empty($data)) return $data;
    else return FALSE;
  }
  
  
  public function delete($id)
  {
    if(empty($id)) throw new Exception(__METHOD__.' - Attenzione la variabile $id risulta vuota. Valore: '.var_export($id,TRUE), 1);
   
    $this->_CI->mongo_db->where(array('_id' => new MongoId($id)))->delete('apikey'); 
  }  
}
