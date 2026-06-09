<?php

namespace App\Tests\Functional;

/**
 * Panel jest zamknięty dla niezalogowanych — firewall przekierowuje na logowanie (OWASP A01).
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
