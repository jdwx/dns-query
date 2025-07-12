<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Exceptions\ProtocolException;
use JDWX\DNSQuery\Exceptions\SetupException;
use JDWX\Socket\Socket;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface as HttpRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface as HttpStreamFactoryInterface;


final class TransportFactory {


    private static ?HttpClientInterface $httpClient = null;

    private static ?HttpRequestFactoryInterface $requestFactory = null;

    private static ?HttpStreamFactoryInterface $streamFactory = null;


    public static function httpsGet( string $i_stHost, ?int $i_nuTimeoutSeconds = null,
                                     ?int   $i_nuTimeoutMicroseconds = null ) : TransportInterface {
        if ( self::$httpClient && self::$requestFactory ) {
            return new PsrGetHttpsTransport( self::getClient(), self::getRequestFactory(), $i_stHost,
                $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        }
        if ( extension_loaded( 'curl' ) ) {
            return new CurlGetHttpsTransport( $i_stHost, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        }
        return new SimpleGetHttpsTransport( $i_stHost, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public static function httpsPost( string $i_stHost, ?int $i_nuTimeoutSeconds = null,
                                      ?int   $i_nuTimeoutMicroseconds = null ) : TransportInterface {
        if ( self::$httpClient && self::$requestFactory ) {
            return new PsrPostHttpsTransport( self::getClient(), self::getRequestFactory(), self::getStreamFactory(),
                $i_stHost, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        }
        if ( extension_loaded( 'curl' ) ) {
            return new CurlPostHttpsTransport( $i_stHost, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        }
        return new SimplePostHttpsTransport( $i_stHost, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public static function setHttpClient( HttpClientInterface         $i_client,
                                          HttpRequestFactoryInterface $i_requestFactory,
                                          ?HttpStreamFactoryInterface $i_streamFactory = null ) : void {
        if ( ! $i_streamFactory ) {
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ( ! $i_requestFactory instanceof HttpStreamFactoryInterface ) {
                throw new SetupException(
                    'Stream factory must be provided or request factory must implement StreamFactoryInterface'
                );
            }
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $i_streamFactory = $i_requestFactory;
        }
        self::$httpClient = $i_client;
        self::$requestFactory = $i_requestFactory;
        self::$streamFactory = $i_streamFactory;
    }


    public static function tcp( string $i_stHost, ?int $i_uPort = null, ?int $i_nuTimeoutSeconds = null,
                                ?int   $i_nuTimeoutMicroseconds = null, ?string $i_nstLocalAddress = null,
                                ?int   $i_nuLocalPort = null ) : TransportInterface {
        $i_uPort ??= 53; // Default port for TCP DNS queries
        try {
            $sock = Socket::createByAddress( $i_stHost, SOCK_STREAM );
            if ( is_string( $i_nstLocalAddress ) ) {
                $sock->bind( $i_nstLocalAddress, $i_nuLocalPort ?? 0 );
            }
        } catch ( \Throwable $e ) {
            throw new SetupException( "Failed to set up TCP socket: {$e->getMessage()}", $e->getCode(), $e );
        }
        try {
            $sock->connect( $i_stHost, $i_uPort );
        } catch ( \Throwable $e ) {
            throw new ProtocolException( "Failed to connect TCP socket: {$e->getMessage()}", 0, $e );
        }
        $transport = new StreamSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        return $transport;
    }


    public static function udp( string $i_stHost, ?int $i_uPort = null, ?int $i_nuTimeoutSeconds = null,
                                ?int   $i_nuTimeoutMicroseconds = null, ?string $i_nstLocalAddress = null,
                                ?int   $i_nuLocalPort = null ) : TransportInterface {
        $i_uPort ??= 53; // Default port for UDP DNS queries
        try {
            $sock = Socket::createByAddress( $i_stHost, SOCK_DGRAM );
            if ( is_string( $i_nstLocalAddress ) ) {
                $sock->bind( $i_nstLocalAddress, $i_nuLocalPort ?? 0 );
            }
        } catch ( \Throwable $e ) {
            throw new SetupException( "Failed to set up UDP socket: {$e->getMessage()}", $e->getCode(), $e );
        }
        try {
            $sock->connect( $i_stHost, $i_uPort );
        } catch ( \Throwable $e ) {
            throw new ProtocolException( "Failed to connect UDP socket: {$e->getMessage()}", 0, $e );
        }
        return new DatagramSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public static function unix( string $i_stPath, int $i_uType, ?int $i_nuTimeoutSeconds = null,
                                 ?int   $i_nuTimeoutMicroseconds = null ) : TransportInterface {
        try {
            $sock = Socket::create( AF_UNIX, $i_uType );
        } catch ( \Throwable $e ) {
            throw new SetupException( "Failed to create Unix socket: {$e->getMessage()}", $e->getCode(), $e );
        }
        try {
            $sock->connect( $i_stPath );
        } catch ( \Throwable $e ) {
            throw new ProtocolException( "Failed to connect Unix socket: {$e->getMessage()}", 0, $e );
        }
        $transport = ( $i_uType === SOCK_STREAM )
            ? new StreamSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds )
            : new DatagramSocketTransport( $sock, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
        return $transport;
    }


    private static function getClient() : HttpClientInterface {
        if ( ! self::$httpClient ) {
            throw new SetupException( 'HTTP client is not set.' );
        }
        return self::$httpClient;
    }


    private static function getRequestFactory() : HttpRequestFactoryInterface {
        if ( ! self::$requestFactory ) {
            throw new SetupException( 'HTTP request factory is not set.' );
        }
        return self::$requestFactory;
    }


    private static function getStreamFactory() : HttpStreamFactoryInterface {
        if ( ! self::$streamFactory ) {
            throw new SetupException( 'HTTP stream factory is not set.' );
        }
        return self::$streamFactory;
    }


}
