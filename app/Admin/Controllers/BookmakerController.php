<?php

namespace App\Admin\Controllers;

use App\Models\Bookmaker;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class BookmakerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Nhà Cái';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Bookmaker());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('view_details', __('View Details'))->modal('Danh sách TVCs và Banners', function ($model) {
            // Lấy danh sách TVCs liên kết với Bookmaker
            $tvcs = $model->tvcs; // Mảng các TVCs
            // Lấy danh sách Banners liên kết với Bookmaker
            $banners = $model->banners()->get();

            // Tạo bảng TVC
            $tableHtml = '';
            if ($tvcs->isNotEmpty()) {
                $tableHtml .= '<h5>Danh sách TVCs</h5>';
                $tableHtml .= '<table class="table table-bordered">';
                $tableHtml .= '<thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Ngày kết thúc</th>
                                    <th>Thời hạn (ngày)</th>
                                </tr>
                            </thead>';
                $tableHtml .= '<tbody>';
                foreach ($tvcs as $tvc) {
                    $remainingDays = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($tvc->end_day), false);
                    $expiryText = $remainingDays >= 0 ? "Còn {$remainingDays} ngày" : "Đã hết hạn";
                    $tableHtml .= "<tr>
                                    <td>{$tvc->id}</td>
                                    <td>{$tvc->name}</td>
                                    <td>{$tvc->status}</td>
                                    <td>{$tvc->start_day}</td>
                                    <td>{$tvc->end_day}</td>
                                    <td>{$expiryText}</td>
                                </tr>";
                }
                $tableHtml .= '</tbody></table>';
            } else {
                $tableHtml .= "<p>Chưa có TVC cho Nhà cái này.</p>";
            }

            // Tạo bảng Banner
            if ($banners->isNotEmpty()) {
                $tableHtml .= '<h5>Danh sách Banners</h5>';
                $tableHtml .= '<table class="table table-bordered">';
                $tableHtml .= '<thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Link Redirect</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Ngày kết thúc</th>
                                    <th>Thời hạn (ngày)</th>
                                </tr>
                            </thead>';
                $tableHtml .= '<tbody>';
                foreach ($banners as $banner) {
                    $remainingDays = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($banner->end_day), false);
                    $expiryText = $remainingDays >= 0 ? "Còn {$remainingDays} ngày" : "Đã hết hạn";
                    $tableHtml .= "<tr>
                                    <td>{$banner->id}</td>
                                    <td>{$banner->name}</td>
                                    <td><a href='{$banner->redirect_url}' target='_blank'>{$banner->redirect_url}</a></td>
                                    <td>{$banner->status}</td>
                                    <td>{$banner->start_day}</td>
                                    <td>{$banner->end_day}</td>
                                    <td>{$expiryText}</td>
                                </tr>";
                }
                $tableHtml .= '</tbody></table>';
            } else {
                $tableHtml .= "<p>Chưa có Banner nào liên quan đến Nhà cái này.</p>";
            }

            return $tableHtml; // Trả về nội dung HTML của cả TVCs và Banners
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
        $show = new Show(Bookmaker::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
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
        $form = new Form(new Bookmaker());

        $form->text('name', __('Name'));

        return $form;
    }

    public function createBookmaker(Request $request)
    {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Create new bookmaker
        $bookmaker = new Bookmaker();
        $bookmaker->name = $request->name;

        // Save and return response
        if ($bookmaker->save()) {
            return response()->json([
                'status' => true,
                'message' => 'Save succeeded !',
                'bookmaker' => $bookmaker, // Trả về thông tin của bookmaker
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi khi lưu bookmaker!'
            ]);
        }
    }
}
