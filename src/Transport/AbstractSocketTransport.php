<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\TransportException;
use JDWX\Socket\Exceptions\WriteException;
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


    public function send( string|WriteBufferInterface $i_stData ) : void {
        $u = $this->socket->sendTimed( $i_stData, null, $this->uTimeoutSeconds, $this->uTimeoutMicroseconds );
        if ( 0 === $u ) {
            throw new TransportException( 'Failed to send data via socket.' );
        }
    }


    public function setTimeout( int $i_uTimeoutSeconds, int $i_uTimeoutMicroseconds ) : void {
        $this->uTimeoutSeconds = $i_uTimeoutSeconds;
        $this->uTimeoutMicroseconds = $i_uTimeoutMicroseconds;
    }


    protected function read( int $i_uBufferSize ) : ?string {
        if ( ! $this->socket->selectForRead( $this->uTimeoutSeconds, $this->uTimeoutMicroseconds ) ) {
            return null;
        }
        return $this->socket->read( $i_uBufferSize );
    }


    protected function readTimed( int $i_uExactLength ) : string {
        return $this->socket->readTimed( $i_uExactLength, $this->uTimeoutSeconds, $this->uTimeoutMicroseconds );
    }


    protected function sendVector( string ...$i_rVector ) : void {
        $r = [ 'iov' => $i_rVector ];
        try {
            $this->socket->sendMsg( $r );
        } catch ( WriteException $e ) {
            throw new TransportException( 'Failed to send data via socket.', 0, $e );
        }
    }


}
