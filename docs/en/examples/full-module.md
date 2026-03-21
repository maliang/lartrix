# Full Module Example

Create a blog module from scratch.

## 1. Create the Module

```bash
php artisan module:make Blog
```

## 2. Create Migrations

```bash
php artisan module:make-migration create_posts_table Blog
php artisan module:make-migration create_categories_table Blog
```

## 3. Create Models

```bash
php artisan module:make-model Post Blog
php artisan module:make-model Category Blog
```

## 4. Create Controller

```bash
php artisan module:make-controller PostController Blog
```

## 5. Register Routes

Edit `Modules/Blog/Routes/api.php`:

```php
Route::middleware(['auth:admin'])->group(function () {
    Route::resource('posts', PostController::class);
});
```

## 6. Create Menu

Log in to the admin panel and add a menu entry:
- Title: Blog Management
- Path: /blog/posts
- Permission: posts.list

## 7. Done

You can now visit /blog/posts to manage posts.
