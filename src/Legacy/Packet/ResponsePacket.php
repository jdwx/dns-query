<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\Packet;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\LegacyQuestion;
use JDWX\DNSQuery\Legacy\RR\AAAA;
use JDWX\DNSQuery\Legacy\RR\RR;
use JDWX\DNSQuery\RR\A;
use JDWX\DNSQuery\RR\NS;


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
 * This class handles building new DNS response packets; it parses binary packed
 * packets that come off the wire
 *
 */
class ResponsePacket extends Packet {


    /** @var string The name servers that this response came from. */
    public string $answerFrom;

    /** @var int The socket type the answer came from (TCP/UDP) */
    public int $answerSocketType;

    /** @var float The query response time in microseconds */
    public float $responseTime = 0.0;


    /**
     * Constructor - builds a new ResponsePacket object
     *
     * @param string $i_data binary DNS packet
     * @param int $i_size the length of the DNS packet
     *
     * @throws Exception
     */
    public function __construct( string $i_data, int $i_size ) {
        $this->set( $i_data, $i_size );
    }


    /** Returns a list of name server addresses that the response claims are
     * authoritative for the given name.
     *
     * The array returned has two levels.  The first is the name server name.
     * The second is an array containing any addresses found in the
     * "additional" section of the response.  This list may be empty if no
     * records of the given type are present in that section.
     *
     * @param ?string $i_name Name the returned addresses must be authoritative for.
     *                        If null, the name will be taken from the question section.
     * @param bool $i_useIPv4 If true, IPv4 addresses will be returned.
     * @param bool $i_useIPv6 If true, IPv6 addresses will be returned.
     *
     * @return string[][] List of name server names and their addresses.
     * @throws Exception
     */
    public function extractAuthoritativeAddresses( ?string $i_name = null, bool $i_useIPv4 = true,
                                                   bool    $i_useIPv6 = false ) : array {
        $names = $this->extractAuthoritativeNameServers( $i_name );
        if ( empty( $names ) ) {
            return [];
        }

        $out = [];
        foreach ( $names as $name ) {
            $out[ $name ] = [];
            foreach ( $this->additional as $rr ) {
                if ( $rr->name != $name ) {
                    continue;
                }
                if ( $i_useIPv4 && $rr instanceof A ) {
                    $out[ $name ][] = $rr->address;
                }
                if ( $i_useIPv6 && $rr instanceof AAAA ) {
                    $out[ $name ][] = $rr->address;
                }
            }
        }
        return $out;
    }


    /** Returns a list of name servers that the response claims are authoritative for the
     *  specified name.
     *
     * @param ?string $i_name The name that the name servers must be authoritative for.
     *                        If null, uses the name from the question section of the packet.
     * @return string[]       A list of any authoritative name servers found.
     * @throws Exception
     */
    public function extractAuthoritativeNameServers( ?string $i_name = null ) : array {

        if ( ! is_string( $i_name ) ) {
            if ( 1 !== count( $this->question ) ) {
                throw new Exception(
                    'There must be exactly one question in the response to use the default name.'
                );
            }
            $i_name = $this->question[ 0 ]->qName;
        }

        $nameList = explode( '.', $i_name );
        $newNameServers = [];
        foreach ( $this->authority as $rr ) {
            if ( ! $rr instanceof NS ) {
                continue;
            }
            $authList = explode( '.', $rr->name );
            $len = count( $authList );
            $check = array_slice( $nameList, -$len );
            if ( $authList == $check ) {
                $newNameServers[] = $rr->nsdName;
            }
        }
        return $newNameServers;
    }


    /**
     * builds a new ResponsePacket object
     *
     * @param string $i_data binary DNS packet
     * @param int $i_size the length of the DNS packet
     *
     * @return bool
     * @throws Exception
     */
    public function set( string $i_data, int $i_size ) : bool {

        # Store the full packet.
        $this->rdata = $i_data;
        $this->rdLength = $i_size;

        # Parse the header.

        # We don't bother checking the size yet, because the first thing the
        # Header class does is check the size and throw and exception if it's
        # invalid.
        #
        # We also don't need to worry about checking to see if the header is
        # null or not, since the Header() constructor will throw an
        # exception if the packet is invalid.
        $this->header = new Header( $this );

        # If the truncation bit is set, then just return right here, because the
        # rest of the packet is probably empty; and there's no point in processing
        # anything else.
        if ( $this->header->tc == 1 ) {
            return false;
        }

        # Parse the questions.
        for ( $ii = 0 ; $ii < $this->header->qdCount ; ++$ii ) {
            $this->question[ $ii ] = new LegacyQuestion( $this );
        }

        # Parse the answers.
        for ( $ii = 0 ; $ii < $this->header->anCount ; ++$ii ) {
            $rr = RR::parse( $this );
            if ( ! is_null( $rr ) ) {

                $this->answer[] = $rr;
            }
        }

        # Parse the authority section.
        for ( $ii = 0 ; $ii < $this->header->nsCount ; ++$ii ) {
            $rr = RR::parse( $this );
            if ( ! is_null( $rr ) ) {

                $this->authority[] = $rr;
            }
        }

        # Parse the additional section.
        for ( $ii = 0 ; $ii < $this->header->arCount ; ++$ii ) {
            $rr = RR::parse( $this );
            if ( ! is_null( $rr ) ) {

                $this->additional[] = $rr;
            }
        }

        return true;
    }


}
