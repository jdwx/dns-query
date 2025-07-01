<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;


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
 * @since     File available since Release 1.0.0
 *
 */


/**
 * APL Resource Record - RFC3123
 *
 *     +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *     |                          AddressFamily                        |
 *     +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *     |             Prefix            | N |         AFDLength         |
 *     +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *     /                            AFDPart                            /
 *     |                                                               |
 *     +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *
 */
class APL extends RR {


    /** @var list<array<string, mixed>> List of all the address prefix list items */
    public array $aplItems = [];


    /**
     * @inheritDoc
     * @param array<string, string> $i_rData
     */
    protected function rrFromString( array $i_rData ) : bool {
        foreach ( $i_rData as $item ) {

            if ( preg_match( '/^(!?)([1|2]):([^\/]*)\/(\d{1,3})$/', $item, $matches ) ) {

                $newItem = [
                    'address_family' => (int) $matches[ 2 ],
                    'prefix' => $matches[ 4 ],
                    'n' => ( $matches[ 1 ] == '!' ) ? 1 : 0,
                    'afd_part' => strtolower( $matches[ 3 ] ),
                ];

                $address = $this->_trimZeros(
                    $newItem[ 'address_family' ], $newItem[ 'afd_part' ]
                );

                $newItem[ 'afd_length' ] = count( explode( '.', $address ) );

                $this->aplItems[] = $newItem;
            }
        }

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( count( $this->aplItems ) > 0 ) {

            $data = '';

            foreach ( $this->aplItems as $item ) {

                # Pack the address_family and prefix values.
                $data .= pack(
                    'nCC',
                    $item[ 'address_family' ],
                    $item[ 'prefix' ],
                    ( $item[ 'n' ] << 7 ) | $item[ 'afd_length' ]
                );

                switch ( $item[ 'address_family' ] ) {
                    case 1:
                        $address = explode(
                            '.',
                            $this->_trimZeros( $item[ 'address_family' ], $item[ 'afd_part' ] )
                        );

                        foreach ( $address as $byte ) {
                            $data .= chr( (int) $byte );
                        }
                        break;
                    case 2:
                        $address = explode(
                            ':',
                            $this->_trimZeros( $item[ 'address_family' ], $item[ 'afd_part' ] )
                        );

                        foreach ( $address as $byte ) {
                            $data .= pack( 'H', $byte );
                        }
                        break;
                    default:
                        return null;
                }
            }

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $offset = 0;

            while ( $offset < $this->rdLength ) {

                # Unpack the family, prefix, negate and length values.
                /** @noinspection SpellCheckingInspection */
                $parse = unpack(
                    'naddress_family/Cprefix/Cextra', substr( $this->rdata, $offset )
                );

                $item = [

                    'address_family' => $parse[ 'address_family' ],
                    'prefix' => $parse[ 'prefix' ],
                    'n' => ( $parse[ 'extra' ] >> 7 ) & 0x1,
                    'afd_length' => $parse[ 'extra' ] & 0xf,
                ];

                switch ( $item[ 'address_family' ] ) {

                    case 1:
                        $octets = unpack(
                            'C*', substr( $this->rdata, $offset + 4, $item[ 'afd_length' ] )
                        );
                        assert( is_array( $octets ) );
                        if ( count( $octets ) < 4 ) {

                            for ( $count = count( $octets ) + 1 ; $count < 4 + 1 ; $count++ ) {
                                $octets[ $count ] = 0;
                            }
                        }

                        $item[ 'afd_part' ] = implode( '.', $octets );

                        break;
                    case 2:
                        $octets = unpack(
                            'C*', substr( $this->rdata, $offset + 4, $item[ 'afd_length' ] )
                        );
                        assert( is_array( $octets ) );
                        if ( count( $octets ) < 8 ) {

                            for ( $count = count( $octets ) + 1 ; $count < 8 + 1 ; $count++ ) {
                                $octets[ $count ] = 0;
                            }
                        }

                        $item[ 'afd_part' ] = sprintf(
                            '%x:%x:%x:%x:%x:%x:%x:%x',
                            $octets[ 1 ], $octets[ 2 ], $octets[ 3 ], $octets[ 4 ], $octets[ 5 ], $octets[ 6 ], $octets[ 7 ], $octets[ 8 ]
                        );

                        break;
                    default:
                        return false;
                }

                $this->aplItems[] = $item;

                $offset += 4 + $item[ 'afd_length' ];
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = '';

        foreach ( $this->aplItems as $item ) {

            if ( $item[ 'n' ] == 1 ) {

                $out .= '!';
            }

            $out .= $item[ 'address_family' ] . ':' .
                $item[ 'afd_part' ] . '/' . $item[ 'prefix' ] . ' ';
        }

        return trim( $out );
    }


    /**
     * Return an IP address with the right-hand zeros trimmed
     *
     * @param int $family IP address family from the rdata
     * @param string $address IP address
     *
     * @return string The trimmed IP address.
     */
    private function _trimZeros( int $family, string $address ) : string {

        switch ( $family ) {
            case 1:
                $aa = array_reverse( explode( '.', $address ) );
                break;
            case 2:
                $aa = array_reverse( explode( ':', $address ) );
                break;
            default:
                return '';
        }

        foreach ( $aa as $value ) {

            if ( $value === '0' ) {

                array_shift( $aa );
            }
        }

        return match ( $family ) {
            1 => implode( '.', array_reverse( $aa ) ),
            2 => implode( ':', array_reverse( $aa ) ),
        };
    }


}
