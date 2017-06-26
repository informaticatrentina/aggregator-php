<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * EntryManager
 * 
 * @package aggregator
 * @author  Stefano Beccalli
 * @copyright Copyright (c) 2017
 * @link  http://www.jlbbooks.it
 * @since Version 1.1.0
 */
class EntryManager
{
  protected $_CI=NULL;
  const api_key="voophoiYei6dee6u";
  private $_sortingTagSlug;
  private $_sortingDirection;
  private $_collection;
  
  public function __construct()
  {
    $this->_CI = &get_instance();
    $this->_CI->load->library('TagManager');
    $this->_sortingTagSlug = '';
    $this->_sortingDirection = 1;    
    $this->_collection=array();
  }
  
  public function save($entry_data)
  {    
    if(empty($entry_data) || !is_array($entry_data)) throw new Exception(__METHOD__.' - Attenzione la variabile $entry_data risulta vuota. Valore: '.var_export($entry_data,TRUE), 1);
   
    if(isset($entry_data['tags']) && !empty($entry_data['tags']))
    {
      foreach($entry_data['tags'] as $key => $tag)
      {
        $tagid=$this->_CI->tagmanager->save($tag);
        if(isset($entry_data['tags'][$key])) 
        {
          $entry_data['tags'][$key]['id']=new MongoId($tagid);
        }
      }
    }  
    
    $id=$this->_CI->mongo_db->insert('entry', $entry_data);    
    return (string) $id;   
  }
  
  public function update($entry_data)
  {    
    if(empty($entry_data) || !is_array($entry_data)) throw new Exception(__METHOD__.' - Attenzione la variabile $entry_data risulta vuota. Valore: '.var_export($entry_data,TRUE), 1);
    $id=$entry_data['_id'];
    unset($entry_data['_id']);
    if(isset($entry_data['removed_tags']))
    {
      foreach($entry_data['removed_tags'] as $tag)
      {
        $tagid=$this->_CI->tagmanager->remove($tag);
      }
      unset($entry_data['removed_tags']);
    } 
    $data=array();
    if(isset($entry_data['author']['name'])) $data['author.name']=$entry_data['author']['name'];
    if(isset($entry_data['content']['description'])) $data['content.description']= $entry_data['content']['description'];
    if(isset($entry_data['content']['summary'])) $data['content.summary']=$entry_data['content']['summary'];
    if(isset($entry_data['links']['alternates'])) $data['links.alternates']=$entry_data['links']['alternates'];
    if(isset($entry_data['links']['enclosures'])) $data['links.enclosures']=$entry_data['links']['enclosures'];
    if(isset($entry_data['modification_date'])) $data['modification_date']=$entry_data['modification_date'];
    if(isset($entry_data['publication_date'])) $data['publication_date']=$entry_data['publication_date'];
    if(isset($entry_data['related']['type'])) $data['related.type']=$entry_data['related']['type'];
    if(isset($entry_data['related']['id'])) $data['related.id']=$entry_data['related']['id'];
    if(isset($entry_data['source'])) $data['source']=$entry_data['source'];
    if(isset($entry_data['status'])) $data['status']=$entry_data['status'];
    if(isset($entry_data['tags'])) $data['tags']=$entry_data['tags'];
    if(isset($entry_data['title'])) $data['title']=$entry_data['title'];      
    
    // Procedo con l'update  
    $this->_CI->mongo_db->where(array('_id' => new MongoId($id)))->set($data)->update('entry');
    return array('data' => 'Entry Updated');  
  }
  
  public function prepareEntry($entry, $user_data)
  {
    if(empty($entry)) throw new Exception(__METHOD__.' - Attenzione la variabile $entry risulta vuota. Valore: '.var_export($entry,TRUE), 1);
    if(empty($user_data)) throw new Exception(__METHOD__.' - Attenzione la variabile $user_data risulta vuota. Valore: '.var_export($user_data,TRUE), 1);
    if(isset($user_data['enclosures']))
    {
      $links='';
      if(intval($user_data['enclosures'])==0)
      {
        $links=$entry['links'];
        unset($links['enclosures']);
      }
    }
    
    if(isset($user_data['return_content']))
    {
      $content = $entry['content'];
      $return_content = explode(",",$user_data['return_content']);
      $final_content = array();
      foreach($content as $key => $value)
      {
        array_push($final_content,$value);
      }      
      if(!empty($final_content))
      {
         $entry['content'] = $final_content;
      }    
    }
    
    $entry['id'] = (string)$entry['_id'];
        
    if(isset($entry['tags']))
    {
      foreach($entry['tags'] as $key => $tag)
      {
        if(isset($tag['id']))
        {
          $entry['tags'][$key]['id'] = (string)$tag['id'];          
        }
      }
    }
    
    if(isset($entry['creation_date']) && !empty($entry['creation_date']))
    {
      $entry['creation_date']=new MongoDate($entry['creation_date']);      
    }
    
    if(isset($entry['modification_date']) && !empty($entry['modification_date']))
    {
      $entry['modification_date']=new MongoDate($entry['modification_date']);
    }
    
    if(isset($entry['publication_date']) && !empty($entry['publication_date']))
    {
      $entry['publication_date']=new MongoDate($entry['publication_date']);
    }
    
    unset($entry['_id']);
    return $entry;
  }
  
  public function getTagsOfEntry($id)
  {
    if(empty($id)) throw new Exception(__METHOD__.' - Attenzione la variabile $id risulta vuota. Valore: '.var_export($id,TRUE), 1);
    
     $existing_tags = array();
    
     $tags = $this->_CI->mongo_db->select(array('tags'))->where(array('_id' => new MongoId($id)))->get('entry'); 
     if(isset($tags[0])) $tags=$tags[0];
     
     if(!empty($tags))
     {
       foreach($tags['tags'] as $tag)
       {
         $this->_CI->tagmanager->remove($tag);
         array_push($existing_tags, $tag['slug']);         
       }
      }
    return $existing_tags;
  }
  
  public function countTagsLink($id)
  {
    if(empty($id)) throw new Exception(__METHOD__.' - Attenzione la variabile $id risulta vuota. Valore: '.var_export($id,TRUE), 1);
    $count=0;
    $existing_tags = array();
    
    $tags_link = $this->_CI->mongo_db->where(array('related.type' => 'proposal', 'related.id' => $id, 'tags.0.name' => 'Link'))->get('entry'); 
      
    if(!empty($tags_link))
    {
      $count=intval(count($tags_link));
    }
    return $count;
  }
  
  public function countTagsProposal($id)
  {
    if(empty($id)) throw new Exception(__METHOD__.' - Attenzione la variabile $id risulta vuota. Valore: '.var_export($id,TRUE), 1);
    $count=0;
    $existing_tags = array();
    
    $tags_link = $this->_CI->mongo_db->where(array('related.type' => 'proposal', 'related.id' => $id, 'tags.0.name !=' => 'Link'))->get('entry'); 
      
    if(!empty($tags_link))
    {
      $count=intval(count($tags_link));
    }
    return $count;
  }
  
  public function delete($id)
  {
    if(empty($id)) throw new Exception(__METHOD__.' - Attenzione la variabile $id risulta vuota. Valore: '.var_export($id,TRUE), 1);
   
    $this->_CI->mongo_db->where(array('_id' => new MongoId($id)))->delete('entry'); 
    return 'Entry Deleted';
  }
  public function parseSchemeUri($tagString)
  {
    if(empty($tagString)) throw new Exception(__METHOD__.' - Attenzione la variabile $tagString risulta vuota. Valore: '.var_export($tagString,TRUE), 1);
    
    $schemeUri = '';
    preg_match("/^(\w+)(\{(.*)\})/",$tagString,$result);
    
    if(!empty($result) && is_array($result) && isset($result[0]))
    {
      $schemeUri = $result[0];
    }
    return $schemeUri;
  }
  public function parseSchemeName($tagString)
  {
    if(empty($tagString)) throw new Exception(__METHOD__.' - Attenzione la variabile $tagString risulta vuota. Valore: '.var_export($tagString,TRUE), 1);
    
    $schemeName = '';
    preg_match("/^(\w+)(\{(.*)\})/",$tagString,$result);
    
    if(!empty($result) && is_array($result) && isset($result[0]))
    {
      $schemeName = $result[0];
    }
    return $schemeName;
  }
  
    
  public function parseIndividualTagForScheme($tagString)
  {
    if(empty($tagString)) throw new Exception(__METHOD__.' - Attenzione la variabile $tagString risulta vuota. Valore: '.var_export($tagString,TRUE), 1);
    $tagDetail = array();   
    
    preg_match("/^(\w+)(\{(.*)\})*(\[(.*)\])*/",$tagString,$result);  
        
    if(!empty($result) && is_array($result) && isset($result[1]))
    {
      $tagDetail['slug'] = $result[1];
    }
    
    if(!empty($result) && is_array($result) && isset($result[3]))
    {
      $tagDetail['scheme'] = $result[3];
    }
    
    if(!empty($result) && is_array($result) && isset($result[5]))
    {
      $tagDetail['scheme_name'] = $result[5];
    } 
    return $tagDetail;
  }
  
  public function parseTags($tag)
  {
    if(empty($tag)) throw new Exception(__METHOD__.' - Attenzione la variabile $tag risulta vuota. Valore: '.var_export($tag,TRUE), 1);
    $explodedTag = explode(",",$tag);   
   
    $tagsAnd = array();
    $tagsOr = array();
    
    if(is_array($explodedTag) && !empty($explodedTag))
    {        
      foreach($explodedTag as $t)
      {
        if (strpos($t, '|') !== false) 
        { 
          $explodeForOr = explode("|",$t);
          if(isset($explodeForOr[0]) && !empty($explodeForOr[0]))
          {
            $tagDetail = $this->parseIndividualTagForScheme($explodeForOr[0]);
            array_push($tagsAnd, $tagDetail);
          }
          
          if(count($explodeForOr) > 1)
          {
            for($i=1;$i<count($explodeForOr);$i++)
            {
               $tagDetail = $this->parseIndividualTagForScheme($explodeForOr[$i]);
               array_push($tagsOr, $tagDetail);
            }
          }  
        }
        else
        {         
          $tagDetail = $this->parseIndividualTagForScheme($t);
          array_push($tagsAnd, $tagDetail);           
        }
      }
    }
    
    return array('and' => $tagsAnd, 'or' => $tagsOr);
  }
  
  public function deleteByRelated($related)
  {
    if(empty($related) || !is_array($related)) throw new Exception(__METHOD__.' - Attenzione la variabile $related risulta vuota. Valore: '.var_export($related,TRUE), 1);
    
    $message = '';
    $flag = true;
    
    if(!in_array('type',$related))
    {
      $message = 'Type can not be empty in related';
      $flag = false;
    }
    
    if(!in_array('id',$related) && $flag)
    {
      $message = 'ID can not be empty in related';
      $flag = false;
    }
    
    if($flag)
    {
       $condition = array();
       $condition['$and'] = json_encode(array('related.type' => $related['type'], 'related.id' => $related['id']));
       $this->_CI->mongo_db->where(array('related.type' => $related['type'], 'related.id' => $related['id']))->delete('entry');
       $message = 'Entries deleted successfully';       
    }
    return $message;
  }
  
  public function manageSorting($sortby, $direction)
  {
    if(empty($sortby)) throw new Exception(__METHOD__.' - Attenzione la variabile $sortby risulta vuota. Valore: '.var_export($sortby,TRUE), 1);
    if(empty($direction)) throw new Exception(__METHOD__.' - Attenzione la variabile $direction risulta vuota. Valore: '.var_export($direction,TRUE), 1);
   
    $validSortBy = '';
    
    if($direction==1) $direction='ASC';
    else $direction='DESC';
  
    if($sortby=='creation_date' || $sortby=='publication_date' || $sortby=='modification_date')
    {
      $validSortBy=$sortby;
    }
    
    if($sortby=='author')
    {
      $validSortBy='author.name';
    }
    if(strpos($sortby, 'tag') !== false)
    {
      $tagArray =  explode(":",$sortby);      
      if(count($tagArray)==2)
      {
        $this->_sortingTagSlug = $tagArray[1];
        $this->_sortingDirection = $direction;
        $validSortBy='tags.weight';
      }
    }
    return array($validSortBy => $direction);
  }
  
  public function tagWeightSort($doc1, $doc2)
  {
    if(empty($doc1) || !is_array($doc1)) throw new Exception(__METHOD__.' - Attenzione la variabile $doc1 risulta vuota. Valore: '.var_export($doc1,TRUE), 1);
    if(empty($doc2) || !is_array($doc2)) throw new Exception(__METHOD__.' - Attenzione la variabile $doc2 risulta vuota. Valore: '.var_export($doc2,TRUE), 1);
    $sorttag1 = array();
    $sorttag2 = array();
     
    if(!in_array('tags',$doc1))
    {
      return -1 * abs($this->_sortingDirection);
    }
    
    if(!in_array('tags',$doc2))
    {
      return $this->_sortingDirection;
    }
    
    if(isset($doc1['tags']))
    {
      foreach($doc1['tags'] as $tag)
      {
        if($tag['slug']==$this->_sortingTagSlug)
        {
          $sorttag1 = $tag;
          break;
        }
      }
    }
    
    if(isset($doc2['tags']))
    {
      foreach($doc2['tags'] as $tag)
      {
        if($tag['slug']==$this->_sortingTagSlug)
        {
          $sorttag2 = $tag;
          break;
        }
      }
    }
    
    if(count($sorttag1)==0)
    {
      return -1 * abs($this->_sortingDirection);
    }
    
    if(count($sorttag2)==0)
    {
      return $this->_sortingDirection;
    }
    
    if(in_array('slug',$sorttag1) && in_array('slug',$sorttag2))        
    {
      if($sorttag1['slug']== '' || $sorttag2['slug'] =='')
      {
        return 0;
      }
      if(isset($sorttag1['weight']) && isset($sorttag2['weight']) && intval($sorttag1['weight']) > intval($sorttag2['weight']))
      {
        return $this->_sortingDirection;
      }
      else return -1 * abs($this->_sortingDirection);      
    }
    else return 0;
  }
  public function get($user_data)
  {
    if(empty($user_data) || !is_array($user_data)) throw new Exception(__METHOD__.' - Attenzione la variabile $user_data risulta vuota. Valore: '.var_export($user_data,TRUE), 1);
    
    $sort = '_id';
    $offset = 0;
    $conditions = array();
    $count = 0;
    $limit = 1;    
  
    # We provide support for filtering on the basis of id.
    if(isset($user_data['id']))
    {
      $conditions['_id'] = new MongoId($user_data['id']);
    }
    
    # Support for filtering on the basis of source
    if(isset($user_data['source']))
    {
      $conditions['source'] = $user_data['source'];
    }
    
    # We provide support for filtering on the basis of title.
    if(isset($user_data['title']))
    {
      $conditions['title'] = $user_data['title'];
    }
    
    # Limit can be imposed on number of results to return. If no limit is
    # mentioned, only 1 result is returned.
    if(isset($user_data['limit']))
    {
      $limit = intval($user_data['limit']);
    }
    
    # We provide support for filtering of results on the basis of their
    # status. By default, entries with status active are returned in
    # results
    if(isset($user_data['status']) && ($user_data['status']=='0' || $user_data['status']=='1'))
    {
      $conditions['status'] = intval($user_data['status']);
    }
else {
$conditions['status'] = 'active';
}
if(isset($user_data['status']) && ($user_data['status'] == 'active' || $user_data['status'] == 'inactive'))
{
$conditions['status'] = $user_data['status'];
}
    # We provide support for filtering of results on the basis of their guid   # NOQA
    if(isset($user_data['guid']))
    {
      $guidList =  explode(",@#,",$user_data['guid']);
      $guidCondition = array();
      if(!empty($guidList))
      {
        foreach($guidList as $gid)
        {
          array_push($guidCondition, json_encode(array('guid' => $gid)));
        }
        $conditions['$or'] = $guidCondition;
      } 
    }
    # We provide support for returning results between two dates.
    if(isset($user_data['interval']))
    {
      $interval =  explode(",",$user_data['interval']);
      
      if(!empty($interval) && isset($interval[0]) && isset($interval[1]))
      {
        $start=$interval[0];
        $end=$interval[1];
        $conditions['publication_date'] = array('$gte' => $start, '$lte' => $end);         
      }
    }
    # We provide support for filtering of results on the basis of their
    # tags, tagSchemea, tagschemeUri, tagweight
    if(isset($user_data['tags']))
    {      
      $andtags = array();
      $ortags = array();
      $tagString = $user_data['tags'];
      $result = $this->parseTags($tagString);         
      
      if(!empty($result) && is_array($result) && isset($result['and']) && count($result['and']) > 0)
      {
        foreach($result['and'] as $tag)
        {
          if(is_array($tag) && isset($tag['scheme']))
          {
            array_push($andtags, array('tags.slug' => $tag['slug'], 'tags.scheme' => $tag['scheme']));
          }
          else
          {
            array_push($andtags, array('tags.slug' => $tag['slug']));
          }
        }
      }
      if(!empty($result) && is_array($result) && isset($result['or']) && count($result['or']) > 0)
      {
        foreach($result['or'] as $tag)
        {
          if(is_array($tag) && isset($tag['scheme']))
          {
            array_push($ortags, array('tags.slug' => $tag['slug'], 'tags.scheme' => $tag['scheme']));
          }
          else
          {
            array_push($ortags, array('tags.slug' => $tag['slug']));
          }
        }
        $conditions['$or'] = $ortags;
        
        if(!empty($andtags))
        {
          foreach($andtags as $andtag)
          {
            array_push($conditions['$or'], $andtag);
          }
        }
      }
      
      if(!empty($result) && is_array($result) && isset($result['and']) && count($result['and']) > 0)
      {   
        $conditions['$and'] = $andtags;
      }
    }
    $return_fields = array();   
   
    if(isset($user_data['related']))
    {
      $related=array();
      $related =  explode(",",  urldecode($user_data['related']));
      if(!empty($related) && isset($related[0]) && isset($related[1]))
      {
        $conditions['related.type'] = $related[0];
        $conditions['related.id'] = $related[1];
      }
    }
    
   
    if(isset($user_data['metadata']))
    if(in_array('metadata',$user_data))
    {
      $metadatas=array();
      $metadatas =  explode(",",$user_data['metadata']);
      if(!empty($metadatas) && isset($metadatas[0]) && isset($metadatas[1]))
      {
        $conditions['metadata.id'] = $metadatas[0];
        $conditions['metadata.name'] = $metadatas[1];
      }
    }
    
    # User can define which fields do they want in the results returned.
    # By default, only id is returned.
    
    if(isset($user_data['return_fields']))
    {
      $return_fields =  explode(",",urldecode($user_data['return_fields']));
      if(!empty($return_fields))
      {
        if(!empty($conditions)) $this->_collection=$this->_CI->mongo_db->where($conditions)->get('entry');
        else     $this->_collection=$this->_CI->mongo_db->get('entry');
        
       
        foreach($return_fields as $i)
        {
          $i=trim($i);
          if($i != '*' && !isset($this->_collection[0][$i]))
          {
            if($i != 'id')
            {
              if (($key = array_search($i, $return_fields)) !== false) 
              {
                unset($return_fields[$key]);
              }
            }
          }
        }
      }
    }    
    
    # Sorting is also supported.
    if(isset($user_data['sort']))
    {      
      if(!empty($user_data['sort']) && substr($user_data['sort'],0,1) == '-')
      {        
        $user_data['sort']=str_replace("-","",$user_data['sort']);
        $sort = $this->manageSorting($user_data['sort'], -1);
      }
      else $sort = $this->manageSorting($user_data['sort'], 1);   
    }    
   
    if(isset($user_data['offset']))
    {
      $offset = intval($user_data['offset']);
    }
    
    # Results can be filtered on the basis of author also
    if(isset($user_data['author']))
    {
      $conditions['author.slug'] = $user_data['author'];
    }
    
    if(isset($user_data['range']))    
    {
       $rangeCondition = array();
       $return_column = array('creation_date' => 1);
       $sort = 'creation_date';
       $date = 0;
       $range = explode(":",$user_data['range']); 
       if(isset($range[0]))
       {
         $rangeCondition['_id'] = $range[0];
       }
       if(isset($range[1]))
       {
         $limit = intval($range[1]);
       }
       
       
       $data1 = $this->_CI->mongo_db->select('creation_date')->where(array('_id' => new MongoId($range[0])))->get('entry');
       
       
       if(!empty($data1))
       {
         foreach($data1 as $entry)
         {
           $date=$entry['creation_date'];
         }
       }       
       
       
       $data=$this->_CI->mongo_db->where($conditions)->where_gt('creation_date',$date)->order_by(array($sort => 'ASC'))->limit($limit)->get('entry'); 
                   
       $entries = array('before' => array(), 'after' => array());
       
       if(!empty($data))
       {
         foreach($data as $entry)
         {           
           $tmp_entry=$this->prepareEntry($entry,$user_data);
           
           $outputEntry = array();
           
           if(count($return_fields) > 0)
           {
             if($return_fields[0] == '*')
             {
                $outputEntry = $tmp_entry;
             }
             else 
             {
               foreach($return_fields as $return_field)
               {
                 $return_field=trim($return_field);
                 $outputEntry[$return_field] = $tmp_entry[$return_field];
               }
             }
           }
           else 
           {
             $outputEntry['id'] = $tmp_entry['id'];
           }    
         }
         $entries['after'] = $outputEntry;
       }
                  
       $data=$this->_CI->mongo_db->where(array('creation_date' => $date))->order_by(array('creation_date' => 'DESC'))->limit($limit)->get('entry');      
                   
       if(!empty($data))
       {
         foreach($data as $entry)
         {
            $tmp_entry=$this->prepareEntry($entry,$user_data);
            $outputEntry = array();
            if(count($return_fields) > 0)
            {
              if($return_fields[0] == '*') return $tmp_entry;            
              else
              {
                foreach($return_fields as $return_field)
                {
                  $return_field=trim($return_field);                
                  if(isset($tmp_entry[$return_field]))
                  {
                    $outputEntry[$return_field] = $tmp_entry[$return_field];
                  }
                }
              }
            }
            else
            {
              $outputEntry['id'] = $tmp_entry['id'];
            }
         }
         $entries['before'] = $outputEntry;
       }       
       return $entries;
      }
      
      # Only use when explicitly required
      # 
      # FIRST DEBUG SB       
     // file_put_contents('debug.log',print_r($conditions,TRUE),FILE_APPEND);
    
      if($offset > 0)
      {
        if(!is_array($sort))
        {
          // debudg
           //$data=$this->_CI->mongo_db->where($conditions)->limit($limit)->offset($offset)->get('entry');
           $data=$this->_CI->mongo_db->get('entry');
           file_put_contents('debug.log',print_r($data,TRUE),FILE_APPEND);
           $count = intval($this->_CI->mongo_db->where($conditions)->limit($limit)->offset($offset)->count('entry'));
        }
        else
        {
           $data=$this->_CI->mongo_db->where($conditions)->order_by($sort)->limit($limit)->offset($offset)->get('entry');
           $count = intval($this->_CI->mongo_db->where($conditions)->limit($limit)->offset($offset)->count('entry'));
        }
      }
      else
      { 
        if(isset($sort['tags.weight']))
        {         
          $data=$this->_CI->mongo_db->where($conditions)->get('entry');         
          $datatags=array();
          if(!empty($data) && !empty($this->_sortingTagSlug))
          {
            foreach($data as $key => $element)
            {
              if(isset($element['tags']))
              {
                 foreach($element['tags'] as $tag)
                {        
                   if(isset($tag['name']) && isset($tag['weight']) && $tag['name']==$this->_sortingTagSlug)
                  {
                    $datatags[]=array('key' => $key, 'name' => $tag['name'], 'weight' => $tag['weight']);
                  }
                }
              }
            }
          }
          if(!empty($datatags))
          {
            $key_array_index=array();
            function compare_tags_asc($a, $b)
            {
              return strnatcmp($a['weight'], $b['weight']);
            }
            function compare_tags_desc($a, $b)
            {
              return strnatcmp($b['weight'], $a['weight']);
            }           
            
            if($this->_sortingDirection==1) usort($datatags, 'compare_tags_desc');
            else usort($datatags, 'compare_tags_asc');
            // Ora mi prendo le chiavi dell'array          
            foreach($datatags as $tgs)
            {
              if(isset($tgs['key'])) $key_array_index[]=$tgs['key'];
            }
            if(!empty($key_array_index))
            {
              // Riordino l'array iniziale
              $tmp_data=array();
              foreach($key_array_index as $elementk)
              {
                if(isset($data[$elementk]))
                $tmp_data[]=$data[$elementk];
              }
              if(!empty($tmp_data)) $data = array_values($tmp_data);
            }            
          }
        }
        else
        {
          
          if(!is_array($sort))
          { 
            $data=$this->_CI->mongo_db->where($conditions)->limit($limit)->get('entry');
            $count = intval($this->_CI->mongo_db->where($conditions)->limit($limit)->count('entry'));
          }
          else
          {        
            $data=$this->_CI->mongo_db->where($conditions)->order_by($sort)->limit($limit)->get('entry');
            $count = intval($this->_CI->mongo_db->where($conditions)->limit($limit)->count('entry'));
          }
        }  
      }
      $entries = array();      
      # SECOND DEBUG SB 
      file_put_contents('debug.log',print_r($data,TRUE),FILE_APPEND); 
      if(isset($user_data['count']))
      {
        // ERRATO
        //$count = count($data);
        if(intval($user_data['count'])==2)
        {
          // Fix SB - Se il numero di parametri è insufficiente a determinare il risultato sperato $count=0
          if(count($user_data)<=5) $count=0;
          array_push($entries, array('count' => $count));
          return $entries;
        }
      } 
    
      if(!empty($data))
      {       
        foreach($data as $entry)
        {               
          $tmp_entry=$this->prepareEntry($entry,$user_data);
              
          $outputEntry = array();
   
          if(count($return_fields) > 0)
          { 
            if(isset($return_fields[0]) && $return_fields[0] == '*')
            {
              $outputEntry = $tmp_entry;
            }
            else
            {
              foreach($return_fields as $return_field)
              {               
                $return_field=trim($return_field);                
                if(isset($tmp_entry[$return_field]))
                {
                  // Formatto la data
                  if($return_field=='creation_date') 
                  {
                    if(isset($tmp_entry[$return_field]->sec))
                    {
                      date_default_timezone_set('Europe/Rome');
                      $outputEntry[$return_field] = date('Y-m-d H:i:s',$tmp_entry[$return_field]->sec);
                    }
                    else $outputEntry[$return_field] = $tmp_entry[$return_field];                             
                  }
                  elseif($return_field=='publication_date') 
                  {                    
                    if(isset($tmp_entry[$return_field]->sec))
                    {                      
                      date_default_timezone_set('Europe/Rome');
                      $outputEntry[$return_field] = date('Y-m-d H:i:s',$tmp_entry[$return_field]->sec);
                    }
                    else $outputEntry[$return_field] = $tmp_entry[$return_field];                     
                  }
                  elseif($return_field=='modification_date') 
                  {      
                    if(isset($tmp_entry[$return_field]->sec))
                    {
                      date_default_timezone_set('Europe/Rome');
                      $outputEntry[$return_field] = date('Y-m-d H:i:s',$tmp_entry[$return_field]->sec);
                    }
                    else $outputEntry[$return_field] = $tmp_entry[$return_field];                             
                  }
                  else $outputEntry[$return_field] = $tmp_entry[$return_field];
                }
              }
            }
          }
          else
          {
            $outputEntry['id'] = $tmp_entry['id'];
          }
          array_push($entries, $outputEntry);            
        }
      }
      if(isset($user_data['count']))
      {
        // ERRATO
        //$count = count($data);
        if(intval($user_data['count'])==1)
        {
          array_push($entries, array('count' => $count));          
        }
      }
      // DEBUG FINALE SB    
      //file_put_contents('debug.log',print_r($entries,TRUE),FILE_APPEND); 
      return $entries;
  }
}