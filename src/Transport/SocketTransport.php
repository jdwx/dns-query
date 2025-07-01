<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Exceptions\TransportException;
use JDWX\Socket\Socket;


class SocketTransport implements TransportInterface {


    private const int DEFAULT_TIMEOUT_SECONDS      = 5;

    private const int DEFAULT_TIMEOUT_MICROSECONDS = 0;


    private int $uTimeoutSeconds;

    private int $uTimeoutMicroseconds;


    public function __construct( private readonly Socket $socket,
                                 ?int                    $uTimeoutSeconds = null,
                                 ?int                    $uTimeoutMicroseconds = null ) {
        $this->uTimeoutSeconds = $uTimeoutSeconds ?? self::DEFAULT_TIMEOUT_SECONDS;
        $this->uTimeoutMicroseconds = $uTimeoutMicroseconds ?? self::DEFAULT_TIMEOUT_MICROSECONDS;
    }


    public static function tcp( string $i_stHost, int $i_uPort = 53, ?int $i_nuTimeoutSeconds = null,
                                ?int   $i_nuTimeoutMicroseconds = null, ?string $i_nstLocalAddress = null,
                                ?int   $i_nuLocalPort = null ) : self {
        $sock = Socket::createByAddress( $i_stHost, SOCK_STREAM );
        if ( is_string( $i_nstLocalAddress ) ) {
            $sock->bind( $i_nstLocalAddress, $i_nuLocalPort ?? 0 );
        }
        $sock->connect( $i_stHost, $i_uPort );
        return new self( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public static function udp( string $i_stHost, int $i_uPort = 53, ?int $i_nuTimeoutSeconds = null,
                                ?int   $i_nuTimeoutMicroseconds = null, ?string $i_nstLocalAddress = null,
                                ?int   $i_nuLocalPort = null ) : self {
        $sock = Socket::createByAddress( $i_stHost, SOCK_DGRAM );
        if ( is_string( $i_nstLocalAddress ) ) {
            $sock->bind( $i_nstLocalAddress, $i_nuLocalPort ?? 0 );
        }
        $sock->connect( $i_stHost, $i_uPort );
        return new self( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public static function unix( string $i_stPath, int $i_uType, ?int $i_nuTimeoutSeconds = null,
                                 ?int   $i_nuTimeoutMicroseconds = null ) : self {
        $sock = Socket::create( AF_UNIX, $i_uType );
        $sock->connect( $i_stPath );
        return new self( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public function getSocket() : Socket {
        return $this->socket;
    }


    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        if ( ! $this->socket->selectForRead( $this->uTimeoutSeconds, $this->uTimeoutMicroseconds ) ) {
            return null;
        }
        return $this->socket->read( $i_uBufferSize );
    }


    public function send( string $i_stData ) : void {
        if ( ! $this->socket->selectForWrite( $this->uTimeoutSeconds, $this->uTimeoutMicroseconds ) ) {
            throw new TransportException( 'Socket send timed out' );
        }
        $this->socket->send( $i_stData );
    }


    public function setTimeout( int $i_uTimeoutSeconds, int $i_uTimeoutMicroseconds ) : void {
        $this->uTimeoutSeconds = $i_uTimeoutSeconds;
        $this->uTimeoutMicroseconds = $i_uTimeoutMicroseconds;
    }


}
