<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;


interface TransportInterface {


    public function receive( int $i_uBufferSize = 65_536 ) : ?string;


    public function send( string|WriteBufferInterface $i_data ) : void;


}