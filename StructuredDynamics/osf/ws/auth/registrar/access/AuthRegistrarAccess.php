<?php

/*! @ingroup WsAuth Authentication / Registration Web Service */
//@{

/*! @file \StructuredDynamics\osf\ws\auth\registrar\access\AuthRegistrarAccess.php
    @brief Define the Authentication / Registration web service
 */

namespace StructuredDynamics\osf\ws\auth\registrar\access;  

use \StructuredDynamics\osf\ws\framework\CrudUsage;
use \StructuredDynamics\osf\ws\framework\Conneg;
use \StructuredDynamics\osf\ws\auth\lister\AuthLister;
use \StructuredDynamics\osf\ws\dataset\read\DatasetRead;

/** AuthRegister Access Web Service. It registers an Access on the OSF instance. Register 
    an Access (user access to a dataset, for a given set of web services, with some CRUD permissions) 
    on the OSF instance
    
    @author Frederick Giasson, Structured Dynamics LLC.
*/

class AuthRegistrarAccess extends \StructuredDynamics\osf\ws\framework\WebService
{
  /** URL where the DTD of the XML document can be located on the Web */
  private $dtdURL;

  /** Supported serialization mime types by this Web service */
  public static $supportedSerializations =
    array ("application/json", "application/rdf+xml", "application/rdf+n3", "application/*", "text/xml", "text/*",
      "*/*");

  /** Group related to the access record being created */
  private $group = "";

  /** CRUD access being registered */
  private $crud;

  /** WS URIs being registered */
  private $ws_uris = array();

  /** Dataset being registered */
  private $dataset = "";

  /** URI of the access to update if action=update */
  private $target_access_uri = "";

  /** Type of action to perform: (1) create (2) delete_target (3) delete_specific (4) delete_all (5) update */
  private $action = "";

  /** Error messages of this web service */
  private $errorMessenger =
    '{
                        "ws": "/ws/auth/registrar/access/",
                        "_200": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-200",
                          "level": "Warning",
                          "name": "Action type undefined",
                          "description": "No type of \'action\' has been defined for this query."
                        },
                        "_201": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-201",
                          "level": "Warning",
                          "name": "No Group to register to",
                          "description": "No Group URI has been defined for this query."
                        },
                        "_202": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-202",
                          "level": "Warning",
                          "name": "No crud access defined",
                          "description": "No crud access have been defined for this query."
                        },
                        "_203": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-203",
                          "level": "Warning",
                          "name": "No web service URI(s) defined",
                          "description": "No web service URI(s) have been defined for this query."
                        },
                        "_204": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-204",
                          "level": "Warning",
                          "name": "No dataset defined",
                          "description": "No dataset has been defined for this query."
                        },
                        "_205": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-205",
                          "level": "Warning",
                          "name": "No target Access URI defined for update",
                          "description": "No target Access URI has been defined to be updated for this query."
                        },
                        "_207": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-207",
                          "level": "Warning",
                          "name": "Unexisting group",
                          "description": "The group URI you provided is not existing in this OSF network instance"
                        },
                        "_300": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-300",
                          "level": "Fatal",
                          "name": "Can\'t create the access to this dataset",
                          "description": "An error occured when we tried to create the new access to this dataset"
                        },
                        "_301": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-301",
                          "level": "Fatal",
                          "name": "Can\'t update the access to this dataset",
                          "description": "An error occured when we tried to update the new access to this dataset"
                        },
                        "_302": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-302",
                          "level": "Fatal",
                          "name": "Can\'t delete the access to this dataset",
                          "description": "An error occured when we tried to delete the new access to this dataset"
                        },  
                        "_303": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-303",
                          "level": "Fatal",
                          "name": "Can\'t delete all accesses to this dataset",
                          "description": "An error occured when we tried to delete all accesses to this dataset"
                        },
                        "_304": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-304",
                          "level": "Fatal",
                          "name": "Requested source interface not existing",
                          "description": "The source interface you requested is not existing for this web service endpoint."
                        },
                        "_305": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-305",
                          "level": "Fatal",
                          "name": "Requested incompatible Source Interface version",
                          "description": "The version of the source interface you requested is not compatible with the version of the source interface currently hosted on the system. Please make sure that your tool get upgraded for using this current version of the endpoint."
                        },
                        "_306": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-306",
                          "level": "Fatal",
                          "name": "Source Interface\'s version not compatible with the web service endpoint\'s",
                          "description": "The version of the source interface you requested is not compatible with the one of the web service endpoint. Please contact the system administrator such that he updates the source interface to make it compatible with the new endpoint version."
                        },
                        "_307": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-307",
                          "level": "Fatal",
                          "name": "Can\'t delete access record",
                          "description": "Can\'t delete the specific access record specified in this query"
                        },
                        "_308": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-308",
                          "level": "Fatal",
                          "name": "Can\'t delete all accesses to this group",
                          "description": "An error occured when we tried to delete all accesses to this group"
                        },
                        "_309": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-309",
                          "level": "Warning",
                          "name": "Invalid group URI",
                          "description": "The URI of the group is not valid."
                        },                        
                        "_310": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-310",
                          "level": "Warning",
                          "name": "Invalid dataset URI",
                          "description": "The URI of the dataset is not valid."
                        },                        
                        "_311": {
                          "id": "WS-AUTH-REGISTRAR-ACCESS-311",
                          "level": "Warning",
                          "name": "Invalid target access URI",
                          "description": "The URI of the target access is not valid."
                        }
                      }';

  /**
  * Implementation of the __get() magic method. We do implement it to create getter functions
  * for all the protected and private variables of this class, and to all protected variables
  * of the parent class.
  * 
  * This implementation is needed by the interfaces layer since we want the SourceInterface
  * class to access the variables of the web service class for which it is used as a 
  * source interface.
  * 
  * This means that all the privated and propected variables of these web service objects
  * are available to users; but they won't be able to set values for them.
  * 
  * Also note that This method is about 4 times slower than having the varaible as public instead 
  * of protected and private. However, these variables are only accessed about 10 to 200 times 
  * per script call. This means that for accessing these undefined variable using the __get magic 
  * method call, then it adds about 0.00022 seconds to the call or, about 0.22 milli-second 
  * (one fifth of a millisecond) For the gain of keeping the variables protected and private, 
  * we can spend this one fifth of a milli-second. This is a good compromize.  
  * 
  * @param mixed $name Name of the variable that is currently not defined for this object
  */
  public function __get($name)
  {
    // Check if the variable exists (so, if it is private or protected). If it is, then
    // we return the value. Otherwise a fatal error will be returned by PHP.
    if(isset($this->{$name}))
    {
      return($this->{$name});
    }
  }                      
                      
  /** Constructor

      @param $crud   A quadruple with a value "True" or "False" defined as <Create;Read;Update;Delete>. 
                    Each value is separated by the ";" character. an example of such a quadruple is:
                    "crud=True;True;False;False", meaning: Create = True,
                    Read = True, Update = False and Delete = False
      @param $ws_uris A list of ";" separated Web services URI accessible by this access  definition
      @param $dataset URI of the target dataset of this access  description
      @param $action One of:  (1)"create (default)": Create a new access description
                              (2) "delete_target": Delete target access permissions records for a specific IP address and a specific dataset. This deletes all the access permissions of a user for a target dataset. 
                              (3) "delete_specific": Delete a specific access permissions records
                              (4) "delete_all": Delete all access descriptions for a target dataset or a target group
                              (5) "update": Update an existing access description 
      @param $target_access_uri Target URI of the access resource to update. Only used when param4 = update
      @param $group Target Group URI related to the acces record being created
      @param $interface Name of the source interface to use for this web service query. Default value: 'default'                                  
      @param $requestedInterfaceVersion Version used for the requested source interface. The default is the latest 
                                        version of the interface.
      
      @return returns NULL
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  function __construct($crud, $ws_uris, $dataset, $action, $target_access_uri, $group, 
                       $interface='default', $requestedInterfaceVersion="")
  {
    parent::__construct();
    
    $this->version = "3.0";

    $this->group = $group;
    $this->target_access_uri = $target_access_uri;

    $crud = explode(";", $crud);

    $this->crud = new CrudUsage((isset($crud[0]) && strtolower($crud[0]) == "true" ? TRUE : FALSE), (isset($crud[1]) && strtolower($crud[1])
      == "true" ? TRUE : FALSE), (isset($crud[2]) && strtolower($crud[2]) == "true" ? TRUE : FALSE), (isset($crud[3]) && strtolower($crud[3])
      == "true" ? TRUE : FALSE));

    $this->ws_uris = explode(";", $ws_uris);
    $this->dataset = $dataset;
    $this->action = $action;
    
    if(strtolower($interface) == "default")
    {
      $this->interface = $this->default_interfaces["auth_registrar_access"];
    }
    else
    {
      $this->interface = $interface;
    } 
    
    $this->requestedInterfaceVersion = $requestedInterfaceVersion;

    $this->uri = $this->wsf_base_url . "/wsf/ws/auth/registrar/access/";
    $this->title = "Authentication Access Registration Web Service";
    $this->crud_usage = new CrudUsage(TRUE, TRUE, FALSE, TRUE);
    $this->endpoint = $this->wsf_base_url . "/ws/auth/registrar/access/";

    $this->errorMessenger = json_decode($this->errorMessenger);
  }

  function __destruct()
  {
    parent::__destruct();
  }

  /** Validate a query to this web service
      
      @return TRUE if valid; FALSE otherwise
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function validateQuery()
  {
    if($this->validateUserAccess($this->wsf_graph))
    {
      if(!empty($this->group) && !$this->isValidIRI($this->group))
      {
        $this->conneg->setStatus(400);
        $this->conneg->setStatusMsg("Bad Request");
        $this->conneg->setStatusMsgExt($this->errorMessenger->_309->name);
        $this->conneg->setError($this->errorMessenger->_309->id, $this->errorMessenger->ws,
          $this->errorMessenger->_309->name, $this->errorMessenger->_309->description, "",
          $this->errorMessenger->_309->level);

        return;
      }        
      
      if(!empty($this->dataset) && !$this->isValidIRI($this->dataset))
      {
        $this->conneg->setStatus(400);
        $this->conneg->setStatusMsg("Bad Request");
        $this->conneg->setStatusMsgExt($this->errorMessenger->_310->name);
        $this->conneg->setError($this->errorMessenger->_310->id, $this->errorMessenger->ws,
          $this->errorMessenger->_310->name, $this->errorMessenger->_310->description, "",
          $this->errorMessenger->_310->level);

        return;
      }        
      
      if(!empty($this->target_access_uri) && !$this->isValidIRI($this->target_access_uri))
      {
        $this->conneg->setStatus(400);
        $this->conneg->setStatusMsg("Bad Request");
        $this->conneg->setStatusMsgExt($this->errorMessenger->_311->name);
        $this->conneg->setError($this->errorMessenger->_311->id, $this->errorMessenger->ws,
          $this->errorMessenger->_311->name, $this->errorMessenger->_311->description, "",
          $this->errorMessenger->_311->level);

        return;
      }        
      
      if(strtolower($this->action) != "create" && strtolower($this->action) != "delete_target"
        && strtolower($this->action) != "delete_all" && strtolower($this->action) != "update"
        && strtolower($this->action) != "delete_specific")
      {
        $this->conneg->setStatus(400);
        $this->conneg->setStatusMsg("Bad Request");
        $this->conneg->setStatusMsgExt($this->errorMessenger->_200->name);
        $this->conneg->setError($this->errorMessenger->_200->id, $this->errorMessenger->ws,
          $this->errorMessenger->_200->name, $this->errorMessenger->_200->description, "",
          $this->errorMessenger->_200->level);
        return;
      }

      if($this->group == "" && strtolower($this->action) != "delete_all" &&
         strtolower($this->action) != "delete_specific")
      {
        $this->conneg->setStatus(400);
        $this->conneg->setStatusMsg("Bad Request");
        $this->conneg->setStatusMsgExt($this->errorMessenger->_201->name);
        $this->conneg->setError($this->errorMessenger->_201->id, $this->errorMessenger->ws,
          $this->errorMessenger->_201->name, $this->errorMessenger->_201->description, "",
          $this->errorMessenger->_201->level);
        return;
      }

      if(strtolower($this->action) != "delete_target" && strtolower($this->action) != "delete_all" && 
         strtolower($this->action) != "delete_specific" )
      {
        // Only need this information for create/update
        if($this->crud == "")
        {
          $this->conneg->setStatus(400);
          $this->conneg->setStatusMsg("Bad Request");
          $this->conneg->setStatusMsgExt($this->errorMessenger->_202->name);
          $this->conneg->setError($this->errorMessenger->_202->id, $this->errorMessenger->ws,
            $this->errorMessenger->_202->name, $this->errorMessenger->_202->description, "",
            $this->errorMessenger->_202->level);

          return;
        }
      }

      if(strtolower($this->action) != "delete_target" && strtolower($this->action) != "delete_all" && 
         strtolower($this->action) != "delete_specific" )
      {
        // Only need this information for create/update
        if(count($this->ws_uris) <= 0 || $this->ws_uris[0] == "")
        {
          $this->conneg->setStatus(400);
          $this->conneg->setStatusMsg("Bad Request");
          $this->conneg->setStatusMsgExt($this->errorMessenger->_203->name);
          $this->conneg->setError($this->errorMessenger->_203->id, $this->errorMessenger->ws,
            $this->errorMessenger->_203->name, $this->errorMessenger->_203->description, "",
            $this->errorMessenger->_203->level);
          return;
        }
      }

      if(($this->dataset == "" && strtolower($this->action) != "delete_specific") && 
         !($this->group != "" && strtolower($this->action) == "delete_all"))
      {
        $this->conneg->setStatus(400);
        $this->conneg->setStatusMsg("Bad Request");
        $this->conneg->setStatusMsgExt($this->errorMessenger->_204->name);
        $this->conneg->setError($this->errorMessenger->_204->id, $this->errorMessenger->ws,
          $this->errorMessenger->_204->name, $this->errorMessenger->_204->description, "",
          $this->errorMessenger->_204->level);
        return;
      }

      if((strtolower($this->action) == "update" || strtolower($this->action) == "delete_specific") 
         && $this->target_access_uri == "")
      {
        $this->conneg->setStatus(400);
        $this->conneg->setStatusMsg("Bad Request");
        $this->conneg->setStatusMsgExt($this->errorMessenger->_205->name);
        $this->conneg->setError($this->errorMessenger->_205->id, $this->errorMessenger->ws,
          $this->errorMessenger->_205->name, $this->errorMessenger->_205->description, "",
          $this->errorMessenger->_205->level);
        return;
      }    
      
      // Make sure the group URI exists
      if(strtolower($this->action) != "delete_all" &&
         strtolower($this->action) != "delete_specific")
      {
        $authLister = new AuthLister("groups", "", $this->group);

        $authLister->pipeline_conneg($this->conneg->getAccept(), $this->conneg->getAcceptCharset(),
          $this->conneg->getAcceptEncoding(), $this->conneg->getAcceptLanguage());

        $authLister->process();

        if(stripos($authLister->ws_serialize(), $this->group) === FALSE)
        {
          $this->conneg->setStatus(400);
          $this->conneg->setStatusMsg("Bad Request");
          $this->conneg->setStatusMsgExt($this->errorMessenger->_207->name);
          $this->conneg->setError($this->errorMessenger->_207->id, $this->errorMessenger->ws,
            $this->errorMessenger->_207->name, $this->errorMessenger->_207->description, "",
            $this->errorMessenger->_207->level);
          return;
        }      
      }
    }
  }

  /** Returns the error structure

      @return returns the error structure
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function pipeline_getError() { return ($this->conneg->error); }

  /**  @brief Create a resultset in a pipelined mode based on the processed information by the Web service.

      @return a resultset XML document
      
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function pipeline_getResultset() { return ""; }

  /** Inject the DOCType in a XML document

      @param $xmlDoc The XML document where to inject the doctype
      
      @return a XML document with a doctype
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function injectDoctype($xmlDoc) { return ""; }

  /** Do content negotiation as an external Web Service

      @param $accept Accepted mime types (HTTP header)
      
      @param $accept_charset Accepted charsets (HTTP header)
      
      @param $accept_encoding Accepted encodings (HTTP header)
  
      @param $accept_language Accepted languages (HTTP header)
    
      @return returns NULL
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function ws_conneg($accept, $accept_charset, $accept_encoding, $accept_language)
  {
    $this->conneg = new Conneg($accept, $accept_charset, $accept_encoding, $accept_language,
      AuthRegistrarAccess::$supportedSerializations);

    // Validate call
    $this->validateCall();  
      
    // Validate query
    if($this->conneg->getStatus() == 200)
    {
      $this->validateQuery();
    }      
  }

  /** Do content negotiation as an internal, pipelined, Web Service that is part of a Compound Web Service

      @param $accept Accepted mime types (HTTP header)
      
      @param $accept_charset Accepted charsets (HTTP header)
      
      @param $accept_encoding Accepted encodings (HTTP header)
  
      @param $accept_language Accepted languages (HTTP header)
    
      @return returns NULL
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function pipeline_conneg($accept, $accept_charset, $accept_encoding, $accept_language)
  {     
    $this->ws_conneg($accept, $accept_charset, $accept_encoding, $accept_language); 
    
    $this->isInPipelineMode = TRUE;
  }

  /** Returns the response HTTP header status

      @return returns the response HTTP header status
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function pipeline_getResponseHeaderStatus() { return $this->conneg->getStatus(); }

  /** Returns the response HTTP header status message

      @return returns the response HTTP header status message
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function pipeline_getResponseHeaderStatusMsg() { return $this->conneg->getStatusMsg(); }

  /** Returns the response HTTP header status message extension

      @return returns the response HTTP header status message extension
    
      @note The extension of a HTTP status message is
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function pipeline_getResponseHeaderStatusMsgExt() { return $this->conneg->getStatusMsgExt(); }

  /** Serialize the web service answer.

      @return returns the serialized content
    
      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function ws_serialize() { return ""; }

  /** Register the Access to the WSF

      @author Frederick Giasson, Structured Dynamics LLC.
  */
  public function process()
  {
    // Check if the interface called by the user is existing
    $class = $this->sourceinterface_exists(rtrim($this->wsf_base_path, "/")."/auth/registrar/access/interfaces/");
    
    if($class != "")
    {    
      $class = 'StructuredDynamics\osf\ws\auth\registrar\access\interfaces\\'.$class;
      
      $interface = new $class($this);
      
      // Validate versions
      if($this->requestedInterfaceVersion == "")
      {
        // The default requested version is the last version of the interface
        $this->requestedInterfaceVersion = $interface->getVersion();
      }
      else
      {
        if(!$interface->validateWebServiceCompatibility())
        {
          $this->conneg->setStatus(400);
          $this->conneg->setStatusMsg("Bad Request");
          $this->conneg->setStatusMsgExt($this->errorMessenger->_306->name);
          $this->conneg->setError($this->errorMessenger->_306->id, $this->errorMessenger->ws,
            $this->errorMessenger->_306->name, $this->errorMessenger->_306->description, 
            "Requested Source Interface: ".$this->interface,
            $this->errorMessenger->_306->level);
            
          return;        
        }
        
        if(!$interface->validateInterfaceVersion())
        {
          $this->conneg->setStatus(400);
          $this->conneg->setStatusMsg("Bad Request");
          $this->conneg->setStatusMsgExt($this->errorMessenger->_305->name);
          $this->conneg->setError($this->errorMessenger->_305->id, $this->errorMessenger->ws,
            $this->errorMessenger->_305->name, $this->errorMessenger->_305->description, 
            "Requested Source Interface: ".$this->interface,
            $this->errorMessenger->_305->level);  
            
            return;
        }
      }      
      
      // Process the code defined in the source interface
      $interface->processInterface();
    }
    else
    { 
      // Interface not existing
      $this->conneg->setStatus(400);
      $this->conneg->setStatusMsg("Bad Request");
      $this->conneg->setStatusMsgExt($this->errorMessenger->_304->name);
      $this->conneg->setError($this->errorMessenger->_304->id, $this->errorMessenger->ws,
        $this->errorMessenger->_304->name, $this->errorMessenger->_304->description, 
        "Requested Source Interface: ".$this->interface,
        $this->errorMessenger->_304->level);
    } 
  }
}

//@}

?>