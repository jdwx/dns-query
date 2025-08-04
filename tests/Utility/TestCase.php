<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Utility;


class TestCase extends \PHPUnit\Framework\TestCase {


    public static function assertSameHex( string $expected, string $actual ) : void {
        self::assertSame( bin2hex( $expected ), bin2hex( $actual ) );
    }


}
