<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;


class AbstractGetHttpsTransport extends AbstractHttpsTransport {


    public function send( WriteBufferInterface|string $i_data ) : void {
        // TODO: Implement send() method.
    }


    protected function base64urlEncode( string $i_data ) : string {
        return rtrim( strtr( base64_encode( $i_data ), '+/', '-_' ), '=' );
    }


    protected function encodedUrl( string $i_data ) : string {
        return $this->stURL . '?dns=' . $this->base64urlEncode( $i_data );
    }


}
