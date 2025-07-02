<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Packet\ResponsePacket;
use JDWX\DNSQuery\Legacy\RR\A;
use JDWX\DNSQuery\Legacy\RR\AAAA;
use JDWX\DNSQuery\Legacy\RR\CNAME;


/** Starts from a set of root name servers and goes through each step of the resolution process
 * itself, as needed to get an authoritative answer in the absence of a local recursive resolver.
 */
class RecursiveResolver {


    /** @var list<string> */
    protected array $rootNameServers = [];

    protected bool $useIPv4;

    protected bool $useIPv6;

    protected bool $useDNSSEC;

    /** @var array<string, Resolver> A stash of resolvers with given name server lists. */
    protected array $resolvers = [];

    /** @var array<string, list<string>> */
    protected array $addressCache = [];

    protected bool $debug = false;


    /** Constructor, optionally taking one or more custom root nameservers
     *
     * @param null|string|list<string>|NamedRoot $i_rootNameServers A list of root name servers
     * @param bool $i_useIPv4 Whether to use IPv4 nameservers
     * @param bool $i_useIPv6 Whether to use IPv6 nameservers
     * @param bool $i_useDNSSEC Whether to request and validate DNSSEC (not implemented yet)
     */
    public function __construct( string|array|NamedRoot|null $i_rootNameServers = null,
                                 bool                        $i_useIPv4 = true, bool $i_useIPv6 = false,
                                 bool                        $i_useDNSSEC = false ) {
        $this->useIPv4 = $i_useIPv4;
        $this->useIPv6 = $i_useIPv6;
        $this->useDNSSEC = $i_useDNSSEC;
        if ( is_string( $i_rootNameServers ) ) {
            $this->rootNameServers = [ $i_rootNameServers ];
        } elseif ( is_array( $i_rootNameServers ) ) {
            $this->rootNameServers = $i_rootNameServers;
        } elseif ( $i_rootNameServers instanceof NamedRoot ) {
            $this->rootNameServers = $i_rootNameServers->listAddresses( $i_useIPv4, $i_useIPv6 );
        } else {
            $root = new NamedRoot();
            $this->rootNameServers = $root->listAddresses( $i_useIPv4, $i_useIPv6 );
        }

    }


    /** Extracts a list of authoritative name server addresses from a response packet.
     *
     * @param string $i_name Name the servers need to be authoritative for.
     * @param ResponsePacket $i_rsp Response packet to try to extract name servers from.
     * @return list<string> A list of IP addresses for the name servers.
     *
     * @throws Exception
     */
    public function extractAuthoritativeAddresses( string $i_name, ResponsePacket $i_rsp ) : array {
        $nsList = $i_rsp->extractAuthoritativeAddresses( $i_name, $this->useIPv4, $this->useIPv6 );
        if ( empty( $nsList ) ) {
            return [];
        }

        $out = [];
        foreach ( $nsList as $nsName => $nsAddresses ) {
            if ( ! empty( $nsAddresses ) ) {
                array_push( $out, ...$nsAddresses );
            } else {
                array_push( $out, ...$this->lookupNameServer( $nsName ) );
            }
        }
        echo 'Authoritative for ', $i_name, ': ', implode( ', ', $out ), "\n";
        return $out;
    }


    /** Get the configured list of root name server addresses.
     * @return list<string> A list of IP addresses for the root name servers.
     */
    public function getRootNameServers() : array {
        return $this->rootNameServers;
    }


    /** Learn a mapping from a name to an address.
     * This prevents having to continually re-query from the root for IP info
     * about the same name servers, especially when the name servers are not in
     * the same zone as the name being queried.
     *
     * @param string $i_name The name associated with the address.
     * @param string $i_address The address to learn.
     */
    public function learnAddress( string $i_name, string $i_address ) : void {
        if ( ! array_key_exists( $i_name, $this->addressCache ) ) {
            $this->addressCache[ $i_name ] = [];
        }
        if ( in_array( $i_address, $this->addressCache[ $i_name ] ) ) {
            return;
        }
        echo 'Learned ', $i_name, ': ', $i_address, "\n";
        $this->addressCache[ $i_name ][] = $i_address;
    }


    /**
     * @return list<string> The list of addresses for the name server.
     *
     * When a name server is listed in the authority section but it isn't given an address in the
     * glue records, we have to start over and look up that name server separately.
     */
    public function lookupNameServer( string $i_nameServer ) : array {
        echo 'Lookup: ', $i_nameServer, "\n";
        if ( ! array_key_exists( $i_nameServer, $this->addressCache ) ) {
            if ( $this->useIPv4 ) {
                $this->query( $i_nameServer );
            }
            if ( $this->useIPv6 ) {
                $this->query( $i_nameServer, 'AAAA' );
            }
        }
        return $this->addressCache[ $i_nameServer ];
    }


    /** Make a resolver with the correct properties, mainly attaching the cache.
     *
     * @param list<string> $i_nameServers List of name server addresses to use.
     *
     * @return Resolver Initialized resolver
     *
     * @throws Exception
     */
    public function makeResolver( array $i_nameServers ) : Resolver {
        $key = implode( ',', $i_nameServers );
        if ( array_key_exists( $key, $this->resolvers ) ) {
            return $this->resolvers[ $key ];
        }
        $rsv = ( new Resolver( $i_nameServers ) )->setCacheDefault()->setRecurse( false );
        if ( $this->useDNSSEC ) {
            $rsv->setDNSSEC();
        }
        $this->resolvers[ $key ] = $rsv;
        return $rsv;
    }


    /**
     * Perform recursive resolution.
     *
     * @param string $i_name
     * @param string $i_type
     * @param int $i_maxDepth
     * @return list<ResponsePacket>
     * @throws Exception
     */
    public function query( string $i_name, string $i_type = 'A', int $i_maxDepth = 16 ) : array {
        return $this->queryRecursive( $i_name, $i_type, $this->getRootNameServers(), $i_maxDepth );
    }


    /** Enable or disable debugging.
     *
     * @param bool $i_debug
     * @return $this
     */
    public function setDebug( bool $i_debug ) : static {
        $this->debug = $i_debug;
        return $this;
    }


    /** Walk through the answer and additional sections of a response and
     * learn any addresses found there.
     *
     * @param ResponsePacket $i_rsp The response packet to learn from.
     */
    protected function learnFromResponse( ResponsePacket $i_rsp ) : void {
        foreach ( $i_rsp->answer as $rr ) {
            if ( $this->useIPv4 && $rr instanceof A ) {
                $this->learnAddress( $rr->name, $rr->address );
            }
            if ( $this->useIPv6 && $rr instanceof AAAA ) {
                $this->learnAddress( $rr->name, $rr->address );
            }
        }
        foreach ( $i_rsp->additional as $rr ) {
            if ( $this->useIPv4 && $rr instanceof A ) {
                $this->learnAddress( $rr->name, $rr->address );
            }
            if ( $this->useIPv6 && $rr instanceof AAAA ) {
                $this->learnAddress( $rr->name, $rr->address );
            }
        }
    }


    /** If we hit a CNAME, we have to potentially start over or continue with a different name.
     * @return ResponsePacket[] A sequence of response packets.
     * @throws Exception
     */
    protected function queryCNAME( string $i_name, string $i_type, ResponsePacket $i_rsp, int $i_maxDepth ) : array {

        $newNameServers = $this->extractAuthoritativeAddresses( $i_name, $i_rsp );
        echo 'CNAME Restart: ', implode( ', ', $newNameServers ), "\n";

        # If we have not been given any usable additional name servers, we have to start over.
        if ( empty( $newNameServers ) ) {
            $newNameServers = $this->getRootNameServers();
        }

        return $this->queryRecursive( $i_name, $i_type, $newNameServers, $i_maxDepth );

    }


    /**
     * @param list<string> $i_nameServers
     * @return list<ResponsePacket> A list of response packets.
     * @suppress PhanTypeSuspiciousEcho Packets are Stringable.
     */
    protected function queryRecursive( string $i_name, string $i_type, array $i_nameServers,
                                       int    $i_maxDepth ) : array {

        # Malicious name servers can create infinite loops.  We do not want to loop forever.
        if ( 0 == $i_maxDepth ) {
            throw new Exception( 'Maximum depth exceeded' );
        }
        $i_maxDepth -= 1;

        if ( $this->debug ) {
            echo 'Resolve ', $i_name, ' ', $i_type, ' on: ', implode( ', ', $i_nameServers ), "\n";
        }
        $rsv = $this->makeResolver( $i_nameServers );

        $rsp = $rsv->query( $i_name, $i_type );
        if ( $this->debug ) {
            echo 'Response: ', $rsp, "\n";
        }
        $this->learnFromResponse( $rsp );
        if ( 1 === count( $rsp->answer ) ) {
            $answer = $rsp->answer[ 0 ];
            if ( $answer instanceof CNAME ) {
                if ( $this->debug ) {
                    echo 'CNAME: ', $answer->name, ' => ', $answer->cname, "\n";
                }
                if ( $answer->name == $i_name ) {
                    return [ $rsp, ...$this->queryCNAME( $answer->cname, $i_type, $rsp, $i_maxDepth ) ];
                }
            }
        }
        if ( ! empty( $rsp->answer ) ) {
            return [ $rsp ];
        }
        if ( $rsp->header->rCode != Lookups::E_NONE ) {
            return [ $rsp ];
        }

        if ( empty( $rsp->authority ) ) {
            throw new Exception( "No authority found for $i_name" );
        }

        $nsList = $this->extractAuthoritativeAddresses( $i_name, $rsp );
        if ( empty( $nsList ) ) {
            return [ $rsp ];
        }
        echo 'New list from ', $rsp->answerFrom, ': ', implode( ', ', $nsList ), "\n";

        return [ $rsp, ... $this->queryRecursive( $i_name, $i_type, $nsList, $i_maxDepth ) ];
    }


}