<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Message\Message;


class SerializeCodec implements TransportCodecInterface {


    public function decode( string $i_packet ) : Message {
        return unserialize( $i_packet, [ 'allowed_classes' => true ] );
    }


    public function encode( Message $i_message ) : string {
        return serialize( $i_message );
    }


}
