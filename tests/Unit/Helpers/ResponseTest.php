<?php

namespace Lartrix\Tests\Unit\Helpers;

use Lartrix\Exceptions\ApiException;
use Lartrix\Tests\TestCase;

class ResponseTest extends TestCase
{
    /** @test */
    public function success_with_message_only(): void
    {
        $data = success('操作成功');
        $this->assertEquals(0, $data['code']);
        $this->assertEquals('操作成功', $data['msg']);
        $this->assertNull($data['data']);
    }

    /** @test */
    public function success_with_array_as_data(): void
    {
        $data = success(['id' => 1, 'name' => 'test']);
        $this->assertEquals(0, $data['code']);
        $this->assertEquals('success', $data['msg']);
        $this->assertEquals(['id' => 1, 'name' => 'test'], $data['data']);
    }

    /** @test */
    public function success_with_message_and_data(): void
    {
        $data = success('获取成功', ['id' => 1]);
        $this->assertEquals(0, $data['code']);
        $this->assertEquals('获取成功', $data['msg']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    /** @test */
    public function success_with_custom_code(): void
    {
        $data = success('操作成功', null, 200);
        $this->assertEquals(200, $data['code']);
    }

    /** @test */
    public function error_with_message_only(): void
    {
        try {
            error('操作失败');
            $this->fail('error() 应抛出 ApiException');
        } catch (ApiException $e) {
            $this->assertEquals('操作失败', $e->getMessage());
            $this->assertEquals(500, $e->getErrorCode());
            $this->assertNull($e->getData());
        }
    }

    /** @test */
    public function error_with_message_and_data(): void
    {
        try {
            error('验证失败', ['name' => '名称不能为空']);
            $this->fail('error() 应抛出 ApiException');
        } catch (ApiException $e) {
            $this->assertEquals('验证失败', $e->getMessage());
            $this->assertEquals(['name' => '名称不能为空'], $e->getData());
        }
    }

    /** @test */
    public function error_with_custom_code(): void
    {
        try {
            error('未授权', null, 401);
            $this->fail('error() 应抛出 ApiException');
        } catch (ApiException $e) {
            $this->assertEquals(401, $e->getErrorCode());
        }
    }
}
