<?php

namespace Lartrix\Tests\Unit\Helpers;

use Lartrix\Tests\TestCase;

class ResponseTest extends TestCase
{
    /** @test */
    public function success_with_message_only(): void
    {
        $data = json_decode(success('操作成功')->getContent(), true);
        $this->assertEquals(0, $data['code']);
        $this->assertEquals('操作成功', $data['msg']);
        $this->assertNull($data['data']);
    }

    /** @test */
    public function success_with_array_as_data(): void
    {
        $data = json_decode(success(['id' => 1, 'name' => 'test'])->getContent(), true);
        $this->assertEquals(0, $data['code']);
        $this->assertEquals('ok', $data['msg']);
        $this->assertEquals(['id' => 1, 'name' => 'test'], $data['data']);
    }

    /** @test */
    public function success_with_message_and_data(): void
    {
        $data = json_decode(success('获取成功', ['id' => 1])->getContent(), true);
        $this->assertEquals(0, $data['code']);
        $this->assertEquals('获取成功', $data['msg']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    /** @test */
    public function success_with_custom_code(): void
    {
        $data = json_decode(success('操作成功', null, 200)->getContent(), true);
        $this->assertEquals(200, $data['code']);
    }

    /** @test */
    public function error_with_message_only(): void
    {
        $data = json_decode(error('操作失败')->getContent(), true);
        $this->assertEquals(1, $data['code']);
        $this->assertEquals('操作失败', $data['msg']);
        $this->assertNull($data['data']);
    }

    /** @test */
    public function error_with_message_and_data(): void
    {
        $data = json_decode(error('验证失败', ['name' => '名称不能为空'])->getContent(), true);
        $this->assertEquals(1, $data['code']);
        $this->assertEquals('验证失败', $data['msg']);
        $this->assertEquals(['name' => '名称不能为空'], $data['data']);
    }

    /** @test */
    public function error_with_custom_code(): void
    {
        $data = json_decode(error('未授权', null, 401)->getContent(), true);
        $this->assertEquals(401, $data['code']);
    }
}
