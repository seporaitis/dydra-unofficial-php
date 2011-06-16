<?php

/**
 *  Represents a Dydra.com RDF repository.
 *
 *  @author Julius Seporaitis <julius@seporaitis.net>
 */

require_once 'DydraResource.php';

class DydraRepository extends DydraResource {

    /**
     *  @var string Repository ID
     */
    private $repositoryId = null;

    public function __construct(DydraClient $client, $repositoryId = null) {
        $this->repositoryId = $repositoryId;

        parent::__construct($client);
    }

    /**
     *  Returns a list of repositories.
     *
     *  @return array
     */
    public function getRepositoryList() {
        $client = $this->getClient()->reset()
        ->setPath("/" . $this->getClient()->getAccountId() . "/repositories")
        ->setAccept(DydraClient::JSON);

        list($responseCode, $body, $headers) = $client->executeRequest();
        if ($responseCode !== 200) {
            throw new DydraException("Could not retrieve repository list from Dydra.");
        }

        $list = json_decode($body);
        if ($list === null) {
            throw new DydraException("Could not parse JSON from Dydra.");
        }

        return $list;
    }

    /**
     *  Create repository.
     *
     *  @param string $name         Repository name
     *  @param string $summary      Summary of data contained within repository
     *  @param string $description  A long form description of repository (can contain markdown formatting)
     *  @param string $homepage     A URL to the repository homepage
     *
     *  @return object
     */
    public function createRepository($name, $summary = null, $description = null, $homepage = null) {
        $client = $this->getClient()->reset()
        ->setPath("/" . $this->getClient()->getAccountId() . "/repositories")
        ->setAccept(DydraClient::JSON)
        ->setMethod("POST");

        $data = array(
            'repository[name]' => $name
        );
        if(!empty($summary)) {
            $data['repository[summary]'] = $summary;
        }
        if(!empty($description)) {
            $data['repository[description]'] = $description;
        }
        if(!empty($homepage)) {
            $data['repository[homepage]'] = $homepage;
        }
        $client->setData($data);

        list($responseCode, $body, $headers) = $client->executeRequest();

        if (!in_array($responseCode,array(
            200, // OK
            201, // Created
        ))) {
            throw new DydraException("Could not create repository on Dydra.");
        }

        $repo = json_decode($body);
        if($repo === null) {
            throw new DydraException("Could not parse JSON from Dydra.");
        }

        return $repo;
    }

    /**
     *  Delete repository.
     *
     *  @return boolean
     */
    public function deleteRepository($name) {
        $client = $this->getClient()->reset()
        ->setPath("/" . $this->getClient()->getAccountId() . "/" . $name)
        ->setAccept(DydraClient::JSON)
        ->setMethod("DELETE");

        list($responseCode, $body, $headers) = $client->executeRequest();

        return ($responseCode == 200);
    }

    /**
     *  Insert single or multiple triples into repository.
     *
     *  Triples array can be either array with single triple:
     *
     *  $array = array(
     *      'subject' => 'http://...',
     *      'predicate' => 'http://...',
     *      'object' => '...',
     *      'type' => '...'         // optional
     *  )
     *
     *  or array with multiple triples, starting at index 0:
     *
     *  $array = array(
     *      0 => array(
     *          'subject' => 'http://...',
     *          'predicate' => 'http://...',
     *          'object' => '...',
     *          'type' => '...'         // optional
     *      ),
     *      .....
     *  )
     *
     *  @param array $array
     *  @return boolean
     */
    public function insert(array $array) {
        if(!isset($array[0])) {
            $array = array($array);
        }
        
        if (empty($array[0])) {
            throw new DydraException("Empty triples array detected.");
        }
        
        if (!isset($array[0]['subject']) || !isset($array[0]['predicate']) || !isset($array[0]['object'])) {
            throw new DydraException("Invalid triples array detected.");
        }

        $data = "";
        foreach ($array as $triple) {
            $data .= sprintf("<%s> <%s> %s .\n", $triple['subject'], $triple['predicate'], (isset($triple['type']) ? sprintf("\"%s\"^^<%s>", $triple['object'], $triple['type']) : sprintf("\"%s\"", $triple['object'])));
        }

        $client = $this->getClient()->reset()
        ->setPath("/" . $this->getClient()->getAccountId() . "/" . $this->getRepositoryId() . "/statements")
        ->setMethod("POST")
        ->setHeader("Content-Type", "text/plain; charset=utf-8")
        ->setData($data);

        list($responseCode, $body, $headers) = $client->executeRequest();

        if (!in_array($responseCode,array(
            200, // OK
            201, // Created
            204, // No Content
        ))) {
            return false;
        }

        return true;
    }

    /**
     *  Import multiple triples into repository from file.
     *
     *  @param string $filename
     *  @return boolean
     */
    public function import($filePath) {
        if (!is_file($filePath)) {
            throw new DydraException("Could not find a file at path: '" . $filePath . "'.");
        }

        if (!is_readable($filePath)) {
            throw new DydraException("File at '" . $filePath . "' is not readable.");
        }

        $client = $this->getClient()->reset()
        ->setPath("/" . $this->getClient()->getAccountId() . "/" . $this->getRepositoryId() . "/statements")
        ->setMethod("POST")
        ->setData(array('file' => "@{$filePath};type=text/plain"));

        list($responseCode, $body, $headers) = $client->executeRequest();

        if (!in_array($responseCode,array(
            200, // OK
            201, // Created
            204, // No Content
        ))) {
            return false;
        }

        return true;
    }

    /**
     *  Execute SPARQL query.
     *
     *  @param string $sparql
     *  @param array $params
     *
     *  @return string
     */
    public function query($sparql, array $params = array()) {
        if (!is_string($sparql)) {
            throw new DydraException("SPARQL query is not a string.");
        }

        $preparedQuery = call_user_func_array('sprintf', array_merge(array($sparql), $params));
        
        $client = $this->getClient()->reset()
        ->setAccept(DydraClient::ANY)
        ->setPath("/" . $this->getClient()->getAccountId() . "/" . $this->getRepositoryId() . "/sparql")
        ->setHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8")
        ->setData(array('query' => $preparedQuery));

        list($responseCode, $body, $headers) = $client->executeRequest();
        
        if(!in_array($responseCode, array(
            200,
        ))) {
            return null;
        }

        return $body;
    }

    /**
     *  Set current repository ID.
     *
     *  @param string $repositoryId     Repository Id
     */
    public function setRepositoryId($repositoryId) {
        $this->repositoryId = $repositoryId;
        return $this;
    }

    /**
     *  Get current repository ID.
     *
     *  @return string
     */
    public function getRepositoryId() {
        return $this->repositoryId;
    }
}
