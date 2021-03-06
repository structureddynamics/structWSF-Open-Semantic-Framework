<?php

/*! @ingroup WsFramework Framework for the Web Services  */
//@{

/*! @file \StructuredDynamics\osf\ws\framework\Solr.php
    @brief Query the Solr server.
 */

namespace StructuredDynamics\osf\ws\framework;  
 
use \DOMDocument;
use \DOMXPath;
use \StructuredDynamics\osf\ws\framework\ProcessorXML;
use \StructuredDynamics\osf\ws\framework\WebService;
 
/** Query the Solr server.

     @author Frederick Giasson, Structured Dynamics LLC.
*/

class Solr
{
  /** URL where to reach the Solr update endpoint */
  private $updateUrl;

  /** URL where to reach the Solr select (normal query) endpoint */
  private $selectUrl;
  
  /** URL where to reach the Solr Luke endpoint */
  private $lukeUrl;
  
  /** This is the folder there the file of the index where all the fields defined in Solr
   *         are indexed. You have to make sure that the web server has write access to this folder.
   *         This folder path has to end with a slash "/". 
   */
  private $fieldIndexFolder;
  
  /**
  * The error message to display in case that that Solr returns an error. This value should be used
  * when sendQuery() and sendContent() returns FALSE.
  */
  public $errorMessage = '';
  
  /**
  * The error message to display in case that that Solr returns an error. This value should be used
  * when sendQuery() and sendContent() returns FALSE. It contains the webpage returned by Solr
  * which is used for debugging purposes.
  */
  public $errorMessageDebug = '';
  

  /** Constructor

      @param $core An optional target Solr core in a multicore setting
      @param $host The host name where the Solr server is accessible
      @param $port The port number where the Solr server is accessible
      @param $fieldIndexFolder The folder where the Solr fields index should be saved on the server
      
      @return returns the XML resultset
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  function __construct($core = "", $host = "localhost", $port = "8983", $fieldIndexFolder = "/tmp/")
  {
    if($core != "")
    {
      $this->updateUrl = "http://$host:$port/solr/$core/update";
      $this->selectUrl = "http://$host:$port/solr/$core/select";
      $this->lukeUrl = "http://$host:$port/solr/$core/admin/luke?numTerms=0";
    }
    else
    {
      $this->updateUrl = "http://$host:$port/solr/update";
      $this->selectUrl = "http://$host:$port/solr/select";
      $this->lukeUrl = "http://$host:$port/solr/admin/luke?numTerms=0";
    }
    
    $this->fieldIndexFolder = $fieldIndexFolder;
  }

  function __destruct() { }

  /** Send a select query to the Solr server

      @param $query Solr query
      
      @return returns the XML resultset
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function select($query) { return $this->sendQuery($query); }

  /** Send a update query to the Solr server

      @param $content Solr content (add) XML item to add to the server
      
      @return returns the XML resultset with the status of this request
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function update($content) { return $this->sendContent($content); }

  /** Send a commit query to the Solr server

      @return returns the XML resultset with the status of this request
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function commit() { return $this->sendContent("<commit />"); }

  /** Delete a specific instance record in the solr index

      @param $uri URI of the instance record to delete
      
      @param $dataset Dataset URI where the instance record is described
      
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function deleteInstanceRecord($uri, $dataset)
  {
    if($uri != "" && $dataset != "")
    {
      return $this->sendContent("<delete><id>" . md5($dataset . $uri) . "</id></delete>");
    }
  }

  /** Send a optimize query to the Solr server

      @return returns the XML resultset with the status of this request
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function optimize() { return $this->sendContent("<optimize />"); }

  /** Remove all records in the Solr index

      @return returns the XML resultset with the status of this request
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function flushIndex() { return $this->sendContent("<delete><query>*:*</query></delete>"); }

  /** Remove all records in the Solr index, belonging to a specific dataset

      @param $dataset Dataset to remove
      
      @return returns the XML resultset with the status of this request
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function flushDataset($dataset)
    { return $this->sendContent("<delete><query>dataset:\"$dataset\"</query></delete>"); }

  /** Send any kind of query to the Solr server.

      @param $query Solr query to send to the server
      @return returns the XML resultset with the status of this request
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  private function sendQuery($query)
  {
    $ch = curl_init();

  
    $headers = array( "Content-Type: text/xml" );    

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $this->selectUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $data = curl_exec($ch);

    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);     
    
    if(curl_errno($ch) || 
       $httpStatusCode == 400)
    {
      $this->errorMessage = 'The search query you provided is not valid, and returns no result. We can\'t say where the error is coming from, but there is one, probably one with the syntax used.';
      $this->errorMessageDebug = htmlentities($data);
      return FALSE;
    }
    elseif($httpStatusCode == 404)
    {
      if(strpos($data, 'Specified dictionary does not exist'))
      {
        $this->errorMessage = 'Specified dictionary does not exist. Please make sure that the spellchecking is properly configured in the Solr instance.';
      }
      else
      {
        $this->errorMessage = 'The search query you provided is not valid, and returns no result. We can\'t say where the error is coming from, but there is one, probably one with the syntax used.';
      }
      
      $this->errorMessageDebug = htmlentities($data);
      return FALSE;
    }
    elseif($httpStatusCode !== 200)
    {
      $this->errorMessage = 'Unexpected error from Solr for this search query';
      
      $this->errorMessageDebug = htmlentities($data);
      return FALSE;      
    }
    else
    {
      return($data);
    }
  }

  /** Encode content to be included in XML files

      @param $string The content string to be encoded
      
      @return returns the encoded string
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function xmlEncode($string)
    { return str_replace(array ("&", "<", ">"), array ("&amp;", "&lt;", "&gt;"), $string); }

  /** Send any kind of query to the Solr server.

      @param $content Solr query to send to the server
      
      @return returns the XML resultset with the status of this request
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  private function sendContent($content)
  {
    $ch = curl_init();

    $headers = array( "Content-Type: text/xml" );

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $this->updateUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $data = curl_exec($ch);

    if(curl_errno($ch))
    {
      $this->errorMessage = 'The search query you provided is not valid, and returns no result. We can\'t say where the error is coming from, but there is one, probably one with the syntax used.';
      $this->errorMessageDebug = htmlentities($data);
      return(FALSE);
    }
    else
    {
      if(strstr($data, '<int name="status">0</int>'))
      {
        return(TRUE);
      }
      else
      {
        $this->errorMessage = 'Data couldn\'t be saved in Solr.';
        $this->errorMessageDebug = htmlentities($data);
        return(FALSE);
      }
    }
  }
  
  /**
  * @brief Get the array of the name of all the fields that have been indexed in Solr.
  * 
  * @return Return an array of all the names of the fields defined in the Solr index.
  * 
  * @author Frederick Giasson, Structured Dynamics LLC.
  */  
  public function getFieldsIndex()
  {
    if(!file_exists($this->fieldIndexFolder."fieldsIndex.srz"))  
    {
      // Force the creation of the index if the file is not existing
      $this->updateFieldsIndex();
    }
    
    return(unserialize(file_get_contents($this->fieldIndexFolder."fieldsIndex.srz")));
  }
  
  /**
  * @brief Force an update of the fields index using the Luke solr endpoint
  * 
  * @return Return FALSE if the index couldn't be update. TRUE otherwise.
  * @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function updateFieldsIndex()
  {
    set_time_limit(65535);
    
    $ch = curl_init();

    $headers = array( "Content-Type: text/xml" );

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $this->lukeUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $data = curl_exec($ch);

    if(curl_errno($ch))
    {
      return FALSE;
    }
    else
    {
      $fields = array();
      
      $domResultset = new DomDocument("1.0", "utf-8");
      $domResultset->loadXML($data);

      $xpath = new DOMXPath($domResultset);
      
      $founds = $xpath->query("//*/lst[@name='fields']//lst");

      if($founds->length <= 250)
      {
        foreach($founds as $found)
        {        
          if($this->isFieldUsed($found->getAttribute("name")))
          {
            array_push($fields, $found->getAttribute("name"));
          }
        } 
      }
      else
      {
        // There is a threshold of maximum 250 fields to check if the field is being used in the index
        // About that threshold, the system administrator will have to run the Solr Optimize command
        // to optimize the index and remove the un-used fields and then re-run the updateFieldIndex()
        // function.
        //
        // When this threshold is reach, the system assumes that all the fields returned by Luke
        // are behing used.
        foreach($founds as $found)
        {        
          array_push($fields, $found->getAttribute("name"));
        } 
      }
      
      $fields = array_unique($fields);
      
      file_put_contents($this->fieldIndexFolder."fieldsIndex.srz", serialize($fields));
    }    
    
    return TRUE;
  }
  
  /**
  * Sometimes, there can be orphan dynamic fields in the Solr index that does now disapear
  * if we optimize the index. This function is used to make sure that there are documents
  * that uses that field in the Solr index.
  * 
  * @param mixed $field
  */
  private function isFieldUsed($field)
  {
    $ch = curl_init();
    
    $headers = array( "Content-Type: text/xml" );    

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $this->selectUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'q='.urlencode($field).':*&start=0&rows=0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $data = curl_exec($ch);

    if(curl_errno($ch))
    {
      return(FALSE);
    }
    else
    {      
      $domResultset = new DomDocument("1.0", "utf-8");
      $domResultset->loadXML($data);

      $xpath = new DOMXPath($domResultset);
      
      $founds = $xpath->query("*[@numFound]");

      foreach($founds as $found)
      {
        $nbResources = $found->attributes->getNamedItem("numFound")->nodeValue;
        break;
      }      
      
      if($nbResources > 0)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }    
    
    return(TRUE);
  }
}


/** Description of a Solr document record

     @author Frederick Giasson, Structured Dynamics LLC.
*/

class SolrDocument
{
  /** Unique identifier of the document */
  public $uri;

  /** Types of a document */
  public $types;

  /** Inferred types of a document */
  public $inferredTypes;

  /** object property/label pairs */
  public $objectPropertiesLabels;

  /** property/text pairs */
  public $propertiesTexts;

  function __construct($uri = "", $types = array(), $inferredTypes = array(), $objectPropertiesLabels = array(),
    $propertiesTexts = array())
  {
    $this->uri = $uri;
    $this->types = $types;
    $this->inferredTypes = $inferredTypes;
    $this->objectPropertiesLabels = $objectPropertiesLabels;
    $this->propertiesTexts = $propertiesTexts;
  }

  function __destruct() { }

  /** Add an object property/label pair

      @param $propertyLabel Property/label pair to add to the solr document
      
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function addObjectPropertyLabel($propertyLabel) { array_push($this->objectPropertiesLabels, $propertyLabel); }

  /** Add an object property/text pair

      @param $propertyText Property/text pair to add to the solr document
      
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function addPropertyText($propertyText) { array_push($this->propertiesTexts, $propertyText); }

  /** Add a type to the solr document

      @param $type type to add to the solr document
      
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function addType($type) { array_push($this->types, $type); }

  /** Add an inferred type

      @param $inferredType Inferred type to add to the solr document
      
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function addInferredType($inferredType) { array_push($this->inferredTypes, $inferredType); }

  /** Serialize this solr document to get indexed by solr.

      @return returns a serialized solr document
      
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function serializeSolrDocument()
  {
    $serialization = "<doc>";

    $serialization .= "<field name=\"uri\">" . $this->uri . "</field>";

    foreach($this->types as $type)
    {
      $serialization .= "<field name=\"type\">" . $type . "</field>";
    }

    foreach($this->inferredTypes as $inferredType)
    {
      $serialization .= "<field name=\"inferred_type\">" . $inferredType . "</field>";
    }

    foreach($this->propertiesTexts as $propertyText)
    {
      $serialization .= "<field name=\"property\">" . $this->xmlEncode($propertyText[0]) . "</field>";
      $serialization .= "<field name=\"text\">" . $this->xmlEncode($propertyText[1]) . "</field>";
    }

    foreach($this->objectPropertiesLabels as $propertyLabel)
    {
      $serialization .= "<field name=\"object_property\">" . $this->xmlEncode($propertyLabel[0]) . "</field>";
      $serialization .= "<field name=\"object_label\">" . $this->xmlEncode($propertyLabel[1]) . "</field>";
    }

    $serialization .= "</doc>";

    return ($serialization);
  }
  
  /** Encode content to be included in XML files

      @param $string The content string to be encoded                                  
      
      @return returns the encoded string
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function xmlEncode($string)
    { return str_replace(array ("&", "<", ">"), array ("&amp;", "&lt;", "&gt;"), $string); }
}

//@}

?>