<?php

/**
 *  Represents a Dydra.com resource.
 *
 *  This is the base class for all classes that represent dereferenceable
 *  HTTP resources on Dydra.com.
 *
 *  @author Julius Seporaitis <julius@seporaitis.net>
 */

class DydraResource {

    /**
     *  @var DydraClient Dydra REST API wrapper.
     */
    private $client = null;

    public function __construct(DydraClient $client) {
        $this->client = $client;
    }

    /**
     *  @return DydraClient
     */
    protected function getClient() {
        return $this->client;
    }
}
