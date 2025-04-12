<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\BaseQuery;
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
 * A Resource Record - RFC1035 section 3.4.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    ADDRESS                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class A extends RR {


    /*
     * The IPv4 address in quad-dotted notation
     */
    public string $address;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[ArrayShape( [ 'ip' => 'string' ] )] public function getPHPRData() : array {
        return [
            'ip' => $this->address,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $value = array_shift( $i_rData );

        if ( BaseQuery::isIPv4( $value ) ) {

            $this->address = $value;
            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        $i_packet->offset += 4;
        return inet_pton( $this->address );
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $this->address = inet_ntop( $this->rdata );
            /**
             * PhpStan doesn't know that inet_ntop() will return false if the
             * address is invalid.
             * @phpstan-ignore notIdentical.alwaysTrue
             */
            if ( $this->address !== false ) {

                return true;
            }
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->address;
    }


}
