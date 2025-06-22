<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\RecordClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( RecordClass::class )]
final class RecordClassTest extends TestCase {


    public function testIsValidName() : void {
        self::assertTrue( RecordClass::isValidName( 'IN' ) );
        self::assertTrue( RecordClass::isValidName( 'CH' ) );
        self::assertTrue( RecordClass::isValidName( 'HS' ) );
        self::assertFalse( RecordClass::isValidName( 'INVALID' ) );
    }


}
