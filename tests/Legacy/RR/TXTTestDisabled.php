<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Legacy\RR;


use JDWX\DNSQuery\Legacy\RR\RR;
use JDWX\DNSQuery\Legacy\RR\TXT;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( TXT::class )]
#[CoversClass( RR::class )]
final class TXTTestDisabled extends TestCase {


    /**
     * This is largely a proxy test for RR::formatString().
     */
    public function testGetPHPRData() : void {
        $rr = new TXT();
        $rr->text = [ 'foo' ];
        self::assertSame( [ 'txt' => '"foo"' ], $rr->getPHPRData() );

        $rr->text = [ 'foo', 'bar', 'baz' ];
        self::assertSame( [ 'txt' => '"foo" "bar" "baz"' ], $rr->getPHPRData() );

        $rr->text = [ 'foo', 'bar baz', 'qux' ];
        self::assertSame( [ 'txt' => '"foo" "bar baz" "qux"' ], $rr->getPHPRData() );

        $rr->text = [ '  foo  ' ];
        self::assertSame( [ 'txt' => '"  foo  "' ], $rr->getPHPRData() );

        $rr->text = [ '"foo"' ];
        self::assertSame( [ 'txt' => '"\\"foo\\""' ], $rr->getPHPRData() );

        $rr->text = [];
        self::assertSame( [ 'txt' => '""' ], $rr->getPHPRData() );
    }


}
