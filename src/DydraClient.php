<?php

/**
 *  Implements a Dydra.com REST API client.
 *
 *  @author Julius Seporaitis <julius@seporaitis.net>
 */

class DydraClient {

    /** Remote host returned something other than an HTTP response. */
    const ERROR_MALFORMED_RESPONSE  = 1000;

    const HOST = "http://dydra.com";

    const RDFXML = "application/rdf+xml";
    const NTRIPLES = "text/plain";
    const TURTLE = "application/x-turtle";
    const N3 = "text/rdf+n3";
    const JSON = "application/json";
    const ANY = "*/*";

    /**
     *  @var string Account ID
     */
    private $accountId = null;

    /**
     *  @var string Authentication token.
     */
    private $authToken = null;

    /**
     *  @var resource Curl connection.
     */
    private $connection = null;

    /**
     *  @var string HTTP method
     */
    private $method = 'GET';

    private $path = null;
    private $data = null;
    private $headers = array();

    private $accept = self::RDFXML;

    /**
     *  Initializes client variables.
     *
     *  @param string $accountId        Account Id
     *  @param string $repositoryId     Repository Id
     */
    public function __construct($accountId, $authToken) {
        $this->accountId = $accountId;
        $this->authToken = $authToken;

        $this->connection = curl_init();
    }

    /**
     *  Destroys the curl connection.
     */
    public function __destruct() {
       curl_close($this->connection);
    }

    /**
     *  Execute request on dydra.com
     */
    public function executeRequest() {
        curl_setopt($this->connection, CURLOPT_URL, $this->getURL());
        curl_setopt($this->connection, CURLOPT_CUSTOMREQUEST, $this->getMethod());
        curl_setopt($this->connection, CURLOPT_HTTPHEADER, $this->prepareHTTPHeaders());
        curl_setopt($this->connection, CURLOPT_HEADER, true); // Return response headers? Yes.
        curl_setopt($this->connection, CURLOPT_RETURNTRANSFER, true);
        
        if($this->getMethod() === "POST") {
            curl_setopt($this->connection, CURLOPT_POSTFIELDS, $this->getData());
        }

        $response = curl_exec($this->connection);

        return $this->parseHTTPResponse($response);
    }

    /**
     *  Reset connection for next request.
     */
    public function reset() {
        $this->method = 'GET';
        $this->path = null;
        $this->data = null;
        $this->headers = array();
        $this->accept = self::RDFXML;
        return $this;
    }

    /**
     *  Prepare request URL.
     *
     *  @return string
     */
    protected function getURL() {
        $url = rtrim(static::HOST, "/") . "/" . ltrim($this->getPath(), "/");

        if ($this->getMethod() === "GET") {
            $data = $this->getData();

            // if data is array, prepare a string
            if (is_array($data)) {
                $parts = array();
                foreach ($data as $key => $value) {
                    $parts[] = "{$key}=" . urlencode($value);
                }
                $data = implode("&", $parts);
            }

            // if data is a string - add it at the end of url.
            if (is_string($data) && !empty($data)) {
                if (!strpos($url, "?")) {
                    $url .= "?" . $data;
                } else {
                    $url .= "&" . $data;
                }
            }
        }

        // Add authentication token at the end of URL.
        if (!strpos($url, "?")) {
            $url .= "?auth_token=" . $this->authToken;
        } else {
            $url .= "&auth_token=" . $this->authToken;
        }

        return $url;
    }

    /**
     *  Parse HTTP response and return array with
     *      - response code
     *      - response body
     *      - response headers
     *
     *  Method is from libphutils' HTTPFuture,
     *  @see https://github.com/facebook/libphutil/blob/master/src/future/http/HTTPFuture.php
     *
     *  @return array
     */
    protected function parseHTTPResponse($response) {
        static $rex_base = "@^(?<head>.*?)\r?\n\r?\n(?<body>.*)$@s";
        static $rex_head = "@^HTTP/\S+ (?<code>\d+) .*?(?:\r?\n(?<headers>.*))?$@s";
        static $rex_header = '@^(?<name>.*?):\s*(?<value>.*)$@';

        static $malformed = array(
          self::ERROR_MALFORMED_RESPONSE,
          null,
          array(),
        );

        // remove "HTTP/1.1 100 Continue"
        $response = ltrim(str_ireplace("HTTP/1.1 100 Continue", "", $response), "\r\n ");

        $matches = null;
        if (!preg_match($rex_base, $response, $matches)) {
            return $malformed;
        }

        $head = $matches['head'];
        $body = $matches['body'];

        if (!preg_match($rex_head, $head, $matches)) {
            return $malformed;
        }

        $response_code = (int)$matches['code'];

        $headers = array();
        if (isset($matches['headers'])) {
            $head_raw = $matches['headers'];
            if (strlen($head_raw)) {
                $headers_raw = preg_split("/\r?\n/", $head_raw);
                foreach ($headers_raw as $header) {
                    $m = null;
                    if (preg_match($rex_header, $header, $m)) {
                        $headers[] = array($m['name'], $m['value']);
                    } else {
                        $headers[] = array($header, null);
                    }
                }
            }
        }

        return array(
            $response_code,
            $body,
            $headers
        );
    }

    /**
     *  Prepares HTTP headers.
     *
     *  @return string
     */
    protected function prepareHTTPHeaders() {
        $this->setHeader("Accept", $this->getAccept());

        $headers = array();
        foreach ($this->getAllHeaders() as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        return $headers;
    }
    
    /**
     *  Returns account Id.
     *
     *  @return string
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     *  Set URI path component.
     *
     *  @param string $path         URI path component
     */
    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    /**
     *  Return URI path component.
     *
     *  @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     *  Set request data.
     *
     *  @param mixed $data          Request data
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     *  Get request data.
     *
     *  @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     *  Set HTTP request method.
     *
     *  @param string $method       HTTP request method
     */
    public function setMethod($method) {
        if (!in_array(strtoupper($method), array("GET", "POST", "PUT", "DELETE"))) {
            throw new DydraException("HTTP method can be one of: 'GET', 'POST', 'PUT' or 'DELETE'.");
        }
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     *  Get HTTP request method.
     *
     *  @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     *  Set HTTP header.
     *
     *  @param string $name         Header name
     *  @param string $value        Header value
     */
    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     *  Get HTTP headers
     *
     *  @param string $name     Header name
     *  @return mixed
     */
    public function getHeader($name) {
        return idx($this->headers, $name);
    }

    /**
     *  Get all HTTP headers
     *
     *  @return array
     */
    public function getAllHeaders() {
        return $this->headers;
    }

    /**
     *  Set default accept header
     *
     *  @param string $mimetype         Mime-type
     */
    public function setAccept($mimetype) {
        static $valid = array(
            self::RDFXML,
            self::NTRIPLES,
            self::TURTLE,
            self::N3,
            self::JSON,
            self::ANY,
        );

        if (!in_array($mimetype, $valid)) {
            throw new DydraException("Mime-Type {$mimetype} is not supported by Dydra.");
        }

        $this->accept = $mimetype;
        return $this;
    }

    /**
     *  Get default accept header.
     *
     *  @return string
     */
    public function getAccept() {
        return $this->accept;
    }

}

?>
