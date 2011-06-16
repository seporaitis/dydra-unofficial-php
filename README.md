Unofficial Dydra.com Software Development Kit (SDK) for PHP
===========================================================

This is unofficial PHP software development kit (SDK) for [Dydra.com][],
the cloud-hosted RDF & SPARQL database service.

Overview
--------

Current implementation supports:

* Repository List Call
* Create/Delete Repository Calls
* Insert/Import Triple(s) Statements
* SPARQL Endpoint Querying

Dependencies
------------

* [PHP](http://php.net/) (>= 5.3.0)

Download
--------

To get a local working copy of the development repository, do:

    $ git clone git://github.com/seporaitis/dydra-unofficial-php.git
    
Run tests
---------

To run tests you will need [PHPUnit](http://www.phpunit.de/). If you have it, run:
    
    $ cd dydra-unofficial-php
    $ phpunit tests/

Authors
-------

* [Julius Šėporaitis](https://github.com/seporaitis)
* Class structure is based on official [Dydra SDK](http://github.com/dydra/dydra-php).

License
-------

This is free and unencumbered public domain software. For more information,
see <http://unlicense.org/> or the accompanying `UNLICENSE` file.

[PHP]:        http://php.net/
[RDF]:        http://www.w3.org/RDF/
[PDD]:        http://unlicense.org/#unlicensing-contributions
[PHPUnit]:    http://www.phpunit.de/
[Dydra.com]:  http://dydra.com/