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
 * @category  Networking
 * @package   Net_DNS2
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
class LOC extends RR
{
    /*
     * the LOC version (should only ever be 0)
     */
    public int $version;

    /*
     * The diameter of a sphere enclosing the described entity
     */
    public string $size;

    /*
     * The horizontal precision of the data
     */
    public string $horiz_pre;

    /*
     * The vertical precision of the data
     */
    public string $vert_pre;

    /*
     * The latitude - stored in decimal degrees
     */
    public float $latitude;

    /* 
     * The longitude - stored in decimal degrees
     */
    public float $longitude;

    /*
     * The altitude - stored in decimal
     */
    public float $altitude;

    /** @var int[] used for quick power-of-ten lookups */
    private array $_powerOfTen = [ 1, 10, 100, 1000, 10000, 100000,
                                 1000000,10000000,100000000,1000000000 ];

    /*
     * some conversion values
     */
    public const CONV_SEC = 1000;
    public const CONV_MIN = 60000;
    public const CONV_DEG = 3600000;

    public const REFERENCE_ALT = 10000000;
    public const REFERENCE_LATLON = 2147483648;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        if ($this->version == 0) {

            return $this->_d2Dms($this->latitude, 'LAT') . ' ' .
                $this->_d2Dms($this->longitude, 'LNG') . ' ' .
                sprintf('%.2fm', $this->altitude) . ' ' .
                sprintf('%.2fm', $this->size) . ' ' .
                sprintf('%.2fm', $this->horiz_pre) . ' ' .
                sprintf('%.2fm', $this->vert_pre);
        }
        
        return '';
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param string[] $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrFromString(array $rdata) : bool
    {
        //
        // format as defined by RFC1876 section 3
        //
        // d1 [m1 [s1]] {"N"|"S"} d2 [m2 [s2]] {"E"|"W"} alt["m"] 
        //      [siz["m"] [hp["m"] [vp["m"]]]]
        //
        $res = preg_match(
            '/^(\d+) \s+((\d+) \s+)?(([\d.]+) \s+)?([NS]) \s+(\d+) ' .
            '\s+((\d+) \s+)?(([\d.]+) \s+)?([EW]) \s+(-?[\d.]+) m?(\s+ ' .
            '([\d.]+) m?)?(\s+ ([\d.]+) m?)?(\s+ ([\d.]+) m?)?/ix', 
            implode(' ', $rdata), $x
        );

        if ($res) {

            //
            // latitude
            //
            $latdeg     = (int) $x[1];
            $latmin     = (isset($x[3])) ? (int) $x[3] : 0;
            $latsec     = (isset($x[5])) ? (float) $x[5] : 0;
            $lathem     = strtoupper($x[6]);

            $this->latitude = $this->_dms2d($latdeg, $latmin, $latsec, $lathem);

            //
            // longitude
            //
            $londeg     = (int) $x[7];
            $lonmin     = (isset($x[9])) ? (int) $x[9] : 0;
            $lonsec     = (isset($x[11])) ? (float) $x[11] : 0;
            $lonhem     = strtoupper($x[12]);

            $this->longitude = $this->_dms2d($londeg, $lonmin, $lonsec, $lonhem);

            //
            // the rest of the values
            //

            $this->size         = (isset($x[15])) ? $x[15] : 1;
            $this->horiz_pre    = ((isset($x[17])) ? $x[17] : 10000);
            $this->vert_pre     = ((isset($x[19])) ? $x[19] : 10);
            $this->altitude     = (float) $x[13];

            // There is no way to specify the version in text; it's always assumed to be 0.
            $this->version = 0;

            return true;
        }

        return false;
    }

    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet $packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ( $this->rdLength <= 0 ) {
            return false;
        }

        //
        // unpack all the values
        //
        $x = unpack(
            'Cver/Csize/Choriz_pre/Cvert_pre/Nlatitude/Nlongitude/Naltitude',
            $this->rdata
        );

        //
        // version must be 0 per RFC 1876 section 2
        //
        $this->version = $x[ 'ver' ];
        if ( $this->version != 0 ) {
            return false;
        }

        $this->size = $this->_precsizeNtoA( $x[ 'size' ] );
        $this->horiz_pre = $this->_precsizeNtoA( $x[ 'horiz_pre' ] );
        $this->vert_pre = $this->_precsizeNtoA( $x[ 'vert_pre' ] );

        //
        // convert the latitude and longitude to degress in decimal
        //
        if ( $x[ 'latitude' ] < 0 ) {

            $this->latitude = ( $x[ 'latitude' ] +
                    self::REFERENCE_LATLON ) / self::CONV_DEG;
        } else {

            $this->latitude = ( $x[ 'latitude' ] -
                    self::REFERENCE_LATLON ) / self::CONV_DEG;
        }

        if ( $x[ 'longitude' ] < 0 ) {

            $this->longitude = ( $x[ 'longitude' ] +
                    self::REFERENCE_LATLON ) / self::CONV_DEG;
        } else {

            $this->longitude = ( $x[ 'longitude' ] -
                    self::REFERENCE_LATLON ) / self::CONV_DEG;
        }

        //
        // convert down the altitude
        //
        $this->altitude = ( $x[ 'altitude' ] - self::REFERENCE_ALT ) / 100;

        return true;

    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet &$packet a Net_DNS2_Packet packet use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if ($this->version == 0) {

            if ($this->latitude < 0) {

                $lat = ($this->latitude * self::CONV_DEG) - self::REFERENCE_LATLON;
            } else {

                $lat = ($this->latitude * self::CONV_DEG) + self::REFERENCE_LATLON;
            }

            if ($this->longitude < 0) {

                $lng = ($this->longitude * self::CONV_DEG) - self::REFERENCE_LATLON;
            } else {

                $lng = ($this->longitude * self::CONV_DEG) + self::REFERENCE_LATLON;
            }

            $packet->offset += 16;

            return pack(
                'CCCCNNN', 
                $this->version,
                $this->_precsizeAtoN($this->size),
                $this->_precsizeAtoN($this->horiz_pre),
                $this->_precsizeAtoN($this->vert_pre),
                $lat, $lng,
                ($this->altitude * 100) + self::REFERENCE_ALT
            );
        }

        return null;
    }

    /**
     * takes an XeY precision/size value, returns a string representation.
     * shamelessly stolen from RFC1876 Appendix A
     *
     * @param int $prec the value to convert
     *
     * @return string
     * @access private
     *
     */
    private function _precsizeNtoA( int $prec ) : string
    {
        $mantissa = (($prec >> 4) & 0x0f) % 10;
        $exponent = (($prec >> 0) & 0x0f) % 10;

        return strval( $mantissa * $this->_powerOfTen[$exponent] );
    }

    /**
     * converts ascii size/precision X * 10**Y(cm) to 0xXY.
     * shamelessly stolen from RFC1876 Appendix A
     *
     * @param string $prec the value to convert
     *
     * @return int
     * @access private
     *
     */
    private function _precsizeAtoN( string $prec ) : int
    {
        $exponent = 0;
        while ($prec >= 10) {

            $prec /= 10;
            ++$exponent;
        }

        return ($prec << 4) | ($exponent & 0x0f);
    }

    /**
     * convert lat/lng in deg/min/sec/hem to decimal value
     *
     * @param int    $deg the degree value
     * @param int    $min the minutes value
     * @param float  $sec the seconds value
     * @param string $hem the hemisphere (N/E/S/W)
     *
     * @return float the decimal value
     * @access private
     *
     */
    private function _dms2d(int $deg, int $min, float $sec, string $hem) : float
    {

        $sign = ($hem == 'W' || $hem == 'S') ? -1 : 1;
        return ((($sec/60+$min)/60)+$deg) * $sign;
    }

    /**
     * convert lat/lng in decimal to deg/min/sec/hem
     *
     * @param float  $data   the decimal value
     * @param string $latlng either LAT or LNG so we can determine the HEM value
     *
     * @return string
     * @access private
     *
     */
    private function _d2Dms( float $data, string $latlng ) : string
    {
        if ($latlng == 'LAT') {
            $hem = ($data > 0) ? 'N' : 'S';
        } else {
            $hem = ($data > 0) ? 'E' : 'W';
        }

        $data = abs($data);

        $deg = (int)$data;
        $min = (int)(($data - $deg) * 60);
        $sec = (int)(((($data - $deg) * 60) - $min) * 60);
        $msec = round((((((($data - $deg) * 60) - $min) * 60) - $sec) * 1000));

        return sprintf('%d %02d %02d.%03d %s', $deg, $min, $sec, round($msec), $hem);
    }
}
