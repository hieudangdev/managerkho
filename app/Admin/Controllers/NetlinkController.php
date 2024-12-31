<?php

namespace App\Admin\Controllers;

use App\Models\Domain;
use App\Models\Netlink;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Http;

class NetlinkController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Netlink';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Netlink());

        // Hiển thị ID
        $grid->column('id', __('ID'));

        // Hiển thị redirect_url dưới dạng mảng các URL
        $grid->column('redirect_url', __('Redirect URL'))->display(function () {
            // Kiểm tra nếu redirect_url là mảng
            if (is_array($this->redirect_url)) {
                // Tạo các thẻ <a> cho từng URL trong mảng
                $links = array_map(function ($url) {
                    return "<a href=\"{$url}\" target=\"_blank\">{$url}</a>";
                }, $this->redirect_url);

                // Nối các thẻ <a> lại với nhau bằng dấu phẩy
                return implode(', ', $links);
            }

            // Nếu redirect_url không phải mảng, trả về thẻ <a> cho URL đơn
            return "<a href=\"{$this->redirect_url}\" target=\"_blank\">{$this->redirect_url}</a>";
        });

        // Hiển thị giá trị min
        $grid->column('min', __('Min Value'));

        // Hiển thị giá trị max
        $grid->column('max', __('Max Value'));

        // Hiển thị danh sách domains
        $grid->domains("List Domains")->display(function () {
            $domains = $this->domains;
            if ($domains->isNotEmpty()) {
                $links = $domains->map(function ($domain) {
                    return "<a href=\"{$domain->domain_url}\" target=\"_blank\">{$domain->domain_url}</a>";
                });

                return $links->implode(', ');
            }

            return "No domains available";
        });



        // Hiển thị hành động: Run TVC
        $grid->column('actions')->display(function () {
            // URL cho hai hành động Run và Stop
            $runUrl = route('netlink.run', ['id' => $this->id, 'active' => 'true']);

            // HTML cho hai nút
            $runButton = "<a href=\"{$runUrl}\" class=\"btn btn-success\">Run TVC</a>";

            // Nếu trạng thái không phải 'active', chỉ hiển thị nút Run
            return $runButton;
        });

        $grid->column('stop_actions')->display(function () {
            // URL cho hai hành động Run và Stop
            $stopUrl = route('netlink.run', ['id' => $this->id, 'active' => 'false']);

            // HTML cho hai nút
            $stopButton = "<a href=\"{$stopUrl}\" class=\"btn btn-danger\">Stop TVC</a>";

            return $stopButton;
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
        $show = new Show(Netlink::findOrFail($id));

        $show->field('id', __('ID'));

        $show->field('redirect_url', __('Redirect URL'))->as(function ($redirectUrl) {
            if (is_array($redirectUrl)) {
                $links = array_map(function ($url) {
                    return "<a href=\"{$url}\" target=\"_blank\">{$url}</a>";
                }, $redirectUrl);
                return implode(', ', $links);
            }
            return "<a href=\"{$redirectUrl}\" target=\"_blank\">{$redirectUrl}</a>";
        })->unescape();

        $show->field('min', __('Min Value'));
        $show->field('max', __('Max Value'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }



    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Netlink());

        // Trường redirect_url dạng textarea
        $form->textarea('redirect_url', __('Redirect URL'))
            ->required()
            ->help('Nhập các URL, mỗi URL cách nhau bằng dấu phẩy hoặc xuống dòng.');

        // Các trường còn lại
        $form->number('min', __('Min Value'))->min(0)->default(0);
        $form->number('max', __('Max Value'))->min(0)->default(0);

        // Trường Domains
        $form->checkbox('domains', __('Domains'))->options(Domain::all()->pluck('domain_url', 'id'))
            ->rules('required');

        return $form;
    }




    public function run($id, $active)
    {
        $netlink = Netlink::findOrFail($id);

        // Lấy danh sách các domain liên quan
        $domains = $netlink->domains;

        if ($domains->isEmpty()) {
            admin_toastr('No domains associated with this Netlink.', 'error');
            return redirect()->back();
        }

        $redirectUrls = $netlink->redirect_url;  // Lấy mảng redirect_url
        $min = $netlink->min;
        $max = $netlink->max;

        $errorDomains = []; // Mảng lưu danh sách các domain bị lỗi
        $activeReq = $active == 'true' ? true : false;
        foreach ($domains as $domain) {
            $apiUrl = rtrim($domain->domain_url, '/') . '/api/netlink';
            $payload = [
                'url' => $redirectUrls,  // Truyền mảng redirect_url
                'min' => $min,
                'max' => $max,
                'active' => $activeReq
            ];
            dd($payload);
            try {
                $response = Http::post($apiUrl, $payload);

                if ($response->failed()) {
                    $errorDomains[] = $domain->domain_url; // Thêm domain vào mảng lỗi
                } else {
                    admin_toastr('Tất cả domain đều hoạt động thành công!', 'success');
                }
            } catch (\Exception $e) {
                $errorDomains[] = $domain->domain_url; // Thêm domain vào mảng lỗi
            }
        }

        return redirect()->back();
    }
}
