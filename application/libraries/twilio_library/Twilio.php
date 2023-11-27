<?php
 
function Services_Twilio_autoload($className)
{
    if (substr($className, 0, 15) != 'Services_Twilio' && substr($className, 0, 26) != 'TaskRouter_Services_Twilio') {
        return false;
    }
    $file = str_replace('_', '/', $className);
    $file = str_replace('Services/', '', $file);
    return include dirname(__FILE__) . "/$file.php";
}

spl_autoload_register('Services_Twilio_autoload');
 
abstract class Base_Services_Twilio extends Services_Twilio_Resource
{
    const USER_AGENT = 'twilio-php/3.13.1';

    protected $http;
    protected $last_response;
    protected $retryAttempts;
    protected $version;
    protected $versions = array('2010-04-01');

    public function __construct(
        $sid,
        $token,
        $version = null,
        Services_Twilio_TinyHttp $_http = null,
        $retryAttempts = 1
    )
    {
        $this->version = in_array($version, $this->versions) ? $version : end($this->versions);

        if (null === $_http) {
            if (!in_array('openssl', get_loaded_extensions())) {
                throw new Services_Twilio_HttpException("The OpenSSL extension is required but not currently enabled. For more information, see http://php.net/manual/en/book.openssl.php");
            }
            if (in_array('curl', get_loaded_extensions())) {
                $_http = new Services_Twilio_TinyHttp(
                    $this->_getBaseUri(),
                    array(
                        "curlopts" => array(
                            CURLOPT_USERAGENT => self::qualifiedUserAgent(phpversion()),
                            CURLOPT_HTTPHEADER => array('Accept-Charset: utf-8'),
                        ),
                    )
                );
            } else {
                $_http = new Services_Twilio_HttpStream(
                    $this->_getBaseUri(),
                    array(
                        "http_options" => array(
                            "http" => array(
                                "user_agent" => self::qualifiedUserAgent(phpversion()),
                                "header" => "Accept-Charset: utf-8\r\n",
                            ),
                            "ssl" => array(
                                'verify_peer' => true,
                                'verify_depth' => 5,
                            ),
                        ),
                    )
                );
            }
        }
        $_http->authenticate($sid, $token);
        $this->http = $_http;
        $this->retryAttempts = $retryAttempts;
    }
 
    public static function buildQuery($queryData, $numericPrefix = '')
    {
        $query = '';
     
        foreach ($queryData as $key => $value) {
             
            if (is_int($key)) {
                $key = $numericPrefix . $key;
            }
 
            if (is_array($value)) {
               
                foreach ($value as $value2) {
                    
                    if ($query !== '') {
                        $query .= '&';
                    }
                    // Recurse
                    $query .= self::buildQuery(array($key => $value2), $numericPrefix);
                }
            } else {
                
                if ($query !== '') {
                    $query .= '&';
                }
                
                $query .= $key . '=' . urlencode((string)$value);
            }
        }
        return $query;
    }
 
    public static function getRequestUri($path, $params, $full_uri = false)
    {
        $json_path = $full_uri ? $path : "$path.json";
        if (!$full_uri && !empty($params)) {
            $query_path = $json_path . '?' . http_build_query($params, '', '&');
        } else {
            $query_path = $json_path;
        }
        return $query_path;
    }
 
    public static function qualifiedUserAgent($php_version)
    {
        return self::USER_AGENT . " (php $php_version)";
    }
 
    public function createData($path, $params = array(), $full_uri = false)
    {
		if (!$full_uri) {
			$path = "$path.json";
		}
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
        $response = $this->http->post(
            $path, $headers, self::buildQuery($params, '')
        );
        return $this->_processResponse($response);
    }
 
    public function deleteData($path, $params = array())
    {
        $uri = self::getRequestUri($path, $params);
        return $this->_makeIdempotentRequest(array($this->http, 'delete'),
            $uri, $this->retryAttempts);
    }
 
    public function getRetryAttempts()
    {
        return $this->retryAttempts;
    }
 
    public function getVersion()
    {
        return $this->version;
    }
 
    public function retrieveData($path, $params = array(),
                                 $full_uri = false
    )
    {
        $uri = static::getRequestUri($path, $params, $full_uri);
        return $this->_makeIdempotentRequest(array($this->http, 'get'),
            $uri, $this->retryAttempts);
    }
 
    protected function _getBaseUri()
    {
        return 'https://api.twilio.com';
    }
 
    protected function _makeIdempotentRequest($callable, $uri, $retriesLeft)
    {
        $response = call_user_func_array($callable, array($uri));
        list($status, $headers, $body) = $response;
        if ($status >= 500 && $retriesLeft > 0) {
            return $this->_makeIdempotentRequest($callable, $uri, $retriesLeft - 1);
        } else {
            return $this->_processResponse($response);
        }
    }
 
    private function _processResponse($response)
    {
        list($status, $headers, $body) = $response;
        if ($status === 204) {
            return true;
        }
        $decoded = json_decode($body);
        if ($decoded === null) {
            throw new Services_Twilio_RestException(
                $status,
                'Could not decode response body as JSON. ' .
                'This likely indicates a 500 server error'
            );
        }
        if (200 <= $status && $status < 300) {
            $this->last_response = $decoded;
            return $decoded;
        }
        throw new Services_Twilio_RestException(
            $status,
            isset($decoded->message) ? $decoded->message : '',
            isset($decoded->code) ? $decoded->code : null,
            isset($decoded->more_info) ? $decoded->more_info : null
        );
    }
}
 
class Services_Twilio extends Base_Services_Twilio
{

    CONST URI = 'https://api.twilio.com';
    protected $versions = array('2008-08-01', '2010-04-01');

    public function __construct(
        $sid,
        $token,
        $version = null,
        Services_Twilio_TinyHttp $_http = null,
        $retryAttempts = 1
    )
    {
        parent::__construct($sid, $token, $version, $_http, $retryAttempts);

        $this->accounts = new Services_Twilio_Rest_Accounts($this, "/{$this->version}/Accounts");
        $this->account = $this->accounts->get($sid);
    }
}
 
class TaskRouter_Services_Twilio extends Base_Services_Twilio
{
    protected $versions = array('v1');
    private $accountSid;

    public function __construct(
        $sid,
        $token,
        $workspaceSid,
        $version = null,
        Services_Twilio_TinyHttp $_http = null,
        $retryAttempts = 1
    )
    {
        parent::__construct($sid, $token, $version, $_http, $retryAttempts);

        $this->workspaces = new Services_Twilio_Rest_TaskRouter_Workspaces($this, "/{$this->version}/Workspaces");
        $this->workspace = $this->workspaces->get($workspaceSid);
        $this->accountSid = $sid;
    }
 
	public static function getRequestUri($path, $params, $full_uri = false)
	{
		if (!$full_uri && !empty($params)) {
			$query_path = $path . '?' . http_build_query($params, '', '&');
		} else {
			$query_path = $path;
		}
		return $query_path;
	}

    public static function createWorkspace($sid, $token, $friendlyName, array $params = array(), Services_Twilio_TinyHttp $_http = null)
    {
        $taskrouterClient = new TaskRouter_Services_Twilio($sid, $token, null, null, $_http);
        return $taskrouterClient->workspaces->create($friendlyName, $params);
    }

    public function getTaskQueuesStatistics(array $params = array())
    {
        return $this->retrieveData("/{$this->version}/Workspaces/{$this->workspace->sid}/TaskQueues/Statistics", $params);
    }

    public function getTaskQueueStatistics($taskQueueSid, array $params = array())
    {
        return $this->retrieveData("/{$this->version}/Workspaces/{$this->workspace->sid}/TaskQueues/{$taskQueueSid}/Statistics", $params);
    }

    public function getWorkersStatistics(array $params = array())
    {
        return $this->retrieveData("/{$this->version}/Workspaces/{$this->workspace->sid}/Workers/Statistics", $params);
    }

    public function getWorkerStatistics($workerSid, array $params = array())
    {
        return $this->retrieveData("/{$this->version}/Workspaces/{$this->workspace->sid}/Workers/{$workerSid}/Statistics", $params);
    }

    public function getWorkflowStatistics($workflowSid, array $params = array())
    {
        return $this->retrieveData("/{$this->version}/Workspaces/{$this->workspace->sid}/Workflows/{$workflowSid}/Statistics", $params);
    }

    public function getWorkspaceStatistics(array $params = array())
    {
        return $this->retrieveData("/{$this->version}/Workspaces/{$this->workspace->sid}/Statistics", $params);
    }

    protected function _getBaseUri()
    {
        return 'https://taskrouter.twilio.com';
    }
}

 
class Lookups_Services_Twilio extends Base_Services_Twilio
{
    protected $versions = array('v1');
    private $accountSid;

    public function __construct(
        $sid,
        $token,
        $version = null,
        Services_Twilio_TinyHttp $_http = null,
        $retryAttempts = 1
    )
    {
        parent::__construct($sid, $token, $version, $_http, $retryAttempts);

        $this->accountSid = $sid;
        $this->phone_numbers = new Services_Twilio_Rest_Lookups_PhoneNumbers($this, "/{$this->version}/PhoneNumbers");
    }
 
	public static function getRequestUri($path, $params, $full_uri = false)
	{
		if (!$full_uri && !empty($params)) {
			$query_path = $path . '?' . http_build_query($params, '', '&');
		} else {
			$query_path = $path;
		}
		return $query_path;
	}
 
    protected function _getBaseUri()
    {
        return 'https://lookups.twilio.com';
    }

}
