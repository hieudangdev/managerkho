<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class EditDomainFields extends BatchAction
{
    public $name = 'Edit Domain Fields'; // Tên hiển thị của action

    // Định nghĩa form để nhập dữ liệu mới
    public function form()
    {
        $this->select('owner', 'Owner')->options(Administrator::pluck('username', 'id'));
        $this->text('UserAdmin', 'User Admin');
        $this->text('PassAdmin', 'Pass Admin');
        $this->text('prefixAdmin', 'Prefix Admin');
    }

    // Xử lý logic cập nhật
    public function handle(Collection $models, Request $request)
    {
        // Lấy giá trị mới từ form
        $newOwner = $request->get('owner');
        $newUserAdmin = $request->get('UserAdmin');
        $newPassAdmin = $request->get('PassAdmin');
        $newPrefixAdmin = $request->get('prefixAdmin');

        foreach ($models as $model) {
            if ($newOwner) {
                $model->owner = $newOwner;
            }
            if ($newUserAdmin) {
                $model->UserAdmin = $newUserAdmin;
            }
            if ($newPassAdmin) {
                $model->PassAdmin = $newPassAdmin;
            }
            if ($newPrefixAdmin) {
                $model->prefixAdmin = $newPrefixAdmin;
            }
            $model->save();
        }

        return $this->response()->success('Selected domains have been updated successfully.')->refresh();
    }
}
