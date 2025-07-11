<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


/**
 * Stores information about the RData contents of various DNS record types.
 *
 * @suppress PhanInvalidConstantExpression
 * @suppress PhanCommentObjectInClassConstantType
 */
final class RDataMaps {


    /** @var array<int, array<string, RDataType>> */
    private const array MAP_LIST = [

        RecordType::A->value => [ 'address' => RDataType::IPv4Address ],

        RecordType::AAAA->value => [ 'address' => RDataType::IPv6Address ],

        RecordType::AFSDB->value => [
            'subtype' => RDataType::UINT16,
            'hostname' => RDataType::DomainName,
        ],

        RecordType::ALIAS->value => [ 'alias' => RDataType::DomainName ],

        RecordType::AVC->value => [
            'text' => RDataType::CharacterStringList,
        ],

        RecordType::CAA->value => [
            'flags' => RDataType::UINT16,
            'tag' => RDataType::CharacterString,
            'value' => RDataType::CharacterString,
        ],

        RecordType::CNAME->value => [ 'cname' => RDataType::DomainName ],

        RecordType::DNAME->value => [ 'dname' => RDataType::DomainName ],

        RecordType::HINFO->value => [
            'cpu' => RDataType::CharacterString,
            'os' => RDataType::CharacterString,
        ],

        RecordType::ISDN->value => [
            'isdnAddress' => RDataType::CharacterString,
            'sa' => RDataType::CharacterString,
        ],

        RecordType::KX->value => [
            'preference' => RDataType::UINT16,
            'exchange' => RDataType::CharacterString,
        ],

        RecordType::L32->value => [
            'preference' => RDataType::UINT16,
            'locator32' => RDataType::IPv4Address,
        ],

        RecordType::LP->value => [
            'preference' => RDataType::UINT16,
            'fqdn' => RDataType::DomainName,
        ],

        RecordType::MX->value => [
            'preference' => RDataType::UINT16,
            'exchange' => RDataType::DomainName,
        ],

        RecordType::NAPTR->value => [
            'order' => RDataType::UINT16,
            'preference' => RDataType::UINT16,
            'flags' => RDataType::CharacterString,
            'services' => RDataType::CharacterString,
            'regexp' => RDataType::CharacterString,
            'replacement' => RDataType::DomainName,
        ],

        RecordType::NS->value => [ 'nsdname' => RDataType::DomainName ],

        RecordType::OPT->value => [ 'options' => RDataType::OptionList ],

        RecordType::PTR->value => [ 'ptrdname' => RDataType::DomainName ],

        RecordType::PX->value => [
            'preference' => RDataType::UINT16,
            'map822' => RDataType::DomainName,
            'mapX400' => RDataType::DomainName,
        ],

        RecordType::RP->value => [
            'mboxDName' => RDataType::DomainName,
            'txtDName' => RDataType::DomainName,
        ],

        RecordType::RRSIG->value => [
            'typeCovered' => RDataType::UINT16,
            'algorithm' => RDataType::UINT8,
            'labels' => RDataType::UINT8,
            'originalTTL' => RDataType::UINT32,
            'signatureExpiration' => RDataType::UINT32,
            'signatureInception' => RDataType::UINT32,
            'keyTag' => RDataType::UINT16,
            'signerName' => RDataType::DomainNameUncompressed,
            'signature' => RDataType::HexBinary,
        ],

        RecordType::RT->value => [
            'preference' => RDataType::UINT16,
            'intermediateHost' => RDataType::DomainName,
        ],

        RecordType::SOA->value => [
            'mname' => RDataType::DomainName,
            'rname' => RDataType::DomainName,
            'serial' => RDataType::UINT32,
            'refresh' => RDataType::UINT32,
            'retry' => RDataType::UINT32,
            'expire' => RDataType::UINT32,
            'minimum' => RDataType::UINT32,
        ],

        RecordType::SPF->value => [
            'text' => RDataType::CharacterStringList,
        ],

        RecordType::SRV->value => [
            'priority' => RDataType::UINT16,
            'weight' => RDataType::UINT16,
            'port' => RDataType::UINT16,
            'target' => RDataType::DomainName,
        ],

        RecordType::SSHFP->value => [
            'algorithm' => RDataType::UINT8,
            'fptype' => RDataType::UINT8,
            'fingerprint' => RDataType::HexBinary,
        ],

        RecordType::TXT->value => [
            'text' => RDataType::CharacterStringList,
        ],

        RecordType::X25->value => [ 'psdnAddress' => RDataType::CharacterString ],
    ];


    /**
     * @param array<string, RDataType>|int|string|RecordType|ResourceRecordInterface $i_type
     * @return array<string, RDataType>
     */
    public static function map( array|int|string|RecordType|ResourceRecordInterface $i_type ) : array {
        $map = self::tryMap( $i_type );
        if ( $map !== null ) {
            return $map;
        }
        if ( $i_type instanceof RecordType ) {
            $i_type = $i_type->name;
        }
        throw new RecordException( "No RData map for record type {$i_type}" );
    }


    /**
     * @param array<string, RDataType>|int|string|RecordType|ResourceRecordInterface $i_type
     * @return array<string, RDataType>|null
     */
    public static function tryMap( array|int|string|RecordType|ResourceRecordInterface $i_type ) : ?array {
        if ( is_array( $i_type ) ) {
            return $i_type;
        }

        $i_type = RecordType::tryNormalize( $i_type );
        if ( $i_type instanceof RecordType ) {
            $value = $i_type->value;
            return self::MAP_LIST[ $value ] ?? null;
        }

        return null;

    }


}
