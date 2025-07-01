<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Transport\BufferInterface;


class SerializeCodec implements CodecInterface {


    public function decode( BufferInterface $i_buffer ) : Message {
        $uLength = $i_buffer->consumeUINT32();
        $st = $i_buffer->consume( $uLength );
        return unserialize( $st, [ 'allowed_classes' => true ] );
    }


    public function encode( Message $i_msg ) : string {
        $st = serialize( $i_msg );
        return Binary::packUINT32( strlen( $st ) ) . $st;
    }


}
