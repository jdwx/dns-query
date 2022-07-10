<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\Packet;
use JDWX\DNSQuery\Packet\RequestPacket;


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
 * TSIG Resource Record - RFC 2845
 *
 *      0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     /                          algorithm                            /
 *     /                                                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |                          time signed                          |
 *     |                               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |                               |              fudge            |
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |            mac size           |                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               /
 *     /                              mac                              /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |           original id         |              error            |
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |          other length         |                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               /
 *     /                          other data                           /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class TSIG extends RR
{
    /*
     * TSIG Algorithm Identifiers
     */
    public const HMAC_MD5 = 'hmac-md5.sig-alg.reg.int';   // RFC 2845, required
    public const GSS_TSIG = 'gss-tsig';                   // unsupported, optional
    public const HMAC_SHA1 = 'hmac-sha1';                  // RFC 4635, required
    public const HMAC_SHA224 = 'hmac-sha224';                // RFC 4635, optional
    public const HMAC_SHA256 = 'hmac-sha256';                // RFC 4635, required
    public const HMAC_SHA384 = 'hmac-sha384';                // RFC 4635, optional
    public const HMAC_SHA512 = 'hmac-sha512';                // RFC 4635, optional

    /*
     * the map of hash values to names
     */
    public static array $hash_algorithms = [

        self::HMAC_MD5      => 'md5',
        self::HMAC_SHA1     => 'sha1',
        self::HMAC_SHA224   => 'sha224',
        self::HMAC_SHA256   => 'sha256',
        self::HMAC_SHA384   => 'sha384',
        self::HMAC_SHA512   => 'sha512'
    ];

    /*
     * algorithm used; only supports HMAC-MD5
     */
    public string $algorithm;

    /*
     * The time it was signed
     */
    public int $time_signed;

    /*
     * allowed offset from the time signed
     */
    public int $fudge;

    /*
     * size of the digest
     */
    public int $mac_size;

    /*
     * the digest data
     */
    public string $mac;

    /*
     * the original id of the request
     */
    public int $original_id;

    /*
     * additional error code
     */
    public int $error;

    /*
     * length of the "other" data, should only ever be 0 when there is
     * no error, or 6 when there is the error RCODE_BADTIME
     */
    public int $other_length;

    /*
     * the other data; should only ever be a timestamp when there is the
     * error RCODE_BADTIME
     */
    public string $other_data;

    /*
     * the key to use for signing - passed in, not included in the rdata
     */
    public string $key;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        $out = $this->cleanString($this->algorithm) . '. ' . 
            $this->time_signed . ' ' . 
            $this->fudge . ' ' . $this->mac_size . ' ' .
            base64_encode($this->mac) . ' ' . $this->original_id . ' ' . 
            $this->error . ' '. $this->other_length;

        if ($this->other_length > 0) {

            $out .= ' ' . $this->other_data;
        }

        return $out;
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
        // the only value passed in is the key
        //
        // this assumes it's passed in base64 encoded.
        //
        $this->key = preg_replace('/\s+/', '', array_shift($rdata));

        //
        // the rest of the data is set to default
        //
        $this->algorithm    = self::HMAC_MD5;
        $this->time_signed  = time();
        $this->fudge        = 300;
        $this->mac_size     = 0;
        $this->mac          = '';
        $this->original_id  = 0;
        $this->error        = 0;
        $this->other_length = 0;
        $this->other_data   = '';

        //
        // per RFC 2845 section 2.3
        //
        $this->class        = 'ANY';
        $this->ttl          = 0;

        return true;
    }


    /**
     * parses the rdata of the Packet object
     *
     * @param Packet $packet a Packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     * @throws Exception
     */
    protected function rrSet(Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            //
            // expand the algorithm
            //
            $newOffset          = $packet->offset;
            $this->algorithm    = $packet->expandEx( $newOffset );
            $offset             = $newOffset - $packet->offset;

            //
            // unpack time, fudge and mac_size
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack(
                '@' . $offset . '/ntime_high/Ntime_low/nfudge/nmac_size', 
                $this->rdata
            );

            $this->time_signed  = $x['time_low'];
            $this->fudge        = $x['fudge'];
            $this->mac_size     = $x['mac_size'];

            $offset += 10;

            //
            // copy out the mac
            //
            if ($this->mac_size > 0) {
            
                $this->mac = substr($this->rdata, $offset, $this->mac_size);
                $offset += $this->mac_size;
            }

            //
            // unpack the original id, error, and other_length values
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack(
                '@' . $offset . '/noriginal_id/nerror/nother_length', 
                $this->rdata
            );
        
            $this->original_id  = $x['original_id'];
            $this->error        = $x['error'];
            $this->other_length = $x['other_length'];

            //
            // the only time there is actually any "other data", is when there's
            // a BADTIME error code.
            //
            // The other length should be 6, and the other data field includes the
            // servers current time - per RFC 2845 section 4.5.2
            //
            if ($this->error == Lookups::RCODE_BADTIME) {

                if ($this->other_length != 6) {

                    return false;
                }

                //
                // other data is a 48bit timestamp
                //
                /** @noinspection SpellCheckingInspection */
                $x = unpack(
                    'nhigh/nlow', 
                    substr($this->rdata, $offset + 6, $this->other_length)
                );
                $this->other_data = $x['low'];
            }

            return true;
        }

        return false;
    }


    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet $packet a Packet packet use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     * @throws Exception
     */
    protected function rrGet(Packet $packet) : ?string
    {
        if (strlen($this->key) > 0) {

            //
            // create a new packet for the signature-
            //
            $new_packet = new RequestPacket('example.com', 'SOA', 'IN');

            //
            // copy the packet data over
            //
            $new_packet->copy($packet);

            //
            // remove the TSIG object from the additional list
            //
            array_pop($new_packet->additional);
            $new_packet->header->arcount = count($new_packet->additional);

            //
            // copy out the data
            //
            $sig_data = $new_packet->get();

            //
            // add the name without compressing
            //
            $sig_data .= Packet::pack($this->name);

            //
            // add the class and TTL
            //
            $sig_data .= pack(
                'nN', Lookups::$classes_by_name[$this->class], $this->ttl
            );

            //
            // add the algorithm name without compression
            //
            $sig_data .= Packet::pack(strtolower($this->algorithm));

            //
            // add the rest of the values
            //
            /** @noinspection SpellCheckingInspection */
            $sig_data .= pack(
                'nNnnn', 0, $this->time_signed, $this->fudge, 
                $this->error, $this->other_length
            );
            if ($this->other_length > 0) {

                $sig_data .= pack('nN', 0, $this->other_data);
            }

            //
            // sign the data
            //
            $this->mac = $this->_signHMAC(
                $sig_data, base64_decode($this->key), $this->algorithm
            );
            $this->mac_size = strlen($this->mac);

            //
            // compress the algorithm
            //
            $data = Packet::pack(strtolower($this->algorithm));

            //
            // pack the time, fudge and mac size
            //
            $data .= pack(
                'nNnn', 0, $this->time_signed, $this->fudge, $this->mac_size
            );
            $data .= $this->mac;

            //
            // check the error and other_length
            //
            if ($this->error == Lookups::RCODE_BADTIME) {

                $this->other_length = strlen($this->other_data);
                if ($this->other_length != 6) {

                    return null;
                }
            } else {

                $this->other_length = 0;
                $this->other_data = '';
            }

            //
            // pack the id, error and other_length
            //
            $data .= pack(
                'nnn', $packet->header->id, $this->error, $this->other_length
            );
            if ($this->other_length > 0) {

                $data .= pack('nN', 0, $this->other_data);
            }

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }

    /**
     * signs the given data with the given key, and returns the result
     *
     * @param string $data      the data to sign
     * @param string $key       key to use for signing
     * @param string $algorithm the algorithm to use; defaults to MD5
     *
     * @return string the signed digest
     * @throws Exception
     * @access private
     *
     */
    private function _signHMAC( string $data, string $key, string $algorithm = self::HMAC_MD5) : string
    {
        if (!isset(self::$hash_algorithms[$algorithm])) {

            throw new Exception(
                'invalid or unsupported algorithm',
                Lookups::E_PARSE_ERROR
            );
        }

        return hash_hmac(self::$hash_algorithms[$algorithm], $data, $key, true);
    }


}
