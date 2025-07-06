<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\Socket\Socket;


final class TransportFactory {


    public static function tcp( string $i_stHost, int $i_uPort = 53, ?int $i_nuTimeoutSeconds = null,
                                ?int   $i_nuTimeoutMicroseconds = null, ?string $i_nstLocalAddress = null,
                                ?int   $i_nuLocalPort = null ) : TransportInterface {
        $sock = Socket::createByAddress( $i_stHost, SOCK_STREAM );
        if ( is_string( $i_nstLocalAddress ) ) {
            $sock->bind( $i_nstLocalAddress, $i_nuLocalPort ?? 0 );
        }
        $sock->connect( $i_stHost, $i_uPort );
        $transport = new StreamSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        return $transport;
    }


    public static function udp( string $i_stHost, int $i_uPort = 53, ?int $i_nuTimeoutSeconds = null,
                                ?int   $i_nuTimeoutMicroseconds = null, ?string $i_nstLocalAddress = null,
                                ?int   $i_nuLocalPort = null ) : TransportInterface {
        $sock = Socket::createByAddress( $i_stHost, SOCK_DGRAM );
        if ( is_string( $i_nstLocalAddress ) ) {
            $sock->bind( $i_nstLocalAddress, $i_nuLocalPort ?? 0 );
        }
        $sock->connect( $i_stHost, $i_uPort );
        return new DatagramSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public static function unix( string $i_stPath, int $i_uType, ?int $i_nuTimeoutSeconds = null,
                                 ?int   $i_nuTimeoutMicroseconds = null ) : TransportInterface {
        $sock = Socket::create( AF_UNIX, $i_uType );
        $sock->connect( $i_stPath );
        $transport = ( $i_uType === SOCK_STREAM )
            ? new StreamSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds )
            : new DatagramSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        return $transport;
    }


}
