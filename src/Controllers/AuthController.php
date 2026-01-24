<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\AuthService;
use function Lartrix\Support\success;
use function Lartrix\Support\error;

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
            error('用户名或密码错误', null, 40001);
        }

        $token = $result['token'];

        return success('登录成功', [
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request): array
    {
        $this->authService->logout($request->user());
        return success('登出成功');
    }

    /**
     * 刷新 Token
     */
    public function refresh(Request $request): array
    {
        $token = $this->authService->refresh($request->user());

        return success('刷新成功', [
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
            'name' => $user->name,
            'nick_name' => $user->nick_name,
            'real_name' => $user->real_name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'avatar' => $user->avatar,
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
            error('Token 不存在', null, 40004);
        }

        return success('撤销成功');
    }
}
