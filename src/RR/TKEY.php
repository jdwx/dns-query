<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Net_DNS2;
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
class TKEY extends RR
{
    public string $algorithm;
    public string $inception;
    public string $expiration;
    public int $mode;
    public int $error;
    public int $keySize;
    public string $keyData;
    public int $otherSize;
    public string $otherData;

    /*
     * TSIG Modes
     */
    public const TSIG_MODE_RES = 0;
    public const TSIG_MODE_SERV_ASSIGN = 1;
    public const TSIG_MODE_DH = 2;
    public const TSIG_MODE_GSS_API = 3;
    public const TSIG_MODE_RESV_ASSIGN = 4;
    public const TSIG_MODE_KEY_DELE = 5;

    /*
     * map the mod IDs to names so we can validate
     */
    public array $tsig_mode_id_to_name = [

        self::TSIG_MODE_RES           => 'Reserved',
        self::TSIG_MODE_SERV_ASSIGN   => 'Server Assignment',
        self::TSIG_MODE_DH            => 'Diffie-Hellman',
        self::TSIG_MODE_GSS_API       => 'GSS-API',
        self::TSIG_MODE_RESV_ASSIGN   => 'Resolver Assignment',
        self::TSIG_MODE_KEY_DELE      => 'Key Deletion'
    ];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        $out = $this->cleanString($this->algorithm) . '. ' . $this->mode;
        if ($this->keySize > 0) {

            $out .= ' ' . trim($this->keyData, '.') . '.';
        } else {

            $out .= ' .';
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
        // data passed in is assumed: <algorithm> <mode> <key>
        //
        $this->algorithm    = $this->cleanString(array_shift($rdata));
        $this->mode         = (int) array_shift($rdata);
        $this->keyData     = trim(array_shift($rdata), '.');

        //
        // the rest of the data is set manually
        //
        $this->inception    = (string) time();
        $this->expiration   = (string) ( time() + 86400 ); // 1 day
        $this->error        = 0;
        $this->keySize     = strlen($this->keyData);
        $this->otherSize   = 0;
        $this->otherData   = '';

        return true;
    }


    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet $packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {
        
            //
            // expand the algorithm
            //
            $offset = $packet->offset;
            $this->algorithm = $packet->expandEx( $offset );
            
            //
            // unpack inception, expiration, mode, error and key size
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack(
                '@' . $offset . '/Ninception/Nexpiration/nmode/nerror/nkey_size', 
                $packet->rdata
            );

            $this->inception    = Net_DNS2::expandUint32($x['inception']);
            $this->expiration   = Net_DNS2::expandUint32($x['expiration']);
            $this->mode         = $x['mode'];
            $this->error        = $x['error'];
            $this->keySize     = $x['key_size'];

            $offset += 14;

            //
            // if key_size > 0, then copy out the key
            //
            if ($this->keySize > 0) {

                $this->keyData = substr($packet->rdata, $offset, $this->keySize);
                $offset += $this->keySize;
            }

            //
            // unpack the other length
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('@' . $offset . '/nother_size', $packet->rdata);
            
            $this->otherSize = $x['other_size'];
            $offset += 2;

            //
            // if other_size > 0, then copy out the data
            //
            if ($this->otherSize > 0) {

                $this->otherData = substr(
                    $packet->rdata, $offset, $this->otherSize
                );
            }

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet &$packet a Net_DNS2_Packet packet to use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->algorithm) > 0) {

            //
            // make sure the size values are correct
            //
            $this->keySize     = strlen($this->keyData);
            $this->otherSize   = strlen($this->otherData);

            //
            // add the algorithm without compression
            //
            $data = Packet::pack($this->algorithm);

            //
            // pack in the inception, expiration, mode, error and key size
            //
            /** @noinspection SpellCheckingInspection */
            $data .= pack(
                'NNnnn', $this->inception, $this->expiration, 
                $this->mode, 0, $this->keySize
            );

            //
            // if the key_size > 0, then add the key
            //
            if ($this->keySize > 0) {
            
                $data .= $this->keyData;
            }

            //
            // pack in the other size
            //
            $data .= pack('n', $this->otherSize);
            if ($this->otherSize > 0) {

                $data .= $this->otherData;
            }

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
