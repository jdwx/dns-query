<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests;


use JDWX\DNSQuery\NamedRoot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/** Test the NamedRoot class. */
#[CoversClass( NamedRoot::class )]
final class NamedRootTest extends TestCase {


    /** @var array|string[] The list of root name servers. */
    public static array $rootNameServers = [
        'a.root-servers.net',
        'b.root-servers.net',
        'c.root-servers.net',
        'd.root-servers.net',
        'e.root-servers.net',
        'f.root-servers.net',
        'g.root-servers.net',
        'h.root-servers.net',
        'i.root-servers.net',
        'j.root-servers.net',
        'k.root-servers.net',
        'l.root-servers.net',
        'm.root-servers.net',
    ];


    /** @var array|string[] A list of root name server IPv4 addresses sorted by IP. */
    public static array $rootNameServersIPv4 = [
        '192.112.36.4',   # g.root-servers.net
        '192.203.230.10', # e.root-servers.net
        '192.33.4.12',    # c.root-servers.net
        '192.36.148.17',  # i.root-servers.net
        '192.5.5.241',    # f.root-servers.net
        '192.58.128.30',  # j.root-servers.net
        '193.0.14.129',   # k.root-servers.net
        '198.41.0.4',     # a.root-servers.net
        '198.97.190.53',  # h.root-servers.net
        '199.7.83.42',    # l.root-servers.net
        '199.7.91.13',    # d.root-servers.net
        '199.9.14.201',   # b.root-servers.net
        '202.12.27.33',   # m.root-servers.net
    ];


    /** Check the list of returned IPv4 addresses for the root name servers. */
    public function testNamedRootIPv4() : void {
        $namedRoot = new NamedRoot();
        $check = $namedRoot->listAddresses();
        sort( $check );
        self::assertSame( self::$rootNameServersIPv4, $check );
    }


    /** Test the IPv6 root name server addresses. */
    public function testNamedRootIPv6() : void {
        $rootNameServersIPv6 = [
            '2001:503:ba3e::2:30',  # a.root-servers.net
            '2001:500:200::b',      # b.root-servers.net
            '2001:500:2::c',        # c.root-servers.net
            '2001:500:2d::d',       # d.root-servers.net
            '2001:500:a8::e',       # e.root-servers.net
            '2001:500:2f::f',       # f.root-servers.net
            '2001:500:12::d0d',     # g.root-servers.net
            '2001:500:1::53',       # h.root-servers.net
            '2001:7fe::53',         # i.root-servers.net
            '2001:503:c27::2:30',   # j.root-servers.net
            '2001:7fd::1',          # k.root-servers.net
            '2001:500:9f::42',      # l.root-servers.net
            '2001:dc3::35',         # m.root-servers.net
        ];
        sort( $rootNameServersIPv6 );

        $namedRoot = new NamedRoot();
        $check = $namedRoot->listAddresses( false, true );
        sort( $check );
        self::assertSame( $rootNameServersIPv6, $check );
    }


    /** Test the list of root name server names. */
    public function testRootNames() : void {
        $root = new NamedRoot();
        $check = $root->listNameServers();
        sort( $check );
        self::assertSame( self::$rootNameServers, $check );
    }


}