<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';


/**
 * Api
 * 
 * @package Aggregator
 * @author  Stefano Beccalli
 * @copyright Copyright (c) 2017
 * @link  http://www.jlbbooks.it
 * @since Version 1.0.0
 */
class Api extends REST_Controller 
{
  function __construct()
  {
    parent::__construct();
    $this->load->library('EntryManager');
    $this->load->library('APIKeyHandler');
  }
  
  
 # entries_get
 #
 # This method is called when a get request is made via API. It parses all the
 # parameters passed by user and sends them to the get method for preparing results # NOQA
 # on the basis of conditions.
 # Parameters :
 # offset: the first entry to return (defaults to 1)
 # limit: the number of entries to return (defaults to 1)
 # id=n1,n2,n3: entries whose id matches any in n1,n2,n3...:
 # guid=URI: entries whose guid matches URI
 # tag=t1,t2,t3|t4{schemeURI}[schemeNAME]
 # interval=timestamp1,timestamp2
 # timestamp1 and timestamp 2 must be valid timestamps of the start and ed of the requested date interval, entries falling in the interval will be returned # NOQA
 # enclosures=0, if assigned 0 as value enclosures won't be included in the feed (defaults to 1) # NOQA
 # sort=sortOrder, allows to sort either by any field of entries:
 # date_published, author, date_changed, url, guid, day_parsed
 # the sorting order defaults to ascending, to switch to descending prepend a minus to field name or tag: # NOQA
 # source=string. Allows to load entries of a particular source
 #
  public function entries_get($apikey=NULL)
  {
    if(empty($apikey) || !$this->apikeyhandler->keyCheck($apikey))
    {
      $response_arr=array('status' => 'false', 'data' => array('errorCode' => "102", 'errorMessage' => 'Authentication failed, Invalid api key'));
      $this->response($response_arr, REST_Controller::HTTP_OK);
      return;
    }
    
    $user_data = array();
		
		$get=$this->get();
    
    if(empty($get))
    {
       $this->response(array('true', array()), REST_Controller::HTTP_OK);
    }    
    
    if(isset($get['id']) && !empty($get['id'])) { $user_data['id']=urldecode($get['id']); }  
    if(isset($get['title']) && !empty($get['title'])) { $user_data['title']=urldecode($get['title']); }
    if(isset($get['limit']) && !empty($get['limit'])) { $user_data['limit']=urldecode($get['limit']); }
    if(isset($get['status']) && !empty($get['status'])) { $user_data['status']=urldecode($get['status']); }
    if(isset($get['guid']) && !empty($get['guid'])) { $user_data['guid']=urldecode($get['guid']); }
    if(isset($get['tags']) && !empty($get['tags'])) { $user_data['tags']=urldecode($get['tags']); }
    if(isset($get['interval']) && !empty($get['interval'])) { $user_data['interval']=urldecode($get['interval']); }
    if(isset($get['count']) && !empty($get['count'])) { $user_data['count']=urldecode($get['count']); }
    if(isset($get['return_fields']) && !empty($get['return_fields'])) { $user_data['return_fields']=urldecode($get['return_fields']); }
    if(isset($get['sort']) && !empty($get['sort'])) { $user_data['sort']=urldecode($get['sort']); }
    if(isset($get['enclosures']) && !empty($get['enclosures'])) { $user_data['enclosures']=urldecode($get['enclosures']); }
    if(isset($get['author']) && !empty($get['author'])) { $user_data['iauthor']=urldecode($get['author']); }
    if(isset($get['offset']) && !empty($get['offset'])) { $user_data['offset']=urldecode($get['offset']); }
    if(isset($get['count']) && !empty($get['count'])) { $user_data['count']=urldecode($get['count']); }
    if(isset($get['return_content']) && !empty($get['return_content'])) { $user_data['return_content']=urldecode($get['return_content']); }
    if(isset($get['range']) && !empty($get['range'])) { $user_data['range']=urldecode($get['range']); }
    if(isset($get['related']) && !empty($get['related'])) { $user_data['related']=urldecode($get['related']); }
    if(isset($get['source']) && !empty($get['source'])) { $user_data['source']=urldecode($get['source']); }
    if(isset($get['metadata']) && !empty($get['metadata'])) { $user_data['metadata']=urldecode($get['metadata']); }
       
    $entries = $this->entrymanager->get($user_data);
    if(!empty($entries)) $this->response(array("status" => "true", "data" => $entries), REST_Controller::HTTP_OK);
    else $this->response(array("status" => "true", "data" => array()), REST_Controller::HTTP_OK);
  }
  
  public function entries_delete($apikey=NULL)
  {
    if(empty($apikey) || !$this->apikeyhandler->keyCheck($apikey))
    {
      $response_arr=array('status' => 'false', 'data' => array('errorCode' => "102", 'errorMessage' => 'Authentication failed, Invalid api key'));
      $this->response($response_arr, REST_Controller::HTTP_OK);
      return;
    }
    
    $json_del=json_decode(file_get_contents("php://input"));   
      
    if ($json_del === null && json_last_error() !== JSON_ERROR_NONE)
    {
      $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST);
    }
    
    if(!isset($json_del->id) || empty($json_del->id) || !isset($json_del->related) || empty($json_del->related))
    {
      $error_code=array('111', 'ID is Mandatory');
      $this->response(array('false', array('message' => $error_code)), REST_Controller::HTTP_OK);
    }
    if(isset($json_del->id) && !empty($json_del->id))
    {
      $response=$this->entrymanager->delete($json_del->id);
      $this->response(array('true', $response), REST_Controller::HTTP_OK);
    }    
    if(isset($json_del->related) && !empty($json_del->related))
    {
      $response=$this->entrymanager->deleteByRelated($json_del->related);
      $this->response(array('true', $response), REST_Controller::HTTP_OK);      
    }
  }
  
 # entries_put
 #
 # This method is called when a PUT request is made via API.
 # Parameters :
 # status: active|deleted|hidden|draft
 # source: Source in not supported in put API, as it does not make sense to
 #         update source info
 # links: Type
 #          Format
 #          URI
 # related
 #   Type
 #   id
 # metadata
 #     id  string
 #     name  string
 #     description string
 # publication_date date time
 # title String
 # author
 #    Name
 #    Slug
 # content content is a mandatory element
 # tags
 #   scheme tag name (string), tag slug (sanitized string), tag value (integer)
 # longitude float
 # latitude  float
 # return_fields comma separated name of fields
  public function entries_put($apikey=NULL)
  {
    if(empty($apikey) || !$this->apikeyhandler->keyCheck($apikey))
    {
      $response_arr=array('status' => 'false', 'data' => array('errorCode' => "102", 'errorMessage' => 'Authentication failed, Invalid api key'));
      $this->response($response_arr, REST_Controller::HTTP_OK);
      return;
    }
		
		$put=$this->put('entry');
    
    if(empty($put))
    {
       $this->response(array('true', array()), REST_Controller::HTTP_OK);
    }

		$entry=json_decode($put,TRUE);		
    
    if(!isset($entry['id']))
    {
      $error_code=array('111', 'ID is Mandatory'); 
      $this->response(array('true', array('message' => $error_code)), REST_Controller::HTTP_OK);
    }
    else $entry_data['_id'] = $entry['id'];
 
    if(isset($entry['content'])) $entry_data['content'] = $entry['content'];
    
    if(isset($entry['author']))
    {
      if(isset($entry['author']['name']))
      {
        if(is_string($entry['author']['name'])) $entry_data['author.name'] = $entry['author']['name'];
        else $error_code=array('106', 'Invalid Author Name'); 
      }   
      
      if(isset($entry['author']['slug']))
      {
        if(is_string($entry['author']['slug'])) $entry_data['author.slug'] = $entry['author']['slug'];
        else $error_code=array('106', 'Invalid Author Slug');
      }        
    }
    
    if(isset($entry['title']))
    {
      if(is_string($entry['title'])) $entry_data['title'] = $entry['title'];
      else $error_code=array('109', 'Invalid Title');
    }   
    
    if(isset($entry['status']))
    {
      if(is_string($entry['status'])) $entry_data['status'] = $entry['status'];
      else $error_code=array('110', 'Invalid Status');
    }    
    
    if(isset($entry['tags']))
    {
      $existing_tags = $this->entrymanager->getTagsOfEntry($entry_data['_id']);
      
      $removed_tags = array();
      $entry_data['tags'] = array();
           
      foreach($entry['tags'] as $tag)
      {
        $tags = array();
        if(isset($tag['name']))
        {
          if(is_string($tag['name'])) $tags['name'] = $tag['name'];
          else $error_code=array('107', 'Invalid Tag Name');
        }
        
        if(isset($tag['slug']))
        {
          if(is_string($tag['name'])) $tags['slug'] = $tag['slug'];
          else $error_code=array('107', 'Invalid Tag Slug');
        }
        
        if(isset($tag['scheme_name']))
        {
          if(is_string($tag['scheme_name'])) $tags['scheme_name'] = $tag['scheme_name'];
        }
        
        if(isset($tag['scheme']))
        {
          if(is_string($tag['scheme'])) $tags['scheme'] = $tag['scheme'];
          else $error_code=array('107', 'Invalid Tag Scheme');
        }
        
        if(isset($tag['weight']))
        {
          if(is_string($tag['weight'])) $tags['weight'] = $tag['weight'];
        }
        array_push($entry_data['tags'], $tags);
      }
      
      if(!empty($existing_tags))
      {
        foreach($existing_tags as $tag)
        {
          array_push($removed_tags, $tag);
          foreach($entry_data['tags'] as $i)
          {
            if($tag === $i['slug'])
            {
              if (($key = array_search($tag, $removed_tags)) !== false) 
              {
                unset($removed_tags[$key]);
                break;
              }
            }
          }
        }       
      } 
      $entry_data['removed_tags'] = $removed_tags;
    }   
    
    if(isset($entry['links']))
    {
      $entry_data['links'] = array();
      $alternates = array();
      $enclosures = array();
      
      if(isset($entry['links']['enclosures']))
      {
        foreach($entry['links']['enclosures'] as $enclosur)
        {
           $enclosure = array();
           $enclosure['type'] = $enclosur['type'];
           $enclosure['uri'] = $enclosur['uri'];
           array_push($enclosures, $enclosure);
        }
      }
      
      if(isset($entry['links']['alternates']))
      {
        foreach($entry['links']['alternates'] as $alternat)
        {
           $alternate = array();
           $alternate['type'] = $alternat['type'];
           $alternate['uri'] = $alternat['uri'];
           array_push($alternates, $alternat);
        }
      }      
      $entry_data['links'] = array('alternates' => $alternates, 'enclosures' => $enclosures);      
    }
    
    if(isset($entry['latitude']))
    {
      if(is_float($entry['latitude'])) $entry_data['latitude'] = $entry['latitude'];
    }
    
    if(isset($entry['longitude']))
    {
      if(is_float($entry['longitude'])) $entry_data['longitude'] = $entry['longitude'];
    }
    
    if(isset($entry['related']))
    {
      $related = array();
      if(isset($entry['related']['type']))
      {
        $related['type'] = $entry['related']['type'];
      }
      if(isset($entry['related']['id']))
      {
        $related['id'] = $entry['related']['id'];
      }
      $entry_data['related'] = $related;  
    }
    
    if(isset($entry['content']))
    {
      $entry_data['content'] = $entry['content'];
    }
    
    if(isset($entry['publication_date']))
    {
      $entry_data['cpublication_date'] = $entry['publication_date'];
    }
    
    if(isset($entry['modification_date']))
    {
      $entry_data['modification_date'] = $entry['modification_date'];
    }
    
    if(isset($entry['metadata']))
    {
      $metadatas = array();
      foreach($entry['metadata'] as $data)
      {
         $metadata = array();
         if(isset($data['id']))
         {
            if(is_string($data['id'])) $metadata['id'] = $data['id'];
            else $error_code = array('112', 'Invalid metadata id');
         }
         if(isset($data['name']))
         {
            if(is_string($data['name'])) $metadata['name'] = $data['name'];
            else $error_code = array('113', 'Invalid metadata name');
         }
         if(isset($data['description']))
         {
            $metadata['description'] = $data['description'];
         }
         array_push($metadatas, $metadata);
      }
      $entry_data['metadata'] = $metadatas;
    }
    
    $response = $this->entrymanager->update($entry_data);  
    if(!empty($response)) $this->response(array('status' => true, 'data' => $response), REST_Controller::HTTP_OK);    
    else $this->response(array('status' => true, array()), REST_Controller::HTTP_OK);
  }

 # entries_post
 #
 # This method is called when a POST request is made via API.
 # Parameters :
 # GUID: String Can be a URI
 # status: active|deleted|hidden|draft
 # source: string not mandatory
 # links: Type
 #          Format
 #          URI
 # related
 #   Type
 #   id
 # publication_date date time
 # title String
 # author
 #    Name
 #    Slug
 # metadata
 #     id  string
 #     name  string
 #     description string
 # content content is a mandatory element
 # tags
 #   scheme tag name (string), tag slug (sanitized string), tag value (integer)
 # longitude float
 # latitude  float
 # publication_date datetime
 # modification_date datetime
 # return_fields comma separated name of fields
  
  public function entries_post($apikey=NULL)
  {
    if(empty($apikey) || !$this->apikeyhandler->keyCheck($apikey))
    {
      $response_arr=array('status' => 'false', 'data' => array('errorCode' => "102", 'errorMessage' => 'Authentication failed, Invalid api key'));
      $this->response($response_arr, REST_Controller::HTTP_OK);
      return;
    }
    
    $entry_data = array();
    $error_code = '';
		
		$post=$this->post('entry');
    
    if(empty($post))
    {
       $this->response(array('true', array()), REST_Controller::HTTP_OK);
    }
    
    $entry=json_decode($post,TRUE);    
    
    if(!isset($entry['content'])) 
    {
      $error_code=array('104', 'Content is mandatory. PLease add Content');
      $this->response(array('false', array('message' => $error_code)), REST_Controller::HTTP_OK);
    }
    else
    {
      if(isset($entry['content']['description'])) $entry_data['content']['description']=$entry['content']['description'];   
      if(isset($entry['content']['summary'])) $entry_data['content']['summary']=$entry['content']['summary'];         
    }
    
    if(isset($entry['creation_date'])) $entry_data['creation_date']=$entry['creation_date'];   
    if(isset($entry['publication_date'])) $entry_data['publication_date']=$entry['publication_date'];
    if(isset($entry['modification_date'])) $entry_data['modification_date']=$entry['modification_date'];
    
    if(isset($entry['author'])) 
    {
      $author_info = array();
      if(isset($entry['author']['name']))
      {
        if(is_string($entry['author']['name'])) $author_info['name']=$entry['author']['name'];
        else $error_code=array('106', 'Invalid Author Name');
      }
      
      if(isset($entry['author']['slug']))
      {
        if(is_string($entry['author']['slug'])) $author_info['slug']=$entry['author']['slug'];
        else $error_code=array('106', 'Invalid Author Slug');
      }
      $entry_data['author']=$author_info;
    }
      
    if(isset($entry['tags'])) 
    {
      $entry_data['tags'] = array();
      
      foreach($entry['tags'] as $tag)
      {
        $tags = array();
        if(isset($tag['name']))
        {
          if(is_string($tag['name'])) $tags['name']=$tag['name'];
          else $error_code=array('106', 'Invalid Author Name');
        }
        
        if(isset($tag['slug']))
        {
          if(is_string($tag['slug'])) $tags['slug']=$tag['slug'];
          else $error_code=array('106', 'Invalid Author Slug');
        }
        
        if(isset($tag['scheme_name']) && is_string($tag['scheme_name']))
        {
          $tags['scheme_name']=$tag['scheme_name'];
        }      

        if(isset($tag['scheme']))
        {
          if(is_string($tag['scheme'])) $tags['scheme']=$tag['scheme'];
          else $error_code=array('107', 'Invalid Tag Scheme');
        }
        
        if(isset($tag['weight']))
        {
          $tags['weight']=$tag['weight'];
        }
        array_push($entry_data['tags'], $tag);        
      }
    }
      
    if(isset($entry['guid']))
    {
      if(is_string($entry['guid'])) $entry_data['guid']=$entry['guid'];
      else $error_code=array('108', 'Invalid GUID');
    }
      
    if(isset($entry['title']))
    {
      if(is_string($entry['title'])) $entry_data['title']=$entry['title'];
      else $error_code=array('109', 'Invalid Title');
    }
      
    if(isset($entry['status']))
    {
      if(is_string($entry['status'])) $entry_data['status']=$entry['status'];
      else $error_code=array('110', 'Invalid Status');
    }

    if(isset($entry['links']))
    {
      $entry_data['links'] = array();
      $alternates = array();
      $enclosures = array();
      if(isset($entry_data['links']['enclosures']))
      {
        foreach($entry_data['links']['enclosures'] as $enclosur)
        {
          $enclosure = array();
          $enclosure['type'] = $enclosur['type'];
          $enclosure['uri'] = $enclosur['uri'];
          array_push($enclosures, $enclosure);
        }
      }
        
      if(isset($entry_data['links']['alternates']))
      {
        foreach($entry_data['links']['alternates'] as $alternat)
        {
          $alternate = array();
          $alternate['type'] = $alternat['type'];
          $alternate['uri'] = $alternat['uri'];
          array_push($alternates, $alternate);
        }
      }
      $entry_data['links'] = array('alternates' => $alternates, 'enclosures' => $enclosures);
    }
   
    if(isset($entry['latitude']) && is_float($entry['latitude']))
    {
      $entry_data['latitude']=$entry['latitude'];
    }
      
    if(isset($entry['longitude']) && is_float($entry['longitude']))
    {
      $entry_data['longitude']=$entry['longitude'];
    }

    if(isset($entry['related'])) 
    {
      $related=array();
      if(isset($entry['related']['type']))
      {
          $related['type'] = $entry['related']['type'];
      }
      if(isset($entry['related']['id']))
      {
        $related['id'] = $entry['related']['id'];
      }
      $entry_data['related'] = $related;
    }
      
    if(isset($entry['source']))
    {
      if(is_string($entry['source'])) $entry_data['source']=$entry['source'];
      else $entry_data['source']='';
    }

    if(isset($entry['metadata'])) 
    {
      $entry_data['metadata'] = array();
      foreach($entry['metadata'] as $data)
      {
        $metadata = array();
        if(isset($data['id']))
        {
          if(is_string($data['id'])) $metadata['id'] = $data['id'];
          else  $error_code=array('112', 'Invalid metadata id');
        }
      
        if(isset($data['name']))
        {
          if(is_string($data['name'])) $metadata['name'] = $data['name'];
          else  $error_code=array('113', 'Invalid metadata name');
        }
    
        if(isset($data['description']))
        {
          $metadata['description'] = $data['description'];
        }
        array_push($entry_data['metadata'], $metadata);
      }
    }
    if ($error_code != '')
    {
      $this->response(array('true', array('message' => $error_code)), REST_Controller::HTTP_OK);
    }
    else
    {
      $response = $this->entrymanager->save($entry_data);
      if(!empty($response)) $this->response(array('status' => true, 'data' => $response), REST_Controller::HTTP_OK);
      else $this->response(array('true', array()), REST_Controller::HTTP_OK);
    }   
  }
}