<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
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
 * SOA Resource Record - RFC1035 section 3.3.13
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                     mName                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                     rName                     /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    SERIAL                     |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    REFRESH                    |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                     RETRY                     |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    EXPIRE                     |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    MINIMUM                    |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class SOA extends RR {


    /** @var string Master DNS server */
    public string $mName;

    /** @var string Mailbox of the responsible person */
    public string $rName;

    /** @var int Serial number */
    public int $serial;

    /** @var int Refresh time */
    public int $refresh;

    /** @var int Retry interval */
    public int $retry;

    /** @var int Expire time */
    public int $expire;

    /** @var int Minimum TTL for any RR in this zone */
    public int $minimum;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     * @return array<string, int|string>
     */
    #[ArrayShape( [ 'mname' => 'string', 'rname' => 'string', 'serial' => 'int', 'refresh' => 'int',
        'retry' => 'int', 'expire' => 'int', 'minimum-ttl' => 'int' ] )]
    public function getPHPRData() : array {
        return [
            'mname' => $this->mName,
            'rname' => $this->rName,
            'serial' => $this->serial,
            'refresh' => $this->refresh,
            'retry' => $this->retry,
            'expire' => $this->expire,
            'minimum-ttl' => $this->minimum,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->mName = $this->cleanString( $i_rData[ 0 ] );
        $this->rName = $this->cleanString( $i_rData[ 1 ] );

        $this->serial = (int) $i_rData[ 2 ];
        $this->refresh = (int) $i_rData[ 3 ];
        $this->retry = (int) $i_rData[ 4 ];
        $this->expire = (int) $i_rData[ 5 ];
        $this->minimum = (int) $i_rData[ 6 ];

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->mName ) > 0 ) {

            $data = $i_packet->compress( $this->mName, $i_packet->offset );
            $data .= $i_packet->compress( $this->rName, $i_packet->offset );

            $data .= pack(
                'N5', $this->serial, $this->refresh, $this->retry,
                $this->expire, $this->minimum
            );

            $i_packet->offset += 20;

            return $data;
        }

        return null;
    }


    /** @inheritDoc
     * @throws Exception
     */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Parse the names.
            $offset = $i_packet->offset;

            $this->mName = $i_packet->expandEx( $offset );
            $this->rName = $i_packet->expandEx( $offset, true );

            # Get the SOA values.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack(
                '@' . $offset . '/Nserial/Nrefresh/Nretry/Nexpire/Nminimum/',
                $i_packet->rdata
            );

            $this->serial = $parse[ 'serial' ];
            $this->refresh = $parse[ 'refresh' ];
            $this->retry = $parse[ 'retry' ];
            $this->expire = $parse[ 'expire' ];
            $this->minimum = $parse[ 'minimum' ];

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->cleanString( $this->mName ) . '. ' .
            $this->cleanString( $this->rName ) . '. ' .
            $this->serial . ' ' . $this->refresh . ' ' . $this->retry . ' ' .
            $this->expire . ' ' . $this->minimum;
    }


}
