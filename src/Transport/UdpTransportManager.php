<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


class UdpTransportManager implements TransportManagerInterface {


    public function getTransport( string $i_stServerAddress ) : TransportInterface {
        return new UdpTransport( $i_stServerAddress );
    }


}
