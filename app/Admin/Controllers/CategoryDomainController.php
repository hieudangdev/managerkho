<?php

namespace App\Admin\Controllers;

use App\Models\CategoryDomain;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CategoryDomainController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Danh mục Domain';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CategoryDomain());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('icon', __('Icon'))->display(function ($icon) {
            return $icon ? "<i class='fa {$icon}'></i>" : '';
        });

        $grid->column('total_domains', __('Tổng số Domains'))->display(function () {
            return $this->domains()->count();
        });
        // Cột hiển thị danh sách domains
        $grid->column('view_domains', __('Danh sách Domains'))->modal('Danh sách Domains', function ($model) {
            // Lấy danh sách domains qua quan hệ
            $domains = $model->domains()->with('adminUser')->get();

            if ($domains->isNotEmpty()) {
                $tableHtml = '<table class="table table-bordered">';
                $tableHtml .= '<thead>
                            <tr>
                                <th>ID</th>
                                <th>URL</th>
                                <th>Owner</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>';

                $tableHtml .= '<tbody>';
                foreach ($domains as $domain) {
                    $ownerName = $domain->adminUser ? $domain->adminUser->name : 'Chưa có'; 
                    $adminUrl = url("kho8k/domains/{$domain->id}");
                    $tableHtml .= "<tr>
                                <td>{$domain->id}</td>
                                <td><a href='{$adminUrl}' target='_blank'>{$domain->domain_url}</a></td>
                                <td>{$ownerName}</td>
                                <td>" . ($domain->status ? 'Active' : 'Inactive') . "</td>
                            </tr>";
                }
                $tableHtml .= '</tbody></table>';

                return $tableHtml;
            }

            return "<p>Chưa có Domain nào trong Category này.</p>";
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
        $show = new Show(CategoryDomain::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('icon', __('Icon'))->as(function ($icon) {
            return $icon ? "<i class='fa {$icon}'></i>" : '';
        })->unescape();
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CategoryDomain());

        $form->text('name', __('Name'));
        $form->icon('icon', __('Icon'));

        return $form;
    }
}
