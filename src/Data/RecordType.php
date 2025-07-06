<?php /** @noinspection SpellCheckingInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


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


    case ZZZ_TEST_ONLY_DO_NOT_USE = 999_999_999; # Internal use only


    public static function anyToId( int|string|self $i_value ) : int {
        if ( is_int( $i_value ) ) {
            return self::requireValidId( $i_value );
        }
        if ( is_string( $i_value ) ) {
            $x = self::tryFromName( $i_value );
            if ( $x instanceof self ) {
                return $x->value;
            }
            if ( preg_match( '/^TYPE(\d+)$/', $i_value, $matches ) ) {
                return (int) $matches[ 1 ];
            }
            throw new RecordTypeException( "Unknown record type: {$i_value}" );
        }
        return $i_value->value;
    }


    public static function anyToName( int|string|self $i_value ) : string {
        if ( is_int( $i_value ) ) {
            $x = self::tryFrom( $i_value );
            return $x->name ?? "TYPE{$i_value}";
        }
        if ( is_string( $i_value ) ) {
            $x = self::tryFromName( $i_value );
            return $x->name ?? throw new RecordTypeException( "Unknown record type: {$i_value}" );
        }
        return $i_value->name;
    }


    public static function consume( ReadBufferInterface $i_buffer ) : self {
        $id = $i_buffer->consumeUINT16();
        return self::tryFrom( $id )
            ?? throw new RecordTypeException( "Invalid record type ID in binary data: {$id}" );
    }


    public static function fromBinary( string $i_bin ) : self {
        $x = self::tryFromBinary( $i_bin );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException( 'Invalid binary data for RecordType' );
    }


    public static function fromName( string $i_stName ) : self {
        $x = self::tryFromName( $i_stName );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException( "Unknown record type: {$i_stName}" );
    }


    public static function fromPhpId( int $i_phpId ) : self {
        $x = self::tryFromPhpId( $i_phpId );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException( "Unknown PHP DNS type: {$i_phpId}" );
    }


    public static function idToName( int $i_id ) : string {
        $i_id = self::requireValidId( $i_id );
        $x = self::tryFrom( $i_id );
        if ( $x instanceof self ) {
            return $x->name;
        }
        return "TYPE{$i_id}";
    }


    public static function isValidId( int $i_id ) : bool {
        return self::tryFrom( $i_id ) !== null;
    }


    public static function isValidName( string $i_stName ) : bool {
        return self::tryFromName( $i_stName ) !== null;
    }


    public static function nameToId( string $i_stName ) : int {
        return self::fromName( $i_stName )->value;
    }


    public static function normalize( int|string|self|ResourceRecordInterface $i_recordType ) : self {
        $x = self::tryNormalize( $i_recordType );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new RecordTypeException( "Invalid record type ID: {$i_recordType}" );
    }


    /**
     * @param int $i_phpId PHP DNS constant ID (e.g., DNS_A, DNS_CNAME, etc.)
     * @return int The corresponding RecordType ID
     * @throws RecordTypeException
     */
    public static function phpIdToId( int $i_phpId ) : int {
        $id = self::tryPhpIdToId( $i_phpId );
        if ( is_int( $id ) ) {
            return $id;
        }
        throw new RecordTypeException( "Unknown PHP DNS constant: {$i_phpId}" );
    }


    /**
     * @param int $i_phpId PHP DNS constant ID (e.g., DNS_A, DNS_CNAME, etc.)
     * @return string The corresponding RecordType name (e.g., 'A', 'CNAME', etc.)
     * @throws RecordTypeException
     */
    public static function phpIdToName( int $i_phpId ) : string {
        $name = self::tryPhpIdToName( $i_phpId );
        if ( is_string( $name ) ) {
            return $name;
        }
        throw new RecordTypeException( "Unknown PHP DNS constant: {$i_phpId}" );
    }


    public static function tryConsume( ReadBufferInterface $i_buffer ) : ?self {
        return self::tryFrom( $i_buffer->consumeUINT16() );
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


    /**
     * @param string $i_stName
     * @param bool $i_bDropCache For testing only; set to true to drop the internal cache
     * @return self|null
     */
    public static function tryFromName( string $i_stName, bool $i_bDropCache = false ) : ?self {
        $i_stName = strtoupper( trim( $i_stName ) );
        if ( '*' === $i_stName || 'ALL' === $i_stName ) {
            return self::ANY;
        }

        static $cache = [];
        if ( $i_bDropCache ) {
            $cache = [];
        }
        if ( empty( $cache ) ) {
            foreach ( self::cases() as $case ) {
                $cache[ $case->name ] = $case;
            }
        }

        return $cache[ $i_stName ] ?? null;
    }


    public static function tryFromPhpId( int $i_phpId ) : ?self {
        return match ( $i_phpId ) {
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


    public static function tryIdToName( int $i_id ) : ?string {
        return self::tryFrom( $i_id )?->name;
    }


    public static function tryNameToId( string $i_stName ) : ?int {
        return self::tryFromName( $i_stName )?->value;
    }


    public static function tryNormalize( int|string|self|ResourceRecordInterface $i_recordType ) : ?self {
        if ( $i_recordType instanceof ResourceRecordInterface ) {
            $i_recordType = $i_recordType->typeValue();
        }
        if ( is_int( $i_recordType ) ) {
            return self::tryFrom( $i_recordType );
        }
        if ( is_string( $i_recordType ) ) {
            return self::tryFromName( $i_recordType );
        }
        return $i_recordType;
    }


    public static function tryPhpIdToId( int $i_phpId ) : ?int {
        return self::tryFromPhpId( $i_phpId )?->value;
    }


    public static function tryPhpIdToName( int $i_phpId ) : ?string {
        if ( DNS_ALL === $i_phpId ) {
            return 'ANY';
        }
        return self::tryFromPhpId( $i_phpId )?->name;
    }


    private static function requireValidId( int $i_id ) : int {
        if ( $i_id < 0 || $i_id > 65535 ) {
            throw new RecordTypeException( "Invalid record type ID: {$i_id}" );
        }
        return $i_id;
    }


    public function is( int|string|self|ResourceRecordInterface $i_value ) : bool {
        if ( $i_value instanceof ResourceRecordInterface ) {
            $i_value = $i_value->typeValue();
        }
        $i_value = self::tryNormalize( $i_value );
        return $this === $i_value;
    }


    public function toBinary() : string {
        return pack( 'n', $this->value );
    }


}
