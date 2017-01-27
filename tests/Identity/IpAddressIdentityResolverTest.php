<?php
/**
 * This file is part of the Rate Limit package.
 *
 * Copyright (c) Nikola Posa
 *
 * For full copyright and license information, please refer to the LICENSE file,
 * located at the package root folder.
 */

declare(strict_types=1);

namespace RateLimit\Tests\Identity;

use PHPUnit\Framework\TestCase;
use RateLimit\Identity\IdentityResolverInterface;
use RateLimit\Identity\IpAddressIdentityResolver;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Request;

/**
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class IpAddressIdentityResolverTest extends TestCase
{
    /**
     * @var IdentityResolverInterface
     */
    protected $identityResolver;

    protected function setUp()
    {
        $this->identityResolver = new IpAddressIdentityResolver();
    }

    /**
     * @test
     */
    public function it_resolves_http_client_ip_as_identity()
    {
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_CLIENT_IP' => '192.168.1.7',
        ]);

        $identity = $this->identityResolver->getIdentity($request);

        $this->assertEquals('192.168.1.7', $identity);
    }

    /**
     * @test
     */
    public function it_resolves_http_x_forwarded_for_as_identity()
    {
        $request = ServerRequestFactory::fromGlobals([
            'HTTP_X_FORWARDED_FOR' => '192.168.1.7',
        ]);

        $identity = $this->identityResolver->getIdentity($request);

        $this->assertEquals('192.168.1.7', $identity);
    }

    /**
     * @test
     */
    public function it_resolves_remote_addr_as_identity()
    {
        $request = ServerRequestFactory::fromGlobals([
            'REMOTE_ADDR' => '192.168.1.7',
        ]);

        $identity = $this->identityResolver->getIdentity($request);

        $this->assertEquals('192.168.1.7', $identity);
    }

    /**
     * @test
     */
    public function it_fails_to_resolve_if_none_of_related_server_params_is_not_set()
    {
        $request = ServerRequestFactory::fromGlobals([]);

        $identity = $this->identityResolver->getIdentity($request);

        $this->assertEquals('ANONYMOUS', $identity);
    }

    /**
     * @test
     */
    public function it_fails_to_resolve_if_request_is_not_server_request()
    {
        $request = new Request();

        $identity = $this->identityResolver->getIdentity($request);

        $this->assertEquals('ANONYMOUS', $identity);
    }

    /**
     * @test
     *
     * @dataProvider getServerRequests
     */
    public function it_resolves_identity_based_on_correct_server_params_priority(ServerRequest $request, string $expectedIdentity)
    {
        $identity = $this->identityResolver->getIdentity($request);

        $this->assertEquals($expectedIdentity, $identity);
    }

    public function getServerRequests()
    {
        return [
            [
                ServerRequestFactory::fromGlobals([
                    'REMOTE_ADDR' => '192.168.1.5',
                    'HTTP_X_FORWARDED_FOR' => '192.168.1.6',
                    'HTTP_CLIENT_IP' => '192.168.1.7',
                ]),
                '192.168.1.7'
            ],
            [
                ServerRequestFactory::fromGlobals([
                    'REMOTE_ADDR' => '192.168.1.5',
                    'HTTP_X_FORWARDED_FOR' => '192.168.1.6',
                ]),
                '192.168.1.6'
            ],
        ];
    }
}
