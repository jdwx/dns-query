<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Cache\Cache;
use JDWX\DNSQuery\Cache\ICache;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\RR\OPT;
use JDWX\DNSQuery\RR\RR;
use JDWX\DNSQuery\RR\SIG;
use JDWX\DNSQuery\RR\TSIG;
use Psr\SimpleCache\CacheInterface;


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
 * This is the main resolver class, providing DNS query functions.
 *
 */
class Resolver extends BaseQuery {


    /** @var bool If we should set the recursion desired bit to 1 (true) or 0 (false)
     *
     * By default this is set to true; we want the DNS server to perform a recursive
     * request. If set to false, the RD bit will be set to 0, and the server will
     * not perform recursion on the request.  This setting is appropriate when you
     * know you are querying an authoritative name server for the zone.
     */
    public bool $recurse = true;

    /** @var bool Use strict query mode (suppress CNAME-only answers) */
    public bool $strictQueryMode = false;

    /** @var ?ICache A caching implementation, if one is desired. */
    protected ICache|null $cache = null;


    /**
     * An easy way to use the query interface for those familiar with dns_get_record().
     *
     * This does not return Authority and Additional sections because I was not able to
     * find any case where the dns_get_record() function returns them when querying a
     * recursive local resolver.  If you find one, please open an issue on GitHub with
     * the details.
     *
     * @param string            $i_hostname The hostname to use for the query.
     * @param int               $i_type    The type of record to look up (using PHP constants like DNS_A)
     * @param null|array|string $nameServers Optional name server or list of name servers to use.
     * @param null|string       $resolvConf Optional path to resolv.conf file to use.
     * @return array|false
     * @throws Exception
     * @noinspection PhpMethodNamingConventionInspection
     */
    public static function dns_get_record( string            $i_hostname, int $i_type = DNS_ANY,
                                           array|string|null $nameServers = null,
                                           ?string           $resolvConf = null ) : array|false {
        $resolver = new static( $nameServers, $resolvConf );
        $thing1 = null;
        $thing2 = null;
        return $resolver->compatQuery( $i_hostname, $i_type, $thing1, $thing2 );
    }


    /**
     * An interface similar to dns_get_record that allows customizing the resolver request.
     *
     * This is the very simplest query interface.  It's suitable if you're
     * adapting from dns_get_record() and just making one quick query, for example to query a
     * different name server than your local resolver.
     *
     * If you are making multiple queries, it's better to use instantiate the resolver class and
     * use the native query() method or the compatQuery() method, which also returns results
     * similar to dns_get_record().
     *
     * @param string                $i_hostname The hostname to look up
     * @param int                   $i_type     The type of record to look up (using PHP constants)
     * @param array|null           &$authoritativeNameServers (output) Any authoritative name servers found.
     * @param array|null           &$additionalRecords (output) Any additional records found.
     * @param null|string[]|string  $nameServers Optional name server or list of name servers to use.
     * @param null|string           $resolvConf Optional resolv.conf file to use.
     *
     * @return array[]|false      An array of the discovered records on success, otherwise false
     *
     * @throws Exception
     */
    public function compatQuery( string            $i_hostname, int $i_type = DNS_ANY,
                                 array             &$authoritativeNameServers = null,
                                 array             &$additionalRecords = null,
                                 array|string|null $nameServers = null,
                                 ?string           $resolvConf = null ) : array|false {
        if ( $i_type == DNS_A6 ) {
            trigger_error( 'Per RFC6563, A6 records should not be implemented or deployed.', E_USER_WARNING );
            return false;
        }
        if ( ! array_key_exists( $i_type, Lookups::$rrClassByPHPId ) ) {
            trigger_error( 'Invalid record type: $type', E_USER_WARNING );
            return false;
        }
        $class = Lookups::$rrClassByPHPId[ $i_type ];
        $id = Lookups::$rrTypesClassToId[ $class ];
        $type = Lookups::$rrTypesById[ $id ];

        $resolver = new Resolver( $nameServers, $resolvConf );
        $rsp = $resolver->query( $i_hostname, $type );
        $rAnswer = [];
        foreach ( $rsp->answer as $rr ) {
            $rAnswer[] = $rr->getPHPRecord();
        }

        if ( $authoritativeNameServers !== null ) {
            $authoritativeNameServers = [];
            foreach ( $rsp->authority as $rr ) {
                $authoritativeNameServers[] = $rr->getPHPRecord();
            }
        }

        if ( $additionalRecords !== null ) {
            $additionalRecords = [];
            foreach ( $rsp->additional as $rr ) {
                $additionalRecords[] = $rr->getPHPRecord();
            }
        }

        return $rAnswer;

    }


    /**
     * does an inverse query for the given RR; most DNS servers do not implement
     * inverse queries, but they should be able to return "not implemented"
     *
     * @param RR $rr RR object to lookup
     *
     * @return ResponsePacket Response from server
     * @throws Exception
     */
    public function iquery( RR $rr ) : ResponsePacket {

        # Create an empty packet.
        $packet = new RequestPacket( $rr->name, 'A', 'IN' );

        # Unset the question.
        $packet->question = [];
        $packet->header->qdCount = 0;

        # Set the opcode to IQUERY.
        $packet->header->opcode = Lookups::OPCODE_IQUERY;

        # Add the given RR as the answer.
        $packet->answer[] = $rr;
        $packet->header->anCount = 1;

        # Check for an authentication method; either TSIG or SIG.
        if ( ( $this->authSignature instanceof TSIG )
            || ( $this->authSignature instanceof SIG )
        ) {
            $packet->additional[] = $this->authSignature;
            $packet->header->arCount = count( $packet->additional );
        }

        # Send the packet and get back the response.
        return $this->sendPacket( $packet, $this->useTCP );
    }


    /**
     * does a basic DNS lookup query
     *
     * @param string $i_name the DNS name to lookup
     * @param string $i_type the name of the RR type to lookup
     * @param string $i_class the name of the RR class to lookup
     *
     * @return ResponsePacket object
     * @throws Exception
     */
    public function query( string $i_name, string $i_type = 'A', string $i_class = 'IN' ) : ResponsePacket {

        # We don't support incremental zone transfers; so if it's requested, a full
        # zone transfer can be returned.
        /** @noinspection SpellCheckingInspection */
        if ( $i_type == 'IXFR' ) {
            $i_type = 'AXFR';
        }

        # If the name *looks* too short, then append the domain from the config.
        if ( ( ! str_contains( $i_name, '.' ) ) && ( $i_type != 'PTR' ) ) {
            $i_name .= '.' . strtolower( $this->domain );
        }

        # Create a new packet based on the input.
        $packet = new RequestPacket( $i_name, $i_type, $i_class );

        # Check for an authentication method (either TSIG or SIG).
        if ( ( $this->authSignature instanceof TSIG )
            || ( $this->authSignature instanceof SIG )
        ) {
            $packet->additional[] = $this->authSignature;
            $packet->header->arCount = count( $packet->additional );
        }

        # Check for the DNSSEC flag, and if it's true, then add an OPT
        # RR to the additional section, and set the DO flag to 1.
        if ( $this->dnssec ) {

            # Create a new OPT RR.
            $opt = new OPT();

            # Set the DO flag, and the other values
            $opt->do = 1;

            # The OPT record overloads the class field to contain payload size information.
            $opt->class = (string) $this->dnssecPayloadSize;

            # Add the RR to the additional section.
            $packet->additional[] = $opt;
            $packet->header->arCount = count( $packet->additional );
        }

        # Set the DNSSEC AD or CD bits if requested.
        if ( $this->dnssecADFlag ) {
            $packet->header->ad = 1;
        }
        if ( $this->dnssecCDFlag ) {
            $packet->header->cd = 1;
        }

        $packetHash = null;

        # Don't use the cache for zone transfers
        if ( $this->cache && $this->cache::isTypeCacheable( $i_type ) ) {
            # If caching is turned on, check then hash the question, and
            # do a cache lookup.

            # Hash the key and check for it in the cache.
            $packetHash = $this->cache::hashRequest( $packet );
            $xx = $this->cache->get( $packetHash );
            if ( $xx ) {
                # Return the cached packet.
                return $xx;
            }
        }

        # Set the RD (recursion desired) bit to 1 / 0 depending on the config
        # setting.
        $packet->header->rd = $this->recurse ? 1 : 0;

        # Send the packet and get back the response.
        # *Always* use TCP for zone transfers. Does this cause any problems?
        $response = $this->sendPacket(
            $packet, ( $i_type == 'AXFR' ) ? true : $this->useTCP
        );

        # If strict_query mode is enabled AND we've received some answers,
        # then make sure that the name that was looked up is actually in
        # the response object.
        if ( $this->strictQueryMode
            && ( $response->header->anCount > 0 )
        ) {

            $found = false;

            # Look for the requested name/type/class.
            foreach ( $response->answer as $object ) {

                if ( ( strcasecmp( trim( $object->name, '.' ), trim( $packet->question[ 0 ]->qName, '.' ) ) == 0 )
                    && ( $object->type == $packet->question[ 0 ]->qType )
                    && ( $object->class == $packet->question[ 0 ]->qClass )
                ) {
                    $found = true;
                    break;
                }
            }

            # If it's not found, then unset the answer section. It's not correct to
            # throw an exception here; if the hostname didn't exist, then
            # sendPacket() would have already thrown an NXDOMAIN error. So the name
            # *exists*, but just doesn't have any records of the request type/class.
            #
            # The correct response in this case is an empty answer section; the
            # authority section may still have usable information, like a SOA record.
            if ( ! $found ) {
                $response->answer = [];
                $response->header->anCount = 0;
            }
        }

        # Cache the response object if allowable.
        # $packet_hash is only set here if caching is turned on, allowable,
        # and the record wasn't already cached.
        if ( is_string( $packetHash ) ) {
            $this->cache->put( $packetHash, $response );
        }

        return $response;
    }


    /**
     * Adds a caching implementation to the resolver object.
     *
     * @param ICache $i_cache The caching implementation to use
     * @return static Fluent setter
     */
    public function setCache( ICache $i_cache ) : static {
        $this->cache = $i_cache;
        return $this;
    }


    /**
     * Adds a default caching implementation.
     *
     * @return static Fluent setter
     */
    public function setCacheDefault() : static {
        $cache = new Cache();
        return $this->setCache( $cache );
    }


    /**
     * Adds a default caching implementation using a provided PSR-16 cache.
     *
     * @param CacheInterface $i_cacheInterface The PSR-16 cache implementation to use
     *
     * @return static Fluent setter
     */
    public function setCacheInterface( CacheInterface $i_cacheInterface ) : static {
        $cache = new Cache( $i_cacheInterface );
        return $this->setCache( $cache );
    }


    /** Whether to ask the name server to do recursive lookups.
     *
     * This controls whether RD will be set in outgoing queries.
     *
     * @param bool $i_recurse Whether to ask for recursive lookups.
     * @return static Fluent setter
     */
    public function setRecurse( bool $i_recurse ) : static {
        $this->recurse = $i_recurse;
        return $this;
    }


    /**
     * Enables strict query mode.
     *
     * By default, according to RFC 1034,
     * CNAME RRs cause special action in DNS software.  When a name server
     * fails to find a desired RR in the resource set associated with the
     * domain name, it checks to see if the resource set consists of a CNAME
     * record with a matching class.  If so, the name server includes the CNAME
     * record in the response and restarts the query at the domain name
     * specified in the data field of the CNAME record.
     *
     * this can cause "unexpected" behaviours, since i'm sure *most* people
     * don't know DNS does this; there may be cases where the resolver returns a
     * positive response, even though the hostname the user looked up did not
     * actually exist.
     *
     * Enable strict query mode means that if the hostname that was looked up isn't
     * actually in the answer section of the response, the resolver will return an
     * empty answer section, instead of an answer section that could contain
     * CNAME records.
     *
     * @param bool $i_strictQueryMode Whether to enable strict mode or not.
     * @return static Fluent setter
     */
    public function setStrictQueryMode( bool $i_strictQueryMode = true ) : static {
        $this->strictQueryMode = $i_strictQueryMode;
        return $this;
    }


}

