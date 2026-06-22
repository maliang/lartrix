<?php

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
        if (\is_array($msg)) {
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

if (!function_exists('__t')) {
    /**
     * 语言翻译辅助函数
     *
     * 封装 Laravel 的 __() 函数，支持 lartrix 命名空间。
     * 如果 key 以 'lartrix.' 开头，自动添加命名空间前缀。
     * 支持参数替换：__t('auth.login_ok')、__t('welcome :name', ['name' => '张三'])
     *
     * @param string $key     翻译键名（可使用 'lartrix.auth.login_ok' 或 'auth.login_ok'）
     * @param array  $replace 替换参数
     * @return string
     */
    function __t(string $key, array $replace = []): string
    {
        $key = str_starts_with($key, 'lartrix.') ? substr($key, 8) : $key;
        return app(\Lartrix\Services\TranslationService::class)->translate($key, $replace);
    }
}
