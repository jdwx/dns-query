<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\ConnectionException;
use JDWX\Socket\Socket;


abstract class AbstractSocketTransport implements TransportInterface {


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


    public function getSocket() : Socket {
        return $this->socket;
    }


    public function send( string|WriteBufferInterface $i_data ) : void {
        try {
            $u = $this->socket->sendTimed( $i_data, null, $this->uTimeoutSeconds, $this->uTimeoutMicroseconds );
        } catch ( \Throwable $e ) {
            throw new ConnectionException( "send failed: {$e->getMessage()}", 0, $e );
        }
        if ( 0 === $u ) {
            throw new ConnectionException( 'send timed out' );
        }
    }


    public function setTimeout( int $i_uTimeoutSeconds, int $i_uTimeoutMicroseconds ) : void {
        $this->uTimeoutSeconds = $i_uTimeoutSeconds;
        $this->uTimeoutMicroseconds = $i_uTimeoutMicroseconds;
    }


    protected function read( int $i_uBufferSize ) : ?string {
        try {
            if ( ! $this->socket->selectForRead( $this->uTimeoutSeconds, $this->uTimeoutMicroseconds ) ) {
                return null;
            }
            return $this->socket->read( $i_uBufferSize );
        } catch ( \Throwable $e ) {
            throw new ConnectionException( "read failed: {$e->getMessage()}", 0, $e );
        }
    }


    protected function readTimed( int $i_uExactLength ) : string {
        try {
            return $this->socket->readTimed( $i_uExactLength, $this->uTimeoutSeconds, $this->uTimeoutMicroseconds );
        } catch ( \Throwable $e ) {
            throw new ConnectionException( "readTimed failed: {$e->getMessage()}", 0, $e );
        }
    }


    protected function sendVector( string ...$i_rVector ) : void {
        $r = [ 'iov' => $i_rVector ];
        try {
            $this->socket->sendMsg( $r );
        } catch ( \Throwable $e ) {
            throw new ConnectionException( "sendVector failed: {$e->getMessage()}", 0, $e );
        }
    }


}
