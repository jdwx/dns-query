<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\RR\RR;
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
 * HINFO Resource Record - RFC1035 section 3.3.2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      CPU                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                       OS                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class HINFO extends RR {


    /** @var string Computer Information */
    public string $cpu;

    /** @var string Operating System */
    public string $os;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[ArrayShape( [ 'cpu' => 'string', 'os' => 'string' ] )] public function getPHPRData() : array {
        return [
            'cpu' => $this->cpu,
            'os' => $this->os,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $data = $this->buildString( $i_rData );
        if ( count( $data ) == 2 ) {

            $this->cpu = trim( $data[ 0 ], '"' );
            $this->os = trim( $data[ 1 ], '"' );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->cpu ) > 0 ) {

            $data = pack( 'Ca*Ca*', strlen( $this->cpu ), $this->cpu, strlen( $this->os ), $this->os );

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc
     * @throws Exception
     */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $offset = $i_packet->offset;

            $this->cpu = $i_packet->labelEx( $offset );
            $this->os = $i_packet->labelEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return static::formatString( $this->cpu ) . ' ' . self::formatString( $this->os );
    }


}
