<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\TransportException;


class SimplePostHttpsTransport extends AbstractHttpsTransport {


    public function send( string|WriteBufferInterface $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }
        $options = [
            'http' => [
                'header' => "Content-type: application/dns-message\r\n",
                'method' => 'POST',
                'content' => $i_data,
                'timeout' => $this->fTimeoutSeconds,
            ],
        ];
        $context = stream_context_create( $options );
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $bst = @file_get_contents( $this->stURL, false, $context );
        if ( ! is_string( $bst ) ) {
            throw new TransportException( "Failed to send data via HTTPS POST transport to {$this->stURL}." );
        }
        $this->rBuffer[] = $bst;
    }


}
