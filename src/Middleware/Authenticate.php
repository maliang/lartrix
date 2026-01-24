<?php

namespace Lartrix\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use function Lartrix\Support\error;

class Authenticate
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 检查 Token 有效性
        if (!$request->user()) {
            error('未认证', null, 40001);
        }

        // 检查用户状态
        if (!$request->user()->isActive()) {
            error('用户已禁用', null, 40101);
        }

        return $next($request);
    }
}
