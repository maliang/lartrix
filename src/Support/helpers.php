<?php

namespace Lartrix\Support;

use Lartrix\Exceptions\ApiException;

if (!function_exists('success')) {
    /**
     * 成功响应
     *
     * @param string|array $msg 消息或数据（如果是数组则作为 data）
     * @param mixed $data 数据
     * @param int $code 状态码
     * @return array
     */
    function success(string|array $msg = 'success', mixed $data = null, int $code = 0): array
    {
        // 如果第一个参数是数组，则作为 data，msg 使用默认值
        if (is_array($msg)) {
            $data = $msg;
            $msg = 'success';
        }

        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}

if (!function_exists('error')) {
    /**
     * 错误响应（通过抛出异常触发）
     *
     * @param string $msg 错误消息
     * @param mixed $data 数据
     * @param int $code 错误码
     * @return never
     * @throws ApiException
     */
    function error(string $msg, mixed $data = null, int $code = 500): never
    {
        throw new ApiException($msg, $data, $code);
    }
}
