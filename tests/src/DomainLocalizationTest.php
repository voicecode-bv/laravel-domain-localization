<?php

namespace Kevindierkx\LaravelDomainLocalization\Tests;

use Kevindierkx\LaravelDomainLocalization\DomainLocalization;
use Kevindierkx\LaravelDomainLocalization\Exceptions\UnsupportedLocaleException;
use Localization;

class DomainLocalizationTest extends TestCase
{
    public function test_not_having_the_default_locale_in_the_supported_locales_throws_an_exception()
    {
        $this->expectException(UnsupportedLocaleException::class);

        new DomainLocalization('foo', []);
    }

    public function test_getting_default_locale_matches_the_app_locale()
    {
        $this->assertSame(Localization::getDefaultLocale(), $this->app['config']->get('app.locale'));
    }

    public function test_getting_the_current_locale_matches_the_app_locale()
    {
        $this->assertSame(Localization::getCurrentLocale(), 'en');
    }

    public function test_setting_the_current_locale_matches_the_app_locale()
    {
        Localization::setCurrentLocale('foo');

        $this->assertSame($this->app['config']->get('app.locale'), 'foo');
    }

    public function test_getting_the_tld_matches_the_correct_tld_from_the_supported_locales()
    {
        Localization::addLocale('custom', ['tld' => self::TEST_TLD_CUSTOM]);
        Localization::setRequestInstance($this->createRequest('https://test'.self::TEST_TLD_CUSTOM.'/test'));

        $this->assertSame(Localization::getTld(), self::TEST_TLD_CUSTOM);
    }

    public function test_getting_the_tld_matches_the_fallback_and_not_an_incorrect_tld_when_the_tld_is_not_exactly_in_the_supported_locales()
    {
        Localization::setRequestInstance($this->createRequest('https://test'.self::TEST_TLD_CUSTOM.'/test'));

        $this->assertSame(Localization::getTld(), '.dev');
    }

    /**
     * @param string  $a
     * @param string  $b
     * @param bool    $isPositive
     *
     * @dataProvider getCompareDataProvider
     */
    public function test_compare_to_always_favor_the_longest_string(string $a, string $b, bool $isPositive)
    {
        $class = new \ReflectionClass(DomainLocalization::class);
        $method = $class->getMethod('compareStrLength');
        $method->setAccessible(true);

        $this->assertSame($method->invokeArgs($this->app['domain.localization'], [$a, $b]) >= 0, $isPositive);
    }

    public function getCompareDataProvider()
    {
        return [
            ['.dev',     '.com.dev', true],
            ['.nl.dev',  '.dev',     false],
            ['.test.nl', '.nl',      false],
        ];
    }

    public function test_getting_localized_url()
    {
        Localization::addLocale('nl', self::TEST_NL_CONFIG);
        Localization::setRequestInstance($this->createRequest(self::TEST_URL_EN.'/test'));

        $this->assertSame(Localization::getLocalizedUrl('nl'), self::TEST_URL_NL.'/test');
    }

    public function test_getting_unknown_localized_url_throws_an_exception()
    {
        $this->expectException(UnsupportedLocaleException::class);

        Localization::getLocalizedUrl('nl');
    }

    public function test_resolving_the_uri_returns_uri_from_the_request()
    {
        Localization::setRequestInstance($this->createRequest(self::TEST_URL_EN.'/test'));

        $this->assertSame(Localization::resolveUri(), self::TEST_URL_EN.'/test');
    }

    public function test_resolving_the_http_host_returns_http_host_from_the_request()
    {
        Localization::setRequestInstance($this->createRequest('https://'.self::TEST_HTTP_HOST.'/test'));

        $this->assertSame(Localization::resolveHttpHost(), self::TEST_HTTP_HOST);
    }
}
