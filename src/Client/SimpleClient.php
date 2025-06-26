<?php /** @noinspection PhpClassCanBeReadonlyInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Transport\TransportInterface;
use JDWX\DNSQuery\Transport\UdpTransport;


/**
 * Class SimpleClient
 *
 * A simple client implementation that sends all requests to and receives all
 * responses from one server.
 *
 * @package JDWX\DNSQuery\Client
 */
class SimpleClient extends AbstractTimedClient {


    public function __construct( private readonly TransportInterface $transport,
                                 ?int                                $i_nuDefaultTimeoutSeconds = null,
                                 ?int                                $i_nuDefaultTimeoutMicroSeconds = null ) {
        parent::__construct( $i_nuDefaultTimeoutSeconds, $i_nuDefaultTimeoutMicroSeconds );
    }


    public static function createUdp( string $i_stNameServer, int $i_uPort = 53, string $i_stLocalAddress = '',
                                      ?int   $i_uLocalPort = null ) : self {
        $xpt = new UdpTransport( $i_stNameServer, $i_uPort, $i_stLocalAddress, $i_uLocalPort );
        return new self( $xpt );
    }


    public function sendRequest( Message $i_request ) : void {
        $this->transport->sendRequest( $i_request );
    }


    protected function receiveAnyResponse( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?Message {
        return $this->transport->receiveResponse( $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds );
    }


}
