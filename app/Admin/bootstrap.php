<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use Encore\Admin\Admin;
use Encore\Admin\Auth\Database\Menu;

Encore\Admin\Form::forget(['map', 'editor']);
Admin::css('/css/custom.css');
Admin::css('/js/custom.js');

Admin::css('/vendor/file-manager/css/file-manager.css');
Admin::js('/vendor/file-manager/js/file-manager.js');

use Encore\LargeFileUpload\LargeFileField;
use Encore\Admin\Form;

Form::extend('largefile', LargeFileField::class);

$menus = [
    ['title' => 'TVCs', 'icon' => 'fa-film', 'uri' => 'tvcs'],
    ['title' => 'Banner', 'icon' => 'fa-image', 'uri' => 'banners'],
    ['title' => 'Nhà Cái', 'icon' => 'fa-book', 'uri' => 'bookmakers'],
    ['title' => 'Danh mục Domains', 'icon' => 'fa-list', 'uri' => 'categorydomains'],
    ['title' => 'Domains', 'icon' => 'fa-globe', 'uri' => 'domains'],
    ['title' => 'BackLink', 'icon' => 'fa-bullhorn', 'uri' => 'campaigns'],
    ['title' => 'Movies', 'icon' => 'fa-video-camera', 'uri' => 'movies'],
    ['title' => 'Categories', 'icon' => 'fa-list', 'uri' => 'categories'],
];

foreach ($menus as $menu) {
    if (!Menu::where('uri', $menu['uri'])->exists()) {
        Menu::create($menu);
    }
}

// Đăng ký menu vào hệ thống
Admin::menu(function (Menu $menu) use ($menus) {
    foreach ($menus as $menuItem) {
        $menu->add($menuItem);
    }
});

