<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Legacy\Lookups;


class UdpTransport extends IpTransport {


    public function __construct( string $i_stNameServerAddress, int $i_uPort = 53, ?string $i_stLocalAddress = null,
                                 ?int   $i_nuLocalPort = null, int $i_uMaxSize = Lookups::DNS_MAX_UDP_SIZE ) {
        parent::__construct( SOCK_DGRAM, $i_stNameServerAddress, $i_uPort, $i_stLocalAddress, $i_nuLocalPort, $i_uMaxSize );
    }


}
