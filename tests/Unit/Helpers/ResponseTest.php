<?php

namespace Lartrix\Tests\Unit\Helpers;

use Lartrix\Tests\TestCase;

class ResponseTest extends TestCase
{
    /** @test */
    public function success_with_message_only(): void
    {
        $response = success('鎿嶄綔鎴愬姛');
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(0, $data['code']);
        $this->assertEquals('鎿嶄綔鎴愬姛', $data['msg']);
        $this->assertNull($data['data']);
    }

    /** @test */
    public function success_with_array_as_data(): void
    {
        $response = success(['id' => 1, 'name' => 'test']);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(0, $data['code']);
        $this->assertEquals('ok', $data['msg']);
        $this->assertEquals(['id' => 1, 'name' => 'test'], $data['data']);
    }

    /** @test */
    public function success_with_message_and_data(): void
    {
        $response = success('鑾峰彇鎴愬姛', ['id' => 1]);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(0, $data['code']);
        $this->assertEquals('鑾峰彇鎴愬姛', $data['msg']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    /** @test */
    public function success_with_custom_code(): void
    {
        $response = success('鎿嶄綔鎴愬姛', null, 200);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(200, $data['code']);
    }

    /** @test */
    public function error_with_message_only(): void
    {
        $response = error('鎿嶄綔澶辫触');
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(1, $data['code']);
        $this->assertEquals('鎿嶄綔澶辫触', $data['msg']);
        $this->assertNull($data['data']);
    }

    /** @test */
    public function error_with_message_and_data(): void
    {
        $response = error('楠岃瘉澶辫触', ['name' => '鍚嶇О涓嶈兘涓虹┖']);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(1, $data['code']);
        $this->assertEquals('楠岃瘉澶辫触', $data['msg']);
        $this->assertEquals(['name' => '鍚嶇О涓嶈兘涓虹┖'], $data['data']);
    }

    /** @test */
    public function error_with_custom_code(): void
    {
        $response = error('鏈巿鏉?, null, 401);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(401, $data['code']);
    }
}
