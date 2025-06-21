<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\BaseQuery;
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
 * @since     File available since Release 0.6.0
 *
 */


/**
 * TKEY Resource Record - RFC 2930 section 2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   ALGORITHM                   /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   INCEPTION                   |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   EXPIRATION                  |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   MODE                        |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   ERROR                       |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   KEY SIZE                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   KEY DATA                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   OTHER SIZE                  |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   OTHER DATA                  /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class TKEY extends RR {


    public const int TSIG_MODE_RES         = 0;

    public const int TSIG_MODE_SERV_ASSIGN = 1;

    public const int TSIG_MODE_DH          = 2;

    public const int TSIG_MODE_GSS_API     = 3;

    public const int TSIG_MODE_RESV_ASSIGN = 4;

    public const int TSIG_MODE_KEY_DELE    = 5;

    /** @var string Algorithm */
    public string $algorithm;

    /** @var string Inception time */
    public string $inception;

    /** @var string Expiration time */
    public string $expiration;

    /** var intTSIG Modes */
    public int $mode;

    /** @var int Error */
    public int $error;

    /** @var int Key size */
    public int $keySize;

    /** @var string Key data */
    public string $keyData;

    /** @var int Other size */
    public int $otherSize;

    /** @var string Other data */
    public string $otherData;

    /** @var string[] Map the mod IDs to names so we can validate */
    public array $tsigModeIdToName = [
        self::TSIG_MODE_RES => 'Reserved',
        self::TSIG_MODE_SERV_ASSIGN => 'Server Assignment',
        self::TSIG_MODE_DH => 'Diffie-Hellman',
        self::TSIG_MODE_GSS_API => 'GSS-API',
        self::TSIG_MODE_RESV_ASSIGN => 'Resolver Assignment',
        self::TSIG_MODE_KEY_DELE => 'Key Deletion',
    ];


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # Data passed in is assumed: <algorithm> <mode> <key>
        $this->algorithm = $this->cleanString( array_shift( $i_rData ) );
        $this->mode = (int) array_shift( $i_rData );
        $this->keyData = trim( array_shift( $i_rData ), '.' );

        # The rest of the data is set manually.
        $this->inception = (string) time();
        $this->expiration = (string) ( time() + 86400 ); # 1 day
        $this->error = 0;
        $this->keySize = strlen( $this->keyData );
        $this->otherSize = 0;
        $this->otherData = '';

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->algorithm ) > 0 ) {

            # Make sure the size values are correct.
            $this->keySize = strlen( $this->keyData );
            $this->otherSize = strlen( $this->otherData );

            # Add the algorithm without compression.
            $data = Packet::pack( $this->algorithm );

            # Pack in the inception, expiration, mode, error and key size.
            /** @noinspection SpellCheckingInspection */
            $data .= pack(
                'NNnnn', $this->inception, $this->expiration,
                $this->mode, 0, $this->keySize
            );

            # If the key_size > 0, then add the key.
            if ( $this->keySize > 0 ) {

                $data .= $this->keyData;
            }

            # Pack in the other size.
            $data .= pack( 'n', $this->otherSize );
            if ( $this->otherSize > 0 ) {

                $data .= $this->otherData;
            }

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Expand the algorithm.
            $offset = $i_packet->offset;
            $this->algorithm = $i_packet->expandEx( $offset );

            # Unpack inception, expiration, mode, error and key size.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack(
                '@' . $offset . '/Ninception/Nexpiration/nmode/nerror/nkey_size',
                $i_packet->rdata
            );

            $this->inception = BaseQuery::expandUint32( $parse[ 'inception' ] );
            $this->expiration = BaseQuery::expandUint32( $parse[ 'expiration' ] );
            $this->mode = $parse[ 'mode' ];
            $this->error = $parse[ 'error' ];
            $this->keySize = $parse[ 'key_size' ];

            $offset += 14;

            # If key_size > 0, then copy out the key.
            if ( $this->keySize > 0 ) {

                $this->keyData = substr( $i_packet->rdata, $offset, $this->keySize );
                $offset += $this->keySize;
            }

            # Unpack the other length.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( '@' . $offset . '/nother_size', $i_packet->rdata );

            $this->otherSize = $parse[ 'other_size' ];
            $offset += 2;

            # If other_size > 0, then copy out the data.
            if ( $this->otherSize > 0 ) {

                $this->otherData = substr(
                    $i_packet->rdata, $offset, $this->otherSize
                );
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->cleanString( $this->algorithm ) . '. ' . $this->mode;
        if ( $this->keySize > 0 ) {
            $out .= ' ' . trim( $this->keyData, '.' ) . '.';
        } else {
            $out .= ' .';
        }

        return $out;
    }


}
