<?php

namespace Lartrix\Tests\Unit\Exceptions;

use Lartrix\Tests\TestCase;
use Lartrix\Exceptions\ApiException;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Contracts\Support\Responsable;

class ApiExceptionTest extends TestCase
{
    /** @test */
    public function it_implements_shouldnt_report_interface(): void
    {
        $exception = new ApiException('测试错误');
        
        $this->assertInstanceOf(ShouldntReport::class, $exception);
    }

    /** @test */
    public function it_implements_responsable_interface(): void
    {
        $exception = new ApiException('测试错误');
        
        $this->assertInstanceOf(Responsable::class, $exception);
    }

    /** @test */
    public function it_returns_correct_response_format(): void
    {
        $exception = new ApiException('测试错误', ['field' => 'error'], 400);
        $response = $exception->toResponse(request());
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(400, $data['code']);
        $this->assertEquals('测试错误', $data['msg']);
        $this->assertEquals(['field' => 'error'], $data['data']);
    }

    /** @test */
    public function it_has_default_code(): void
    {
        $exception = new ApiException('测试错误');
        $response = $exception->toResponse(request());
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertEquals(1, $data['code']);
    }

    /** @test */
    public function it_can_be_thrown_and_caught(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('测试错误');

        throw new ApiException('测试错误');
    }
}
