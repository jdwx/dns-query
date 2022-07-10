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
    public string $mode;
    public int $error;
    public int $key_size;
    public string $key_data;
    public int $other_size;
    public string $other_data;

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
        if ($this->key_size > 0) {

            $out .= ' ' . trim($this->key_data, '.') . '.';
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
        $this->mode         = array_shift($rdata);
        $this->key_data     = trim(array_shift($rdata), '.');

        //
        // the rest of the data is set manually
        //
        $this->inception    = (string) time();
        $this->expiration   = (string) ( time() + 86400 ); // 1 day
        $this->error        = 0;
        $this->key_size     = strlen($this->key_data);
        $this->other_size   = 0;
        $this->other_data   = '';

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
            $this->key_size     = $x['key_size'];

            $offset += 14;

            //
            // if key_size > 0, then copy out the key
            //
            if ($this->key_size > 0) {

                $this->key_data = substr($packet->rdata, $offset, $this->key_size);
                $offset += $this->key_size;
            }

            //
            // unpack the other length
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('@' . $offset . '/nother_size', $packet->rdata);
            
            $this->other_size = $x['other_size'];
            $offset += 2;

            //
            // if other_size > 0, then copy out the data
            //
            if ($this->other_size > 0) {

                $this->other_data = substr(
                    $packet->rdata, $offset, $this->other_size
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
            $this->key_size     = strlen($this->key_data);
            $this->other_size   = strlen($this->other_data);

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
                $this->mode, 0, $this->key_size
            );

            //
            // if the key_size > 0, then add the key
            //
            if ($this->key_size > 0) {
            
                $data .= $this->key_data;
            }

            //
            // pack in the other size
            //
            $data .= pack('n', $this->other_size);
            if ($this->other_size > 0) {

                $data .= $this->other_data;
            }

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
