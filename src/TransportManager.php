<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use Countable;
use JDWX\DNSQuery\Network\ITransport;
use JDWX\DNSQuery\Network\Socket;
use JDWX\DNSQuery\Network\TCPTransport;
use JDWX\DNSQuery\Network\UDPTransport;


/**
 * Class TransportManager
 *
 * Tracks a set of network transports used for DNS queries to allow them
 * to be reused.
 *
 * Calling TransportManager::acquire() will return a suitable instance for the
 * specified type.  One will be returned from the internal pool if available.
 * If not, a new transport will be created.
 *
 * When finished, pass that transport back to the manager via
 * TransportManager::release().  This will return it to the internal pool for
 * reuse.  In the event of a problem or network issue, explicitly unset the
 * transport or just allow it to fall out of scope.
 *
 * @package JDWX\DNSQuery
 */


class TransportManager implements Countable {


    /** @var null|string The local address to bind to or null for default. */
    protected ?string $localAddress;

    /** @var null|int The local port to bind to or null for default. */
    protected ?int $localPort;

    /** @var int The timeout for transports under management. */
    protected int $timeout;

    /** @var array<string, ITransport> The cache of already-created idle transports. */
    protected array $transports = [];


    public function __construct( ?string $i_localAddress, ?int $i_localPort, int $i_timeout ) {
        $this->localAddress = $i_localAddress;
        $this->localPort = $i_localPort;
        $this->timeout = $i_timeout;
    }


    /**
     * @throws Exception
     */
    public function acquire( int $i_type, string $i_nameserver, int $i_port ) : ITransport {
        $key = $i_type . ':' . $i_nameserver . ':' . $i_port;
        if ( array_key_exists( $key, $this->transports ) ) {
            $transport = $this->transports[ $key ];
            unset( $this->transports[ $key ] );
            return $transport;
        }
        return match ( $i_type ) {
            Socket::SOCK_DGRAM => new UDPTransport( $i_nameserver, $i_port, $this->localAddress,
                                                    $this->localPort, $this->timeout ),
            Socket::SOCK_STREAM => new TCPTransport( $i_nameserver, $i_port, $this->localAddress,
                                                     $this->localPort, $this->timeout ),
            default => throw new Exception( "Unknown socket type: $i_type" ),
        };
    }


    public function count() : int {
        return count( $this->transports );
    }


    public function release( ITransport $i_transport ) : void {
        $key = self::hashTransport( $i_transport );
        if ( array_key_exists( $key, $this->transports ) ) {
            unset( $this->transports[ $key ] );
        }
        $this->transports[ $key ] = $i_transport;
    }


    public static function hashTransport( ITransport $i_transport ) : string {
        return $i_transport->getType() . ":" . $i_transport->getNameServer() . ":" . $i_transport->getPort();
    }


}

