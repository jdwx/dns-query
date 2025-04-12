<?php /** @noinspection PhpClassNamingConventionInspection */


/** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


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


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\Packet;
use JetBrains\PhpStorm\ArrayShape;


/**
 * This is the base class for DNS Resource Records
 *
 * Each resource record type (defined in RR/*.php) extends this class for
 * base functionality.
 *
 * This class handles parsing and constructing the common parts of the DNS
 * resource records, while the RR specific functionality is handled in each
 * child class.
 *
 * DNS resource record format - RFC1035 section 4.1.3
 *
 *      0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                                               |
 *    /                                               /
 *    /                      NAME                     /
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                      TYPE                     |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                     CLASS                     |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                      TTL                      |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                   rdLength                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--|
 *    /                     RData                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
abstract class RR {


    /** @var string The name portion of the resource record */
    public string $name;

    /** @var string The resource record type */
    public string $type;

    /** @var string The resource record class */
    public string $class;

    /** @var int The time to live for this resource record */
    public int $ttl;

    /** @var int The length of the rdata field */
    public int $rdLength = 0;

    /** @var string The resource record specific data as a packed binary string */
    public string $rdata = '';


    /**
     * Constructor - builds a new RR object
     *
     * @param ?Packet $i_packet a Packet or null to create an empty object
     *
     * @param array<string, mixed>|null $i_rr an array with RR parse values or null to
     *                                 create an empty object
     *
     * @throws Exception
     */
    public function __construct( ?Packet $i_packet = null, ?array $i_rr = null ) {
        if ( ( ! is_null( $i_packet ) ) && ( ! is_null( $i_rr ) ) ) {
            if ( ! $this->set( $i_packet, $i_rr ) ) {
                throw new Exception(
                    'failed to generate resource record',
                    Lookups::E_RR_INVALID
                );
            }
        } else {
            $class = Lookups::$rrTypesClassToId[ static::class ];
            /** @phpstan-ignore function.alreadyNarrowedType */
            if ( is_int( $class ) ) {
                $this->type = Lookups::$rrTypesById[ $class ];
            }

            $this->class = 'IN';
            $this->ttl = 86400;
        }
    }


    /**
     * parses a standard RR format lines, as defined by rfc1035 (kinda)
     *
     * In our implementation, the domain *must* be specified. Format must be:
     *
     *        <name> [<ttl>] [<class>] <type> <rdata>
     * or
     *        <name> [<class>] [<ttl>] <type> <rdata>
     *
     * name, title, class and type are parsed by this function, rdata is passed
     * to the RR specific classes for parsing.
     *
     * @param string $line a standard DNS config line
     *
     * @return RR       returns a new RR object for the given RR
     * @throws Exception
     */
    public static function fromString( string $line ) : RR {

        if ( strlen( $line ) == 0 ) {
            throw new Exception(
                'empty config line provided.',
                Lookups::E_PARSE_ERROR
            );
        }

        $type = '';
        $class = 'IN';
        $ttl = 86400;

        # Split the line by spaces.
        $values = preg_split( '/\s+/', $line );
        if ( count( $values ) < 3 ) {

            throw new Exception(
                'failed to parse config: minimum of name, type and rdata required.',
                Lookups::E_PARSE_ERROR
            );
        }

        # Assume the first value is the name.
        $name = trim( strtolower( array_shift( $values ) ), '.' );

        # The next value is either a TTL, Class or Type.
        foreach ( $values as $value ) {

            switch ( true ) {
                case is_numeric( $value ):
                    # This is here because of a bug in is_numeric() in certain versions of
                    # PHP on Windows.
                    # Unable to verify, but it doesn't hurt anything. - JDWX 2025-04-12
                    /** @phpstan-ignore identical.alwaysFalse */
                case ( $value === 0 ):

                    $ttl = (int) array_shift( $values );
                    break;

                case isset( Lookups::$classesByName[ strtoupper( $value ) ] ):

                    $class = strtoupper( array_shift( $values ) );
                    break;

                case isset( Lookups::$rrTypesByName[ strtoupper( $value ) ] ):

                    $type = strtoupper( array_shift( $values ) );
                    break 2;

                default:

                    throw new Exception(
                        'invalid config line provided: unknown file: ' . $value,
                        Lookups::E_PARSE_ERROR
                    );
            }
        }

        # Look up the class to use.
        $className = Lookups::$rrTypesIdToClass[ Lookups::$rrTypesByName[ $type ] ];

        if ( ! class_exists( $className ) ) {
            throw new Exception(
                'un-implemented resource record type: ' . $type,
                Lookups::E_RR_INVALID
            );
        }

        $obj = new $className();
        if ( ! $obj instanceof RR ) {
            throw new Exception(
                'failed to create new RR record for type: ' . $type,
                Lookups::E_RR_INVALID
            );
        }

        # Set the parsed values.
        $obj->name = $name;
        $obj->class = $class;
        $obj->ttl = $ttl;

        # Parse the rdata.
        if ( $obj->rrFromString( $values ) === false ) {

            throw new Exception(
                'failed to parse rdata for config: ' . $line,
                Lookups::E_PARSE_ERROR
            );
        }

        return $obj;
    }


    /**
     * parses a binary packet, and returns the appropriate RR object,
     * based on the RR type of the binary content.
     *
     * @param Packet $packet a Packet used for decompressing names
     *
     * @return ?RR                   returns a new RR object for
     *                                 the given RR or null if no record was created
     * @throws Exception
     */
    public static function parse( Packet $packet ) : ?RR {
        $object = [];

        # Expand the name.
        $object[ 'name' ] = $packet->expand( $packet->offset );
        if ( is_null( $object[ 'name' ] ) ) {

            throw new Exception(
                'failed to parse resource record: failed to expand name.',
                Lookups::E_PARSE_ERROR
            );
        }
        if ( $packet->rdLength < ( $packet->offset + 10 ) ) {

            throw new Exception(
                'failed to parse resource record: packet too small.',
                Lookups::E_PARSE_ERROR
            );
        }

        # Unpack the RR details.
        $object[ 'type' ] = ord( $packet->rdata[ $packet->offset++ ] ) << 8 |
            ord( $packet->rdata[ $packet->offset++ ] );
        $object[ 'class' ] = ord( $packet->rdata[ $packet->offset++ ] ) << 8 |
            ord( $packet->rdata[ $packet->offset++ ] );

        $object[ 'ttl' ] = ord( $packet->rdata[ $packet->offset++ ] ) << 24 |
            ord( $packet->rdata[ $packet->offset++ ] ) << 16 |
            ord( $packet->rdata[ $packet->offset++ ] ) << 8 |
            ord( $packet->rdata[ $packet->offset++ ] );

        $object[ 'rdlength' ] = ord( $packet->rdata[ $packet->offset++ ] ) << 8 |
            ord( $packet->rdata[ $packet->offset++ ] );

        if ( $packet->rdLength < ( $packet->offset + $object[ 'rdlength' ] ) ) {
            return null;
        }

        # Lookup the class to use.
        $class = Lookups::$rrTypesIdToClass[ $object[ 'type' ] ];

        if ( ! class_exists( $class ) ) {
            throw new Exception(
                'unimplemented resource record type: ' . $object[ 'type' ],
                Lookups::E_RR_INVALID
            );
        }

        $obj = new $class( $packet, $object );
        if ( $obj instanceof RR ) {
            $packet->offset += $object[ 'rdlength' ];
        }
        return $obj;

    }


    /**
     * magic __toString() method to return the RR object as a string
     *
     * @return string
     */
    public function __toString() {
        return $this->name . '. ' . $this->ttl . ' ' . $this->class .
            ' ' . $this->type . ' ' . $this->rrToString();
    }


    /**
     * return the same data as __toString(), but as an array, so each value can be
     * used without having to parse the string.
     *
     * @return array<string, int|string>
     */
    #[ArrayShape(
        [ 'name' => 'string', 'ttl' => 'int', 'class' => 'string',
            'type' => 'mixed|string', 'rdata' => 'string' ]
    )]
    public function asArray() : array {
        return [
            'name' => $this->name,
            'ttl' => $this->ttl,
            'class' => $this->class,
            'type' => $this->type,
            'rdata' => $this->rrToString(),
        ];
    }


    /**
     * cleans up some RR data
     *
     * @param string $i_data the text string to clean
     *
     * @return string returns the cleaned string
     */
    public function cleanString( string $i_data ) : string {
        return strtolower( rtrim( $i_data, '.' ) );
    }


    /**
     * returns a binary packed DNS RR object
     *
     * @param Packet $i_packet a Packet used for compressing names
     *
     * @return string
     *
     * @throws Exception
     */
    public function get( Packet $i_packet ) : string {
        $rdata = '';

        # Pack the name.
        $data = $i_packet->compress( $this->name, $i_packet->offset );

        # Pack the main values.
        if ( $this->type == 'OPT' ) {

            # Pre-build the TTL value.
            assert( $this instanceof OPT );
            $this->preBuild();

            # The class value is different for OPT types.
            $data .= pack(
                'nnN',
                Lookups::$rrTypesByName[ $this->type ],
                $this->class,
                $this->ttl
            );
        } else {

            $data .= pack(
                'nnN',
                Lookups::$rrTypesByName[ $this->type ],
                Lookups::$classesByName[ $this->class ],
                $this->ttl
            );
        }

        # Increase the offset, and allow for the rdLength.
        $i_packet->offset += 10;

        # Get the RR specific details.
        if ( $this->rdLength != -1 ) {
            $rdata = $this->rrGetEx( $i_packet );
        }

        # Add the RR.
        $data .= pack( 'n', strlen( $rdata ) ) . $rdata;

        return $data;
    }


    /** Get the rdata in the format used by PHP's dns_get_record() function.
     * @return array<string, mixed> The rdata or an empty array if this record type isn't support by dns_get_record().
     */
    public function getPHPRData() : array {
        return [];
    }


    /** Get the whole record in the format used by PHP's dns_get_record() function.
     *
     * See the caveats in getPHPRData() about RR-specific data.
     *
     * @return array<string, mixed> The record as an array.
     */
    public function getPHPRecord() : array {
        return array_merge( [
            'host' => $this->name,
            'class' => 'IN',
            'ttl' => $this->ttl,
            'type' => $this->type,
        ], $this->getPHPRData() );
    }


    /**
     * builds a new RR object
     *
     * @param Packet $i_packet (output) a Packet or null to create an empty object
     * @param array<string, mixed> $i_rr an array with RR parse values or null to
     *                                 create an empty object
     *
     * @return bool
     * @throws Exception
     */
    public function set( Packet $i_packet, array $i_rr ) : bool {
        $this->name = $i_rr[ 'name' ];
        $this->type = Lookups::$rrTypesById[ $i_rr[ 'type' ] ];

        # For RR OPT (41), the class value includes the requestor's UDP payload size,
        # and not a class value
        if ( $this->type == 'OPT' ) {
            $this->class = (string) $i_rr[ 'class' ];
        } else {
            $this->class = Lookups::$classesById[ $i_rr[ 'class' ] ];
        }

        $this->ttl = $i_rr[ 'ttl' ];
        $this->rdLength = $i_rr[ 'rdlength' ];
        $this->rdata = substr( $i_packet->rdata, $i_packet->offset, $i_rr[ 'rdlength' ] );

        return $this->rrSet( $i_packet );
    }


    /**
     * Build an array of strings from an array of chunks of text split by spaces.
     *
     * @param string[] $i_chunks an array of chunks of text split by spaces
     *
     * @return string[]
     */
    protected function buildString( array $i_chunks ) : array {
        $data = [];
        $count = 0;
        $in = false;

        foreach ( $i_chunks as $chunk ) {

            $chunk = trim( $chunk );
            if ( strlen( $chunk ) == 0 ) {
                continue;
            }

            if ( ( $chunk[ 0 ] == '"' )
                && ( $chunk[ strlen( $chunk ) - 1 ] == '"' )
                && ( $chunk[ strlen( $chunk ) - 2 ] != '\\' )
            ) {

                $data[ $count ] = $chunk;
                ++$count;
                $in = false;

            } elseif ( $chunk[ 0 ] == '"' ) {

                $data[ $count ] = $chunk;
                $in = true;

            } elseif ( ( $chunk[ strlen( $chunk ) - 1 ] == '"' )
                && ( $chunk[ strlen( $chunk ) - 2 ] != '\\' )
            ) {

                $data[ $count ] .= ' ' . $chunk;
                ++$count;
                $in = false;

            } elseif ( $in ) {
                $data[ $count ] .= ' ' . $chunk;
            } else {
                $data[ $count++ ] = $chunk;
            }
        }

        foreach ( $data as $index => $string ) {
            $data[ $index ] = str_replace( '\"', '"', trim( $string, '"' ) );
        }

        return $data;
    }


    /**
     * return a formatted string; if a string has spaces in it, then return
     * it with double quotes around it, otherwise, return it as it was passed in.
     *
     * @param string $i_str the string to format
     *
     * @return string
     */
    protected function formatString( string $i_str ) : string {
        return '"' . str_replace( '"', '\"', trim( $i_str, '"' ) ) . '"';
    }


    /**
     * Parse the rdata portion from a standard DNS config line
     *
     * @param string[] $i_rData a string split line of values for the rdata
     *
     * @return bool
     */
    abstract protected function rrFromString( array $i_rData ) : bool;


    /**
     * Returns the rdata portion of the RR, advancing the referenced
     * packet offset by the correct size.
     *
     * @param Packet $i_packet Packet to use for compressed names
     *
     * @return ?string A binary packed string, or null on failure
     * @throws Exception
     */
    abstract protected function rrGet( Packet $i_packet ) : ?string;


    /**
     * returns a binary packet DNS RR object and throws an
     * exception if it fails
     *
     * @param Packet $i_packet Packet to use for compressed names
     *
     *
     * @return string       A binary packed string
     *
     * @throws Exception
     */
    protected function rrGetEx( Packet $i_packet ) : string {
        $str = $this->rrGet( $i_packet );
        if ( is_string( $str ) ) {
            return $str;
        }
        throw new Exception( 'getting type-specific RDATA failed' );
    }


    /**
     * Parse the rdata from the current position of the provided
     * Packet object, advancing the packet's internal offset accordingly.
     *
     * @param Packet $i_packet a Packet to parse the RR from
     *
     * @return bool
     * @throws Exception
     */
    abstract protected function rrSet( Packet $i_packet ) : bool;


    /**
     * Return the rdata portion of the packet as a string.
     *
     * This is *not* the same as the __toString() magic method, which
     * returns the whole RR.
     *
     * @return  string The rdata portion of the packet as a string.
     */
    abstract protected function rrToString() : string;


}
