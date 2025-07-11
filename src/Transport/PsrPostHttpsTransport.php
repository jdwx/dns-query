<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface as HttpRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface as HttpStreamFactoryInterface;


class PsrPostHttpsTransport extends AbstractHttpsTransport {


    public function __construct( private readonly HttpClientInterface         $client,
                                 private readonly HttpRequestFactoryInterface $requestFactory,
                                 private readonly HttpStreamFactoryInterface  $streamFactory,
                                 string                                       $i_stURL,
                                 ?int                                         $i_nuTimeoutSeconds = null,
                                 ?int                                         $i_nuTimeoutMicroseconds = null ) {
        parent::__construct( $i_stURL, $i_nuTimeoutSeconds, $i_nuTimeoutMicroseconds );
    }


    public function send( WriteBufferInterface|string $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }

        $request = $this->requestFactory->createRequest( 'POST', $this->stURL )
            ->withHeader( 'Content-Type', 'application/dns-message' )
            ->withBody( $this->streamFactory->createStream( $i_data ) );

        $response = $this->client->sendRequest( $request );

        if ( $response->getStatusCode() !== 200 ) {
            throw new \RuntimeException( 'Failed to send request, status code: ' . $response->getStatusCode() );
        }

        $this->rBuffer[] = (string) $response->getBody();
    }


}
