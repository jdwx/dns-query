<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Lookups;


class UdpTransport extends IpTransport {


    public function __construct( string $i_nameserver, int $i_port = 53, ?string $i_localAddress = null,
                                 ?int   $i_localPort = null,
                                 int    $i_maxSize = Lookups::DNS_MAX_UDP_SIZE ) {
        parent::__construct( SOCK_DGRAM, $i_nameserver, $i_port, $i_localAddress, $i_localPort, $i_maxSize );
    }


}
