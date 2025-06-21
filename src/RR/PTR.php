<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exceptions\Exception;
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
 * PTR Resource Record - RFC1035 section 3.3.12
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   ptrDName                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class PTR extends RR {


    /** @var string Hostname of the PTR entry */
    public string $ptrDName;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     * @return array<string, string>
     */
    #[ArrayShape( [ 'target' => 'string' ] )] public function getPHPRData() : array {
        return [
            'target' => $this->ptrDName,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->ptrDName = rtrim( implode( ' ', $i_rData ), '.' );
        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->ptrDName ) > 0 ) {
            return $i_packet->compress( $this->ptrDName, $i_packet->offset );
        }

        return null;
    }


    /** @inheritDoc
     * @throws Exception
     */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {
            $offset = $i_packet->offset;
            $this->ptrDName = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return rtrim( $this->ptrDName, '.' ) . '.';
    }


}
