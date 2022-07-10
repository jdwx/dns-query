<?php


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
class IPSECKEY extends RR
{
    public const GATEWAY_TYPE_NONE = 0;
    public const GATEWAY_TYPE_IPV4 = 1;
    public const GATEWAY_TYPE_IPV6 = 2;
    public const GATEWAY_TYPE_DOMAIN = 3;

    public const ALGORITHM_NONE = 0;
    public const ALGORITHM_DSA = 1;
    public const ALGORITHM_RSA = 2;

    /*
     * Precedence (used the same way as a preference field)
     */
    public int $precedence;

    /*
     * Gateway type - specifies the format of the gateway information
     * This can be either:
     *
     *  0    No Gateway
     *  1    IPv4 address
     *  2    IPV6 address
     *  3    wire-encoded domain name (not compressed)
     *
     */
    public int $gatewayType;

    /*
     * The algorithm used
     *
     * This can be:
     *
     *  0    No key is present
     *  1    DSA key is present
     *  2    RSA key is present
     *
     */
    public int $algorithm;

    /*
     * The gateway information
     */
    public string $gateway;

    /*
     * the public key
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
        $out = $this->precedence . ' ' . $this->gatewayType . ' ' .
            $this->algorithm . ' ';
        
        switch($this->gatewayType) {
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
        // load the data
        //
        $precedence     = (int) array_shift( $rdata );
        $gateway_type   = (int) array_shift( $rdata );
        $algorithm      = (int) array_shift( $rdata );
        $gateway        = trim(strtolower(trim(array_shift($rdata))), '.');
        $key            = array_shift($rdata) ?? "";
        
        //
        // validate it
        //
        switch($gateway_type) {
        case self::GATEWAY_TYPE_NONE:
            $gateway = '';
            break;

        case self::GATEWAY_TYPE_IPV4:
            if ( ! Net_DNS2::isIPv4( $gateway ) ) {
                return false;
            }
            break;

        case self::GATEWAY_TYPE_IPV6:
            if ( ! Net_DNS2::isIPv6( $gateway ) ) {
                return false;
            }
            break;

        case self::GATEWAY_TYPE_DOMAIN:
            // do nothing
            break;

        default:
            return false;
        }
        
        //
        // check the algorithm and key
        //
        switch($algorithm) {
        case self::ALGORITHM_NONE:
            $key = '';
            break;

        case self::ALGORITHM_DSA:
        case self::ALGORITHM_RSA:
            // do nothing
            break;

        default:
            return false;
        }

        //
        // store the values
        //
        $this->precedence   = $precedence;
        $this->gatewayType = $gateway_type;
        $this->algorithm    = $algorithm;
        $this->gateway      = $gateway;
        $this->key          = $key;

        return true;
    }


    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet $packet a Packet to parse the RR from
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
            // parse off the precedence, gateway type and algorithm
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('Cprecedence/Cgateway_type/Calgorithm', $this->rdata);

            $this->precedence   = $x['precedence'];
            $this->gatewayType = $x['gateway_type'];
            $this->algorithm    = $x['algorithm'];

            $offset = 3;

            //
            // extract the gateway based on the type
            //
            switch($this->gatewayType) {
            case self::GATEWAY_TYPE_NONE:
                $this->gateway = '';
                break;

            case self::GATEWAY_TYPE_IPV4:
                $this->gateway = inet_ntop(substr($this->rdata, $offset, 4));
                $offset += 4;
                break;

            case self::GATEWAY_TYPE_IPV6:
                $ip = unpack('n8', substr($this->rdata, $offset, 16));
                if (count($ip) == 8) {

                    $this->gateway = vsprintf('%x:%x:%x:%x:%x:%x:%x:%x', $ip);
                    $offset += 16;
                } else {

                    return false;
                }
                break;

            case self::GATEWAY_TYPE_DOMAIN:
                $domainOffset = $offset + $packet->offset;
                $this->gateway = $packet->expandEx( $domainOffset );
                $offset = ($domainOffset - $packet->offset);
                break;

            default:
                return false;
            }

            //
            // extract the key
            //
            switch($this->algorithm) {
            case self::ALGORITHM_NONE:
                $this->key = '';
                break;
                
            case self::ALGORITHM_DSA:
            case self::ALGORITHM_RSA:
                $this->key = base64_encode(substr($this->rdata, $offset));
                break;
             
            default:
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet $packet a Packet to use for compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        //
        // pack the precedence, gateway type and algorithm
        //
        $data = pack(
            'CCC', $this->precedence, $this->gatewayType, $this->algorithm
        );

        //
        // add the gateway based on the type
        //
        switch($this->gatewayType) {
        case self::GATEWAY_TYPE_NONE:
            // add nothing
            break;
        
        case self::GATEWAY_TYPE_IPV4:
        case self::GATEWAY_TYPE_IPV6:
            $data .= inet_pton($this->gateway);
            break;
            
        case self::GATEWAY_TYPE_DOMAIN:
            $data .= chr(strlen($this->gateway))  . $this->gateway;
            break;
            
        default:
            return null;
        }

        //
        // add the key if there's one specified
        //
        switch($this->algorithm) {
        case self::ALGORITHM_NONE:
            // add nothing
            break;
            
        case self::ALGORITHM_DSA:
        case self::ALGORITHM_RSA:
            $data .= base64_decode($this->key);
            break;
            
        default:
            return null;
        }

        $packet->offset += strlen($data);
        
        return $data;
    }
}
