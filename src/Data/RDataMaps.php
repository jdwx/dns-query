<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


/**
 * @suppress PhanInvalidConstantExpression
 * @suppress PhanCommentObjectInClassConstantType
 */
final class RDataMaps {


    /** @var array<int, array<string, RDataType>> */
    public const array MAP_LIST = [
        RecordType::A->value => [ 'address' => RDataType::IPv4Address ],
        RecordType::MX->value => [
            'preference' => RDataType::UINT16,
            'exchange' => RDataType::DomainName,
        ],
        RecordType::TXT->value => [
            'text' => RDataType::CharacterStringList,
        ],
    ];


}
