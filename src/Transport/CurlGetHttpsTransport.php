<?php /** @noinspection PhpComposerExtensionStubsInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\TransportException;


class CurlGetHttpsTransport extends AbstractGetHttpsTransport {


    use CurlTransportTrait;


    public function send( WriteBufferInterface|string $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }

        $ch = $this->curlHandle( $this->encodedUrl( $i_data ) );

        $response = curl_exec( $ch );
        if ( false === $response ) {
            throw new TransportException( 'Curl error: ' . curl_error( $ch ) );
        }

        curl_close( $ch );

        $this->rBuffer[] = $response;
    }


}
