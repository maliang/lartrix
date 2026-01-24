<?php

namespace Lartrix\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;

/**
 * API 异常类
 * 
 * 实现 ShouldntReport 接口，不记录到日志
 * 实现 Responsable 接口，自动渲染为 JSON 响应
 */
class ApiException extends Exception implements Responsable, ShouldntReport
{
    /**
     * 错误码
     */
    protected int $errorCode;

    /**
     * 附加数据
     */
    protected mixed $data;

    /**
     * 构造函数
     *
     * @param string $message 错误消息
     * @param mixed $data 附加数据
     * @param int $code 错误码
     */
    public function __construct(string $message, mixed $data = null, int $code = 500)
    {
        parent::__construct($message);
        $this->errorCode = $code;
        $this->data = $data;
    }

    /**
     * 获取错误码
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 获取附加数据
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * 渲染为 JSON 响应
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'code' => $this->errorCode,
            'msg' => $this->getMessage(),
            'data' => $this->data,
        ]);
    }
}
