<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Network;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\ResponsePacket;


class UDPTransport extends IPTransport {


    /**
     * Create a UDP transport for DNS packets.
     *
     * @param string      $i_nameserver The nameserver to use as an IPv4 or IPv6 address.
     * @param int         $i_port       The port to use (53 is default).
     * @param null|string $i_localHost  The local host to use (or null for default).
     * @param null|int    $i_localPort  The local port to use (or null for default).
     * @param int         $i_timeout    The timeout in seconds to use for the socket.
     * @throws Exception
     */
    public function __construct( string $i_nameserver, int $i_port = 53, ?string $i_localHost = null,
                                 ?int   $i_localPort = null, int $i_timeout = 5 ) {
        parent::__construct( Socket::SOCK_DGRAM, $i_nameserver, $i_port, $i_localHost, $i_localPort, $i_timeout );
    }


    /**
     * @throws Exception
     */
    public function receiveResponse() : ResponsePacket {
        //
        // grab the start time
        //
        $start_time = microtime( true );

        $size = 0;

        //
        // read the content, using select to wait for a response
        //
        $result = $this->read( $size, Lookups::DNS_MAX_UDP_SIZE );
        if ( $size < Lookups::DNS_HEADER_SIZE ) {
            throw new Exception("received packet is too small to be a valid DNS packet", Lookups::E_NS_SOCKET_FAILED );
        }

        //
        // create the packet object
        //
        $response = new ResponsePacket( $result, $size );

        //
        // store the query time
        //
        $response->response_time = microtime( true ) - $start_time;

        //
        // add the name server that the response came from to the response object,
        // and the socket type that was used.
        //
        $response->answer_from = $this->nameServer;
        $response->answer_socket_type = Socket::SOCK_DGRAM;
        return $response;

    }


}

