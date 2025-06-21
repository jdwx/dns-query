<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


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
 * LOC Resource Record - RFC1876 section 2
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |        VERSION        |         SIZE          |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |       HORIZ PRE       |       VERT PRE        |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                   LATITUDE                    |
 *      |                                               |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                   LONGITUDE                   |
 *      |                                               |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                   ALTITUDE                    |
 *      |                                               |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class LOC extends RR {


    # Some conversion values
    public const int CONV_SEC          = 1000;

    public const int CONV_MIN          = 60000;

    public const int CONV_DEG          = 3600000;

    public const int REFERENCE_ALT     = 10000000;

    public const int REFERENCE_LAT_LON = 2147483648;

    /** @var int[] used for quick power-of-ten lookups */
    private static array $powerOfTen = [
        1, 10, 100, 1000, 10000, 100000,
        1000000, 10000000, 100000000, 1000000000,
    ];

    /** @var int LOC version (should only ever be 0) */
    public int $version;

    /** @var string The diameter of a sphere enclosing the described entity */
    public string $size;

    /** @var float Altitude - stored in decimal */
    public float $altitude;

    /** @var string The horizontal precision of the data */
    public string $horizPrecision;

    /** @const The vertical precision of the data */
    public string $vertPrecision;

    /** @var float Latitude - stored in decimal degrees */
    public float $latitude;

    /** @var float Longitude - stored in decimal degrees */
    public float $longitude;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # Format as defined by RFC1876 section 3
        #
        # d1 [m1 [s1]] {"N"|"S"} d2 [m2 [s2]] {"E"|"W"} alt["m"]
        #      [siz["m"] [hp["m"] [vp["m"]]]]
        $res = preg_match(
            '/^(\d+) \s+((\d+) \s+)?(([\d.]+) \s+)?([NS]) \s+(\d+) ' .
            '\s+((\d+) \s+)?(([\d.]+) \s+)?([EW]) \s+(-?[\d.]+) m?(\s+ ' .
            '([\d.]+) m?)?(\s+ ([\d.]+) m?)?(\s+ ([\d.]+) m?)?/ix',
            implode( ' ', $i_rData ), $matches
        );

        if ( $res ) {

            # Latitude
            $latDegrees = (int) $matches[ 1 ];
            $latMinutes = (int) $matches[ 3 ];
            $latSeconds = (float) $matches[ 5 ];
            $latHemisphere = strtoupper( $matches[ 6 ] );

            $this->latitude = $this->convertDMSHToDecimal( $latDegrees, $latMinutes, $latSeconds, $latHemisphere );

            # Longitude
            $longDegrees = (int) $matches[ 7 ];
            $longMinutes = (int) $matches[ 9 ];
            $longSeconds = (float) $matches[ 11 ];
            $longHemisphere = strtoupper( $matches[ 12 ] );

            $this->longitude = $this->convertDMSHToDecimal( $longDegrees, $longMinutes, $longSeconds, $longHemisphere );

            # The rest of the values
            $this->size = ( isset( $matches[ 15 ] ) ) ? $matches[ 15 ] : 1;
            $this->horizPrecision = ( ( isset( $matches[ 17 ] ) ) ? $matches[ 17 ] : 10000 );
            $this->vertPrecision = ( ( isset( $matches[ 19 ] ) ) ? $matches[ 19 ] : 10 );
            $this->altitude = (float) $matches[ 13 ];

            # There is no way to specify the version in text; it's always assumed to be 0.
            $this->version = 0;

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( $this->version == 0 ) {

            if ( $this->latitude < 0 ) {

                $lat = ( $this->latitude * self::CONV_DEG ) - self::REFERENCE_LAT_LON;
            } else {

                $lat = ( $this->latitude * self::CONV_DEG ) + self::REFERENCE_LAT_LON;
            }

            if ( $this->longitude < 0 ) {

                $lng = ( $this->longitude * self::CONV_DEG ) - self::REFERENCE_LAT_LON;
            } else {

                $lng = ( $this->longitude * self::CONV_DEG ) + self::REFERENCE_LAT_LON;
            }

            $i_packet->offset += 16;

            /** @noinspection SpellCheckingInspection */
            return pack(
                'CCCCNNN',
                $this->version,
                $this->precisionSizeAtoN( $this->size ),
                $this->precisionSizeAtoN( $this->horizPrecision ),
                $this->precisionSizeAtoN( $this->vertPrecision ),
                $lat, $lng,
                ( $this->altitude * 100 ) + self::REFERENCE_ALT
            );
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength <= 0 ) {
            return false;
        }

        # Unpack all the values.
        /** @noinspection SpellCheckingInspection */
        $parse = unpack(
            'Cver/Csize/Choriz_pre/Cvert_pre/Nlatitude/Nlongitude/Naltitude',
            $this->rdata
        );

        # Version must be 0 per RFC 1876 section 2.
        $this->version = $parse[ 'ver' ];
        if ( $this->version != 0 ) {
            return false;
        }

        $this->size = $this->precisionSizeNtoA( $parse[ 'size' ] );
        $this->horizPrecision = $this->precisionSizeNtoA( $parse[ 'horiz_pre' ] );
        $this->vertPrecision = $this->precisionSizeNtoA( $parse[ 'vert_pre' ] );

        # Convert the latitude and longitude to degrees in decimal.
        if ( $parse[ 'latitude' ] < 0 ) {
            $this->latitude = ( $parse[ 'latitude' ] +
                    self::REFERENCE_LAT_LON ) / self::CONV_DEG;
        } else {
            $this->latitude = ( $parse[ 'latitude' ] -
                    self::REFERENCE_LAT_LON ) / self::CONV_DEG;
        }

        if ( $parse[ 'longitude' ] < 0 ) {
            $this->longitude = ( $parse[ 'longitude' ] +
                    self::REFERENCE_LAT_LON ) / self::CONV_DEG;
        } else {
            $this->longitude = ( $parse[ 'longitude' ] -
                    self::REFERENCE_LAT_LON ) / self::CONV_DEG;
        }

        # Convert down the altitude.
        $this->altitude = ( $parse[ 'altitude' ] - self::REFERENCE_ALT ) / 100;

        return true;

    }


    /** @inheritDoc */
    protected function rrToString() : string {
        if ( $this->version == 0 ) {

            return $this->convertDecimalToDMSH( $this->latitude, 'LAT' ) . ' ' .
                $this->convertDecimalToDMSH( $this->longitude, 'LNG' ) . ' ' .
                sprintf( '%.2fm', $this->altitude ) . ' ' .
                sprintf( '%.2fm', $this->size ) . ' ' .
                sprintf( '%.2fm', $this->horizPrecision ) . ' ' .
                sprintf( '%.2fm', $this->vertPrecision );
        }

        return '';
    }


    /**
     * convert lat/lng in deg/min/sec/hem to decimal value
     *
     * @param int $deg the degree value
     * @param int $min the minutes value
     * @param float $sec the seconds value
     * @param string $hem the hemisphere (N/E/S/W)
     *
     * @return float the decimal value
     */
    private function convertDMSHToDecimal( int $deg, int $min, float $sec, string $hem ) : float {

        $sign = ( $hem == 'W' || $hem == 'S' ) ? -1 : 1;
        return ( ( ( $sec / 60 + $min ) / 60 ) + $deg ) * $sign;
    }


    /**
     * convert lat/lng in decimal to deg/min/sec/hem
     *
     * @param float $data the decimal value
     * @param string $i_latLong either LAT or LNG so we can determine the HEM value
     *
     * @return string
     */
    private function convertDecimalToDMSH( float $data, string $i_latLong ) : string {
        if ( $i_latLong == 'LAT' ) {
            $hem = ( $data > 0 ) ? 'N' : 'S';
        } else {
            $hem = ( $data > 0 ) ? 'E' : 'W';
        }

        $data = abs( $data );

        $deg = (int) $data;
        $min = (int) ( ( $data - $deg ) * 60 );
        $sec = (int) ( ( ( ( $data - $deg ) * 60 ) - $min ) * 60 );
        $ms = round( ( ( ( ( ( ( $data - $deg ) * 60 ) - $min ) * 60 ) - $sec ) * 1000 ) );

        return sprintf( '%d %02d %02d.%03d %s', $deg, $min, $sec, round( $ms ), $hem );
    }


    /**
     * converts ascii size/precision X * 10**Y(cm) to 0xXY.
     * shamelessly stolen from RFC1876 Appendix A
     *
     * @param string $i_precision the value to convert
     *
     * @return int
     */
    private function precisionSizeAtoN( string $i_precision ) : int {
        $exponent = 0;
        while ( $i_precision >= 10 ) {
            $i_precision /= 10;
            ++$exponent;
        }

        return ( $i_precision << 4 ) | ( $exponent & 0x0f );
    }


    /**
     * takes an XeY precision/size value, returns a string representation.
     * shamelessly stolen from RFC1876 Appendix A
     *
     * @param int $i_precision the value to convert
     *
     * @return string
     */
    private function precisionSizeNtoA( int $i_precision ) : string {
        $mantissa = ( ( $i_precision >> 4 ) & 0x0f ) % 10;
        $exponent = ( ( $i_precision >> 0 ) & 0x0f ) % 10;

        return strval( $mantissa * self::$powerOfTen[ $exponent ] );
    }


}
