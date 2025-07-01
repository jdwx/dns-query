<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\Legacy\Lookups;
use JDWX\Socket\Socket;


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
        parent::__construct( new RFC1035Codec() );
        $this->nameServer = $i_nameserver;
        $this->port = $i_port;
        $this->type = $i_type;
        $this->maxSize = $i_maxSize;
        $uFamily = str_contains( $i_nameserver, ':' ) ? AF_INET6 : AF_INET;
        $this->socket = Socket::create( $uFamily, $this->type );
        if ( is_string( $i_localAddress ) || is_int( $i_localPort ) ) {
            $stAddress = $i_localAddress ?? ( $uFamily === AF_INET ? '0.0.0.0' : '::' );
            $uPort = $i_localPort ?? 0;
            $this->socket->bind( $stAddress, $uPort );
        }
    }


    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        $data = '';
        if ( ! $this->socket->selectForRead() ) {
            return null;
        }
        $this->socket->recvFrom( $data, $i_uBufferSize, 0, $this->nameServer, $this->port );
        return $data;
    }


    public function send( string $i_stData ) : void {
        $u = $this->socket->sendTo( $i_stData, strlen( $i_stData ), $this->nameServer, $this->port );
        if ( $u !== strlen( $i_stData ) ) {
            throw new \RuntimeException( 'Failed to send the entire packet.' );
        }
    }


}
