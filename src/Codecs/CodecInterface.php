<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\BufferInterface;
use JDWX\DNSQuery\Message\MessageInterface;


/**
 * Encoders are used to convert between Request/Response objects and
 * data suitable to be sent over the wire.
 */
interface CodecInterface {


    public function decode( BufferInterface $i_buffer ) : ?MessageInterface;


    public function encode( MessageInterface $i_msg ) : string;


}