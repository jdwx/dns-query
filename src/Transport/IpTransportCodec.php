<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Message\Message;


class IpTransportCodec implements TransportCodecInterface {


    public function decode( string $i_packet ) : Message {
        return new Message( 0 );
    }


    public function encode( Message $i_message ) : string {
        return 'request';
    }


}
