<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Webwings\SkautisBundle\Client\FixtureSkautisClient;

/**
 * Walks the same path skautIS puts a real user through: it posts a token to the
 * registered login URL and the app must take it from there.
 */
final class LoginFlowTest extends WebTestCase
{
    public function testAnonymousVisitorIsSentToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testPostedTokenLogsTheUserIn(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSame('Nováková Jana', $crawler->filter('.appbar__brand')->text());
    }

    public function testMemberListShowsTheUnitRoster(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/osoby');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('[data-filter-term]')->count());
    }

    public function testPersonDetailLinksContactsForCalling(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/osoby');
        $client->click($crawler->filter('.list a.row')->first()->link());

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('a')->count());
    }

    public function testManifestIsPublicAndDescribesAnInstallableApp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/manifest.webmanifest');

        self::assertResponseIsSuccessful();

        $manifest = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('standalone', $manifest['display']);
        self::assertNotEmpty($manifest['icons']);
    }

    private function login(): KernelBrowser
    {
        $client = static::createClient();
        $client->request('POST', '/login', ['skautIS_Token' => FixtureSkautisClient::TOKEN]);

        self::assertResponseRedirects('/');
        $client->followRedirect();

        return $client;
    }
}
