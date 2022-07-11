<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


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
abstract class RR
{
    /*
     * The name of the resource record
     */
    public string $name;

    /*
     * The resource record type
     */
    public string $type;

    /*
     * The resource record class
     */
    public string $class;

    /*
     * The time to live for this resource record
     */
    public int $ttl;

    /*
     * The length of the rdata field
     */
    public int $rdLength = 0;

    /*
     * The resource record specific data as a packed binary string
     */
    public string $rdata = '';


    /**
     * Constructor - builds a new RR object
     *
     * @param ?Packet $packet a Packet or null to create an empty object
     *
     * @param ?array           $rr      an array with RR parse values or null to
     *                                 create an empty object
     *
     * @throws Exception
     * @access public
     *
     */
    public function __construct(Packet $packet = null, array $rr = null)
    {
        if ( (!is_null($packet)) && (!is_null($rr)) ) {

            if ( ! $this->set( $packet, $rr ) ) {

                throw new Exception(
                    'failed to generate resource record',
                    Lookups::E_RR_INVALID
                );
            }
        } else {

            $class = Lookups::$rr_types_class_to_id[get_class($this)];
            if (isset($class)) {

                $this->type = Lookups::$rr_types_by_id[$class];
            }

            $this->class    = 'IN';
            $this->ttl      = 86400;
        }
    }

    /**
     * magic __toString() method to return the RR object as a string
     *
     * @return string
     * @access public
     *
     */
    public function __toString()
    {
        return $this->name . '. ' . $this->ttl . ' ' . $this->class .
            ' ' . $this->type . ' ' . $this->rrToString();
    }


    /**
     * abstract definition - method to return a RR as a string; not to 
     * be confused with the __toString() magic method.
     *
     * @return string
     * @access protected
     *
     */
    abstract protected function rrToString() : string;

    /**
     * abstract definition - parses a RR from a standard DNS config line
     *
     * @param array $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    abstract protected function rrFromString(array $rdata) : bool;

    /**
     * abstract definition - sets an RR from a Packet object
     *
     * @param Packet $packet a Packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    abstract protected function rrSet( Packet $packet) : bool;

    /**
     * abstract definition - returns a binary packet DNS RR object
     *
     * @param Packet $packet a Packet to use for compressed names
     *
     *
     * @return ?string                   either returns a binary packed string or
     *                                 null on failure
     * @access protected
     *
     */
    abstract protected function rrGet(Packet $packet) : ?string;


    /**
     * returns a binary packet DNS RR object and throws an
     * exception if it fails
     *
     * @param Packet $packet a Packet to use for compressed names
     *
     *
     * @return string                   either returns a binary packed string
     * @access protected
     *
     * @throws Exception
     */
    protected function rrGetEx( Packet $packet ) : string {
        $x = $this->rrGet( $packet );
        if ( is_string( $x ) ) {
            return $x;
        }
        throw new Exception( "getting type-specific RDATA failed" );
    }


    /**
     * return the same data as __toString(), but as an array, so each value can be 
     * used without having to parse the string.
     *
     * @return array
     * @access public
     *
     */
    #[ArrayShape( [ 'name' => "string", 'ttl' => "int", 'class' => "string", 'type' => "mixed|string", 'rdata' => "string" ] )]
    public function asArray() : array
    {
        return [
            'name'  => $this->name,
            'ttl'   => $this->ttl,
            'class' => $this->class,
            'type'  => $this->type,
            'rdata' => $this->rrToString()
        ];
    }

    /**
     * return a formatted string; if a string has spaces in it, then return 
     * it with double quotes around it, otherwise, return it as it was passed in.
     *
     * @param string $string the string to format
     *
     * @return string
     * @access protected
     *
     */
    protected function formatString( string $string ) : string
    {
        return '"' . str_replace('"', '\"', trim($string, '"')) . '"';
    }
    
    /**
     * builds an array of strings from an array of chunks of text split by spaces
     *
     * @param array $chunks an array of chunks of text split by spaces
     *
     * @return array
     * @access protected
     *
     */
    protected function buildString(array $chunks) : array
    {
        $data = [];
        $c = 0;
        $in = false;

        foreach ($chunks as $r) {

            $r = trim($r);
            if (strlen($r) == 0) {
                continue;
            }

            if ( ($r[0] == '"')
                && ($r[strlen($r) - 1] == '"')
                && ($r[strlen($r) - 2] != '\\')
            ) {

                $data[$c] = $r;
                ++$c;
                $in = false;

            } elseif ($r[0] == '"') {

                $data[$c] = $r;
                $in = true;

            } elseif ( ($r[strlen($r) - 1] == '"')
                && ($r[strlen($r) - 2] != '\\')
            ) {
            
                $data[$c] .= ' ' . $r;
                ++$c;  
                $in = false;

            } elseif ( $in ) {
                    $data[$c] .= ' ' . $r;
            } else {
                    $data[$c++] = $r;
            }
        }

        foreach ($data as $index => $string) {
            
            $data[$index] = str_replace('\"', '"', trim($string, '"'));
        }

        return $data;
    }

    /**
     * builds a new RR object
     *
     * @param Packet $packet (output) a Packet or null to create an empty object
     * @param array           $rr      an array with RR parse values or null to
     *                                 create an empty object
     *
     * @return bool
     * @access public
     *
     */
    public function set(Packet $packet, array $rr) : bool
    {
        $this->name     = $rr['name'];
        $this->type     = Lookups::$rr_types_by_id[$rr['type']];

        //
        // for RR OPT (41), the class value includes the requestor's UDP payload size,
        // and not a class value
        //
        if ($this->type == 'OPT') {
            $this->class = (string) $rr['class'];
        } else {
            $this->class = Lookups::$classes_by_id[$rr['class']];
        }

        $this->ttl      = $rr['ttl'];
        $this->rdLength = $rr['rdlength'];
        $this->rdata    = substr($packet->rdata, $packet->offset, $rr['rdlength']);

        return $this->rrSet($packet);
    }


    /**
     * returns a binary packed DNS RR object
     *
     * @param Packet $packet a Packet used for compressing names
     *
     * @return string
     * @access public
     *
     * @throws Exception
     */
    public function get(Packet $packet) : string
    {
        $rdata = '';

        //
        // pack the name
        //
        $data = $packet->compress($this->name, $packet->offset);

        //
        // pack the main values
        //
        if ($this->type == 'OPT') {

            //
            // pre-build the TTL value
            //
            assert( $this instanceof OPT );
            $this->preBuild();

            //
            // the class value is different for OPT types
            //
            $data .= pack(
                'nnN', 
                Lookups::$rr_types_by_name[$this->type],
                $this->class,
                $this->ttl
            );
        } else {

            $data .= pack(
                'nnN', 
                Lookups::$rr_types_by_name[$this->type],
                Lookups::$classes_by_name[$this->class],
                $this->ttl
            );
        }

        //
        // increase the offset, and allow for the rdlength
        //
        $packet->offset += 10;

        //
        // get the RR specific details
        //
        if ($this->rdLength != -1) {
             $rdata = $this->rrGetEx( $packet );
        }

        //
        // add the RR
        //
        $data .= pack('n', strlen($rdata)) . $rdata;

        return $data;
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
     * @access public
     *
     */
    public static function parse(Packet $packet) : ?RR
    {
        $object = [];

        //
        // expand the name
        //
        $object['name'] = $packet::expand($packet, $packet->offset);
        if (is_null($object['name'])) {

            throw new Exception(
                'failed to parse resource record: failed to expand name.',
                Lookups::E_PARSE_ERROR
            );
        }
        if ($packet->rdLength < ($packet->offset + 10)) {

            throw new Exception(
                'failed to parse resource record: packet too small.',
                Lookups::E_PARSE_ERROR
            );
        }

        //
        // unpack the RR details
        //
        $object['type']     = ord($packet->rdata[$packet->offset++]) << 8 | 
                                ord($packet->rdata[$packet->offset++]);
        $object['class']    = ord($packet->rdata[$packet->offset++]) << 8 | 
                                ord($packet->rdata[$packet->offset++]);

        $object['ttl']      = ord($packet->rdata[$packet->offset++]) << 24 | 
                                ord($packet->rdata[$packet->offset++]) << 16 | 
                                ord($packet->rdata[$packet->offset++]) << 8 | 
                                ord($packet->rdata[$packet->offset++]);

        $object['rdlength'] = ord($packet->rdata[$packet->offset++]) << 8 | 
                                ord($packet->rdata[$packet->offset++]);

        if ($packet->rdLength < ($packet->offset + $object['rdlength'])) {
            return null;
        }

        //
        // lookup the class to use
        //
        $class  = Lookups::$rr_types_id_to_class[$object['type']];

        if ( ! isset( $class ) ) {

            throw new Exception(
                'un-implemented resource record type: ' . $object[ 'type' ],
                Lookups::E_RR_INVALID
            );
        }

        $o = new $class( $packet, $object );
        if ( $o ) {

            $packet->offset += $object[ 'rdlength' ];
        }
        return $o;

    }

    /**
     * cleans up some RR data
     * 
     * @param string $data the text string to clean
     *
     * @return string returns the cleaned string
     *
     * @access public
     *
     */
    public function cleanString( string $data ) : string
    {
        return strtolower(rtrim($data, '.'));
    }

    /**
     * parses a standard RR format lines, as defined by rfc1035 (kinda)
     *
     * In our implementation, the domain *must* be specified- format must be
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
     * @access public
     *
     */
    public static function fromString( string $line ) : RR
    {
        if (strlen($line) == 0) {
            throw new Exception(
                'empty config line provided.',
                Lookups::E_PARSE_ERROR
            );
        }

        $type   = '';
        $class  = 'IN';
        $ttl    = 86400;

        //
        // split the line by spaces
        //
        $values = preg_split('/\s+/', $line);
        if (count($values) < 3) {

            throw new Exception(
                'failed to parse config: minimum of name, type and rdata required.',
                Lookups::E_PARSE_ERROR
            );
        }

        //
        // assume the first value is the name
        //
        $name = trim(strtolower(array_shift($values)), '.');

        //
        // The next value is either a TTL, Class or Type
        //
        foreach ($values as $value) {

            switch(true) {
            case is_numeric($value):
                //
                // this is here because of a bug in is_numeric() in certain versions of
                // PHP on Windows.
                // Unable to verify. - JDWX 2022-07-09
                //
            case ($value === 0):

                $ttl = (int) array_shift( $values );
                break;

            case isset(Lookups::$classes_by_name[strtoupper($value)]):

                $class = strtoupper(array_shift($values));
                break;

            case isset(Lookups::$rr_types_by_name[strtoupper($value)]):

                $type = strtoupper(array_shift($values));
                break 2;

            default:

                throw new Exception(
                    'invalid config line provided: unknown file: ' . $value,
                    Lookups::E_PARSE_ERROR
                );
            }
        }

        //
        // lookup the class to use
        //
        $class_name = Lookups::$rr_types_id_to_class[
            Lookups::$rr_types_by_name[$type]
        ];

        if ( ! isset( $class_name ) ) {

            throw new Exception(
                'un-implemented resource record type: ' . $type,
                Lookups::E_RR_INVALID
            );
        }

        $o = new $class_name();
        if ( is_null( $o ) ) {

            throw new Exception(
                'failed to create new RR record for type: ' . $type,
                Lookups::E_RR_INVALID
            );
        }

        //
        // set the parsed values
        //
        $o->name = $name;
        $o->class = $class;
        $o->ttl = $ttl;

        //
        // parse the rdata
        //
        if ( $o->rrFromString( $values ) === false ) {

            throw new Exception(
                'failed to parse rdata for config: ' . $line,
                Lookups::E_PARSE_ERROR
            );
        }

        return $o;
    }
}