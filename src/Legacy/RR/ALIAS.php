<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\RR\RR;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2022, Benjamin Schwarze <chaosben@gmail.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @author    Benjamin Schwarze <chaosben@gmail.com>
 * @copyright 2022 Benjamin Schwarze <chaosben@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 *
 */


/**
 * ALIAS Resource Record - as implemented by PowerDNS according to draft-ietf-dnsop-aname
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                     ALIAS                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class ALIAS extends RR {


    /** @var string Alias */
    public string $alias;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->alias = $this->cleanString( array_shift( $i_rData ) );
        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->alias ) > 0 ) {
            return $i_packet->compress( $this->alias, $i_packet->offset );
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {
            $offset = $i_packet->offset;
            $this->alias = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->cleanString( $this->alias ) . '.';
    }


}
