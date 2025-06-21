# DNSQuery - Native PHP DNS Resolver and Updater #

### The main features for this package include: ###

* Uses modern PHP namespaces, classes and exceptions
* Support for IPv4 and IPv6, TCP and UDP sockets.
* Includes a separate "Updater" class for handling dynamic update
* Support zone signing using TSIG and SIG(0) for updates and zone transfers
* Supports using PSR-6/PSR-16 caching implementations to improve performance
* Includes many RRs, including DNSSEC RRs.
* Full PHP 8.1 compatibility.

## Installing DNSQuery ##

You can require it directly via Composer: https://packagist.org/packages/jdwx/dns-query

```
composer require jdwx/dns-query
```

Or download the source from GitHub: https://github.com/jdwx/dns-query.git

## Requirements ##

* PHP 8.1+
* The PHP INI setting `mbstring.func_overload` equals 0, 1, 4, or 5.

## Using DNSQuery ##

DNSQuery provides multiple interfaces depending on requirements.

The simplest interface is designed to mimic PHP's built-in dns_get_record() function with additional flexibility to query other name servers.

```php
$out = Resolver::dns_get_record( 'google.com', DNS_MX );
var_dump( $out );
```

```
array(1) {
  [0] =>
  array(6) {
    'host' =>
    string(10) "google.com"
    'class' =>
    string(2) "IN"
    'ttl' =>
    int(300)
    'type' =>
    string(2) "MX"
    'pri' =>
    int(10)
    'target' =>
    string(15) "smtp.google.com"
  }
}
```

But it allows specifying additional options, like what name server to use
for the lookup:

```php
$out = Resolver::dns_get_record( 'google.com', DNS_MX, '1.1.1.1' );
var_dump( $out );
```

(Produces the same output as above.)

You can also specify a list of name servers or a custom resolv.conf file
to use for the lookup. (See the examples.)

For repeated queries, the resolver should be instantiated. It provides
a compatability interface in that form as well:

```php
$rsv = new Resolver();
$out = $rsv->compatQuery( 'google.com', DNS_MX );
var_dump( $out );
```

(Produces the same output as above.)

The native query interface returns full detail about the
response from the name server contacted:

```php
$rsv = new Resolver();
$out = $rsv->query( 'google.com', 'MX' );
var_dump( $out );
```

```
class JDWX\DNSQuery\Packet\ResponsePacket#13 (12) {
  public string $rdata =>
  string(49) "(binary data)"
  public int $rdLength =>
  int(49)
  public int $offset =>
  int(49)
  public JDWX\DNSQuery\Packet\Header $header =>
  class JDWX\DNSQuery\Packet\Header#14 (15) {
    public int $id =>
    int(37144)
    public int $qr =>
    int(1)
    public int $opcode =>
    int(0)
    public int $aa =>
    int(0)
    public int $tc =>
    int(0)
    public int $rd =>
    int(1)
    public int $ra =>
    int(1)
    public int $zero =>
    int(0)
    public int $ad =>
    int(0)
    public int $cd =>
    int(0)
    public int $rCode =>
    int(0)
    public int $qdCount =>
    int(1)
    public int $anCount =>
    int(1)
    public int $nsCount =>
    int(0)
    public int $arCount =>
    int(0)
  }
  public array $question =>
  array(1) {
    [0] =>
    class JDWX\DNSQuery\Question#15 (3) {
      public string $qName =>
      string(10) "google.com"
      public string $qType =>
      string(2) "MX"
      public string $qClass =>
      string(2) "IN"
    }
  }
  public array $answer =>
  array(1) {
    [0] =>
    class JDWX\DNSQuery\RR\MX#16 (8) {
      public string $name =>
      string(10) "google.com"
      public string $type =>
      string(2) "MX"
      public string $class =>
      string(2) "IN"
      public int $ttl =>
      int(226)
      public int $rdLength =>
      int(9)
      public string $rdata =>
      string(9) "(binary data)"
      public int $preference =>
      int(10)
      public string $exchange =>
      string(15) "smtp.google.com"
    }
  }
  public array $authority =>
  array(0) {
  }
  public array $additional =>
  array(0) {
  }
  private array $compressed =>
  array(0) {
  }
  public string $answerFrom =>
  string(7) "1.1.1.1"
  public int $answerSocketType =>
  int(2)
  public float $responseTime =>
  double(0.0037810802459717)
}
```

## Documentation

Documentation is being developed [here](https://github.com/jdwx/dns-query/wiki).

## Stability

Development of test coverage for this package is incomplete and is currently
limited to the Resolver functionality. The Updater and Notifier are not yet
tested, and not all RRs have test coverage.

## History ##

This package was forked from Net_DNS2, which was maintained by Mike
Pultz until 2020. Key differences are:

* PEAR support has been removed.
* Certain things that were previously optional (e.g., the [filter extension](https://www.php.net/manual/en/book.filter.php)) are now required.
* Replaced the hard-to-validate array-style options configuration with fluent setters.
* Changed cache implementation
* Use PSR-4 namespaces and compatible autoloading.
* Passes my organization's internal code quality standards.
* Substantial refactoring of networking code for future flexibility (e.g., DNS over HTTPS).
* Additional unit tests.

I apologize that backwards compatibility is **not** a priority; my company
required adherence to certain coding standards in order to support this work.
This is therefore likely only suited for new development.

The original package represents an enormous amount of work
done over many years. As such Mike Pultz deserves full credit for
most of this package; most of what I am doing is window dressing and
adapting it to meet my specific needs. But since this is a public
repository, I wanted to make clear that I do not claim credit for Mike's
original work.

See the [Net_DNS2 Website](https://netdns2.com/) for more details about Net_DNS2. 
