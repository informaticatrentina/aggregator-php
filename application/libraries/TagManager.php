<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * TagManager
 * 
 * @package aggregator
 * @author  Stefano Beccalli
 * @copyright Copyright (c) 2016
 * @link  http://www.jlbbooks.it
 * @since Version 1.0.0
 */
class TagManager
{
  protected $_CI=NULL;
  
  public function __construct()
  {
    $this->_CI = &get_instance();
  }
  
  public function save($tags)
  {    
    if(empty($tags) || !is_array($tags)) throw new Exception(__METHOD__.' - Attenzione la variabile $tags risulta vuota. Valore: '.var_export($tags,TRUE), 1);
            
    $data=$this->_CI->mongo_db->where(array('slug' => $tags['slug']))->limit(1)->get('tag');   
     
    // Procedo con l'update
    if(!empty($data) && isset($data[0]) && !empty($data[0]))
    {      
      $entry_count = intval($data[0]['entry_count']) + 1;  
      $data['entry_count']= $entry_count;
      $this->_CI->mongo_db->where(array('_id' => new MongoId($data[0]['_id'])))->update('tag',$data);
      return $data[0]['_id'];
    }    
    else // procedo con l'inserimento
    {
      $data=array();
      if(isset($tags['name'])) $data['name']=$tags['name'];
      if(isset($tags['weight'])) $data['weight']=$tags['weight'];
      else $data['weight']=0;
      if(isset($tags['slug'])) $data['slug']=$tags['slug'];
      
      if(!empty($data))
      {
        $data['entry_count'] = 1;
        $id=$this->_CI->mongo_db->insert('tag', $data);
        return $id;
      }     
    }
  }
  
  public function remove($tags)
  {
    if(empty($tags) || is_array($tags)) throw new Exception(__METHOD__.' - Attenzione la variabile $tags risulta vuota. Valore: '.var_export($tags,TRUE), 1);
    if(!isset($tags->slug)) throw new Exception(__METHOD__.' - Attenzione la variabile $tags[\'slug\'] risulta vuota. Valore: '.var_export($tags,TRUE), 1);
    
    $data=$this->_CI->mongo_db->select('_id')->where(array('slug' => $tags->slug))->limit(1)->get('tag');
    
    if(!empty($data) && isset($data[0]) && !empty($data[0]))
    {
      if(isset($data[0]['entry_count']))
      {
        $entry_count=$data[0]['entry_count'];
        
        if($entry_count==1)
        {
           $this->_CI->mongo_db->where(array('_id' => $data[0]['_id']))->delete('tag');
        }
        else
        {
          $entry_count=$entry_count-1;
          $this->_CI->mongo_db->where(array('_id' => $data[0]['_id']))->set('entry_count', $entry_count)->update('tag');
        }
      }
    }
  }  
}
