<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\RR\A;
use JDWX\DNSQuery\RR\AAAA;
use JDWX\DNSQuery\RR\AFSDB;
use JDWX\DNSQuery\RR\ALIAS;
use JDWX\DNSQuery\RR\ALL;
use JDWX\DNSQuery\RR\AMTRELAY;
use JDWX\DNSQuery\RR\ANY;
use JDWX\DNSQuery\RR\APL;
use JDWX\DNSQuery\RR\ATMA;
use JDWX\DNSQuery\RR\AVC;
use JDWX\DNSQuery\RR\CAA;
use JDWX\DNSQuery\RR\CDNSKEY;
use JDWX\DNSQuery\RR\CDS;
use JDWX\DNSQuery\RR\CERT;
use JDWX\DNSQuery\RR\CNAME;
use JDWX\DNSQuery\RR\CSYNC;
use JDWX\DNSQuery\RR\DHCID;
use JDWX\DNSQuery\RR\DLV;
use JDWX\DNSQuery\RR\DNAME;
use JDWX\DNSQuery\RR\DNSKEY;
use JDWX\DNSQuery\RR\DS;
use JDWX\DNSQuery\RR\EID;
use JDWX\DNSQuery\RR\EUI48;
use JDWX\DNSQuery\RR\EUI64;
use JDWX\DNSQuery\RR\HINFO;
use JDWX\DNSQuery\RR\HIP;
use JDWX\DNSQuery\RR\IPSECKEY;
use JDWX\DNSQuery\RR\ISDN;
use JDWX\DNSQuery\RR\KEY;
use JDWX\DNSQuery\RR\KX;
use JDWX\DNSQuery\RR\L32;
use JDWX\DNSQuery\RR\L64;
use JDWX\DNSQuery\RR\LOC;
use JDWX\DNSQuery\RR\LP;
use JDWX\DNSQuery\RR\MX;
use JDWX\DNSQuery\RR\NAPTR;
use JDWX\DNSQuery\RR\NID;
use JDWX\DNSQuery\RR\NIMLOC;
use JDWX\DNSQuery\RR\NS;
use JDWX\DNSQuery\RR\NSAP;
use JDWX\DNSQuery\RR\NSEC;
use JDWX\DNSQuery\RR\NSEC3;
use JDWX\DNSQuery\RR\NSEC3PARAM;
use JDWX\DNSQuery\RR\OPENPGPKEY;
use JDWX\DNSQuery\RR\OPT;
use JDWX\DNSQuery\RR\PTR;
use JDWX\DNSQuery\RR\PX;
use JDWX\DNSQuery\RR\RP;
use JDWX\DNSQuery\RR\RRSIG;
use JDWX\DNSQuery\RR\RT;
use JDWX\DNSQuery\RR\SIG;
use JDWX\DNSQuery\RR\SMIMEA;
use JDWX\DNSQuery\RR\SOA;
use JDWX\DNSQuery\RR\SPF;
use JDWX\DNSQuery\RR\SRV;
use JDWX\DNSQuery\RR\SSHFP;
use JDWX\DNSQuery\RR\TA;
use JDWX\DNSQuery\RR\TALINK;
use JDWX\DNSQuery\RR\TKEY;
use JDWX\DNSQuery\RR\TLSA;
use JDWX\DNSQuery\RR\TSIG;
use JDWX\DNSQuery\RR\TXT;
use JDWX\DNSQuery\RR\TYPE65534;
use JDWX\DNSQuery\RR\URI;
use JDWX\DNSQuery\RR\WKS;
use JDWX\DNSQuery\RR\X25;


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

//
// initialize the packet id value
//
Lookups::$next_packet_id   = mt_rand(0, 65535);

//
// build the reverse lookup tables; this is just so we don't have to
// have duplicate static content lying around.
//
Lookups::$rr_types_by_id       = array_flip(Lookups::$rr_types_by_name);
Lookups::$classes_by_id        = array_flip(Lookups::$classes_by_name);
Lookups::$rr_types_class_to_id = array_flip(Lookups::$rr_types_id_to_class);
Lookups::$algorithm_name_to_id = array_flip(Lookups::$algorithm_id_to_name);
Lookups::$digest_name_to_id    = array_flip(Lookups::$digest_id_to_name);
Lookups::$rr_qtypes_by_id      = array_flip(Lookups::$rr_qtypes_by_name);
Lookups::$rr_metatypes_by_id   = array_flip(Lookups::$rr_metatypes_by_name);
Lookups::$protocol_by_id       = array_flip(Lookups::$protocol_by_name);

/**
 * This class provides simple lookups used throughout the Net_DNS2 code
 *
 */
class Lookups
{
    /*
     * size (in bytes) of a header in a standard DNS packet
     */
    public const DNS_HEADER_SIZE       = 12;

    /*
     * max size of a UDP packet
     */
    public const DNS_MAX_UDP_SIZE      = 512;
    
    /*
     * Query/Response flag
     */
    public const QR_QUERY              = 0;        // RFC 1035
    public const QR_RESPONSE           = 1;        // RFC 1035

    /*
     * DNS Op Codes
     */
    public const OPCODE_QUERY          = 0;        // RFC 1035
    public const OPCODE_IQUERY         = 1;        // RFC 1035, RFC 3425
    public const OPCODE_STATUS         = 2;        // RFC 1035
    public const OPCODE_NOTIFY         = 4;        // RFC 1996
    public const OPCODE_UPDATE         = 5;        // RFC 2136
    public const OPCODE_DSO            = 6;        // RFC 8490

    /*
     * Resource Record Classes
     */
    public const RR_CLASS_IN = 1;        // RFC 1035
    public const RR_CLASS_CH = 3;        // RFC 1035
    public const RR_CLASS_HS = 4;        // RFC 1035
    public const RR_CLASS_NONE = 254;      // RFC 2136
    public const RR_CLASS_ANY = 255;      // RFC 1035

    /*
     * DNS Response Codes
     */
    public const RCODE_NOERROR = 0;        // RFC 1035
    public const RCODE_FORMERR = 1;        // RFC 1035
    public const RCODE_SERVFAIL = 2;        // RFC 1035
    public const RCODE_NXDOMAIN = 3;        // RFC 1035
    public const RCODE_NOTIMP = 4;        // RFC 1035
    public const RCODE_REFUSED = 5;        // RFC 1035
    public const RCODE_YXDOMAIN = 6;        // RFC 2136
    public const RCODE_YXRRSET = 7;        // RFC 2136
    public const RCODE_NXRRSET = 8;        // RFC 2136
    public const RCODE_NOTAUTH = 9;        // RFC 2136
    public const RCODE_NOTZONE = 10;       // RFC 2136
    public const RCODE_DSOTYPENI = 11;       // RFC 8490

    // 12-15 reserved

    public const RCODE_BADSIG = 16;       // RFC 2845
    public const RCODE_BADVERS = 16;       // RFC 6891
    public const RCODE_BADKEY = 17;       // RFC 2845
    public const RCODE_BADTIME = 18;       // RFC 2845
    public const RCODE_BADMODE = 19;       // RFC 2930
    public const RCODE_BADNAME = 20;       // RFC 2930
    public const RCODE_BADALG = 21;       // RFC 2930
    public const RCODE_BADTRUNC = 22;       // RFC 4635
    public const RCODE_BADCOOKIE = 23;       // RFC 7873

    /*
     * internal errors codes returned by the exceptions class
     */
    public const E_NONE = 0;
    public const E_DNS_FORMERR = self::RCODE_FORMERR;
    public const E_DNS_SERVFAIL = self::RCODE_SERVFAIL;
    public const E_DNS_NXDOMAIN = self::RCODE_NXDOMAIN;
    public const E_DNS_NOTIMP = self::RCODE_NOTIMP;
    public const E_DNS_REFUSED = self::RCODE_REFUSED;
    public const E_DNS_YXDOMAIN = self::RCODE_YXDOMAIN;
    public const E_DNS_YXRRSET = self::RCODE_YXRRSET;
    public const E_DNS_NXRRSET = self::RCODE_NXRRSET;
    public const E_DNS_NOTAUTH = self::RCODE_NOTAUTH;
    public const E_DNS_NOTZONE = self::RCODE_NOTZONE;

    // 11-15 reserved

    public const E_DNS_BADSIG = self::RCODE_BADSIG;
    public const E_DNS_BADKEY = self::RCODE_BADKEY;
    public const E_DNS_BADTIME = self::RCODE_BADTIME;
    public const E_DNS_BADMODE = self::RCODE_BADMODE;
    public const E_DNS_BADNAME = self::RCODE_BADNAME;
    public const E_DNS_BADALG = self::RCODE_BADALG;
    public const E_DNS_BADTRUNC = self::RCODE_BADTRUNC;
    public const E_DNS_BADCOOKIE = self::RCODE_BADCOOKIE;

    // other error conditions

    public const E_NS_INVALID_FILE = 200;
    public const E_NS_INVALID_ENTRY = 201;
    public const E_NS_FAILED = 202;
    public const E_NS_SOCKET_FAILED = 203;
    public const E_NS_INVALID_SOCKET = 204;

    public const E_PACKET_INVALID = 300;
    public const E_PARSE_ERROR = 301;
    public const E_HEADER_INVALID = 302;
    public const E_QUESTION_INVALID = 303;
    public const E_RR_INVALID = 304;

    public const E_OPENSSL_ERROR = 400;
    public const E_OPENSSL_UNAVAIL = 401;
    public const E_OPENSSL_INV_PKEY = 402;
    public const E_OPENSSL_INV_ALGO = 403;

    public const E_CACHE_UNSUPPORTED = 500;
    public const E_CACHE_SHM_FILE = 501;
    public const E_CACHE_SHM_UNAVAIL = 502;

    /*
     * EDNS0 Option Codes (OPT)
     */
    // 0 - Reserved
    public const EDNS0_OPT_LLQ = 1;
    public const EDNS0_OPT_UL = 2;
    public const EDNS0_OPT_NSID = 3;
    // 4 - Reserved
    public const EDNS0_OPT_DAU = 5;
    public const EDNS0_OPT_DHU = 6;
    public const EDNS0_OPT_N3U = 7;
    public const EDNS0_OPT_CLIENT_SUBNET = 8;
    public const EDNS0_OPT_EXPIRE = 9;
    public const EDNS0_OPT_COOKIE = 10;
    public const EDNS0_OPT_TCP_KEEPALIVE = 11;
    public const EDNS0_OPT_PADDING = 12;
    public const EDNS0_OPT_CHAIN = 13;
    public const EDNS0_OPT_KEY_TAG = 14;
    // 15 - unassigned
    public const EDNS0_OPT_CLIENT_TAG = 16;
    public const EDNS0_OPT_SERVER_TAG = 17;
    // 18-26945 - unassigned
    public const EDNS0_OPT_DEVICEID = 26946;

    /*
     * DNSSEC Algorithms
     */
    public const DNSSEC_ALGORITHM_RES = 0;
    public const DNSSEC_ALGORITHM_RSAMD5 = 1;
    public const DNSSEC_ALGORITHM_DH = 2;
    public const DNSSEC_ALGORITHM_DSA = 3;
    public const DNSSEC_ALGORITHM_ECC = 4;
    public const DNSSEC_ALGORITHM_RSASHA1 = 5;
    public const DNSSEC_ALGORITHM_DSANSEC3SHA1 = 6;
    public const DSNSEC_ALGORITHM_RSASHA1NSEC3SHA1 = 7;
    public const DNSSEC_ALGORITHM_RSASHA256 = 8;
    public const DNSSEC_ALGORITHM_RSASHA512 = 10;
    public const DNSSEC_ALGORITHM_ECCGOST = 12;
    public const DNSSEC_ALGORITHM_ECDSAP256SHA256 = 13;
    public const DNSSEC_ALGORITHM_ECDSAP384SHA384 = 14;
    public const DNSSEC_ALGORITHM_ED25519 = 15;
    public const DNSSEC_ALGORITHM_ED448 = 16;
    public const DNSSEC_ALGORITHM_INDIRECT = 252;
    public const DNSSEC_ALGORITHM_PRIVATEDNS = 253;
    public const DNSSEC_ALGORITHM_PRIVATEOID = 254;

    /*
     * DNSSEC Digest Types
     */
    public const DNSSEC_DIGEST_RES = 0;
    public const DNSSEC_DIGEST_SHA1 = 1;
    public const DNSSEC_DIGEST_SHA256 = 2;
    public const DNSSEC_DIGEST_GOST = 3;
    public const DNSSEC_DIGEST_SHA384 = 4;

    /*
     * The packet id used when sending requests
     */
    public static int $next_packet_id;

    /*
     * Used to map resource record types to their id's, and back
     */
    public static array $rr_types_by_id   = [];
    public static array $rr_types_by_name = [

        'SIG0'          => 0,       // RFC 2931 pseudo type
        'A'             => 1,       // RFC 1035
        'NS'            => 2,       // RFC 1035
        'MD'            => 3,       // RFC 1035 - obsolete, Not implemented
        'MF'            => 4,       // RFC 1035 - obsolete, Not implemented
        'CNAME'         => 5,       // RFC 1035
        'SOA'           => 6,       // RFC 1035
        'MB'            => 7,       // RFC 1035 - obsolete, Not implemented
        'MG'            => 8,       // RFC 1035 - obsolete, Not implemented
        'MR'            => 9,       // RFC 1035 - obsolete, Not implemented
        'NULL'          => 10,      // RFC 1035 - obsolete, Not implemented
        'WKS'           => 11,      // RFC 1035
        'PTR'           => 12,      // RFC 1035
        'HINFO'         => 13,      // RFC 1035
        'MINFO'         => 14,      // RFC 1035 - obsolete, Not implemented
        'MX'            => 15,      // RFC 1035
        'TXT'           => 16,      // RFC 1035
        'RP'            => 17,      // RFC 1183
        'AFSDB'         => 18,      // RFC 1183
        'X25'           => 19,      // RFC 1183
        'ISDN'          => 20,      // RFC 1183
        'RT'            => 21,      // RFC 1183
        'NSAP'          => 22,      // RFC 1706
        'NSAP_PTR'      => 23,      // RFC 1348 - obsolete, Not implemented
        'SIG'           => 24,      // RFC 2535
        'KEY'           => 25,      // RFC 2535, RFC 2930
        'PX'            => 26,      // RFC 2163
        'GPOS'          => 27,      // RFC 1712 - Not implemented
        'AAAA'          => 28,      // RFC 3596
        'LOC'           => 29,      // RFC 1876
        'NXT'           => 30,      // RFC 2065, obsoleted by RFC 3755
        'EID'           => 31,      // [Patton][Patton1995]
        'NIMLOC'        => 32,      // [Patton][Patton1995]
        'SRV'           => 33,      // RFC 2782
        'ATMA'          => 34,      // Windows only
        'NAPTR'         => 35,      // RFC 2915
        'KX'            => 36,      // RFC 2230
        'CERT'          => 37,      // RFC 4398
        'A6'            => 38,      // downgraded to experimental by RFC 3363
        'DNAME'         => 39,      // RFC 2672
        'SINK'          => 40,      // Not implemented
        'OPT'           => 41,      // RFC 2671
        'APL'           => 42,      // RFC 3123
        'DS'            => 43,      // RFC 4034
        'SSHFP'         => 44,      // RFC 4255
        'IPSECKEY'      => 45,      // RFC 4025
        'RRSIG'         => 46,      // RFC 4034
        'NSEC'          => 47,      // RFC 4034
        'DNSKEY'        => 48,      // RFC 4034
        'DHCID'         => 49,      // RFC 4701
        'NSEC3'         => 50,      // RFC 5155
        'NSEC3PARAM'    => 51,      // RFC 5155
        'TLSA'          => 52,      // RFC 6698
        'SMIMEA'        => 53,      // RFC 8162

                                    // 54 unassigned

        'HIP'           => 55,      // RFC 5205
        'NINFO'         => 56,      // Not implemented
        'RKEY'          => 57,      // Not implemented
        'TALINK'        => 58,      //
        'CDS'           => 59,      // RFC 7344
        'CDNSKEY'       => 60,      // RFC 7344
        'OPENPGPKEY'    => 61,      // RFC 7929
        'CSYNC'         => 62,      // RFC 7477
        'ZONEMD'        => 63,      // Not implemented yet
        'SVCB'          => 64,      // Not implemented yet
        'HTTPS'         => 65,      // Not implemented yet

                                    // 66 - 98 unassigned

        'SPF'           => 99,      // RFC 4408
        'UINFO'         => 100,     // no RFC, Not implemented
        'UID'           => 101,     // no RFC, Not implemented
        'GID'           => 102,     // no RFC, Not implemented
        'UNSPEC'        => 103,     // no RFC, Not implemented
        'NID'           => 104,     // RFC 6742
        'L32'           => 105,     // RFC 6742
        'L64'           => 106,     // RFC 6742
        'LP'            => 107,     // RFC 6742
        'EUI48'         => 108,     // RFC 7043
        'EUI64'         => 109,     // RFC 7043

                                    // 110 - 248 unassigned

        'TKEY'          => 249,     // RFC 2930
        'TSIG'          => 250,     // RFC 2845
        'IXFR'          => 251,     // RFC 1995 - only a full (AXFR) is supported
        'AXFR'          => 252,     // RFC 1035
        'MAILB'         => 253,     // RFC 883, Not implemented
        'MAILA'         => 254,     // RFC 973, Not implemented
        'ANY'           => 255,     // RFC 1035 - we support both 'ANY' and '*'
        'URI'           => 256,     // RFC 7553
        'CAA'           => 257,     // RFC 8659
        'AVC'           => 258,     // Application Visibility and Control
        'DOA'           => 259,     // Not implemented yet
        'AMTRELAY'      => 260,     // RFC 8777

                                    // 261 - 32767 unassigned

        'TA'            => 32768,   // same as DS
        'DLV'           => 32769,   // RFC 4431
        'ALIAS'         => 65401,
        'TYPE65534'     => 65534    // Private Bind record
    ];

    /*
     * Q-types and Meta-types - defined in RFC2929 section 3.1
     */
    public static array $rr_qtypes_by_id      = [];
    public static array $rr_qtypes_by_name    = [

        'IXFR'          => 251,     // RFC 1995 - only a full (AXFR) is supported
        'AXFR'          => 252,     // RFC 1035
        'MAILB'         => 253,     // RFC 883, Not implemented
        'MAILA'         => 254,     // RFC 973, Not implemented
        'ANY'           => 255      // RFC 1035 - we support both 'ANY' and '*'
    ];
    
    public static array $rr_metatypes_by_id   = [];
    public static array $rr_metatypes_by_name = [

        'OPT'           => 41,      // RFC 2671
        'TKEY'          => 249,     // RFC 2930
        'TSIG'          => 250      // RFC 2845
    ];

    /*
     * used to map resource record id's to RR class names
     */
    public static array $rr_types_class_to_id = [];
    public static array $rr_types_id_to_class = [

        1           => A::class,
        2           => NS::class,
        5           => CNAME::class,
        6           => SOA::class,
        11          => WKS::class,
        12          => PTR::class,
        13          => HINFO::class,
        15          => MX::class,
        16          => TXT::class,
        17          => RP::class,
        18          => AFSDB::class,
        19          => X25::class,
        20          => ISDN::class,
        21          => RT::class,
        22          => NSAP::class,
        24          => SIG::class,
        25          => KEY::class,
        26          => PX::class,
        28          => AAAA::class,
        29          => LOC::class,
        31          => EID::class,
        32          => NIMLOC::class,
        33          => SRV::class,
        34          => ATMA::class,
        35          => NAPTR::class,
        36          => KX::class,
        37          => CERT::class,
        39          => DNAME::class,
        41          => OPT::class,
        42          => APL::class,
        43          => DS::class,
        44          => SSHFP::class,
        45          => IPSECKEY::class,
        46          => RRSIG::class,
        47          => NSEC::class,
        48          => DNSKEY::class,
        49          => DHCID::class,
        50          => NSEC3::class,
        51          => NSEC3PARAM::class,
        52          => TLSA::class,
        53          => SMIMEA::class,
        55          => HIP::class,
        58          => TALINK::class,
        59          => CDS::class,
        60          => CDNSKEY::class,
        61          => OPENPGPKEY::class,
        62          => CSYNC::class,
        99          => SPF::class,
        104         => NID::class,
        105         => L32::class,
        106         => L64::class,
        107         => LP::class,
        108         => EUI48::class,
        109         => EUI64::class,

        249         => TKEY::class,
        250         => TSIG::class,

    //    251            - IXFR - handled as a full zone transfer (252)
    //    252            - AXFR - handled as a function call

        255         => ANY::class,
        256         => URI::class,
        257         => CAA::class,
        258         => AVC::class,
        260         => AMTRELAY::class,
        32768       => TA::class,
        32769       => DLV::class,
        65401       => ALIAS::class,
        65534       => TYPE65534::class,
    ];

    /*
     * used to map resource record class names to their id's, and back
     */
    public static array $classes_by_id    = [];
    public static array $classes_by_name  = [

        'IN'    => self::RR_CLASS_IN,        // RFC 1035
        'CH'    => self::RR_CLASS_CH,        // RFC 1035
        'HS'    => self::RR_CLASS_HS,        // RFC 1035
        'NONE'  => self::RR_CLASS_NONE,      // RFC 2136
        'ANY'   => self::RR_CLASS_ANY        // RFC 1035
    ];

    /*
     * maps response codes to error messages
     */
    public static array $result_code_messages = [

        self::RCODE_NOERROR     => 'The request completed successfully.',
        self::RCODE_FORMERR     => 'The name server was unable to interpret the query.',
        self::RCODE_SERVFAIL    => 'The name server was unable to process this query due to a problem with the name server.',
        self::RCODE_NXDOMAIN    => 'The domain name referenced in the query does not exist.',
        self::RCODE_NOTIMP      => 'The name server does not support the requested kind of query.',
        self::RCODE_REFUSED     => 'The name server refuses to perform the specified operation for policy reasons.',
        self::RCODE_YXDOMAIN    => 'Name Exists when it should not.',
        self::RCODE_YXRRSET     => 'RR Set Exists when it should not.',
        self::RCODE_NXRRSET     => 'RR Set that should exist does not.',
        self::RCODE_NOTAUTH     => 'Server Not Authoritative for zone.',
        self::RCODE_NOTZONE     => 'Name not contained in zone.',

        self::RCODE_BADSIG      => 'TSIG Signature Failure.',
        self::RCODE_BADKEY      => 'Key not recognized.',
        self::RCODE_BADTIME     => 'Signature out of time window.',
        self::RCODE_BADMODE     => 'Bad TKEY Mode.',
        self::RCODE_BADNAME     => 'Duplicate key name.',
        self::RCODE_BADALG      => 'Algorithm not supported.',
        self::RCODE_BADTRUNC    => 'Bad truncation.'
    ];

    /*
     * maps response codes to short status tags
     */
    public static array $result_code_tags = [
        self::RCODE_NOERROR    => 'NOERROR',
        self::RCODE_FORMERR    => 'FORMERR',
        self::RCODE_SERVFAIL   => 'SERVFAIL',
        self::RCODE_NXDOMAIN   => 'NXDOMAIN',
        self::RCODE_NOTIMP     => 'NOTIMP',
        self::RCODE_REFUSED    => 'REFUSED',
        self::RCODE_YXDOMAIN   => 'YXDOMAIN',
        self::RCODE_YXRRSET    => 'YXRRSET',
        self::RCODE_NXRRSET    => 'NXRRSET',
        self::RCODE_NOTAUTH    => 'NOTAUTH',
        self::RCODE_NOTZONE    => 'NOTZONE',
        self::RCODE_BADSIG     => 'BADSIG',
        self::RCODE_BADKEY     => 'BADKEY',
        self::RCODE_BADTIME    => 'BADTIME',
        self::RCODE_BADMODE    => 'BADMODE',
        self::RCODE_BADNAME    => 'BADNAME',
        self::RCODE_BADALG     => 'BADALG',
        self::RCODE_BADTRUNC   => 'BADTRUNC'
    ];


    /** Map opcodes to short text tags. */
    public static array $opcode_tags = [
        self::OPCODE_QUERY     => 'QUERY',
        self::OPCODE_IQUERY    => 'IQUERY',
        self::OPCODE_STATUS    => 'STATUS',
        3                      => 'OPCODE3',
        self::OPCODE_NOTIFY    => 'NOTIFY',
        self::OPCODE_UPDATE    => 'UPDATE',
        6                      => 'OPCODE6',
        7                      => 'OPCODE7',
        8                      => 'OPCODE8',
        9                      => 'OPCODE9',
        10                     => 'OPCODE10',
        11                     => 'OPCODE11',
        12                     => 'OPCODE12',
        13                     => 'OPCODE13',
        14                     => 'OPCODE14',
        15                     => 'OPCODE15'
    ];


    /*
     * maps DNS SEC algorithms to their mnemonics
     */
    public static array $algorithm_name_to_id = [];
    public static array $algorithm_id_to_name = [
    
        self::DNSSEC_ALGORITHM_RES                  => 'RES',
        self::DNSSEC_ALGORITHM_RSAMD5               => 'RSAMD5',
        self::DNSSEC_ALGORITHM_DH                   => 'DH',
        self::DNSSEC_ALGORITHM_DSA                  => 'DSA',
        self::DNSSEC_ALGORITHM_ECC                  => 'ECC',
        self::DNSSEC_ALGORITHM_RSASHA1              => 'RSASHA1',
        self::DNSSEC_ALGORITHM_DSANSEC3SHA1         => 'DSA-NSEC3-SHA1',
        self::DSNSEC_ALGORITHM_RSASHA1NSEC3SHA1     => 'RSASHA1-NSEC3-SHA1',
        self::DNSSEC_ALGORITHM_RSASHA256            => 'RSASHA256',
        self::DNSSEC_ALGORITHM_RSASHA512            => 'RSASHA512',
        self::DNSSEC_ALGORITHM_ECCGOST              => 'ECC-GOST',
        self::DNSSEC_ALGORITHM_ECDSAP256SHA256      => 'ECDSAP256SHA256',
        self::DNSSEC_ALGORITHM_ECDSAP384SHA384      => 'ECDSAP384SHA384',
        self::DNSSEC_ALGORITHM_ED25519              => 'ED25519',
        self::DNSSEC_ALGORITHM_ED448                => 'ED448',
        self::DNSSEC_ALGORITHM_INDIRECT             => 'INDIRECT',
        self::DNSSEC_ALGORITHM_PRIVATEDNS           => 'PRIVATEDNS',
        self::DNSSEC_ALGORITHM_PRIVATEOID           => 'PRIVATEOID'
    ];

    /*
     * maps DNSSEC digest types to their mnemonics
     */
    public static array $digest_name_to_id = [];
    public static array $digest_id_to_name = [

        self::DNSSEC_DIGEST_RES         => 'RES',
        self::DNSSEC_DIGEST_SHA1        => 'SHA-1',
        self::DNSSEC_DIGEST_SHA256      => 'SHA-256',
        self::DNSSEC_DIGEST_GOST        => 'GOST-R-34.11-94',
        self::DNSSEC_DIGEST_SHA384      => 'SHA-384'
    ];

    /*
     * Protocols names - RFC 1010
     */
    public static array $protocol_by_id   = [];
    public static array $protocol_by_name = [

        'ICMP'          => 1,
        'IGMP'          => 2,
        'GGP'           => 3,
        'ST'            => 5,
        'TCP'           => 6,
        'UCL'           => 7,
        'EGP'           => 8,
        'IGP'           => 9,
        'BBN-RCC-MON'   => 10,
        'NVP-II'        => 11,
        'PUP'           => 12,
        'ARGUS'         => 13,
        'EMCON'         => 14,
        'XNET'          => 15,
        'CHAOS'         => 16,
        'UDP'           => 17,
        'MUX'           => 18,
        'DCN-MEAS'      => 19,
        'HMP'           => 20,
        'PRM'           => 21,
        'XNS-IDP'       => 22,
        'TRUNK-1'       => 23,
        'TRUNK-2'       => 24,
        'LEAF-1'        => 25,
        'LEAF-2'        => 26,
        'RDP'           => 27,
        'IRTP'          => 28,
        'ISO-TP4'       => 29,
        'NETBLT'        => 30,
        'MFE-NSP'       => 31,
        'MERIT-INP'     => 32,
        'SEP'           => 33,
        // 34 - 60      - Unassigned
        // 61           - any host internal protocol
        'CFTP'          => 62,
        // 63           - any local network
        'SAT-EXPAK'     => 64,
        'MIT-SUBNET'    => 65,
        'RVD'           => 66,
        'IPPC'          => 67,
        // 68           - any distributed file system
        'SAT-MON'       => 69,
        // 70           - Unassigned
        'IPCV'          => 71,
        // 72 - 75      - Unassigned
        'BR-SAT-MON'    => 76,
        // 77           - Unassigned
        'WB-MON'        => 78,
        'WB-EXPAK'      => 79
        // 80 - 254     - Unassigned
        // 255          - Reserved
    ];

    /** PHP DNS Constants to RR classes */
    public static array $rr_class_by_php_id = [
        DNS_A => A::class,
        DNS_CNAME => CNAME::class,
        DNS_HINFO => HINFO::class,
        DNS_CAA => CAA::class,
        DNS_MX => MX::class,
        DNS_NS => NS::class,
        DNS_PTR => PTR::class,
        DNS_SOA => SOA::class,
        DNS_TXT => TXT::class,
        DNS_AAAA => AAAA::class,
        DNS_SRV => SRV::class,
        DNS_NAPTR => NAPTR::class,
        DNS_ALL => ALL::class,
        DNS_ANY => ANY::class,
    ];

    /**
     * returns the next available packet id
     *
     * @return    int
     * @access    public
     *
     */
    public static function nextPacketId() : int
    {
        if (++self::$next_packet_id > 65535) {
            self::$next_packet_id = 1;
        }

        return self::$next_packet_id;
    }


}
