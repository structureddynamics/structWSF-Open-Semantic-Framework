<?php

/*! @ingroup WsOntology Ontology Management Web Service  */
//@{

/*! @file \StructuredDynamics\osf\ws\ontology\update\index.php
    @brief Entry point of a query for the Ontology Update web service
 */
 
include_once("../../../../SplClassLoader.php");   

use \StructuredDynamics\osf\ws\ontology\update\OntologyUpdate;
 
 
// Don't display errors to the users. Set it to "On" to see errors for debugging purposes.
ini_set("display_errors", "Off"); 

ini_set("memory_limit", "256M");

if ($_SERVER['REQUEST_METHOD'] != 'POST') 
{
  header("HTTP/1.1 405 Method Not Allowed");  
  die;
}

// Interface to use for this query
$interface = "default";

if(isset($_POST['interface']))
{
  $interface = $_POST['interface'];
}

// Version of the requested interface to use for this query
$version = "";

if(isset($_POST['version']))
{
  $version = $_POST['version'];
}

// Ontology RDF document where resource(s) to be added are described
$ontology = "";

if(isset($_POST['ontology']))
{
  $ontology = $_POST['ontology'];
}

// The function to query via the webservice
$function = "";

if(isset($_POST['function']))
{
  $function = $_POST['function'];
}

// The parameters of the function to use
$params = "";

if(isset($_POST['parameters']))
{
  $params = $_POST['parameters'];
}

$reasoner = "true";

if(isset($_POST['reasoner']))
{
  if(strtolower($_POST['reasoner']) == "false")
  {
    $reasoner = FALSE;
  }
  else
  {
    $reasoner = TRUE;
  }  
}

$ws_ontologyupdate = new OntologyUpdate($ontology, $interface, $version);

$ws_ontologyupdate->ws_conneg((isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ""), 
                              (isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : ""), 
                              (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : ""), 
                              (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : "")); 

                              
// set reasoner
if($reasoner)
{
  $ws_ontologyupdate->useReasonerForAdvancedIndexation();
}
else
{
  $ws_ontologyupdate->stopUsingReasonerForAdvancedIndexation();
}
                                
                              
$params = explode(";", $params);
$parameters = array();

foreach($params as $param)
{
  $p = explode("=", $param);

  $parameters[strtolower($p[0])] = urldecode((isset($p[1]) ? $p[1] : ''));
}  
 
switch(strtolower($function))
{
  case "saveontology":
    $ws_ontologyupdate->saveOntology();  
  break;

  case "createorupdateentity":
    $advancedIndexation = FALSE;
     
    if($parameters["advancedindexation"] == "1" || 
       strtolower($parameters["advancedindexation"]) == "true")
    {
      $advancedIndexation = TRUE;
    }
  
    $ws_ontologyupdate->createOrUpdateEntity($parameters["document"], $advancedIndexation);
  break;
  
  case "updateentityuri":
    $advancedIndexation = FALSE;
            
    if($parameters["advancedindexation"] == "1" || 
       strtolower($parameters["advancedindexation"]) == "true")
    {
      $advancedIndexation = TRUE;
    }
  
    $ws_ontologyupdate->updateEntityUri($parameters["olduri"], $parameters["newuri"], $advancedIndexation);
  break;
  

  default:
    $ws_ontologyupdate->returnError(400, "Bad Request", "_201");
  break;         
}     
  
$ws_ontologyupdate->ws_respond($ws_ontologyupdate->ws_serialize());

//@}

?>