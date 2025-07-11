<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;


class DatagramSocketTransport extends AbstractSocketTransport {


    /**
     * @param int $i_uBufferSize
     * @return string|null
     *
     * If this method returns null, you should assume that the socket is
     * closed or otherwise unusable.
     */
    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        return $this->read( $i_uBufferSize );
    }


    public function send( string|WriteBufferInterface $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }
        parent::send( $i_data );
    }


}
