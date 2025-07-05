<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Buffer\BufferInterface;
use JDWX\DNSQuery\Message\MessageInterface;


class SerializeCodec implements CodecInterface {


    public function decode( BufferInterface $i_buffer ) : ?MessageInterface {
        if ( ! $i_buffer->readyCheck() ) {
            return null;
        }

        $uLength = $i_buffer->consumeUINT32();
        $st = $i_buffer->consume( $uLength );
        return unserialize( $st, [ 'allowed_classes' => true ] );
    }


    public function encode( MessageInterface $i_msg ) : string {
        $st = serialize( $i_msg );
        return Binary::packUINT32( strlen( $st ) ) . $st;
    }


}
