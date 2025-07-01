<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Legacy\BaseQuery;
use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\RR\RR;


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
 * IPSECKEY Resource Record - RFC4025 section 2.1
 *
 *       0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |  precedence   | gateway type  |  algorithm  |     gateway     |
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-------------+                 +
 *     ~                            gateway                            ~
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |                                                               /
 *     /                          public key                           /
 *     /                                                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-|
 *
 */
class IPSECKEY extends RR {


    public const int GATEWAY_TYPE_NONE   = 0;

    public const int GATEWAY_TYPE_IPV4   = 1;

    public const int GATEWAY_TYPE_IPV6   = 2;

    public const int GATEWAY_TYPE_DOMAIN = 3;

    public const int ALGORITHM_NONE      = 0;

    public const int ALGORITHM_DSA       = 1;

    public const int ALGORITHM_RSA       = 2;

    /** @var int Precedence (used the same way as a preference field) */
    public int $precedence;

    /** @var int Gateway type
     *
     * Specifies the format of the gateway information
     * This can be either:
     *
     *  0    No Gateway
     *  1    IPv4 address
     *  2    IPV6 address
     *  3    wire-encoded domain name (not compressed)
     */
    public int $gatewayType;

    /** @var int Algorithm used
     *
     * This can be:
     *  0    No key is present
     *  1    DSA key is present
     *  2    RSA key is present
     */
    public int $algorithm;

    /** @var string Gateway information */
    public string $gateway;

    /** @var string Public key */
    public string $key;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # Load the data.
        $precedence = (int) array_shift( $i_rData );
        $gatewayType = (int) array_shift( $i_rData );
        $algorithm = (int) array_shift( $i_rData );
        $gateway = trim( strtolower( trim( array_shift( $i_rData ) ) ), '.' );
        $key = array_shift( $i_rData ) ?? '';

        # Validate it.
        switch ( $gatewayType ) {
            case self::GATEWAY_TYPE_NONE:
                $gateway = '';
                break;

            case self::GATEWAY_TYPE_IPV4:
                if ( ! BaseQuery::isIPv4( $gateway ) ) {
                    return false;
                }
                break;

            case self::GATEWAY_TYPE_IPV6:
                if ( ! BaseQuery::isIPv6( $gateway ) ) {
                    return false;
                }
                break;

            case self::GATEWAY_TYPE_DOMAIN:
                # Do nothing.
                break;

            default:
                return false;
        }

        # Check the algorithm and key.
        switch ( $algorithm ) {
            case self::ALGORITHM_NONE:
                $key = '';
                break;

            case self::ALGORITHM_DSA:
            case self::ALGORITHM_RSA:
                # Do nothing.
                break;

            default:
                return false;
        }

        # Store the values.
        $this->precedence = $precedence;
        $this->gatewayType = $gatewayType;
        $this->algorithm = $algorithm;
        $this->gateway = $gateway;
        $this->key = $key;

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        # Pack the precedence, gateway type and algorithm.
        $data = pack(
            'CCC', $this->precedence, $this->gatewayType, $this->algorithm
        );

        # Add the gateway based on the type.
        switch ( $this->gatewayType ) {
            case self::GATEWAY_TYPE_NONE:
                # Add nothing.
                break;

            case self::GATEWAY_TYPE_IPV4:
            case self::GATEWAY_TYPE_IPV6:
                $data .= inet_pton( $this->gateway );
                break;

            case self::GATEWAY_TYPE_DOMAIN:
                $data .= chr( strlen( $this->gateway ) ) . $this->gateway;
                break;

            default:
                return null;
        }

        # Add the key if there's one specified.
        switch ( $this->algorithm ) {
            case self::ALGORITHM_NONE:
                # Add nothing.
                break;

            case self::ALGORITHM_DSA:
            case self::ALGORITHM_RSA:
                $data .= base64_decode( $this->key );
                break;

            default:
                return null;
        }

        $i_packet->offset += strlen( $data );

        return $data;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Parse off the precedence, gateway type and algorithm.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Cprecedence/CgatewayType/Calgorithm', $this->rdata );

            $this->precedence = $parse[ 'precedence' ];
            $this->gatewayType = $parse[ 'gatewayType' ];
            $this->algorithm = $parse[ 'algorithm' ];

            $offset = 3;

            # Extract the gateway based on the type.
            switch ( $this->gatewayType ) {
                case self::GATEWAY_TYPE_NONE:
                    $this->gateway = '';
                    break;

                case self::GATEWAY_TYPE_IPV4:
                    $this->gateway = inet_ntop( substr( $this->rdata, $offset, 4 ) );
                    $offset += 4;
                    break;

                case self::GATEWAY_TYPE_IPV6:
                    $ip = unpack( 'n8', substr( $this->rdata, $offset, 16 ) );
                    if ( count( $ip ) == 8 ) {

                        $this->gateway = vsprintf( '%x:%x:%x:%x:%x:%x:%x:%x', $ip );
                        $offset += 16;
                    } else {

                        return false;
                    }
                    break;

                case self::GATEWAY_TYPE_DOMAIN:
                    $domainOffset = $offset + $i_packet->offset;
                    $this->gateway = $i_packet->expandEx( $domainOffset );
                    $offset = ( $domainOffset - $i_packet->offset );
                    break;

                default:
                    return false;
            }

            # Extract the key.
            switch ( $this->algorithm ) {
                case self::ALGORITHM_NONE:
                    $this->key = '';
                    break;

                case self::ALGORITHM_DSA:
                case self::ALGORITHM_RSA:
                    $this->key = base64_encode( substr( $this->rdata, $offset ) );
                    break;

                default:
                    return false;
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->precedence . ' ' . $this->gatewayType . ' ' .
            $this->algorithm . ' ';

        switch ( $this->gatewayType ) {
            case self::GATEWAY_TYPE_NONE:
                $out .= '. ';
                break;

            case self::GATEWAY_TYPE_IPV4:
            case self::GATEWAY_TYPE_IPV6:
                $out .= $this->gateway . ' ';
                break;

            case self::GATEWAY_TYPE_DOMAIN:
                $out .= $this->gateway . '. ';
                break;
        }

        $out .= $this->key;
        return $out;
    }


}
