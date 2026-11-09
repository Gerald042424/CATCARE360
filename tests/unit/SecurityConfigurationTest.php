<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\Cookie;
use Config\Filters;
use Config\Security;

final class SecurityConfigurationTest extends CIUnitTestCase
{
    public function testGlobalCsrfProtectionIsEnabled(): void
    {
        $this->assertContains('csrf', (new Filters())->globals['before']);
        $this->assertFalse((new Security())->regenerate);
    }

    public function testVercelCookiesAreSecure(): void
    {
        $previous = getenv('VERCEL');
        putenv('VERCEL=1');

        try {
            $this->assertTrue((new Cookie())->secure);
        } finally {
            $previous === false ? putenv('VERCEL') : putenv('VERCEL=' . $previous);
        }
    }

    public function testLogoutRoutesArePostOnly(): void
    {
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertIsString($routes);

        foreach (['/auth/logout', 'adopter/logout', 'admin/logout', '/volunteer/logout'] as $route) {
            $this->assertStringContainsString("->post('{$route}'", $routes);
            $this->assertStringNotContainsString("->get('{$route}'", $routes);
        }
    }
}
