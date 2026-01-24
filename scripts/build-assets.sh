#!/bin/bash
# 
# 构建 trix 前端并复制到 lartrix 资源目录
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARTRIX_DIR="$(dirname "$SCRIPT_DIR")"
TRIX_DIR="$(dirname "$LARTRIX_DIR")/trix"
ASSETS_DIR="$LARTRIX_DIR/resources/admin"

echo "========================================="
echo "  构建 trix 前端资源"
echo "========================================="
echo ""

# 检查 trix 目录是否存在
if [ ! -d "$TRIX_DIR" ]; then
    echo "错误: trix 目录不存在: $TRIX_DIR"
    exit 1
fi

# 进入 trix 目录
cd "$TRIX_DIR"

# 安装依赖（如果需要）
if [ ! -d "node_modules" ]; then
    echo "安装依赖..."
    pnpm install
fi

# 构建
echo "构建前端..."
pnpm build

# 清空目标目录（保留 .gitkeep）
echo "清理目标目录..."
find "$ASSETS_DIR" -mindepth 1 ! -name '.gitkeep' -delete 2>/dev/null || true

# 复制构建文件
echo "复制构建文件..."
cp -r dist/* "$ASSETS_DIR/"

echo ""
echo "========================================="
echo "  构建完成！"
echo "========================================="
echo "资源目录: $ASSETS_DIR"
echo ""
