<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Packet\Packet;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 0.6.0
 *
 */


/**
 * RP Resource Record - RFC1183 section 2.2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   mboxDName                   /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   txtDName                    /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class RP extends RR {


    /** @var string Mailbox for the responsible person */
    public string $mboxDName;

    /** @var string Domain name for which TXT RRs exists */
    public string $txtDName;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->mboxDName = $this->cleanString( $i_rData[ 0 ] );
        $this->txtDName = $this->cleanString( $i_rData[ 1 ] );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->mboxDName ) > 0 ) {

            return $i_packet->compress( $this->mboxDName, $i_packet->offset ) .
                $i_packet->compress( $this->txtDName, $i_packet->offset );
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $offset = $i_packet->offset;

            $this->mboxDName = $i_packet->expandEx( $offset, true );
            $this->txtDName = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->cleanString( $this->mboxDName ) . '. ' . $this->cleanString( $this->txtDName ) . '.';
    }


}
