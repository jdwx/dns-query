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
 * @since     File available since Release 1.2.0
 *
 */


/**
 * URI Resource Record - http://tools.ietf.org/html/draft-faltstrom-uri-06
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |          Priority             |          Weight               |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                             Target                            /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class URI extends RR {


    /** @var int Priority of this target host */
    public int $priority;

    /** @var int Relative weight for entries with the same priority */
    public int $weight;

    /** @var string FQDN of the target host */
    public string $target;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->priority = (int) $i_rData[ 0 ];
        $this->weight = (int) $i_rData[ 1 ];
        $this->target = trim( strtolower( trim( $i_rData[ 2 ] ) ), '"' );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->target ) > 0 ) {

            $data = pack( 'nna*', $this->priority, $this->weight, $this->target );

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the priority and weight.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'npriority/nweight/a*target', $this->rdata );

            $this->priority = $parse[ 'priority' ];
            $this->weight = $parse[ 'weight' ];
            $this->target = $parse[ 'target' ];

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        # Presentation format has double quotes (") around the target.
        return $this->priority . ' ' . $this->weight . ' "' . $this->target . '"';
    }


}
