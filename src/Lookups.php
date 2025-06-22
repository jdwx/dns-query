<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Exceptions\Exception;


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

//
// initialize the packet id value
//

//
// build the reverse lookup tables; this is just so we don't have to
// have duplicate static content lying around.
//
Lookups::$algorithmNameToID = array_flip( Lookups::$algorithmIdToName );
Lookups::$digestNameToId = array_flip( Lookups::$digestIdToName );
Lookups::$rrQTypesById = array_flip( Lookups::$rrQTypesByName );
Lookups::$rrMetaTypesById = array_flip( Lookups::$rrMetaTypesByName );
Lookups::$protocolById = array_flip( Lookups::$protocolByName );


/**
 * This class provides simple lookups used throughout the Net_DNS2 code
 * @suppress PhanInvalidConstantExpression
 */
class Lookups {


    /** @const size (in bytes) of a header in a standard DNS packet */
    public const int DNS_HEADER_SIZE = 12;

    /** @const max size of a UDP packet */
    public const int DNS_MAX_UDP_SIZE = 512;


    # Internal error codes returned by the exceptions class

    /** @const No error. */
    public const int E_NONE = 0;

    /** @const Format error */
    public const int E_DNS_FORMERR = ReturnCode::FORMERR->value;

    /** @const Server failure */
    public const int E_DNS_SERVFAIL = ReturnCode::SERVFAIL->value;

    /** @const No such domain */
    public const int E_DNS_NXDOMAIN = ReturnCode::NXDOMAIN->value;

    /** @const Not implemented. */
    public const int E_DNS_NOTIMP = ReturnCode::NOTIMP->value;

    /** @const Refused */
    public const int E_DNS_REFUSED = ReturnCode::REFUSED->value;

    /** @const Name exists when it should not */
    public const int E_DNS_YXDOMAIN = ReturnCode::YXDOMAIN->value;

    /** @const RRset exists when it should not */
    public const int E_DNS_YXRRSET = ReturnCode::YXRRSET->value;

    /** @const RRset does not exist when it should */
    public const int E_DNS_NXRRSET = ReturnCode::NXRRSET->value;

    /** @const Not authoritative */
    public const int E_DNS_NOTAUTH = ReturnCode::NOTAUTH->value;

    /** @const Prerequisite zone not within update zone */
    public const int E_DNS_NOTZONE = ReturnCode::NOTZONE->value;

    # 11-15 reserved

    /** @const Bad signature */
    public const int E_DNS_BADSIG = ReturnCode::BADSIG->value;

    /** @const Bad key value */
    public const int E_DNS_BADKEY = ReturnCode::BADKEY->value;

    /** @const Bad time value */
    public const int E_DNS_BADTIME = ReturnCode::BADTIME->value;

    /** @const Bad mode value */
    public const int E_DNS_BADMODE = ReturnCode::BADMODE->value;

    /** @const Bad name value */
    public const int E_DNS_BADNAME = ReturnCode::BADNAME->value;

    /** @const Bad algorithm value */
    public const int E_DNS_BADALG = ReturnCode::BADALG->value;

    /** @const Bad truncation */
    public const int E_DNS_BADTRUNC = ReturnCode::BADTRUNC->value;

    /** @const Bad cookie */
    public const int E_DNS_BADCOOKIE = ReturnCode::BADCOOKIE->value;

    # Other error conditions

    /** @const Invalid file */
    public const int E_NS_INVALID_FILE = 200;

    /** @const Invalid entry */
    public const int E_NS_INVALID_ENTRY = 201;

    /** @const Name server failed */
    public const int E_NS_FAILED = 202;

    /** @const Socket failed */
    public const int E_NS_SOCKET_FAILED = 203;

    /** @const Invalid socket */
    public const int E_NS_INVALID_SOCKET = 204;

    /** @const Invalid packet */
    public const int E_PACKET_INVALID = 300;

    /** @const Parse error */
    public const int E_PARSE_ERROR = 301;

    /** @const Invalid header */
    public const int E_HEADER_INVALID = 302;

    /** @const Invalid question */
    public const int E_QUESTION_INVALID = 303;

    /** @const Invalid RR */
    public const int E_RR_INVALID = 304;

    /** @const OpenSSL error */
    public const int E_OPENSSL_ERROR = 400;

    /** @const OpenSSL unavailable */
    public const int E_OPENSSL_UNAVAIL = 401;

    /** @const OpenSSL invalid private key */
    public const int E_OPENSSL_INV_PKEY = 402;

    /** @const OpenSSL invalid algorithm */
    public const int E_OPENSSL_INV_ALGO = 403;

    /** @const Cache unsupported */
    public const int E_CACHE_UNSUPPORTED = 500;

    # EDNS0 Option Codes (OPT)
    # 0 - Reserved
    public const int EDNS0_OPT_LLQ  = 1;

    public const int EDNS0_OPT_UL   = 2;

    public const int EDNS0_OPT_NSID = 3;

    # 4 - Reserved
    public const int EDNS0_OPT_DAU           = 5;

    public const int EDNS0_OPT_DHU           = 6;

    public const int EDNS0_OPT_N3U           = 7;

    public const int EDNS0_OPT_CLIENT_SUBNET = 8;

    public const int EDNS0_OPT_EXPIRE        = 9;

    public const int EDNS0_OPT_COOKIE        = 10;

    public const int EDNS0_OPT_TCP_KEEPALIVE = 11;

    public const int EDNS0_OPT_PADDING       = 12;

    public const int EDNS0_OPT_CHAIN         = 13;

    public const int EDNS0_OPT_KEY_TAG       = 14;

    # 15 - unassigned
    public const int EDNS0_OPT_CLIENT_TAG = 16;

    public const int EDNS0_OPT_SERVER_TAG = 17;

    # 18-26945 - unassigned
    public const int EDNS0_OPT_DEVICEID = 26946;

    # DNSSEC Algorithms
    public const int DNSSEC_ALGORITHM_RES          = 0;

    public const int DNSSEC_ALGORITHM_RSAMD5       = 1;

    public const int DNSSEC_ALGORITHM_DH           = 2;

    public const int DNSSEC_ALGORITHM_DSA          = 3;

    public const int DNSSEC_ALGORITHM_ECC          = 4;

    public const int DNSSEC_ALGORITHM_RSASHA1      = 5;

    public const int DNSSEC_ALGORITHM_DSANSEC3SHA1 = 6;

    /** @noinspection PhpConstantNamingConventionInspection */
    public const int DSNSEC_ALGORITHM_RSASHA1NSEC3SHA1 = 7;

    public const int DNSSEC_ALGORITHM_RSASHA256        = 8;

    public const int DNSSEC_ALGORITHM_RSASHA512        = 10;

    public const int DNSSEC_ALGORITHM_ECCGOST          = 12;

    public const int DNSSEC_ALGORITHM_ECDSAP256SHA256  = 13;

    public const int DNSSEC_ALGORITHM_ECDSAP384SHA384  = 14;

    public const int DNSSEC_ALGORITHM_ED25519          = 15;

    public const int DNSSEC_ALGORITHM_ED448            = 16;

    public const int DNSSEC_ALGORITHM_INDIRECT         = 252;

    public const int DNSSEC_ALGORITHM_PRIVATEDNS       = 253;

    public const int DNSSEC_ALGORITHM_PRIVATEOID       = 254;

    # DNSSEC Digest Types
    public const int DNSSEC_DIGEST_RES    = 0;

    public const int DNSSEC_DIGEST_SHA1   = 1;

    public const int DNSSEC_DIGEST_SHA256 = 2;

    public const int DNSSEC_DIGEST_GOST   = 3;

    public const int DNSSEC_DIGEST_SHA384 = 4;


    /** @var array<int, string> Map Q-types from ID to name (defined in RFC2929 section 3.1) */
    public static array $rrQTypesById = [];

    /** @var array<string, int> Map Q-types from name to ID (defined in RFC2929 section 3.1) */
    public static array $rrQTypesByName = [
        'IXFR' => 251,     # RFC 1995 - only a full (AXFR) is supported
        'AXFR' => 252,     # RFC 1035
        'MAILB' => 253,    # RFC 883, Not implemented
        'MAILA' => 254,    # RFC 973, Not implemented
        'ANY' => 255,       # RFC 1035 - we support both 'ANY' and '*'
    ];

    /** @var array<int, string> Map meta-types from ID to name */
    public static array $rrMetaTypesById = [];

    /** @var array<string, int> Map meta-types from name to ID */
    public static array $rrMetaTypesByName = [
        'OPT' => 41,      # RFC 2671
        'TKEY' => 249,    # RFC 2930
        'TSIG' => 250,     # RFC 2845
    ];

    /** @var array<string, int> Map DNSSEC algorithm names to IDs */
    public static array $algorithmNameToID = [];

    /*
     * maps DNS SEC algorithms to their mnemonics
     */
    /** @var array<int, string> Map DNSSEC algorithm IDs to names */
    public static array $algorithmIdToName = [
        self::DNSSEC_ALGORITHM_RES => 'RES',
        self::DNSSEC_ALGORITHM_RSAMD5 => 'RSAMD5',
        self::DNSSEC_ALGORITHM_DH => 'DH',
        self::DNSSEC_ALGORITHM_DSA => 'DSA',
        self::DNSSEC_ALGORITHM_ECC => 'ECC',
        self::DNSSEC_ALGORITHM_RSASHA1 => 'RSASHA1',
        self::DNSSEC_ALGORITHM_DSANSEC3SHA1 => 'DSA-NSEC3-SHA1',
        self::DSNSEC_ALGORITHM_RSASHA1NSEC3SHA1 => 'RSASHA1-NSEC3-SHA1',
        self::DNSSEC_ALGORITHM_RSASHA256 => 'RSASHA256',
        self::DNSSEC_ALGORITHM_RSASHA512 => 'RSASHA512',
        self::DNSSEC_ALGORITHM_ECCGOST => 'ECC-GOST',
        self::DNSSEC_ALGORITHM_ECDSAP256SHA256 => 'ECDSAP256SHA256',
        self::DNSSEC_ALGORITHM_ECDSAP384SHA384 => 'ECDSAP384SHA384',
        self::DNSSEC_ALGORITHM_ED25519 => 'ED25519',
        self::DNSSEC_ALGORITHM_ED448 => 'ED448',
        self::DNSSEC_ALGORITHM_INDIRECT => 'INDIRECT',
        self::DNSSEC_ALGORITHM_PRIVATEDNS => 'PRIVATEDNS',
        self::DNSSEC_ALGORITHM_PRIVATEOID => 'PRIVATEOID',
    ];

    /** @var array<string, int> Map DNSSEC digest type names to IDs */
    public static array $digestNameToId = [];

    /** @var array<int, string> Map DNSSEC digest type IDs to names */
    public static array $digestIdToName = [
        self::DNSSEC_DIGEST_RES => 'RES',
        self::DNSSEC_DIGEST_SHA1 => 'SHA-1',
        self::DNSSEC_DIGEST_SHA256 => 'SHA-256',
        self::DNSSEC_DIGEST_GOST => 'GOST-R-34.11-94',
        self::DNSSEC_DIGEST_SHA384 => 'SHA-384',
    ];

    /** @var array<int, string> Map protocol names to IDs */
    public static array $protocolById = [];

    /** @var array<string, int> Map protocol IDs to names */
    public static array $protocolByName = [
        'ICMP' => 1,
        'IGMP' => 2,
        'GGP' => 3,
        'ST' => 5,
        'TCP' => 6,
        'UCL' => 7,
        'EGP' => 8,
        'IGP' => 9,
        'BBN-RCC-MON' => 10,
        'NVP-II' => 11,
        'PUP' => 12,
        'ARGUS' => 13,
        'EMCON' => 14,
        'XNET' => 15,
        'CHAOS' => 16,
        'UDP' => 17,
        'MUX' => 18,
        'DCN-MEAS' => 19,
        'HMP' => 20,
        'PRM' => 21,
        'XNS-IDP' => 22,
        'TRUNK-1' => 23,
        'TRUNK-2' => 24,
        'LEAF-1' => 25,
        'LEAF-2' => 26,
        'RDP' => 27,
        'IRTP' => 28,
        'ISO-TP4' => 29,
        'NETBLT' => 30,
        'MFE-NSP' => 31,
        'MERIT-INP' => 32,
        'SEP' => 33,
        # 34 - 60      - Unassigned
        # 61           - any host internal protocol
        'CFTP' => 62,
        # 63           - any local network
        'SAT-EXPAK' => 64,
        'MIT-SUBNET' => 65,
        'RVD' => 66,
        'IPPC' => 67,
        # 68           - any distributed file system
        'SAT-MON' => 69,
        # 70           - Unassigned
        'IPCV' => 71,
        # 72 - 75      - Unassigned
        'BR-SAT-MON' => 76,
        # 77           - Unassigned
        'WB-MON' => 78,
        'WB-EXPAK' => 79,
        # 80 - 254     - Unassigned
        # 255          - Reserved
    ];

    /** @var ?int The next packet ID to use when sending requests */
    private static ?int $nextPacketId = null;


    /**
     * Return the next available packet ID.
     *
     * @return    int  The next packet ID
     * @throws Exception If an appropriate source of randomness cannot be found
     */
    public static function nextPacketId() : int {
        if ( is_null( self::$nextPacketId ) ) {
            try {
                self::$nextPacketId = random_int( 0, 65535 );
            } catch ( \Exception $ex ) {
                throw new Exception( 'Unable to generate a random packet ID: ' . $ex->getMessage() );
            }
        }
        if ( ++self::$nextPacketId > 65535 ) {
            self::$nextPacketId = 1;
        }

        return self::$nextPacketId;
    }


}
