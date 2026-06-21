<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * 用户登录
     */
    public function login(Request $request): array
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login(
            $request->input('username'),
            $request->input('password')
        );

        if (!$result) {
            error(__t('auth.failed'), null, 40001);
        }

        $token = $result['token'];

        return success(__t('auth.login_ok'), [
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request): array
    {
        $this->authService->logout($request->user());
        return success(__t('auth.logout_ok'));
    }

    /**
     * 刷新 Token
     */
    public function refresh(Request $request): array
    {
        $token = $this->authService->refresh($request->user());

        return success(__t('auth.refresh_ok'), [
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * 获取当前用户信息
     */
    public function user(Request $request): array
    {
        $user = $request->user();

        return success([
            'id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getActivePermissions()->pluck('name'),
        ]);
    }

    /**
     * 获取用户所有 Token
     */
    public function tokens(Request $request): array
    {
        $tokens = $this->authService->getTokens($request->user());
        return success($tokens);
    }

    /**
     * 撤销指定 Token
     */
    public function revokeToken(Request $request, int $id): array
    {
        $result = $this->authService->revokeToken($request->user(), $id);

        if (!$result) {
            error(__t('auth.token_not_found'), null, 40004);
        }

        return success(__t('auth.revoke_ok'));
    }

    /**
     * 获取后台配置（API 接口）
     * 注意：只返回前端必需的公开信息
     */
    public function config(): array
    {
        $theme = \Lartrix\Models\Setting::getGroup('theme');
        return success([
            'apiPrefix' => '/' . ltrim(config('lartrix.api_prefix', 'api/admin'), '/'),
            'appTitle' => $theme['appTitle'] ?? 'Lartrix Admin',
            'logo' => $theme['logo'] ?? '',
            'locale' => config('lartrix.locale', 'zh-CN'),
            'fallbackLocale' => config('lartrix.fallback_locale', 'en-US'),
        ]);
    }
}
