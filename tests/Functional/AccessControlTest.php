<?php

namespace App\Tests\Functional;

/**
 * The panel is closed to guests; the firewall redirects to login (OWASP A01).
 */
final class AccessControlTest extends DatabaseWebTestCase
{
    public function testAnonymousIsRedirectedFromDashboard(): void
    {
        $this->client->request('GET', '/panel');

        self::assertResponseRedirects('/login');
    }

    public function testAnonymousIsRedirectedFromGames(): void
    {
        $this->client->request('GET', '/panel/games');

        self::assertResponseRedirects('/login');
    }
}
