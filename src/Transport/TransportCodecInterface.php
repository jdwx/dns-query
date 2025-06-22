<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Message\Message;


/**
 * Encoders are used to convert between Request/Response objects and
 * data suitable to be sent over the wire.
 */
interface TransportCodecInterface {


    public function decode( string $i_packet ) : Message;


    public function encode( Message $i_message ) : string;


}