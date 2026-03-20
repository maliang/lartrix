# 完整模块示例

从零开始创建一个博客模块。

## 1. 创建模块

```bash
php artisan module:make Blog
```

## 2. 创建迁移

```bash
php artisan module:make-migration create_posts_table Blog
php artisan module:make-migration create_categories_table Blog
```

## 3. 创建模型

```bash
php artisan module:make-model Post Blog
php artisan module:make-model Category Blog
```

## 4. 创建控制器

```bash
php artisan module:make-controller PostController Blog
```

## 5. 注册路由

编辑 `Modules/Blog/Routes/api.php`：

```php
Route::middleware(['auth:admin'])->group(function () {
    Route::resource('posts', PostController::class);
});
```

## 6. 创建菜单

登录后台，添加菜单：
- 标题：博客管理
- 路径：/blog/posts
- 权限：posts.list

## 7. 完成

现在可以访问 /blog/posts 管理文章了。
