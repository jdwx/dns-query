<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


interface TransportManagerInterface {


    public function getTransport( string $i_stServerAddress ) : TransportInterface;


}

