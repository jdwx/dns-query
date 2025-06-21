<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Packet\ResponsePacket;


interface ClientInterface {


    public function query( string $i_stDomain, int|string|RecordType $i_rrType = 'A',
                           string $i_stClass = 'IN' ) : ResponsePacket;


}

