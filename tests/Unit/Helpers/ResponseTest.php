<?php

namespace Lartrix\Tests\Unit\Helpers;

use Lartrix\Tests\TestCase;

class ResponseTest extends TestCase
{
    /** @test */
    public function success_with_message_only(): void
    {
        $response = success('操作成功');
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(0, $data['code']);
        $this->assertEquals('操作成功', $data['msg']);
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
        $response = success('获取成功', ['id' => 1]);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(0, $data['code']);
        $this->assertEquals('获取成功', $data['msg']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    /** @test */
    public function success_with_custom_code(): void
    {
        $response = success('操作成功', null, 200);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(200, $data['code']);
    }

    /** @test */
    public function error_with_message_only(): void
    {
        $response = error('操作失败');
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(1, $data['code']);
        $this->assertEquals('操作失败', $data['msg']);
        $this->assertNull($data['data']);
    }

    /** @test */
    public function error_with_message_and_data(): void
    {
        $response = error('验证失败', ['name' => '名称不能为空']);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(1, $data['code']);
        $this->assertEquals('验证失败', $data['msg']);
        $this->assertEquals(['name' => '名称不能为空'], $data['data']);
    }

    /** @test */
    public function error_with_custom_code(): void
    {
        $response = error('未授权', null, 401);
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(401, $data['code']);
    }
}
