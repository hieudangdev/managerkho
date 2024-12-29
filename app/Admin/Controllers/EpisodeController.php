<?php

namespace App\Admin\Controllers;

use App\Models\Episode;
use App\Models\Movie;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class EpisodeController extends AdminController
{
    /**
     * Title for current resource.
     */
    protected $title = 'Episodes';

    /**
     * Make a grid builder.
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Episode());

        $grid->column('id', 'ID')->sortable();
        $grid->column('movie.title', 'Movie Title'); // Hiển thị tên bộ phim từ mối quan hệ
        $grid->column('title', 'Episode Title');
        $grid->column('episode_number', 'Episode Number')->sortable();
        $grid->column('video_url', 'Video URL')->link();
        $grid->column('created_at', 'Created At')->sortable();
        $grid->column('updated_at', 'Updated At');

        return $grid;
    }

    /**
     * Make a show builder.
     */
    protected function detail($id): Show
    {
        $show = new Show(Episode::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('movie.title', 'Movie Title');
        $show->field('title', 'Episode Title');
        $show->field('episode_number', 'Episode Number');
        $show->field('video_url', 'Video URL')->link();
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    /**
     * Make a form builder.
     */
    protected function form(): Form
    {
        $form = new Form(new Episode());

        $form->hidden('movie_id')->default(request('movie_id'));
        $form->text('title', 'Episode Title')->required();
        $form->number('episode_number', 'Episode Number')->min(1)->required();
        $form->file('video_url', 'Video')
            ->help('If no video is selected, the previous video will be kept.')
            ->rules('required_if:video_url,==,null'); // Tùy chỉnh rule nếu cần
        // Giữ lại video_url cũ nếu không thay đổi  
        if ($form->model()->exists) {
            $form->ignore(['video_url']);
        }
        
        $form->saved(function (Form $form) {
            // Lấy movie_id từ form và chuyển hướng về trang show của movie
            $movieId = $form->model()->movie_id;
            admin_toastr('Episode created successfully!');
            return redirect()->to('kho8k/movies/' . $movieId);
        });
        return $form;
    }
}
