<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Network\Socket;
use JDWX\DNSQuery\Network\TCPTransport;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\RR\RR;
use JDWX\DNSQuery\RR\SIG;
use JDWX\DNSQuery\RR\TSIG;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 */


/**
 * This is the base class for the Net_DNS2_Resolver and Net_DNS2_Updater classes.
 *
 */
class Net_DNS2 {

    /** @const The version of the DNS library */
    public const VERSION = '2.0.0';

    /** @const The default path to a resolv.conf file. */
    public const RESOLV_CONF = '/etc/resolv.conf';

    /** @var null|TSIG|SIG the TSIG or SIG RR object for authentication */
    protected TSIG|SIG|null $authSignature = null;

    /** @var int DNS port to use (53) */
    protected int $dnsPort = 53;

    /** @var bool If set, set the DO flag to 1 for DNSSEC requests */
    protected bool $dnssec = false;

    /** @var bool If set, set the AD flag in DNSSEC requests. */
    protected bool $dnssecADFlag = false;

    /** @var bool If set, set the CD flag in DNSSEC requests. */
    protected bool $dnssecCDFlag = false;

    /** @var int The EDNS(0) UDP payload size to use when making DNSSEC requests */
    protected int $dnssecPayloadSize = 4000;

    /** @var string The default domain for names that aren't fully qualified */
    protected string $domain = '';

    /** @var ?Exception the last exception that was generated */
    protected ?Exception $lastException = null;

    /** @var Exception[] the list of exceptions by name server */
    protected array $lastExceptionList = [];

    /** @var string The IP to use for local sockets */
    protected string $localHost = '';

    /** @var int The port to use for local sockets (0 = selected by OS) */
    protected int $localPort = 0;

    /** @var string[] name server list specified as IPv4 or IPv6 addresses */
    private array $nameServers = [];

    /** @var bool Randomize the list of name servers. */
    protected bool $nsRandom = false;

    /** @var string[] Not actually used right now */
    protected array $searchList = [];

    /** @var array<int, Socket[]> local sockets */
    protected array $sock = [ Socket::SOCK_DGRAM => [], Socket::SOCK_STREAM => [] ];

    /** @var int timeout value for socket connections (in seconds) */
    protected int $timeout = 5;

    protected TransportManager $transportManager;

    /** @var bool use options found in the resolv.conf file
     *
     * if this is set, then certain values from the resolv.conf file will override
     * local settings. This is disabled by default to remain backwards compatible.
     */
    protected bool $useResolvOptions = false;

    /** @var bool use TCP only (true/false) */
    protected bool $useTCP = false;


    /**
     * Constructor - base constructor for the Notifier, Resolver and Updater
     *
     * @access public
     *
     * @throws Exception
     */
    public function __construct( array|string|null $nameServers = null, ?string $resolvConf = null ) {
        if ( ! is_null( $nameServers ) && ! is_null( $resolvConf ) ) {
            throw new Exception( 'cannot specify both name servers and resolv.conf file' );
        }
        if ( is_string( $resolvConf ) ) {
            $this->useResolvConf( $resolvConf );
        }
        if ( is_string( $nameServers ) ) {
            $this->setNameServer( $nameServers );
        } elseif ( is_array( $nameServers ) ) {
            $this->setNameServers( $nameServers );
        }
        $this->transportManager = new TransportManager( $this->localHost, $this->localPort, $this->timeout );
    }


    /**
     * formats the given IPv6 address as a fully expanded IPv6 address
     *
     * @param string $_address the IPv6 address to expand
     *
     * @return string the fully expanded IPv6 address
     * @access public
     *
     */
    public static function expandIPv6( string $_address ) : string {
        $hex = unpack( 'H*hex', inet_pton( $_address ) );

        return substr( preg_replace( '/([A-f\d]{4})/', "$1:", $hex[ 'hex' ] ), 0, -1 );
    }


    /**
     * PHP doesn't support unsigned integers, but many of the RRs return
     * unsigned values (like SOA), so there is the possibility that the
     * value will overrun on 32bit systems, and you'll end up with a
     * negative value.
     *
     * 64bit systems are not affected, as their PHP_INT_MAX value should
     * be 64bit (ie 9223372036854775807)
     *
     * This function returns a negative integer value, as a string, with
     * the correct unsigned value.
     *
     * @param int $_int the unsigned integer value to check
     *
     * @return string returns the unsigned value as a string.
     * @access public
     *
     */
    public static function expandUint32( int $_int ) : string {
        if ( ( $_int < 0 ) && ( PHP_INT_MAX == 2147483647 ) ) {
            return sprintf( '%u', $_int );
        } else {
            return (string) $_int;
        }
    }


    /**
     * returns true/false if the given address is a valid IPv4 address
     *
     * @param string $_address the IPv4 address to check
     *
     * @return bool returns true/false if the address is IPv4 address
     * @access public
     *
     */
    public static function isIPv4( string $_address ) : bool {
        return ! ! filter_var( $_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
    }


    /**
     * returns true/false if the given address is a valid IPv6 address
     *
     * @param string $_address the IPv6 address to check
     *
     * @return bool returns true/false if the address is IPv6 address
     * @access public
     *
     */
    public static function isIPv6( string $_address ) : bool {
        return ! ! filter_var( $_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
    }


    /**
     * give users access to close all open sockets on the resolver object; resetting each
     * array, calls the destructor on the Net_DNS2_Socket object, which calls the close()
     * method on each object.
     *
     * @return bool
     * @access public
     *
     */
    public function closeSockets() : bool {
        $this->sock[ Socket::SOCK_DGRAM ] = [];
        $this->sock[ Socket::SOCK_STREAM ] = [];

        return true;
    }


    /**
     * Gets the currently-configured name servers or loads them from the default resolv.conf
     * if none are specified.
     *
     * @return string[] A list of IPv4 or IPv6 addresses of the configured nameservers.
     * @throws Exception
     */
    public function getNameServers() : array {

        if ( empty( $this->nameServers ) ) {
            $this->useResolvConf();
        }

        $nameServers = array_merge( [], $this->nameServers );

        //
        // randomize the name server list if it's asked for
        //
        if ( $this->nsRandom ) {
            shuffle( $nameServers );
        }

        return $nameServers;
    }


    /**
     * return the internal $sock array
     *
     * @return array<int, array>
     * @access public
     */
    public function getSockets() : array {
        return $this->sock;
    }


    /**
     * Set the DNS server port to use.  (Default is 53 for both TCP and UDP DNS.)
     *
     * @param int $i_dnsPort
     * @return static
     */
    public function setDNSPort( int $i_dnsPort = 53 ) : static {
        $this->dnsPort = $i_dnsPort;
        return $this;
    }


    /**
     * Request DNSSEC values, by setting the DO flag to 1; this actually makes
     * the resolver add an OPT RR to the additional section, and sets the DO flag
     * in this RR to 1
     *
     * @param bool $i_dnssec Whether to use DNSSEC.
     * @return static
     */
    public function setDNSSEC( bool $i_dnssec = true ) : static {
        $this->dnssec = $i_dnssec;
        return $this;
    }


    /**
     * set the DNSSEC AD (Authentic Data) bit on/off; the AD bit on the request
     * side was previously undefined, and resolvers we instructed to always clear
     * the AD bit when sending a request.
     *
     * RFC6840 section 5.7 defines setting the AD bit in the query as a signal to
     * the server that it wants the value of the AD bit, without needed to request
     * all the DNSSEC data via the DO bit.
     *
     * @param bool $i_dnssecADFlag
     * @return static
     */
    public function setDNSSECADFlag( bool $i_dnssecADFlag = true ) : static {
        $this->dnssecADFlag = $i_dnssecADFlag;
        return $this;
    }


    /**
     * set the DNSSEC CD (Checking Disabled) bit on/off; turning this off means
     * that the DNS resolver will perform its own signature validation so the DNS
     * servers simply pass through all the details.
     *
     * @param bool $i_dnssecCDFlag
     * @return static
     */
    public function setDNSSECCDFlag( bool $i_dnssecCDFlag = true ) : static {
        $this->dnssecCDFlag = $i_dnssecCDFlag;
        return $this;
    }


    /**
     * the EDNS(0) UDP payload size to use when making DNSSEC requests
     * see RFC 4035 section 4.1 - EDNS Support.
     *
     * there are some different ideas on the suggested size to support; but it seems to
     * be "at least" 1220 bytes, but SHOULD support 4000 bytes.  If this is not
     * set, the default is 4000 bytes.
     *
     * @param int $i_dnssecPayloadSize Payload size in bytes.
     * @return static
     */
    public function setDNSSECPayloadSize( int $i_dnssecPayloadSize ) : static {
        $this->dnssecPayloadSize = $i_dnssecPayloadSize;
        return $this;
    }


    /**
     * Set the local IP address to use.  (Default is empty, which means to use the
     * default local IP address.)
     *
     * @param string $i_localHost
     * @return static
     */
    public function setLocalHost( string $i_localHost = '' ) : static {
        $this->localHost = $i_localHost;
        return $this;
    }


    /**
     * Set the local port to use.  (Default is 0, which means to use a
     * local port selected by the OS.)
     *
     * @param int $i_localPort Local port value to use.
     * @return static                   Fluent interface.
     */
    public function setLocalPort( int $i_localPort = 0 ) : static {
        $this->localPort = $i_localPort;
        return $this;
    }


    /**
     * Shortcut to set a single name server.
     *
     * @param string $nameServer the IPv4 or IPv6 address of the desired name server
     * @throws Exception
     */
    public function setNameServer( string $nameServer ) : static {
        return $this->setNameServers( [ $nameServer ] );
    }


    /**
     * Sets the name servers to be used, specified as IPv4 or IPv6 addresses.
     *
     * @param string[] $nameServers a list of IPv4 or IPv6 addresses
     *
     * @throws Exception
     */
    public function setNameServers( array $nameServers ) : static {
        // collect valid IP addresses in a temporary list
        $ipAddresses = [];

        foreach ( $nameServers as $value ) {
            if ( self::isIPv4( $value ) || self::isIPv6( $value ) ) {
                $ipAddresses[] = $value;
            } else {
                throw new Exception(
                    'invalid nameserver entry: ' . $value,
                    Lookups::E_NS_INVALID_ENTRY
                );
            }
        }

        // only replace the nameservers list if no exception is thrown
        $ipAddresses = array_unique( $ipAddresses );
        if ( empty( $ipAddresses ) ) {
            throw new Exception(
                'empty name servers list; you must provide a list of name ' .
                'servers, or the path to a resolv.conf file.',
                Lookups::E_NS_INVALID_ENTRY
            );
        }
        $this->nameServers = $ipAddresses;
        return $this;
    }


    /**
     * Set whether to randomize the name server list.  (Default is false.)
     *
     * @param bool $i_randomize true to randomize the name server list, false to not randomize
     * @return static                   Fluent interface.
     */
    public function setRandomizeNameServers( bool $i_randomize = true ) : static {
        $this->nsRandom = $i_randomize;
        return $this;
    }


    /**
     * Set the timeout value to use for socket connections.  (Default is 5 seconds.)
     *
     * @param int $i_timeout
     * @return static
     */
    public function setTimeout( int $i_timeout ) : static {
        $this->timeout = $i_timeout;
        return $this;
    }


    /**
     * Set whether to use options found in resolv.conf if one is parsed.
     *
     * Note that this will not affect the use of the resolv.conf file if it is loaded from the
     * constructor.  So if you want this option, set it and then manually call useResolvConf().
     *
     * @param bool $i_useResolvOptions Whether to use options found in resolv.conf
     * @return static                   Fluent interface.
     */
    public function setUseResolvOptions( bool $i_useResolvOptions ) : static {
        $this->useResolvOptions = $i_useResolvOptions;
        return $this;
    }


    /** Default to using TCP for requests.  (TCP will always be used for large
     * requests or AXFR requests.)
     *
     * @param bool $i_useTCP Whether to use TCP for requests by default.
     * @return static         Fluent interface.
     */
    public function setUseTCP( bool $i_useTCP = true ) : static {
        $this->useTCP = $i_useTCP;
        return $this;
    }


    /**
     * adds a SIG RR object for authentication
     *
     * @param SIG|string $filename a signature or the name of a file to load the signature from.
     *
     * @return bool
     * @throws Exception
     * @access public
     * @since  function available since release 1.1.0
     *
     */
    public function signSIG0( SIG|string $filename ) : bool {
        //
        // check for OpenSSL
        //
        if ( extension_loaded( 'openssl' ) === false ) {

            throw new Exception(
                'the OpenSSL extension is required to use SIG(0).',
                Lookups::E_OPENSSL_UNAVAIL
            );
        }

        //
        // if the SIG was pre-created, then use it as-is
        //
        if ( $filename instanceof SIG ) {

            $this->authSignature = $filename;

        } else {

            //
            // otherwise, it's filename which needs to be parsed and processed.
            //
            $private = new PrivateKey( $filename );

            //
            // create a new SIG object
            //
            $this->authSignature = new SIG();

            //
            // reset some values
            //
            $this->authSignature->name = $private->signName;
            $this->authSignature->ttl = 0;
            $this->authSignature->class = 'ANY';

            //
            // these values are pulled from the private key
            //
            $this->authSignature->algorithm = $private->algorithm;
            $this->authSignature->keytag = $private->keytag;
            $this->authSignature->signName = $private->signName;

            //
            // these values are hard-coded for SIG0
            //
            $this->authSignature->typeCovered = 'SIG0';
            $this->authSignature->labels = 0;
            $this->authSignature->origTTL = 0;

            //
            // generate the dates
            //
            $t = time();

            $this->authSignature->sigInception = gmdate( 'YmdHis', $t );
            $this->authSignature->sigExpiration = gmdate( 'YmdHis', $t + 500 );

            //
            // store the private key in the SIG object for later.
            //
            $this->authSignature->privateKey = $private;
        }

        //
        // only RSA algorithms are supported for SIG(0)
        //
        switch ( $this->authSignature->algorithm ) {
            case Lookups::DNSSEC_ALGORITHM_RSAMD5:
            case Lookups::DNSSEC_ALGORITHM_RSASHA1:
            case Lookups::DNSSEC_ALGORITHM_RSASHA256:
            case Lookups::DNSSEC_ALGORITHM_RSASHA512:
            case Lookups::DNSSEC_ALGORITHM_DSA:
                break;
            default:
                throw new Exception(
                    'only asymmetric algorithms work with SIG(0)!',
                    Lookups::E_OPENSSL_INV_ALGO
                );
        }

        return true;
    }


    /**
     * adds a TSIG RR object for authentication
     *
     * @param TSIG|string $key_name the key name to use for the TSIG RR
     * @param string      $signature the key to sign the request.
     * @param string      $algorithm the algorithm to use
     *
     * @return bool
     * @access public
     * @throws Exception
     * @since  function available since release 1.1.0
     *
     */
    public function signTSIG(
        TSIG|string $key_name, string $signature = '', string $algorithm = TSIG::HMAC_MD5
    ) : bool {
        //
        // if the TSIG was pre-created and passed in, then we can just use
        // it as provided.
        //
        if ( $key_name instanceof TSIG ) {

            $this->authSignature = $key_name;

        } else {

            //
            // otherwise create the TSIG RR, but don't add it just yet; TSIG needs
            // to be added as the last additional entry so we'll add it just
            // before we send.
            //
            $xx = RR::fromString(
                strtolower( trim( $key_name ) ) .
                ' TSIG ' . $signature
            );
            assert( $xx instanceof TSIG );
            $this->authSignature = $xx;

            //
            // set the algorithm to use
            //
            $this->authSignature->algorithm = $algorithm;
        }

        return true;
    }


    /**
     * sets the name servers to be used, specified as IPv4 or IPv6 addresses
     *
     * @param ?string $resolvConf a filename to parse in the resolv.conf format or null
     *                             to use the default resolv.conf file
     *
     * @return static
     * @throws Exception
     * @access public
     *
     */
    public function useResolvConf( ?string $resolvConf = null ) : static {
        //
        // temporary list of name servers; do it this way rather than just
        // resetting the local nameservers value, just in case an exception
        // is thrown here; this way we might avoid ending up with an empty
        // list of nameservers.
        //
        $ns = [];

        if ( is_null( $resolvConf ) ) {
            $resolvConf = self::RESOLV_CONF;
        }

        //
        // check to see if the file is readable
        //
        if ( is_readable( $resolvConf ) !== true ) {
            throw new Exception(
                'resolver file file provided is not readable: ' . $resolvConf,
                Lookups::E_NS_INVALID_FILE
            );
        }

        $data = file_get_contents( $resolvConf );
        if ( $data === false ) {
            throw new Exception(
                'failed to read contents of file: ' . $resolvConf,
                Lookups::E_NS_INVALID_FILE
            );
        }

        $lines = explode( "\n", $data );

        foreach ( $lines as $line ) {

            $line = trim( $line );

            //
            // ignore empty lines, and lines that are commented out
            //
            if ( ( strlen( $line ) == 0 )
                || ( $line[ 0 ] == '#' )
                || ( $line[ 0 ] == ';' )
            ) {
                continue;
            }

            //
            // ignore lines with no spaces in them.
            //
            if ( ! str_contains( $line, ' ' ) ) {
                continue;
            }

            [ $key, $value ] = preg_split( '/\s+/', $line, 2 );

            $key = trim( strtolower( $key ) );
            $value = trim( strtolower( $value ) );

            switch ( $key ) {
                case 'nameserver':

                    //
                    // nameserver can be a IPv4 or IPv6 address
                    //
                    if ( self::isIPv4( $value )
                        || self::isIPv6( $value )
                    ) {

                        $ns[] = $value;
                    } else {

                        throw new Exception(
                            'invalid nameserver entry: ' . $value,
                            Lookups::E_NS_INVALID_ENTRY
                        );
                    }
                    break;

                case 'domain':
                    $this->domain = $value;
                    break;

                case 'search':
                    $this->searchList = preg_split( '/\s+/', $value );
                    break;

                case 'options':
                    $this->parseOptions( $value );
                    break;

            }
        }

        //
        // if we don't have a domain, but we have a search list, then
        // take the first entry on the search list as the domain
        //
        if ( ( strlen( $this->domain ) == 0 )
            && ( count( $this->searchList ) > 0 )
        ) {
            $this->domain = $this->searchList[ 0 ];
        }

        $this->setNameServers( $ns );

        return $this;

    }


    /**
     * sends a standard Net_DNS2_Packet_Request packet
     *
     * @param RequestPacket $request a Net_DNS2_Packet_Request object
     * @param bool          $use_tcp true/false if the function should
     *                                 use TCP for the request
     *
     * @return ResponsePacket
     * @throws Exception
     * @access protected
     *
     */
    protected function sendPacket( RequestPacket $request, bool $use_tcp ) : ResponsePacket {
        //
        // get the data from the packet
        //
        $data = $request->get();
        if ( strlen( $data ) < Lookups::DNS_HEADER_SIZE ) {

            throw new Exception(
                'invalid or empty packet for sending!',
                Lookups::E_PACKET_INVALID,
                null,
                $request
            );
        }

        $nameServers = $this->getNameServers();

        //
        // loop so we can handle server errors
        //

        while ( 1 ) {

            //
            // grab the next DNS server
            //
            $ns = current( $nameServers );
            next( $nameServers );

            if ( $ns === false ) {

                if ( ! is_null( $this->lastException ) ) {
                    throw $this->lastException;
                } else {

                    throw new Exception(
                        'every name server provided has failed',
                        Lookups::E_NS_FAILED
                    );
                }
            }

            //
            // if the use TCP flag (force TCP) is set, or the packet is bigger than our
            // max allowed UDP size, which is either 512, or if this is DNSSEC request,
            // then whatever the configured dnssec_payload_size is.
            //
            $max_udp_size = Lookups::DNS_MAX_UDP_SIZE;
            if ( $this->dnssec ) {
                $max_udp_size = $this->dnssecPayloadSize;
            }

            if ( $use_tcp || ( strlen( $data ) > $max_udp_size ) ) {

                try {
                    $response = $this->sendTCPRequest( $ns, $data, $request->question[ 0 ]->qtype == 'AXFR' );
                } catch ( Exception $e ) {

                    $this->lastException = $e;
                    $this->lastExceptionList[ $ns ] = $e;

                    continue;
                }

                //
                // otherwise, send it using UDP
                //
            } else {

                try {
                    $response = $this->sendUDPRequest( $ns, $data );

                    //
                    // check the packet header for a truncated bit; if it was truncated,
                    // then re-send the request as TCP.
                    //
                    if ( $response->header->tc == 1 ) {

                        $response = $this->sendTCPRequest( $ns, $data );
                    }

                } catch ( Exception $e ) {

                    $this->lastException = $e;
                    $this->lastExceptionList[ $ns ] = $e;

                    continue;
                }
            }

            //
            // make sure header id's match between the request and response
            //
            if ( $request->header->id != $response->header->id ) {

                $this->lastException = new Exception(

                    'invalid header: the request and response id do not match.',
                    Lookups::E_HEADER_INVALID,
                    null,
                    $request,
                    $response
                );

                $this->lastExceptionList[ $ns ] = $this->lastException;
                continue;
            }

            //
            // make sure the response is actually a response
            //
            // 0 = query, 1 = response
            //
            if ( $response->header->qr != Lookups::QR_RESPONSE ) {

                $this->lastException = new Exception(

                    'invalid header: the response provided is not a response packet.',
                    Lookups::E_HEADER_INVALID,
                    null,
                    $request,
                    $response
                );

                $this->lastExceptionList[ $ns ] = $this->lastException;
                continue;
            }

            //
            // make sure the response code in the header is ok
            //
            if ( $response->header->rCode != Lookups::RCODE_NOERROR ) {

                $this->lastException = new Exception(

                    'DNS request failed: ' .
                    Lookups::$result_code_messages[ $response->header->rCode ],
                    $response->header->rCode,
                    null,
                    $request,
                    $response
                );

                $this->lastExceptionList[ $ns ] = $this->lastException;
                continue;
            }

            break;
        }

        return $response;
    }


    /**
     * parses the options line from a resolv.conf file; we don't support all the options
     * yet, and using them is optional.
     *
     * @param string $value is the options string from the resolv.conf file.
     *
     * @return void
     * @access private
     *
     */
    private function parseOptions( string $value ) : void {
        //
        // if overrides are disabled (the default), or the options list is empty for some
        // reason, then we don't need to do any of this work.
        //
        if ( ! $this->useResolvOptions || ( strlen( $value ) == 0 ) ) {

            return;
        }

        $options = preg_split( '/\s+/', strtolower( $value ) );

        foreach ( $options as $option ) {

            //
            // override the timeout value from the resolv.conf file.
            //
            if ( ( strncmp( $option, 'timeout', 7 ) == 0 ) && ( str_contains( $option, ':' ) ) ) {

                $val = (int) explode( ':', $option )[ 1 ];

                if ( ( $val > 0 ) && ( $val <= 30 ) ) {

                    $this->timeout = $val;
                }

                //
                // the rotate option just enabled the ns_random option
                //
            } elseif ( strncmp( $option, 'rotate', 6 ) == 0 ) {

                $this->nsRandom = true;
            }
        }

    }


    /**
     * sends a DNS request using TCP
     *
     * @param string $_ns the name server to use for the request
     * @param string $_data the raw DNS packet data
     * @param bool   $_axfr if this is a zone transfer request
     *
     * @return ResponsePacket the response object
     * @throws Exception
     * @access private
     *
     */
    private function sendTCPRequest( string $_ns, string $_data, bool $_axfr = false ) : ResponsePacket {
        $tcp = $this->transportManager->acquire( Socket::SOCK_STREAM, $_ns, $this->dnsPort );
        assert( $tcp instanceOf TCPTransport );
        $tcp->sendData( $_data );

        if ( $_axfr ) {
            $rsp = $tcp->receiveAXFR();
        } else {
            $rsp = $tcp->receiveResponse();
        }
        $this->transportManager->release( $tcp );
        return $rsp;
    }


    /**
     * sends a DNS request using UDP
     *
     * @param string $_ns the name server to use for the request
     * @param string $_data the raw DNS packet data
     *
     * @return ResponsePacket the response object
     * @throws Exception
     * @access private
     *
     */
    private function sendUDPRequest( string $_ns, string $_data ) : ResponsePacket {
        $udp = $this->transportManager->acquire( Socket::SOCK_DGRAM, $_ns, $this->dnsPort );
        $udp->sendData( $_data );
        $rsp = $udp->receiveResponse();
        $this->transportManager->release( $udp );
        return $rsp;
    }


}

