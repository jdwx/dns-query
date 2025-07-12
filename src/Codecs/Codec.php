<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Message\MessageInterface;


readonly class Codec implements CodecInterface {


    public function __construct( private EncoderInterface $encoder,
                                 private DecoderInterface $decoder ) {}


    public static function rfc1035() : self {
        return new self( new RFC1035Encoder(), new RFC1035Decoder() );
    }


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface {
        return $this->decoder->decodeMessage( $i_buffer );
    }


    public function encodeMessage( MessageInterface $i_msg, ?WriteBufferInterface $i_buffer = null ) : WriteBufferInterface {
        $i_buffer ??= new WriteBuffer();
        $this->encoder->encodeMessage( $i_buffer, $i_msg );
        return $i_buffer;
    }


}
