<?php

namespace Lartrix\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lartrix\Services\PermissionService;
use Symfony\Component\HttpFoundation\Response;

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
            error(__t('auth.unauthenticated'), null, 40001);
        }

        $mapped = [];
        $fallback = [];
        foreach ($permissions as $permission) {
            if (str_contains($permission, '=')) {
                [$action, $name] = explode('=', $permission, 2);
                $mapped[$action][] = $name;
            } else {
                $fallback[] = $permission;
            }
        }

        $routeAction = (string) $request->route()?->getActionMethod();
        $requestedAction = (string) $request->input('action_type', '');
        $allowedSubActions = [
            'update' => ['update', 'status', 'reset_password', 'permissions', 'sort'],
            'destroy' => ['delete', 'batch'],
        ];
        $action = isset($allowedSubActions[$routeAction]) && in_array($requestedAction, $allowedSubActions[$routeAction], true)
            ? $requestedAction
            : $routeAction;
        $required = $mapped[$action] ?? $mapped[$routeAction] ?? $mapped['*'] ?? $fallback;

        // 检查用户是否有任一指定权限（排除禁用角色的权限）
        if ($required !== [] && !$this->permissionService->userHasAnyPermission($user, $required)) {
            error(__t('system.forbidden'), null, 40003);
        }

        return $next($request);
    }
}
