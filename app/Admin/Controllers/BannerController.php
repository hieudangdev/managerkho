<?php

namespace App\Admin\Controllers;

use App\Models\Banner;
use App\Models\Bookmaker;
use App\Models\Domain;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Http;

class BannerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Quản lý Banner';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Banner());

        // Hiển thị tên Banner
        $grid->column('name', __('Name'));

        // Hiển thị mô tả
        $grid->column('description', __('Mô tả'));

        $grid->column('redirect_url', __('Link chuyển hướng'))->display(function ($redirectUrl) {
            return $redirectUrl ? "<a href='{$redirectUrl}' target='_blank' style='display: inline-block; max-width: 300px; overflow: hidden; text-overflow: ellipsis;'>{$redirectUrl}</a>" : '-';
        });

        // Hiển thị hình ảnh dưới dạng danh sách
        $grid->column('img_url', __('Hình ảnh'))->display(function ($imgUrls) {
            if (is_array($imgUrls)) {
                return collect($imgUrls)->map(function ($url) {
                    if (!empty($url)) {
                        return "<a href='{$url}' target='_blank' style='display: inline-block; max-width: 300px; overflow: hidden; text-overflow: ellipsis;'>{$url}</a>";
                    }
                    return '';
                })->filter()->implode('<br>');
            }
            return '-';
        });

        // Hiển thị danh sách vị trí
        $grid->column('position', __('Vị trí'))->display(function ($positions) {
            if (is_array($positions)) {
                $positionNames = [
                    'ads_header' => 'Ads Header',
                    'ads_catfish' => 'Ads Catfish',
                    'ads_popup' => 'Pop Up',
                ];

                // Lọc các vị trí đã chọn và chuyển chúng thành tên
                $selectedPositions = array_intersect_key($positionNames, array_flip($positions));

                return implode(', ', $selectedPositions);
            }
            return '-';
        });

        // Hiển thị danh sách các domain
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
        $grid->bookmaker()->name(__('Nhà Cái'))->display(function ($name) {
            return $name ?? '-';
        });
        // Hiển thị trạng thái với màu sắc tùy chỉnh
        $grid->status(__('Trạng Thái'))->display(function ($status) {
            $colors = [
                'active' => 'green',
                'stop' => 'gray',
                'wait' => 'orange',
                'failed' => 'red',
            ];
            $color = $colors[$status] ?? 'black';
            return "<span style='color: {$color};'>" . ucfirst($status) . "</span>";
        });
        // Hiển thị ngày bắt đầu
        $grid->column('start_day', __('Start day'))->sortable();
        // Hiển thị số ngày còn lại
        $grid->column('remaining_days', __('Ngày hết hạn'))->display(function () {
            $endDay = \Carbon\Carbon::parse($this->end_day);
            $today = \Carbon\Carbon::now();
            $daysLeft = $today->diffInDays($endDay, false);

            if ($daysLeft < 0) {
                return "<span style='color: red;'>Đã hết hạn</span>";
            }
            return "Còn {$daysLeft} ngày";
        });
        // Hiển thị hành động Run/Stop
        $grid->column('actions', __('Actions'))->display(function () {
            $runUrl = route('banner.run', ['id' => $this->id]);
            $stopUrl = route('banner.stop', ['id' => $this->id]);

            $runButton = "<a href='{$runUrl}' class='btn btn-success btn-sm'>Run</a>";
            $stopButton = "<a href='{$stopUrl}' class='btn btn-danger btn-sm'>Stop</a>";

            return $this->status === 'active' ? $stopButton : $runButton;
        })->setAttributes(['style' => 'white-space: nowrap;']);

        $grid->filter(function ($filter) {
            $filter->like('name', 'Tên Banner');
            $filter->like('description', 'Mô tả');
            $filter->equal('status', 'Trạng thái')->radio([
                'wait' => 'Chờ',
                'active' => 'Hoạt động',
                'stop' => 'Dừng',
            ]);
            $filter->equal('bookmaker_id', 'Bookmaker')->select(Bookmaker::all()->pluck('name', 'id'));
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
        $show = new Show(Banner::findOrFail($id));
        $show->field('id', __('Id'));
        $show->field('name', __('Tên'));
        $show->field('description', __('Mô tả'));
        $show->bookmaker()->name(__('Nhà Cái'))->as(function ($bookmaker) {
            return $bookmaker['name'] ?? '-'; 
        });

        $show->field('redirect_url', __('Link chuyển hướng'))->as(function ($redirectUrl) {
            return $redirectUrl ? "<a href='{$redirectUrl}' target='_blank'>{$redirectUrl}</a>" : '-';
        })->unescape();

        $show->field('img_url', __('Hình ảnh'))->as(function ($url) {
            if (is_array($url)) {
                $imgUrls = collect($url)->filter()->map(function ($item) {
                    return "<img src='{$item}' style='width: 400px; height: auto;' />";
                })->implode('<br>');
                return $imgUrls;
            }
            return "<img src='{$url}' style='width: 400px; height: auto;'>";
        })->unescape();

        $show->field('position', __('Vị trí'))->as(function ($value) {
            $positions = is_array($value) ? $value : json_decode($value, true);

            if (!$positions) return "Không xác định"; // Nếu không giải mã được, trả về thông báo

            return collect($positions)->map(function ($item) {
                switch ($item) {
                    case 'ads_header':
                        return 'Ads Header';
                    case 'ads_catfish':
                        return 'Ads Catfish';
                    case 'ads_popup':
                        return 'Pop Up';
                    default:
                        return $item;
                }
            })->implode(', ');
        })->unescape();

        $show->field('status', __('Tình trạng'));
        $show->field('start_day', __('Ngày bắt đầu'));
        $show->field('end_day', __('Ngày kết thúc'));
        $show->field('duration', __('Thời hạn'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->domains('Domains', function ($domain) {
            $domain->resource('/admin/domains');
            $domain->id('ID');
            $domain->domain_url('Domain URL');
        });
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Banner());

        $form->text('name', __('Tên'))->rules('required');
        $form->text('description', __('Mô tả'));
        $form->url('redirect_url', __('Link chuyển hướng'))->rules('required');

        $form->text('img_url.ads_header', __('Ảnh Ads Header'))->placeholder('Đường dẫn hình ảnh cho Ads Header');
        $form->html('<button  type="button" class="btn btn-primary"  id="open-file-ads-header" >Select Image</button>');

        $form->text('img_url.ads_catfish', __('Ảnh Ads Catfish'))->placeholder('Đường dẫn hình ảnh cho Ads Catfish');
        $form->html('<button  type="button" class="btn btn-primary"  id="open-file-ads-catfish" >Select Image</button>');

        $form->text('img_url.ads_popup', __('Ảnh Ads Popup'))->placeholder('Đường dẫn hình ảnh cho Ads Popup');
        $form->html('<button  type="button" class="btn btn-primary"  id="open-file-ads-popup" >Select Image</button>');

        Admin::css('/css/custom.css');
        Admin::script($this->uploadscript());

        $form->select('bookmaker_id', __('Bookmaker'))->options(
            \App\Models\Bookmaker::all()->pluck('name', 'id')
        )->help(
            '<button type="button" id="create-bookmaker" class="btn btn-sm btn-success">Tạo mới Bookmaker</button>'
        )->rules('required');

        $form->hidden('status', __('Status'))->default('wait');
        $form->date('start_day', __('Ngày bắt đầu'))->default(date('Y-m-d'))->rules('required');
        $form->hidden('end_day', __('Ngày kết thúc'))->default(date('Y-m-d'));
        $form->number('duration', __('Thời hạn'))->default(30)->rules('required');

        $form->checkbox('domains', __('Domains'))->options(Domain::all()->pluck('domain_url', 'id'))
            ->rules('required');

        // Modal tạo mới Bookmaker
        $form->html('
            <div id="createBookmakerModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="createBookmakerModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createBookmakerModalLabel">Tạo mới Bookmaker</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Không dùng form riêng biệt, chỉ input -->
                            <div class="form-group">
                                <label for="bookmakerName">Tên Bookmaker</label>
                                <input type="text" class="form-control" id="bookmakerName" placeholder="Nhập tên Bookmaker" >
                            </div>
                            <button type="button" class="btn btn-primary" id="submitBookmakerForm">Tạo mới</button>
                        </div>
                    </div>
                </div>
            </div>');
        Admin::script($this->bookmakerscript());

        $form->saving(function (Form $form) {
            // Xử lý ngày bắt đầu và ngày kết thúc dựa trên thời hạn
            if ($form->start_day && $form->duration) {
                $form->end_day = \Carbon\Carbon::parse($form->start_day)
                    ->addDays($form->duration)
                    ->toDateTimeString();
            }

            $imgUrls = [];
            $positions = [];
            $bannerPositions = ['ads_header', 'ads_catfish', 'ads_popup'];

            foreach ($bannerPositions as $position) {
                // Lấy đường dẫn ảnh từ input text
                $imgUrl = $form->input("img_url.$position");
                // Kiểm tra xem đường dẫn có được nhập không
                if (!empty($imgUrl)) {
                    $imgUrls[$position] = $imgUrl;
                    $positions[] = $position;
                } else {
                    // Nếu không có đường dẫn, lấy từ banner hiện tại
                    $imgUrls[$position] =  null; //$form->model()->img_url[$position] ??
                    if (!empty($imgUrls[$position])) {
                        $positions[] = $position; // Thêm vị trí đã nhập vào danh sách positions nếu có giá trị
                    }
                }
            }

            $form->model()->img_url = $imgUrls; // Lưu đường dẫn ảnh dưới dạng JSON
            $form->model()->position = $positions; // Lưu danh sách các vị trí có dữ liệu
        });
        return $form;
    }

    protected function uploadscript()
    {
        return <<<SCRIPT
            document.getElementById('open-file-ads-header').addEventListener('click', (event) => {
                event.preventDefault();
                window.open('/file-manager/fm-button', 'fm', 'width=1000,height=600');
                window.fmSetLinkTarget = 'ads_header';
            });

            document.getElementById('open-file-ads-catfish').addEventListener('click', (event) => {
                event.preventDefault();
                window.open('/file-manager/fm-button', 'fm', 'width=1000,height=600');
                window.fmSetLinkTarget = 'ads_catfish';
            });

            document.getElementById('open-file-ads-popup').addEventListener('click', (event) => {
                event.preventDefault();
                window.open('/file-manager/fm-button', 'fm', 'width=1000,height=600');
                window.fmSetLinkTarget = 'ads_popup';
            });

            window.fmSetLink = function(fileUrl) {
                if (window.fmSetLinkTarget) {
                    document.querySelector('input[name="img_url[' + window.fmSetLinkTarget + ']"]').value = fileUrl;
                }
            };
        SCRIPT;
    }

    protected function bookmakerscript()
    {
        return <<<SCRIPT
            // Mở modal khi nhấn nút thêm mới
            document.getElementById('create-bookmaker').addEventListener('click', function () {
                $('#createBookmakerModal').modal('show');
            });

            // Xử lý tạo mới Bookmaker bằng AJAX
            document.getElementById('submitBookmakerForm').addEventListener('click', function () {
                let name = document.getElementById('bookmakerName').value.trim(); // Lấy giá trị input

                if (!name) {
                    alert("Tên Bookmaker không được để trống!");
                    document.getElementById('bookmakerName').focus();
                    return;
                }

                // Gửi AJAX
                $.ajax({
                    url: '/admin/bookmakers',
                    method: 'POST',
                    data: {
                        name: name,
                        _token: LA.token // Token CSRF được lấy từ LA.token
                    },
                    success: function(response) {
                        if (response.status) {
                            // Đóng modal
                            $('#createBookmakerModal').modal('hide');

                            // Thêm Bookmaker vào dropdown
                            let newOption = new Option(response.bookmaker.name, response.bookmaker.id, true, true);
                            $('.bookmaker_id').append(newOption).trigger('change');

                            alert("Tạo mới Bookmaker thành công!");
                        } else {
                            alert("Có lỗi khi tạo Bookmaker: " + response.message);
                        }
                    },
                    error: function() {
                        alert("Đã xảy ra lỗi trong quá trình gửi yêu cầu!");
                    }
                });
            });
        SCRIPT;
    }

    public function run($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            admin_toastr('Không tìm thấy Banner !', 'error');
            return redirect()->back();
        }

        // Redirect link từ banner
        $redirectLink = $banner->redirect_url ?? '';

        $allPositions = ['ads_header', 'ads_catfish', 'ads_popup'];

        // Tạo payload với các thẻ <img>
        $payload = ['redirect_link' => $redirectLink];
        foreach ($allPositions as $position) {
            $imageLink = $banner->img_url[$position] ?? '';
            if ($imageLink) {
                $payload[$position] = "<img src=\"$imageLink\" alt=\"" . ucfirst(str_replace('_', ' ', $position)) . "\">";
            }
        }
        $domains = $banner->domains;
        $errorDomains = [];
        $allSuccessful = true;

        foreach ($domains as $domain) {
            $apiUrl = rtrim($domain->domain_url, '/') . '/api/update-ads';
            try {
                $response = Http::post($apiUrl, $payload);

                if (!$response->successful()) {
                    $allSuccessful = false;
                    $errorDomains[] = $domain->domain_url;
                }
            } catch (\Exception $e) {
                $allSuccessful = false;
                $errorDomains[] = $domain->domain_url;
            }
        }

        $banner->status = $allSuccessful ? 'active' : 'failed';
        $banner->save();

        if (count($errorDomains) > 0) {
            admin_toastr('Có lỗi xảy ra trên các domain sau: ' . implode(', ', $errorDomains), 'error');
        } else {
            admin_toastr('Tất cả domain đều hoạt động thành công!', 'success');
        }
        return redirect()->back();
    }

    public function stop($id)
    {
        $banner = Banner::find($id);

        // Kiểm tra Banner tồn tại
        if (!$banner) {
            admin_toastr('Không tìm thấy Banner !', 'error');
            return redirect()->back();
        }
        // Chuẩn bị payload với thẻ img
        $allPositions = ['ads_header', 'ads_catfish', 'ads_popup'];
        $payload = [];

        foreach ($allPositions as $position) {
            $imageLink = $banner->img_url[$position] ?? '';
            if ($imageLink) {
                $payload[$position] = "<img src=\"$imageLink\" alt=\"" . ucfirst(str_replace('_', ' ', $position)) . "\">";
            } else {
                $payload[$position] = null; // Nếu không có link ảnh, gửi giá trị null
            }
        }

        $domains = $banner->domains;
        $errorDomains = [];
        $allSuccessful = true;

        // Gửi payload tới từng domain
        foreach ($domains as $domain) {
            $apiUrl = rtrim($domain->domain_url, '/') . '/api/delete-ads';
            try {
                $response = Http::post($apiUrl, $payload);

                if (!$response->successful()) {
                    $allSuccessful = false;
                    $errorDomains[] = $domain->domain_url;
                }
            } catch (\Exception $e) {
                $allSuccessful = false;
                $errorDomains[] = $domain->domain_url;
            }
        }

        // Cập nhật trạng thái
        $banner->status = $allSuccessful ? 'stop' : 'failed';
        $banner->save();

        // Hiển thị thông báo
        if (count($errorDomains) > 0) {
            admin_toastr('Có lỗi xảy ra trên các domain sau: ' . implode(', ', $errorDomains), 'error');
        } else {
            admin_toastr('Banner đã được dừng thành công!', 'success');
        }
        return redirect()->back();
    }
}
