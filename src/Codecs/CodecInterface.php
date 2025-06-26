<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Message\Message;


/**
 * Encoders are used to convert between Request/Response objects and
 * data suitable to be sent over the wire.
 */
interface CodecInterface {


    public function decode( string $i_packet ) : Message;


    public function encode( Message $i_msg ) : string;


}