<?php /** @noinspection SpellCheckingInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\RR\ALL;
use JDWX\DNSQuery\RR\RR;


enum RecordType: int {


    case SIG0       = 0;        # RFC 2931 pseudo type

    case A          = 1;           # RFC 1035

    case NS         = 2;          # RFC 1035

    case MD         = 3;          # RFC 1035 - obsolete; Not implemented

    case MF         = 4;          # RFC 1035 - obsolete; Not implemented

    case CNAME      = 5;       # RFC 1035

    case SOA        = 6;         # RFC 1035

    case MB         = 7;          # RFC 1035 - obsolete; Not implemented

    case MG         = 8;          # RFC 1035 - obsolete; Not implemented

    case MR         = 9;          # RFC 1035 - obsolete; Not implemented

    case NULL       = 10;       # RFC 1035 - obsolete; Not implemented

    case WKS        = 11;        # RFC 1035

    case PTR        = 12;        # RFC 1035

    case HINFO      = 13;      # RFC 1035

    case MINFO      = 14;      # RFC 1035 - obsolete; Not implemented

    case MX         = 15;         # RFC 1035

    case TXT        = 16;        # RFC 1035

    case RP         = 17;         # RFC 1183

    case AFSDB      = 18;      # RFC 1183

    case X25        = 19;        # RFC 1183

    case ISDN       = 20;       # RFC 1183

    case RT         = 21;         # RFC 1183

    case NSAP       = 22;       # RFC 1706

    case NSAP_PTR   = 23;   # RFC 1348 - obsolete; Not implemented

    case SIG        = 24;        # RFC 2535

    case KEY        = 25;        # RFC 2535; RFC 2930

    case PX         = 26;         # RFC 2163

    case GPOS       = 27;       # RFC 1712 - Not implemented

    case AAAA       = 28;       # RFC 3596

    case LOC        = 29;        # RFC 1876

    case NXT        = 30;        # RFC 2065; obsoleted by RFC 3755

    case EID        = 31;        # [Patton][Patton1995]

    case NIMLOC     = 32;     # [Patton][Patton1995]

    case SRV        = 33;        # RFC 2782

    case ATMA       = 34;       # Windows only

    case NAPTR      = 35;      # RFC 2915

    case KX         = 36;         # RFC 2230

    case CERT       = 37;       # RFC 4398

    case A6         = 38;         # downgraded to experimental by RFC 3363

    case DNAME      = 39;      # RFC 2672

    case SINK       = 40;       # Not implemented

    case OPT        = 41;        # RFC 2671

    case APL        = 42;        # RFC 3123

    case DS         = 43;         # RFC 4034

    case SSHFP      = 44;      # RFC 4255

    case IPSECKEY   = 45;   # RFC 4025

    case RRSIG      = 46;      # RFC 4034

    case NSEC       = 47;       # RFC 4034

    case DNSKEY     = 48;     # RFC 4034

    case DHCID      = 49;      # RFC 4701

    case NSEC3      = 50;      # RFC 5155

    case NSEC3PARAM = 51; # RFC 5155

    case TLSA       = 52;       # RFC 6698

    case SMIMEA     = 53;     # RFC 8162

    # 54 unassigned

    case HIP        = 55;        # RFC 5205

    case NINFO      = 56;      # Not implemented

    case RKEY       = 57;       # Not implemented

    case TALINK     = 58;     # DNSSEC Trust Anchor History Service draft (obsolete)

    case CDS        = 59;        # RFC 7344

    case CDNSKEY    = 60;    # RFC 7344

    case OPENPGPKEY = 61; # RFC 7929

    case CSYNC      = 62;      # RFC 7477

    case ZONEMD     = 63;     # Not implemented yet

    case SVCB       = 64;       # Not implemented yet

    case HTTPS      = 65;      # Not implemented yet

    # 66 - 98 unassigned

    case SPF    = 99;      # RFC 4408

    case UINFO  = 100;   # no RFC; Not implemented

    case UID    = 101;     # no RFC; Not implemented

    case GID    = 102;     # no RFC; Not implemented

    case UNSPEC = 103;  # no RFC; Not implemented

    case NID    = 104;     # RFC 6742

    case L32    = 105;     # RFC 6742

    case L64    = 106;     # RFC 6742

    case LP     = 107;      # RFC 6742

    case EUI48  = 108;   # RFC 7043

    case EUI64  = 109;   # RFC 7043

    # 110 - 248 unassigned

    case TKEY     = 249;     # RFC 2930

    case TSIG     = 250;     # RFC 2845

    case IXFR     = 251;     # RFC 1995 - only a full (AXFR) is supported

    case AXFR     = 252;     # RFC 1035

    case MAILB    = 253;    # RFC 883; Not implemented

    case MAILA    = 254;    # RFC 973; Not implemented

    case ANY      = 255;      # RFC 1035 - we support both ANY and *

    case URI      = 256;      # RFC 7553

    case CAA      = 257;      # RFC 8659

    case AVC      = 258;      # Application Visibility and Control

    case DOA      = 259;      # Not implemented yet

    case AMTRELAY = 260; # RFC 8777

    # 261 - 32767 unassigned

    case TA        = 32768;        # same as DS

    case DLV       = 32769;       # RFC 4431

    case ALIAS     = 65401;

    case TYPE65534 = 65534;  # Private Bind record


    public static function classNameToId( string $i_className ) : int {
        return self::fromClassName( $i_className )->value;
    }


    public static function classNameToName( string $i_className ) : string {
        return self::fromClassName( $i_className )->name;
    }


    public static function consume( string $i_bin, int &$i_offset ) : self {
        $x = self::tryConsume( $i_bin, $i_offset );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException(
            'RecordType::consume could not find a matching type.'
        );
    }


    public static function fromBinary( string $i_bin ) : self {
        $x = self::tryFromBinary( $i_bin );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException(
            'RecordType::fromBinary could not find a matching type.'
        );
    }


    public static function fromClassName( string $i_name ) : self {
        $x = self::tryFromClassName( $i_name );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException( "Unknown record class: {$i_name}" );
    }


    public static function fromName( string $i_name ) : self {
        $x = self::tryFromName( $i_name );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException( "Unknown record type: {$i_name}" );
    }


    public static function fromPhpId( int $i_id ) : self {
        $x = self::tryFromPhpId( $i_id );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException( "Unknown PHP DNS type: {$i_id}" );
    }


    public static function idToClassName( int $i_id ) : string {
        return self::from( $i_id )->toClassName();
    }


    public static function idToName( int $i_id ) : string {
        return self::from( $i_id )->name;
    }


    public static function isValidClassName( string $i_className ) : bool {
        return self::tryFromClassName( $i_className ) !== null;
    }


    public static function isValidId( int $i_id ) : bool {
        return self::tryFrom( $i_id ) !== null;
    }


    public static function isValidName( string $i_name ) : bool {
        return self::tryFromName( $i_name ) !== null;
    }


    public static function nameToClassName( string $i_name ) : string {
        return self::fromName( $i_name )->toClassName();
    }


    public static function nameToId( string $i_name ) : int {
        return self::fromName( $i_name )->value;
    }


    /**
     * @param int $i_id PHP DNS constant ID (e.g., DNS_A, DNS_CNAME, etc.)
     * @return string The corresponding class name (e.g., 'JDWX\DNSQuery\RR\A', 'JDWX\DNSQuery\RR\CNAME', etc.)
     * @throws RecordTypeException
     */
    public static function phpIdToClassName( int $i_id ) : string {
        $className = self::tryPhpIdToClassName( $i_id );
        if ( is_string( $className ) ) {
            return $className;
        }
        throw new RecordTypeException( "Unknown PHP DNS constant: {$i_id}" );
    }


    /**
     * @param int $i_id PHP DNS constant ID (e.g., DNS_A, DNS_CNAME, etc.)
     * @return int The corresponding RecordType ID
     * @throws RecordTypeException
     */
    public static function phpIdToId( int $i_id ) : int {
        $id = self::tryPhpIdToId( $i_id );
        if ( is_int( $id ) ) {
            return $id;
        }
        throw new RecordTypeException( "Unknown PHP DNS constant: {$i_id}" );
    }


    /**
     * @param int $i_id PHP DNS constant ID (e.g., DNS_A, DNS_CNAME, etc.)
     * @return string The corresponding RecordType name (e.g., 'A', 'CNAME', etc.)
     * @throws RecordTypeException
     */
    public static function phpIdToName( int $i_id ) : string {
        $name = self::tryPhpIdToName( $i_id );
        if ( is_string( $name ) ) {
            return $name;
        }
        throw new RecordTypeException( "Unknown PHP DNS constant: {$i_id}" );
    }


    public static function tryClassNameToId( string $i_className ) : ?int {
        return self::tryFromClassName( $i_className )?->value;
    }


    public static function tryClassNameToName( string $i_className ) : ?string {
        return self::tryFromClassName( $i_className )?->name;
    }


    public static function tryConsume( string $i_bin, int &$i_offset ) : ?self {
        $st = substr( $i_bin, $i_offset, 2 );
        $i_offset += 2;
        return self::tryFromBinary( $st );
    }


    public static function tryFromBinary( string $i_bin ) : ?self {
        if ( strlen( $i_bin ) < 2 ) {
            throw new RecordTypeException(
                'RecordType::tryFromBinary expects a binary string of at least 2 bytes.'
            );
        }
        $id = unpack( 'n', $i_bin )[ 1 ];
        return self::tryFrom( $id );
    }


    public static function tryFromClassName( string $i_name ) : ?self {
        if ( ALL::class === $i_name ) {
            return self::ANY;
        }
        if ( ! is_a( $i_name, RR::class, true ) ) {
            throw new RecordTypeException( "Unknown record class: {$i_name}" );
        }
        $r = explode( '\\', $i_name );
        $i_name = array_pop( $r );
        return self::tryFromName( $i_name );
    }


    public static function tryFromName( string $i_name ) : ?self {
        if ( '*' === $i_name ) {
            return self::ANY;
        }

        static $cache = [];
        if ( empty( $cache ) ) {
            foreach ( self::cases() as $case ) {
                $cache[ $case->name ] = $case;
            }
        }

        return $cache[ strtoupper( $i_name ) ] ?? null;
    }


    public static function tryFromPhpId( int $i_id ) : ?self {
        return match ( $i_id ) {
            DNS_A => self::A,
            DNS_CNAME => self::CNAME,
            DNS_HINFO => self::HINFO,
            DNS_CAA => self::CAA,
            DNS_MX => self::MX,
            DNS_NS => self::NS,
            DNS_PTR => self::PTR,
            DNS_SOA => self::SOA,
            DNS_TXT => self::TXT,
            DNS_AAAA => self::AAAA,
            DNS_SRV => self::SRV,
            DNS_NAPTR => self::NAPTR,
            DNS_ALL, DNS_ANY => self::ANY,
            default => null,
        };
    }


    public static function tryIdToClassName( int $i_id ) : ?string {
        return self::tryFrom( $i_id )?->toClassName();
    }


    public static function tryIdToName( int $i_id ) : ?string {
        return self::tryFrom( $i_id )?->name;
    }


    public static function tryNameToClassName( string $i_name ) : ?string {
        return self::tryFromName( $i_name )?->toClassName();
    }


    public static function tryNameToId( string $i_name ) : ?int {
        return self::tryFromName( $i_name )?->value;
    }


    public static function tryPhpIdToClassName( int $i_id ) : ?string {
        if ( DNS_ALL === $i_id ) {
            return ALL::class;
        }
        $type = self::tryFromPhpId( $i_id );
        return $type?->toClassName() ?? null;
    }


    public static function tryPhpIdToId( int $i_id ) : ?int {
        return self::tryFromPhpId( $i_id )?->value;
    }


    public static function tryPhpIdToName( int $i_id ) : ?string {
        if ( DNS_ALL === $i_id ) {
            return 'ALL';
        }
        return self::tryFromPhpId( $i_id )?->name;
    }


    public function toBinary() : string {
        return pack( 'n', $this->value );
    }


    public function toClassName() : string {
        if ( $this === self::ANY ) {
            return RR::class;
        }
        $className = 'JDWX\\DNSQuery\\RR\\' . $this->name;
        if ( class_exists( $className ) ) {
            return $className;
        }
        throw new RecordTypeException( "Record type {$this->name} is unimplemented." );
    }


}
