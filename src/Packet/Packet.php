<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Packet;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Question;
use JDWX\DNSQuery\RR\RR;
use Stringable;


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
 * This file contains code based off the Net::DNS Perl module by Michael Fuhr.
 *
 * This is the copyright notice from the PERL Net::DNS module:
 *
 * Copyright (c) 1997-2000 Michael Fuhr.  All rights reserved.  This program is
 * free software; you can redistribute it and/or modify it under the same terms
 * as Perl itself.
 *
 */


/**
 * This is the base class that holds a standard DNS packet.
 *
 * The RequestPacket and ResponsePacket classes extend this
 * class.
 *
 */
class Packet implements Stringable {


    /** @var string Full binary data for this packet. */
    public string $rdata;

    /** @var int Length of the packet. */
    public int $rdLength;

    /** @var int Offset pointer used when building/parsing packets */
    public int $offset = 0;

    /** @var Header object with the DNS packet header */
    public Header $header;

    /** @var Question[] used as "zone" for updates per RFC2136 */
    public array $question = [];

    /** @var RR[] Answers used as "prerequisite" for updates per RFC2136 */
    public array $answer = [];

    /** @var RR[] Authority records used as "update" for updates per RFC2136 */
    public array $authority = [];

    /** @var RR[] Additional records used as "additional" for updates per RFC2136 */
    public array $additional = [];

    /** @var array<string, int> Map from names to integer offsets for compression */
    private array $compressed = [];


    /**
     * parses a domain label from a DNS Packet at the given offset
     *
     * @param Packet $packet Packet to look in for the domain name
     * @param int         &$offset (input/output) Offset into the given packet object
     *
     * @return ?string The domain name or null if it's invalid or not found.
     */
    public static function label( Packet $packet, int &$offset ) : ?string {

        if ( $packet->rdLength < ( $offset + 1 ) ) {

            return null;
        }

        $labelLen = ord( $packet->rdata[ $offset ] );
        ++$offset;

        if ( ( $labelLen + $offset ) > $packet->rdLength ) {

            $name = substr( $packet->rdata, $offset );
            $offset = $packet->rdLength;
        } else {

            $name = substr( $packet->rdata, $offset, $labelLen );
            $offset += $labelLen;
        }

        return $name;
    }


    /**
     * Write the name in DNS binary format.
     *
     * RFC 1035 specifies that names should be written as a series of labels
     * with a single null byte at the end.
     * Each label starts with its length, followed by the label itself.
     * This function converts a provided name to that format, but does not
     * perform any compression.
     *
     * @param string $name the name to be compressed
     *
     * @return string
     */
    public static function pack( string $name ) : string {
        $names = explode( '.', $name );
        $compName = '';

        while ( ! empty( $names ) ) {

            $first = array_shift( $names );
            $length = strlen( $first );

            $compName .= pack( 'Ca*', $length, $first );
        }

        $compName .= "\0";

        return $compName;
    }


    /**
     * Return a summary of the packet as a human-readable string
     *
     * Loosely inspired by the dig/drill format.
     *
     * @return string A summary of the packet
     */
    public function __toString() : string {
        $output = $this->header->__toString();

        foreach ( $this->question as $rr ) {
            $output .= $rr->__toString() . "\n";
        }
        foreach ( $this->answer as $rr ) {
            $output .= $rr->__toString() . "\n";
        }
        foreach ( $this->authority as $rr ) {
            $output .= $rr->__toString() . "\n";
        }
        foreach ( $this->additional as $rr ) {
            $output .= $rr->__toString() . "\n";
        }

        return $output;
    }


    /**
     * Apply standard DNS name compression on the given name and write
     * it to the packet at offset.
     *
     * This logic was based on the Net::DNS::Packet::dn_comp() function
     * by Michael Fuhr
     *
     * @param string $name Name to be compressed
     * @param int    &$offset Offset into the given packet object
     *
     * @return string
     */
    public function compress( string $name, int &$offset ) : string {
        # Use preg_split() rather than explode() so that we can use the negative lookbehind
        # to catch cases where we have escaped dots in strings.
        #
        # There are only a few cases like this; the rName in SOA for example.
        $names = str_replace( '\.', '.', preg_split( '/(?<!\\\)\./', $name ) );
        $compName = '';

        while ( ! empty( $names ) ) {

            $dName = join( '.', $names );

            if ( isset( $this->compressed[ $dName ] ) ) {

                $compName .= pack( 'n', 0xc000 | $this->compressed[ $dName ] );
                $offset += 2;

                break;
            }

            $this->compressed[ $dName ] = $offset;

            $first = array_shift( $names );

            $length = strlen( $first );
            if ( $length <= 0 ) {
                continue;
            }

            # Truncate the label. (See RFC1035 2.3.1.)
            if ( $length > 63 ) {
                $length = 63;
                $first = substr( $first, 0, $length );
            }

            $compName .= pack( 'Ca*', $length, $first );
            $offset += $length + 1;
        }

        if ( empty( $names ) ) {
            $compName .= pack( 'C', 0 );
            $offset++;
        }

        return $compName;
    }


    /**
     * Copy the contents of the given packet to the local packet object.
     *
     * This function intentionally ignores some packet data.
     *
     * @param Packet $i_packet DNS packet to copy the data from
     *
     * @return void
     */
    public function copy( Packet $i_packet ) : void {
        $this->header = $i_packet->header;
        $this->question = $i_packet->question;
        $this->answer = $i_packet->answer;
        $this->authority = $i_packet->authority;
        $this->additional = $i_packet->additional;
    }


    /**
     * expands the domain name stored at a given offset in a DNS Packet
     *
     * This logic was based on the Net::DNS::Packet::dn_expand() function
     * by Michael Fuhr
     *
     * @param int         &$io_offset (input/output) Offset into the given packet object
     * @param bool $i_escapeDotLiterals Escape periods in names
     *
     * @return ?string The domain name, or null if it's invalid or not found.
     */
    public function expand( int &$io_offset, bool $i_escapeDotLiterals = false ) : ?string {
        $name = '';

        while ( 1 ) {
            if ( $this->rdLength < ( $io_offset + 1 ) ) {
                return null;
            }

            $labelLen = ord( $this->rdata[ $io_offset ] );
            if ( $labelLen == 0 ) {

                ++$io_offset;
                break;

            } elseif ( ( $labelLen & 0xc0 ) == 0xc0 ) {
                if ( $this->rdLength < ( $io_offset + 2 ) ) {
                    return null;
                }

                $ptr = ord( $this->rdata[ $io_offset ] ) << 8 | ord( $this->rdata[ $io_offset + 1 ] );
                $ptr = $ptr & 0x3fff;

                $name2 = $this->expand( $ptr, $i_escapeDotLiterals );
                if ( is_null( $name2 ) ) {
                    return null;
                }

                $name .= $name2;
                $io_offset += 2;

                break;
            } else {
                ++$io_offset;

                if ( $this->rdLength < ( $io_offset + $labelLen ) ) {

                    return null;
                }

                $elem = substr( $this->rdata, $io_offset, $labelLen );

                # Escape literal dots in certain cases (like the SOA rName).
                if ( $i_escapeDotLiterals && ( str_contains( $elem, '.' ) ) ) {
                    $elem = str_replace( '.', '\.', $elem );
                }

                $name .= $elem . '.';
                $io_offset += $labelLen;
            }
        }

        return trim( $name, '.' );
    }


    /**
     *  expands the domain name stored at a given offset in this DNS Packet
     *  and throws an exception on failure (contrast static::expand()).
     *
     * @param int  &$io_offset (input/output) Offset into the given Packet object
     * @param bool $i_escapeDotLiterals if we should escape periods in names
     *
     * @return string the expanded domain name
     *
     * @throws Exception
     */
    public function expandEx( int &$io_offset, bool $i_escapeDotLiterals = false ) : string {
        $expand = $this->expand( $io_offset, $i_escapeDotLiterals );
        if ( is_string( $expand ) ) {
            return $expand;
        }
        throw new Exception( 'unable to expand domain in packet', Lookups::E_PARSE_ERROR );
    }


    /**
     * Return a full binary DNS packet
     *
     * @return string The binary packed DNS packet
     *
     * @throws Exception
     */
    public function get() : string {
        $data = $this->header->get( $this );

        foreach ( $this->question as $rr ) {
            $data .= $rr->get( $this );
        }

        foreach ( $this->answer as $rr ) {
            $data .= $rr->get( $this );
        }

        foreach ( $this->authority as $rr ) {
            $data .= $rr->get( $this );
        }

        foreach ( $this->additional as $rr ) {
            $data .= $rr->get( $this );
        }

        return $data;

    }


    /**
     * Parse a domain label from a DNS Packet at the given offset and
     * throws an exception on failure (contrast static::label()).
     *
     * @param int         &$io_offset (input/output) Offset into the given packet object
     *
     * @return string The parsed label
     *
     * @throws Exception If the label cannot be parsed
     */
    public function labelEx( int &$io_offset ) : string {
        $label = $this::label( $this, $io_offset );
        if ( is_string( $label ) ) {
            return $label;
        }
        throw new Exception( 'unable to parse label in packet', Lookups::E_PARSE_ERROR );
    }


    /**
     * resets the values in the current packet object
     *
     * @return bool
     * @throws Exception
     */
    public function reset() : bool {
        $this->header->id = Lookups::nextPacketId();
        $this->rdata = '';
        $this->rdLength = 0;
        $this->offset = 0;
        $this->answer = [];
        $this->authority = [];
        $this->additional = [];
        $this->compressed = [];

        return true;
    }


}
