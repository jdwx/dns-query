<?php


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
 * @since     File available since Release 1.4.5
 *
 */


/**
 * AMTRELAY Resource Record - RFC8777 section 4.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   precedence  |D|    type     |                               |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               +
 *  ~                            relay                              ~
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class AMTRELAY extends RR {


    # Type definitions that match the "type" field below


    /** @const AMTRELAY type None */
    public const int AMTRELAY_TYPE_NONE = 0;

    /** @const AMTRELAY type IPv4 */
    public const int AMTRELAY_TYPE_IPV4 = 1;

    /** @const AMTRELAY type IPv6 */
    public const int AMTRELAY_TYPE_IPV6 = 2;

    /** @const AMTRELAY type Domain Name */
    public const int AMTRELAY_TYPE_DOMAIN = 3;

    /** @var int The precedence for this record */
    public int $precedence;

    /** @var int "Discovery Optional" flag */
    public int $discovery;

    /** @var int The type field indicates the format of the information that is stored in the relay field. */
    public int $relayType;

    /** @var string The relay field is the address or domain name of the AMT relay. */
    public string $relay;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # Extract the values from the array.
        $this->precedence = (int) array_shift( $i_rData );
        $this->discovery = (int) array_shift( $i_rData );
        $this->relayType = (int) array_shift( $i_rData );
        $this->relay = trim( strtolower( trim( array_shift( $i_rData ) ) ), '.' );

        # If there's anything other than 0 in the discovery value, then force it to 1, so
        # that it is effectively either "true" or "false."
        if ( $this->discovery != 0 ) {
            $this->discovery = 1;
        }

        # Validate the type & relay values.
        switch ( $this->relayType ) {
            case self::AMTRELAY_TYPE_NONE:
                $this->relay = '';
                break;

            case self::AMTRELAY_TYPE_IPV4:
                if ( ! BaseQuery::isIPv4( $this->relay ) ) {
                    return false;
                }
                break;

            case self::AMTRELAY_TYPE_IPV6:
                if ( ! BaseQuery::isIPv6( $this->relay ) ) {
                    return false;
                }
                break;

            case self::AMTRELAY_TYPE_DOMAIN:
                # Do nothing.
                break;

            default:

                # Invalid type value.
                return false;

        }

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {

        # Pack the precedence, discovery, and type.
        $data = pack( 'CC', $this->precedence, ( $this->discovery << 7 ) | $this->relayType );

        # Add the relay data based on the type.
        switch ( $this->relayType ) {
            case self::AMTRELAY_TYPE_NONE:
                # Add nothing.
                break;

            case self::AMTRELAY_TYPE_IPV4:
            case self::AMTRELAY_TYPE_IPV6:
                $data .= inet_pton( $this->relay );
                break;

            case self::AMTRELAY_TYPE_DOMAIN:
                $data .= pack( 'Ca*', strlen( $this->relay ), $this->relay );
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

            # Parse off the first two octets.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Cprecedence/Csecond', $this->rdata );

            $this->precedence = $parse[ 'precedence' ];
            $this->discovery = ( $parse[ 'second' ] >> 7 ) & 0x1;
            $this->relayType = $parse[ 'second' ] & 0xf;

            $offset = 2;

            # Parse the relay value based on the type.
            switch ( $this->relayType ) {
                case self::AMTRELAY_TYPE_NONE:
                    $this->relay = '';
                    break;

                case self::AMTRELAY_TYPE_IPV4:
                    $this->relay = inet_ntop( substr( $this->rdata, $offset, 4 ) );
                    break;

                case self::AMTRELAY_TYPE_IPV6:

                    # PHP's inet_ntop returns IPv6 addresses in their compressed form, but we want to keep
                    # with the preferred standard, so we'll parse it manually.
                    $ip = unpack( 'n8', substr( $this->rdata, $offset, 16 ) );
                    if ( count( $ip ) == 8 ) {
                        $this->relay = vsprintf( '%x:%x:%x:%x:%x:%x:%x:%x', $ip );
                    } else {
                        return false;
                    }
                    break;

                case self::AMTRELAY_TYPE_DOMAIN:
                    $domainOffset = $i_packet->offset + $offset;
                    $this->relay = $i_packet->labelEx( $domainOffset );

                    break;

                default:
                    # Invalid type value.
                    return false;
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->precedence . ' ' . $this->discovery . ' ' . $this->relayType . ' ' . $this->relay;

        # 4.3.1 - If the relay type field is 0, the relay field MUST be a dot literal.
        if ( ( $this->relayType == self::AMTRELAY_TYPE_NONE ) || ( $this->relayType == self::AMTRELAY_TYPE_DOMAIN ) ) {
            $out .= '.';
        }

        return $out;
    }


}
