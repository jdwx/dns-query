<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\RR;


use JDWX\DNSQuery\Legacy\BaseQuery;
use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\Strict\OK;
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
 *    |                                               |
 *    |                                               |
 *    |                                               |
 *    |                    ADDRESS                    |
 *    |                                               |
 *    |                   (128 bit)                   |
 *    |                                               |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class AAAA extends RR {


    /** @var string The IPv6 address in the preferred hexadecimal values of the eight 16-bit pieces per RFC 1884. */
    public string $address;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[ArrayShape( [ 'ipv6' => 'string' ] )] public function getPHPRData() : array {
        return [
            'ipv6' => $this->address,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # Expand out compressed formats.
        $value = array_shift( $i_rData );
        if ( BaseQuery::isIPv6( $value ) ) {

            $this->address = $value;
            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        $i_packet->offset += 16;
        $r = [];
        return self::rrToBinary( $r, 0 );
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        # Must be 8 x 16bit chunks, or 16 x 8bit.
        if ( $this->rdLength == 16 ) {
            # PHP's inet_ntop returns IPv6 addresses in their compressed form,
            # but we want to keep with the preferred standard, so we'll parse
            # it manually.
            $xx = unpack( 'n8', $this->rdata );
            if ( count( $xx ) == 8 ) {
                $this->address = vsprintf( '%x:%x:%x:%x:%x:%x:%x:%x', $xx );
                return true;
            }
        }

        return false;
    }


    protected function rrToBinary( array &$io_rLabelMap, int $i_uOffset ) : string {
        return OK::inet_pton( $this->address );
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->address;
    }


}
