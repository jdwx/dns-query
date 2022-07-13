<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Network;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\RequestPacket;


abstract class IPTransport implements ITransport {


    /** @const The default local host to use
     * This needs to be blank because you can't set 0.0.0.0 and then use an IPv6 nameserver.
     */
    public const DEFAULT_LOCAL_HOST = '';

    /** @const The default local port to use (0 = assigned by OS) */
    public const DEFAULT_LOCAL_PORT = 0;

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
     * @param int         $i_type       The type of socket to use (Socket::SOCK_DGRAM or Socket::SOCK_STREAM)
     * @param string      $i_nameserver The nameserver to use as an IPv4 or IPv6 address.
     * @param int         $i_port       The port to use (53 is default).
     * @param null|string $i_localHost  The local host to use (or null for default).
     * @param null|int    $i_localPort  The local port to use (or null for default).
     * @param int         $i_timeout    The timeout in seconds to use for the socket.
     * @param int         $i_maxSize    The maximum size of an incoming packet.
     * @throws Exception
     */
    public function __construct( int  $i_type, string $i_nameserver, int $i_port = 53, ?string $i_localHost = null,
                                 ?int $i_localPort = null, int $i_timeout = 5,
                                 int  $i_maxSize = Lookups::DNS_MAX_UDP_SIZE ) {
        $this->nameServer = $i_nameserver;
        $this->port = $i_port;
        $this->type = $i_type;
        $this->maxSize = $i_maxSize;
        $this->socket = new Socket( $this->type, $i_nameserver, $i_port, $i_timeout );
        if ( is_string( $i_localHost ) || is_int( $i_localPort ) ) {
            $this->socket->bindAddress( $i_localHost ?? self::DEFAULT_LOCAL_HOST, $i_localPort ?? self::DEFAULT_LOCAL_PORT );
        }
        if ( false === $this->socket->open() ) {
            $this->generateError();
        }
    }


    /**
     * cleans up a failed socket and throws the given exception
     *
     * @throws Exception
     * @access private
     *
     */
    private function generateError() : void {
        throw new Exception( $this->socket->lastError, Lookups::E_NS_SOCKET_FAILED );
    }


    /**
     * Return the nameserver associated with this transport.
     * @return string The nameserver IPv4 or IPv6 address.
     */
    public function getNameServer() : string {
        return $this->nameServer;
    }


    /**
     * Return the port associated with this transport.
     * @return int The port number.
     */
    public function getPort() : int {
        return $this->port;
    }


    /** Return the type of socket used by this transport.
     *
     * @return int Socket::SOCK_STREAM or Socket::SOCK_DGRAM.
     */
    public function getType() : int {
        return $this->type;
    }


    /**
     * reads a response from a DNS server
     *
     * @param int       &$o_size the size of the DNS packet read is passed back
     * @param ?int      $i_maxSize the max data size to be read (if null, use max size from construction).
     *
     * @return string   returns the data on success and false on error
     * @access public
     *
     * @throws Exception
     */
    public function read( int & $o_size, ?int $i_maxSize = null ) : string {
        $result = $this->socket->read( $o_size, $i_maxSize ?? $this->maxSize );
        if ( is_string( $result ) ) {
            return $result;
        }
        $this->generateError();
    }


    /**
     * @throws Exception
     */
    public function sendData( string $i_data ) : void {
        if ( $this->socket->write( $i_data ) === false ) {
            $this->generateError();
        }
    }


    /**
     * @throws Exception
     */
    public function sendRequest( RequestPacket $i_request ) : void {
        $this->sendData( $i_request->get() );
    }


}

