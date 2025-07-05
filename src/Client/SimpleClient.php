<?php /** @noinspection PhpClassCanBeReadonlyInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Buffer\BufferInterface;
use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Transport\SocketTransport;
use JDWX\DNSQuery\Transport\TransportBuffer;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Class SimpleClient
 *
 * A simple client implementation that sends all requests to and receives all
 * responses from one server.
 *
 * @package JDWX\DNSQuery\Client
 */
class SimpleClient extends AbstractTimedClient {


    private BufferInterface $buffer;


    public function __construct( private readonly TransportInterface $transport,
                                 private readonly CodecInterface     $codec,
                                 ?int                                $i_nuDefaultTimeoutSeconds = null,
                                 ?int                                $i_nuDefaultTimeoutMicroSeconds = null ) {
        $this->buffer = new TransportBuffer( $this->transport );
        parent::__construct( $i_nuDefaultTimeoutSeconds, $i_nuDefaultTimeoutMicroSeconds );
    }


    public static function createUdp( string $i_stNameServer, int $i_uPort = 53, ?string $i_nstLocalAddress = null,
                                      ?int   $i_nuLocalPort = null ) : self {
        $xpt = SocketTransport::udp( $i_stNameServer, $i_uPort, i_nstLocalAddress: $i_nstLocalAddress,
            i_nuLocalPort: $i_nuLocalPort );
        $codec = new RFC1035Codec();
        return new self( $xpt, $codec );
    }


    public function sendRequest( MessageInterface $i_request ) : void {
        $this->transport->send( $this->codec->encode( $i_request ) );
    }


    protected function receiveAnyResponse() : ?MessageInterface {
        return $this->codec->decode( $this->buffer );
    }


}
