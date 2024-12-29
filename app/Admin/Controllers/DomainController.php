<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\CloneDomains;
use App\Admin\Actions\EditDomainFields;
use App\Models\Domain;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Auth\Database\Administrator;

class DomainController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Domain';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Domain());

        $grid->column('id', __('Id'));
        $grid->column('domain_url', __('Domain url'));
        $grid->category()->name(__('Thể loại'))->display(function ($name) {
            return $name ?? '-';
        });
        $grid->column('status', __('Status'));
        $grid->column('adminUser.username', __('Owner'));
        $grid->column('UserAdmin', __('User Admin'));
        $grid->column('PassAdmin', __('Pass Admin'));
        $grid->column('prefixAdmin', __('Prefix Admin'));
        // Thêm BatchAction
        $grid->batchActions(function ($batch) {
            $batch->add(new CloneDomains());
        });
        // Thêm BatchAction EditDomainFields
        $grid->batchActions(function ($batch) {
            $batch->add(new EditDomainFields());
        });

        $grid->column('run')->display(function () {
            $url = $this->domain_url . $this->prefixAdmin; // campaign id
            return "<a href=\"{$url}\" target='_blank' style='display:block;color: #fff;border-radius: 3px;padding: 3px;width: 41px;text-align: center;background: #3c8dbc;'>Mở</a>";
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Domain::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('domain_url', __('Domain url'));
        $show->category()->name(__('Thể Loại'))->as(function ($category) {
            return $category['name'] ?? '-'; // Hiển thị tên của Bookmaker hoặc dấu '-' nếu không có tên
        });
        $show->field('adminUser.username', __('Owner'));
        $show->field('prefixAdmin', __('Prefix Admin'));
        $show->field('UserAdmin', __('User Admin'));
        $show->field('PassAdmin', __('Pass Admin'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        // Thêm nút Run trong trang Show

        return $show;
    }

    // Hàm để thực thi nút Run

    protected function form()
    {
        $form = new Form(new Domain());

        // Trường textarea để nhập domain URLs
        $form->url('domain_url', __('Domain url'));
        $form->select('category_id', __('Category'))->options(
            \App\Models\CategoryDomain::all()->pluck('name', 'id')
        );
        // Các trường khác như Owner, UserAdmin, PassAdmin, ...
        $form->select('owner', __('Owner'))->options(Administrator::pluck('username', 'id'));
        $form->text('UserAdmin', __('User Admin'));
        $form->text('PassAdmin', __('Pass Admin'));
        $form->text('prefixAdmin', __('Prefix Admin'));
        $form->text('status', __('Status'))->default('active');

        return $form;
    }
}
