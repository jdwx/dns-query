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
 * This is the main resolver class, providing DNS query functions.
 *
 */
class Resolver extends Net_DNS2
{


    /** @var ?ICache A caching implementation, if one is desired. */
    protected ICache|null $cache = null;

    /*
     * if we should set the recursion desired bit to 1 or 0.
     *
     * by default this is set to true, we want the DNS server to perform a recursive
     * request. If set to false, the RD bit will be set to 0, and the server will
     * not perform recursion on the request.
     */
    public bool $recurse = true;

    /** @var bool Use strict query mode (suppress CNAME-only answers) */
    public bool $strictQueryMode = false;



    /**
     * does a basic DNS lookup query
     *
     * @param string $name  the DNS name to lookup
     * @param string $type  the name of the RR type to lookup
     * @param string $class the name of the RR class to lookup
     *
     * @return ResponsePacket object
     * @throws Exception
     * @access public
     *
     */
    public function query( string $name, string $type = 'A', string $class = 'IN' ) : ResponsePacket
    {

        //
        // we don't support incremental zone transfers; so if it's requested, a full
        // zone transfer can be returned
        //
        if ($type == 'IXFR') {

            $type = 'AXFR';
        }

        //
        // if the name *looks* too short, then append the domain from the config
        //
        if ( ( ! str_contains( $name, '.' ) ) && ($type != 'PTR') ) {

            $name .= '.' . strtolower($this->domain);
        }

        //
        // create a new packet based on the input
        //
        $packet = new RequestPacket($name, $type, $class);

        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->authSignature instanceof TSIG)
            || ($this->authSignature instanceof SIG)
        ) {
            $packet->additional[]       = $this->authSignature;
            $packet->header->arcount    = count($packet->additional);
        }

        //
        // check for the DNSSEC flag, and if it's true, then add an OPT
        // RR to the additional section, and set the DO flag to 1.
        //
        if ( $this->dnssec ) {

            //
            // create a new OPT RR
            //
            $opt = new OPT();

            //
            // set the DO flag, and the other values
            //
            $opt->do = 1;

            // TODO: I am very suspicious of this line.  - jdwx
            $opt->class = (string) $this->dnssecPayloadSize;

            //
            // add the RR to the additional section.
            //
            $packet->additional[] = $opt;
            $packet->header->arcount = count($packet->additional);
        }

        //
        // set the DNSSEC AD or CD bits
        //
        if ( $this->dnssecADFlag ) {

            $packet->header->ad = 1;
        }
        if ( $this->dnssecCDFlag ) {

            $packet->header->cd = 1;
        }

        //
        // if caching is turned on, check then hash the question, and
        // do a cache lookup.
        //
        // don't use the cache for zone transfers
        //
        $packet_hash = '';

        if ( $this->cache && $this->cache::isTypeCacheable( $type ) ) {

            //
            // build the key and check for it in the cache.
            //
            $packet_hash = $this->cache::hashRequest( $packet );

            $xx = $this->cache->get( $packet_hash );
            if ( $xx ) {

                //
                // return the cached packet
                //
                return $xx;
            }
        }

        //
        // set the RD (recursion desired) bit to 1 / 0 depending on the config
        // setting.
        //
        if ( ! $this->recurse ) {
            $packet->header->rd = 0;
        } else {
            $packet->header->rd = 1;
        }

        //
        // send the packet and get back the response
        //
        // *always* use TCP for zone transfers. does this cause any problems?
        //
        $response = $this->sendPacket(
            $packet, ($type == 'AXFR') ? true : $this->useTCP
        );

        //
        // if strict mode is enabled, then make sure that the name that was
        // looked up is *actually* in the response object.
        //
        // only do this is strict_query_mode is turned on, AND we've received
        // some answers; no point doing any else if there were no answers.
        //
        if ( $this->strictQueryMode
            && ($response->header->ancount > 0) 
        ) {

            $found = false;

            //
            // look for the requested name/type/class
            //
            foreach ($response->answer as $object) {

                if ( (strcasecmp(trim($object->name, '.'), trim($packet->question[0]->qname, '.')) == 0)
                    && ($object->type == $packet->question[0]->qtype)
                    && ($object->class == $packet->question[0]->qclass)
                ) {
                    $found = true;
                    break;
                }
            }

            //
            // if it's not found, then unset the answer section; it's not correct to
            // throw an exception here; if the hostname didn't exist, then 
            // sendPacket() would have already thrown an NXDOMAIN error. so the name
            // *exists*, but just doesn't have any records of the request type/class.
            //
            // the correct response in this case is an empty answer section; the
            // authority section may still have usual information, like a SOA record.
            //
            if ( ! $found ) {
                
                $response->answer = [];
                $response->header->ancount = 0;
            }
        }

        //
        // cache the response object
        //
        if ( $this->cache && $this->cache::isTypeCacheable( $type ) ) {
            $this->cache->put($packet_hash, $response);
        }

        return $response;
    }


    /**
     * does an inverse query for the given RR; most DNS servers do not implement 
     * inverse queries, but they should be able to return "not implemented"
     *
     * @param RR $rr the RR object to lookup
     * 
     * @return ResponsePacket object
     * @throws Exception
     * @access public
     *
     */
    public function iquery(RR $rr) : ResponsePacket
    {
        //
        // create an empty packet
        //
        $packet = new RequestPacket($rr->name, 'A', 'IN');

        //
        // unset the question
        //
        $packet->question = [];
        $packet->header->qdcount = 0;

        //
        // set the opcode to IQUERY
        //
        $packet->header->opcode = Lookups::OPCODE_IQUERY;

        //
        // add the given RR as the answer
        //
        $packet->answer[] = $rr;
        $packet->header->ancount = 1;

        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->authSignature instanceof TSIG)
            || ($this->authSignature instanceof SIG)
        ) {
            $packet->additional[]       = $this->authSignature;
            $packet->header->arcount    = count($packet->additional);
        }

        //
        // send the packet and get back the response
        //
        return $this->sendPacket($packet, $this->useTCP);
    }


    /**
     * Adds a caching implementation to the resolver object.
     *
     * @param ICache $i_cache
     * @return static
     */
    public function setCache( ICache $i_cache ) : static {
        $this->cache = $i_cache;
        return $this;
    }


    /**
     * Adds a default caching implementation.
     */
    public function setCacheDefault() : static {
        $cache = new Cache();
        return $this->setCache( $cache );
    }


    /**
     * Adds a default caching implementation using a provided PSR-16 cache.
     */
    public function setCacheInterface( CacheInterface $i_cacheInterface ) : static {
        $cache = new Cache( $i_cacheInterface );
        return $this->setCache( $cache );
    }


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
     * @param bool $i_strictQueryMode   Whether to enable strict mode or not.
     */
    public function setStrictQueryMode( bool $i_strictQueryMode = true ) : static {
        $this->strictQueryMode = $i_strictQueryMode;
        return $this;
    }


}

