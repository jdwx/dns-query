<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\TransportException;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface as HttpRequestFactoryInterface;


class PsrGetHttpsTransport extends AbstractGetHttpsTransport {


    public function __construct( private readonly HttpClientInterface         $client,
                                 private readonly HttpRequestFactoryInterface $requestFactory,
                                 string                                       $i_stURL,
                                 ?int                                         $i_nuTimeoutSeconds = null,
                                 ?int                                         $i_nuTimeoutMicroseconds = null ) {
        parent::__construct( $i_stURL, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public function send( string|WriteBufferInterface $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }

        $request = $this->requestFactory->createRequest( 'GET', $this->encodedUrl( $i_data ) );
        $response = $this->client->sendRequest( $request );

        if ( $response->getStatusCode() !== 200 ) {
            throw new TransportException( 'Failed to send request, status code: ' . $response->getStatusCode() );
        }

        $this->rBuffer[] = (string) $response->getBody();
    }


}
