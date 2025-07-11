<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\TransportException;


class SimpleGetHttpsTransport extends AbstractGetHttpsTransport {


    public function send( string|WriteBufferInterface $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $options = [
            'http' => [
                'method' => 'GET',
                'timeout' => $this->fTimeoutSeconds,
            ],
        ];
        $context = stream_context_create( $options );
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $bst = @file_get_contents( $this->encodedUrl( $i_data ), false, $context );
        if ( ! is_string( $bst ) ) {
            throw new TransportException( "Failed to fetch data from {$this->stURL}" );
        }
        $this->rBuffer[] = $bst;
    }


}
