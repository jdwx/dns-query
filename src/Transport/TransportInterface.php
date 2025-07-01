<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


interface TransportInterface {


    public function receive( int $i_uBufferSize = 65_536 ) : ?string;


    public function send( string $i_stData ) : void;


}