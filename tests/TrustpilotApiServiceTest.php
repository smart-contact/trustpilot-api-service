<?php

namespace SmartContact\TrustpilotApiService\Tests;

use PHPUnit\Framework\TestCase;
use SmartContact\TrustpilotApiService\TrustpilotApiService;

final class TrustpilotApiServiceTest extends TestCase
{

  private $serviceOptions = [
    'business_unit_id' => '5e42bc5c37ca7e0001bb19d4',
    'api_key' => 'bVqGnIQShbPsbtiZmCnQcseZ8PBm6QSQ',
    'api_secret' => 'gSQX4dU4sqB5MgeH',
    'username' => 'dev@smart-contact.it',
    'password' => '76Vz#sjKY^HeYE'
  ];

  /** @test */
  public function shouldSetAccessTokenWhenIsInstantiated()
  {

    $trustpilotService = new TrustpilotApiService($this->serviceOptions);

    $accessToken = $trustpilotService->getAccessToken();
    $this->assertNotNull($accessToken);
    $this->assertNotNull($accessToken['access_token']);
    $this->assertNotNull($accessToken['refresh_token']);
    $this->assertNotNull($accessToken['expires_at']);
  }

  /** @test */
  public function shouldUseAccessTokenAlreadyTakenIfStillValid()
  {
    $trustpilotService = new TrustpilotApiService($this->serviceOptions);

    ['access_token' => $prevToken] = $trustpilotService->getAccessToken();

    $trustpilotService->getInvitationTemplates();

    ['access_token' => $actualToken] = $trustpilotService->getAccessToken();

    $this->assertEquals($actualToken, $prevToken);
  }
}