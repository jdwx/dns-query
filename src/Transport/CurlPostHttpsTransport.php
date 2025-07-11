<?php /** @noinspection PhpComposerExtensionStubsInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\TransportException;


class CurlPostHttpsTransport extends AbstractHttpsTransport {


    use CurlTransportTrait;


    public function send( string|WriteBufferInterface $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }

        $ch = $this->curlHandle( $this->stURL );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $i_data );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Accept: application/dns-message',
            'Content-Type: application/dns-message',
        ] );

        $response = curl_exec( $ch );
        if ( false === $response ) {
            throw new TransportException( 'Curl error: ' . curl_error( $ch ) );
        }

        curl_close( $ch );

        $this->rBuffer[] = $response;
    }


}
