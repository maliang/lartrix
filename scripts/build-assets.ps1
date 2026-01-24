#
# 构建 trix 前端并复制到 lartrix 资源目录 (Windows PowerShell)
#

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$LartrixDir = Split-Path -Parent $ScriptDir
$TrixDir = Join-Path (Split-Path -Parent $LartrixDir) "trix"
$AssetsDir = Join-Path $LartrixDir "resources\admin"

Write-Host "========================================="
Write-Host "  构建 trix 前端资源"
Write-Host "========================================="
Write-Host ""

# 检查 trix 目录是否存在
if (-not (Test-Path $TrixDir)) {
    Write-Host "错误: trix 目录不存在: $TrixDir" -ForegroundColor Red
    exit 1
}

# 进入 trix 目录
Set-Location $TrixDir

# 安装依赖（如果需要）
if (-not (Test-Path "node_modules")) {
    Write-Host "安装依赖..."
    pnpm install
}

# 构建
Write-Host "构建前端..."
pnpm build

# 清空目标目录（保留 .gitkeep）
Write-Host "清理目标目录..."
Get-ChildItem -Path $AssetsDir -Exclude ".gitkeep" | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

# 复制构建文件
Write-Host "复制构建文件..."
Copy-Item -Path "dist\*" -Destination $AssetsDir -Recurse -Force

Write-Host ""
Write-Host "========================================="
Write-Host "  构建完成！"
Write-Host "========================================="
Write-Host "资源目录: $AssetsDir"
Write-Host ""
