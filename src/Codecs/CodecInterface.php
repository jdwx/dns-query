<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Message\MessageInterface;


interface CodecInterface {


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface;


    public function encodeMessage( MessageInterface $i_msg ) : WriteBufferInterface;


}