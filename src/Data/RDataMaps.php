<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\RecordException;


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
        RecordType::CNAME->value => [ 'cname' => RDataType::DomainName ],
        RecordType::MX->value => [
            'preference' => RDataType::UINT16,
            'exchange' => RDataType::DomainName,
        ],
        RecordType::NS->value => [ 'nsdname' => RDataType::DomainName ],
        RecordType::OPT->value => [
            'options' => RDataType::OptionList,
        ],
        RecordType::PTR->value => [ 'ptrdname' => RDataType::DomainName ],
        RecordType::SOA->value => [
            'mname' => RDataType::DomainName,
            'rname' => RDataType::DomainName,
            'serial' => RDataType::UINT32,
            'refresh' => RDataType::UINT32,
            'retry' => RDataType::UINT32,
            'expire' => RDataType::UINT32,
            'minimum' => RDataType::UINT32,
        ],
        RecordType::TXT->value => [
            'text' => RDataType::CharacterStringList,
        ],
    ];


    /** @return array<string, RDataType> */
    public static function map( int|string|RecordType $i_type ) : array {
        $map = self::tryMap( $i_type );
        if ( $map !== null ) {
            return $map;
        }
        throw new RecordException( "No RData map for record type {$i_type}" );
    }


    /** @return array<string, RDataType>|null */
    public static function tryMap( int|string|RecordType $i_type ) : ?array {
        try {
            $i_type = RecordType::normalize( $i_type );
        } catch ( RecordException ) {
            return null;
        }
        $value = $i_type->value;
        return self::MAP_LIST[ $value ] ?? null;
    }


}
