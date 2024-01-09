<?php

namespace SmartContact\TrustpilotApiService\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use SmartContact\TrustpilotApiService\TrustpilotApiService;

final class TrustpilotApiServiceTest extends TestCase
{

  protected function setUp(): void
  {
    parent::setUp();

    $dotenv = Dotenv::createImmutable(__DIR__ . '/../', '.env');
    $dotenv->safeLoad();
  }

  private function getServiceOptions()
  {
    return [
      'business_unit_id' => $_ENV['TRUSTPILOT_BUSINESS_UNIT_ID'],
      'api_key' => $_ENV['TRUSTPILOT_API_KEY'],
      'api_secret' => $_ENV['TRUSTPILOT_API_SECRET'],
      'username' => $_ENV['TRUSTPILOT_USERNAME'],
      'password' => $_ENV['TRUSTPILOT_PASSWORD']
    ];
  }


  /** @test */
  public function shouldUseAccessTokenAlreadyTakenIfStillValid()
  {

    $trustpilotService = (new TrustpilotApiService)
      ->init($this->getServiceOptions())
      ->authenticate();
    ['access_token' => $prevToken] = $trustpilotService->getAccessToken();

    $trustpilotService->getInvitationTemplates();

    ['access_token' => $actualToken] = $trustpilotService->getAccessToken();

    $this->assertEquals($actualToken, $prevToken);
  }
}