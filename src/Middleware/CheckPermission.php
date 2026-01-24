<?php

namespace Lartrix\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lartrix\Services\PermissionService;
use Symfony\Component\HttpFoundation\Response;
use function Lartrix\Support\error;

class CheckPermission
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$permissions 需要的权限（任一即可）
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            error('未认证', null, 40001);
        }

        // 检查用户是否有任一指定权限（排除禁用角色的权限）
        if (!empty($permissions) && !$this->permissionService->userHasAnyPermission($user, $permissions)) {
            error('无权限访问', null, 40003);
        }

        return $next($request);
    }
}
