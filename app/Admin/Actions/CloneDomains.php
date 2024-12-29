<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CloneDomains extends BatchAction
{
    public $name = 'Bulk Clone with Custom Domain URLs';

    // Hiển thị form để người dùng nhập danh sách domain URL mới
    public function form()
    {
        $this->textarea('domain_urls', 'Domain URLs')->placeholder(
            'Enter each new domain URL on a new line.'
        ); // Cho phép nhập nhiều URL trên nhiều dòng
    }

    // Xử lý logic clone
    public function handle(Collection $models, Request $request)
    {
        // Lấy danh sách domain URL từ form
        $domainUrls = array_filter(array_map('trim', explode("\n", $request->get('domain_urls'))));

        if (empty($domainUrls)) {
            return $this->response()->error('No domain URLs provided.')->refresh();
        }

        // Clone mỗi bản ghi được chọn theo danh sách URL nhập vào
        foreach ($models as $model) {
            foreach ($domainUrls as $url) {
                $newModel = $model->replicate(); // Tạo bản sao của model gốc
                $newModel->domain_url = $url; // Gán domain_url mới
                $newModel->save(); // Lưu bản ghi mới
            }
        }

        return $this->response()->success("Cloned {$models->count()} records for each custom domain URL.")->refresh();
    }
}
