<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Packet\Packet;
use JetBrains\PhpStorm\ArrayShape;


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
 * SRV Resource Record - RFC2782
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   PRIORITY                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    WEIGHT                     |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                     PORT                      |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    TARGET                     /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class SRV extends RR {


    /** @var int Priority of this target host. */
    public int $priority;

    /** @var int Relative weight for entries with the same priority */
    public int $weight;

    /** @var int Port on the target host for the service. */
    public int $port;

    /** @var string Domain name of the target host */
    public string $target;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     * @return array<string, int|string>
     */
    #[ArrayShape( [ 'pri' => 'int', 'weight' => 'int', 'target' => 'string', 'port' => 'int' ] )]
    public function getPHPRData() : array {
        return [
            'pri' => $this->priority,
            'weight' => $this->weight,
            'target' => $this->target,
            'port' => $this->port,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->priority = (int) $i_rData[ 0 ];
        $this->weight = (int) $i_rData[ 1 ];
        $this->port = (int) $i_rData[ 2 ];

        $this->target = $this->cleanString( $i_rData[ 3 ] );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->target ) > 0 ) {

            $data = pack( 'nnn', $this->priority, $this->weight, $this->port );
            $i_packet->offset += 6;

            $data .= $i_packet->compress( $this->target, $i_packet->offset );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the priority, weight and port.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'npriority/nweight/nport', $this->rdata );

            $this->priority = $parse[ 'priority' ];
            $this->weight = $parse[ 'weight' ];
            $this->port = $parse[ 'port' ];

            $offset = $i_packet->offset + 6;
            $this->target = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->priority . ' ' . $this->weight . ' ' .
            $this->port . ' ' . $this->cleanString( $this->target ) . '.';
    }


}
