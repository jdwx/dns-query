<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\RR\RR;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 1.2.0
 *
 */


/**
 * TALINK Resource Record - DNSSEC Trust Anchor
 *
 * http://tools.ietf.org/id/draft-ietf-dnsop-dnssec-trust-history-00.txt
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   PREVIOUS                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                     NEXT                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class TALINK extends RR {


    /** @var string Previous domain name */
    public string $previous;

    /** @var string Next domain name */
    public string $next;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->previous = $this->cleanString( $i_rData[ 0 ] );
        $this->next = $this->cleanString( $i_rData[ 1 ] );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( ( strlen( $this->previous ) > 0 ) || ( strlen( $this->next ) > 0 ) ) {

            $data = chr( strlen( $this->previous ) ) . $this->previous .
                chr( strlen( $this->next ) ) . $this->next;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $offset = $i_packet->offset;

            $this->previous = $i_packet->labelEx( $offset );
            $this->next = $i_packet->labelEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->cleanString( $this->previous ) . '. ' .
            $this->cleanString( $this->next ) . '.';
    }


}
