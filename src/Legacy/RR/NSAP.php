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
 * @since     File available since Release 0.6.0
 *
 */


/**
 * NSAP Resource Record - RFC1706
 *
 *             |--------------|
 *             | <-- IDP -->  |
 *             |--------------|-------------------------------------|
 *             | AFI |  IDI   |            <-- DSP -->              |
 *             |-----|--------|-------------------------------------|
 *             | 47  |  0005  | DFI | AA |Rsvd | RD |Area | ID |Sel |
 *             |-----|--------|-----|----|-----|----|-----|----|----|
 *      octets |  1  |   2    |  1  | 3  |  2  | 2  |  2  | 6  | 1  |
 *             |-----|--------|-----|----|-----|----|-----|----|----|
 *
 */
class NSAP extends RR {


    public string $afi;

    public string $idi;

    public string $dfi;

    public string $aa;

    public string $rsvd;

    public string $rd;

    public string $area;

    public string $id;

    public string $sel;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $data = strtolower( trim( array_shift( $i_rData ) ) );

        # There is no real standard for format, so we can't rely on the fact that
        # the value will come in with periods separating the values so strip
        # them out if they're included, and parse without them.
        $data = str_replace( [ '.', '0x' ], '', $data );

        # Unpack it as ascii characters.
        /** @noinspection SpellCheckingInspection */
        $parse = unpack( 'A2afi/A4idi/A2dfi/A6aa/A4rsvd/A4rd/A4area/A12id/A2sel', $data );

        # Make sure the afi value is 47
        if ( $parse[ 'afi' ] == '47' ) {

            $this->afi = '0x' . $parse[ 'afi' ];
            $this->idi = $parse[ 'idi' ];
            $this->dfi = $parse[ 'dfi' ];
            $this->aa = $parse[ 'aa' ];
            $this->rsvd = $parse[ 'rsvd' ];
            $this->rd = $parse[ 'rd' ];
            $this->area = $parse[ 'area' ];
            $this->id = $parse[ 'id' ];
            $this->sel = $parse[ 'sel' ];

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( $this->afi == '0x47' ) {

            # Build the aa field.
            $aa = unpack( 'A2x/A2y/A2z', $this->aa );

            # Build the id field.
            $id = unpack( 'A8a/A4b', $this->id );

            /** @noinspection SpellCheckingInspection */
            $data = pack(
                'CnCCCCnnnNnC',
                hexdec( $this->afi ),
                hexdec( $this->idi ),
                hexdec( $this->dfi ),
                hexdec( $aa[ 'x' ] ),
                hexdec( $aa[ 'y' ] ),
                hexdec( $aa[ 'z' ] ),
                hexdec( $this->rsvd ),
                hexdec( $this->rd ),
                hexdec( $this->area ),
                hexdec( $id[ 'a' ] ),
                hexdec( $id[ 'b' ] ),
                hexdec( $this->sel )
            );

            if ( strlen( $data ) == 20 ) {

                $i_packet->offset += 20;
                return $data;
            }
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength == 20 ) {

            # Get the AFI value.
            $this->afi = dechex( ord( $this->rdata[ 0 ] ) );

            # We only support AFI 47- there aren't any others defined.
            if ( $this->afi == '47' ) {

                # Unpack the rest of the values.
                /** @noinspection SpellCheckingInspection */
                $parse = unpack(
                    'Cafi/nidi/Cdfi/C3aa/nrsvd/nrd/narea/Nidh/nidl/Csel',
                    $this->rdata
                );

                $this->afi = sprintf( '0x%02x', $parse[ 'afi' ] );
                $this->idi = sprintf( '%04x', $parse[ 'idi' ] );
                $this->dfi = sprintf( '%02x', $parse[ 'dfi' ] );
                $this->aa = sprintf(
                    '%06x', $parse[ 'aa1' ] << 16 | $parse[ 'aa2' ] << 8 | $parse[ 'aa3' ]
                );
                $this->rsvd = sprintf( '%04x', $parse[ 'rsvd' ] );
                $this->rd = sprintf( '%04x', $parse[ 'rd' ] );
                $this->area = sprintf( '%04x', $parse[ 'area' ] );
                $this->id = sprintf( '%08x', $parse[ 'idh' ] ) .
                    sprintf( '%04x', $parse[ 'idl' ] );
                $this->sel = sprintf( '%02x', $parse[ 'sel' ] );

                return true;
            }
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->cleanString( $this->afi ) . '.' .
            $this->cleanString( $this->idi ) . '.' .
            $this->cleanString( $this->dfi ) . '.' .
            $this->cleanString( $this->aa ) . '.' .
            $this->cleanString( $this->rsvd ) . '.' .
            $this->cleanString( $this->rd ) . '.' .
            $this->cleanString( $this->area ) . '.' .
            $this->cleanString( $this->id ) . '.' .
            $this->sel;
    }


}
