<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Message\MessageInterface;


interface DecoderInterface {


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface;


}