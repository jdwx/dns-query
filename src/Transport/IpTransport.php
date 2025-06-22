<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Lookups;
use JDWX\Strict\OK;
use Socket;


class IpTransport extends AbstractTransport {


    /** @var int The maximum size of an incoming packet. */
    protected int $maxSize;

    /** @var string The nameserver to use as an IPv4 or IPv6 address. */
    protected string $nameServer;

    /** @var int The port to use. */
    protected int $port;

    /** @var Socket The socket associated with this connection. */
    protected Socket $socket;

    /** @var int The type of socket (datagram or stream) */
    protected int $type;


    /**
     * Create an IP transport for DNS packets.
     *
     * @param int $i_type The type of socket to use (Socket::SOCK_DGRAM or Socket::SOCK_STREAM)
     * @param string $i_nameserver The nameserver to use as an IPv4 or IPv6 address.
     * @param int $i_port The port to use (53 is default).
     * @param null|string $i_localAddress The local address to use (or null for default).
     * @param null|int $i_localPort The local port to use (or null for default).
     * @param int $i_maxSize The maximum size of an incoming packet.
     */
    public function __construct( int  $i_type, string $i_nameserver, int $i_port = 53, ?string $i_localAddress = null,
                                 ?int $i_localPort = null, int $i_maxSize = Lookups::DNS_MAX_UDP_SIZE ) {
        parent::__construct( new IpTransportCodec() );
        $this->nameServer = $i_nameserver;
        $this->port = $i_port;
        $this->type = $i_type;
        $this->maxSize = $i_maxSize;
        $uFamily = str_contains( $i_nameserver, ':' ) ? AF_INET6 : AF_INET;
        $this->socket = socket_create( $this->type, $uFamily, 0 );
        if ( is_string( $i_localAddress ) || is_int( $i_localPort ) ) {
            OK::socket_bind( $this->socket, $i_localAddress ?? ( $uFamily === AF_INET ? '0.0.0.0' : '::' ), $i_localPort ?? 0 );
        }
    }


    protected function receivePacket() : string {
        return 'packet';
    }


    protected function sendPacket( string $packet ) : void {
        # Sure, I definitely sent it.
    }


}
