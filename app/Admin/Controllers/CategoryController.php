<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Category';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Category());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('slug', __('Slug'));

        $grid->column('movies_count', __('Số lượng Movie'))->display(function () {
            return $this->movies()->count();
        });
        // Cột hiển thị danh sách Movies
        $grid->column('view_movies', __('Danh sách Movies'))->modal('Danh sách Movies', function ($model) {

            $movies = $model->movies()
                ->select('movies.id', 'movies.title', 'movies.quality', 'movies.video_path')
                ->get();
            // Tạo HTML cho danh sách các movie dưới dạng bảng
            $moviesHtml = '<table class="table table-bordered">';
            $moviesHtml .= '<thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Quality</th>
                                    <th>Video Path</th>
                                </tr>
                            </thead>';
            $moviesHtml .= '<tbody>';
            foreach ($movies as $movie) {
                $moviesHtml .= '<tr>';
                $moviesHtml .= '<td>' . $movie->id . '</td>';
                $moviesHtml .= '<td>' . $movie->title . '</td>';
                $moviesHtml .= '<td>' . $movie->quality . '</td>';
                $moviesHtml .= '<td>' . $movie->video_path . '</td>';
                $moviesHtml .= '</tr>';
            }
            $moviesHtml .= '</tbody>';
            $moviesHtml .= '</table>';

            // Trả về nội dung modal với bảng
            return $moviesHtml;
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
        $show = new Show(Category::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('slug', __('Slug'));
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
        $form = new Form(new Category());

        $form->text('name', __('Name'))->required();
        $form->text('slug', __('Slug'));

        // Thêm script để tự động cập nhật slug
        Admin::script($this->generateSlugScript());

        // Sinh slug trước khi lưu nếu người dùng không nhập slug
        $form->saving(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = \Str::slug($form->name);
            }
        });

        return $form;
    }

    protected function generateSlugScript()
    {
        return <<<SCRIPT
        // Hàm chuyển tiếng Việt sang không dấu và tạo slug
        function removeVietnameseTones(str) {
            return str
                .normalize('NFD') // Chuẩn hóa chuỗi Unicode
                .replace(/[\u0300-\u036f]/g, '') // Xóa dấu
                .replace(/đ/g, 'd').replace(/Đ/g, 'D'); // Thay thế chữ đ
        }

        $(document).on('input', 'input[name="name"]', function () {
            var name = $(this).val(); // Lấy giá trị của Name
            if (name) {
                // Bỏ dấu tiếng Việt và chuyển sang slug
                var slug = removeVietnameseTones(name)
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '') // Xóa ký tự đặc biệt
                    .replace(/\s+/g, '-'); // Thay khoảng trắng bằng gạch ngang
                $('input[name="slug"]').val(slug); // Cập nhật trường Slug
            }
        });
        SCRIPT;
    }
}
