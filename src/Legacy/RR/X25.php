<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;


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
 * X25 Resource Record - RFC1183 section 3.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                  PSDN-address                 /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class X25 extends RR {


    /** @var string PSDN address */
    public string $psdnAddress;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $data = $this->buildString( $i_rData );
        if ( count( $data ) == 1 ) {

            $this->psdnAddress = $data[ 0 ];
            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->psdnAddress ) > 0 ) {

            $data = chr( strlen( $this->psdnAddress ) ) . $this->psdnAddress;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $this->psdnAddress = $i_packet->labelEx( $i_packet->offset );
            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return static::formatString( $this->psdnAddress );
    }


}
