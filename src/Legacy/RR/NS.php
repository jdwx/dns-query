<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\RR;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Packet\Packet;
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
 * NS Resource Record - RFC1035 section 3.3.11
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   NSD NAME                    /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class NS extends RR {


    /** @var string Hostname of the DNS server */
    public string $nsdName;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[ArrayShape( [ 'target' => 'string' ] )] public function getPHPRData() : array {
        return [
            'target' => $this->nsdName,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->nsdName = $this->cleanString( array_shift( $i_rData ) );
        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->nsdName ) > 0 ) {

            return $i_packet->compress( $this->nsdName, $i_packet->offset );
        }

        return null;
    }


    /** @inheritDoc
     * @throws Exception
     */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $offset = $i_packet->offset;
            $this->nsdName = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->cleanString( $this->nsdName ) . '.';
    }


}
