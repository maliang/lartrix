<?php

namespace Lartrix\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lartrix\Services\TranslationService;
use Symfony\Component\HttpFoundation\Response;

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
            error(__t('auth.unauthenticated'), null, 40001);
        }

        // 检查用户状态
        if (!$request->user()->isActive()) {
            error(__t('auth.user_disabled'), null, 40101);
        }

        $locale = app(TranslationService::class)->normalizeLocale(
            (string) ($request->user()->locale ?: config('lartrix.locale', 'zh-CN'))
        );
        if ($locale !== null) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
