<?php

namespace App\Admin\Controllers;

use App\Models\Tvc;
use App\Models\Domain;
use Encore\Admin\Actions\Toastr;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Admin;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Encore\Admin\Widgets\Table;

class TvcController extends AdminController
{

    protected $title = 'Quản lý TVCs';

    // Hiển thị danh sách TVC
    protected function grid(){
        $grid = new Grid(new Tvc());
        $grid->quickSearch(function ($model, $query) {
            $model->where('title', 'like', "%{$input}%")
                ->orWhere('desc', 'like', "%{$input}%")
                ->orWhere('content', 'like', "%{$input}%");
        });
        $grid->filter(function ($filter) {
            // Ẩn filter mặc định theo ID
            $filter->disableIdFilter();

            // Filter theo Video URL
            $filter->like('video_url', 'Video URL');

            // Filter theo Title
            $filter->like('name', 'Name');

            $filter->where(function ($query) {
                $query->whereHas('domains', function ($q) {
                    $q->where('domain_url', 'like', "%{$this->input}%");
                });
            }, 'Domain URL');
            $filter->between('created_at', 'Created Time')->datetime();
        });
        $grid->enableHotKeys();

        $grid->id('ID');
      

        $grid->name('Name')->filter('like');
        $grid->enableHotKeys();

        $grid->redirect_url('Redirect URL')->display(function () {
            $redirect_url = $this->redirect_url; // Lấy giá trị từ cột video_url
            return "<a href=\"{$redirect_url}\" target=\"_blank\" >{$redirect_url}</a>";
        })->filter('like');
        $grid->video_url('Video URL')->display(function () {
            $video_url = $this->video_url; // Lấy giá trị từ cột video_url
            return "<a href=\"{$video_url}\" target=\"_blank\" >{$video_url}</a>";
        });
        $grid->bookmaker()->name(__('Nhà Cái'))->display(function ($name) {
            return $name ?? '-';
        });
        // $grid->time_skip('Time Skip')->display(function () {
        //     $timeskip = $this->time_skip;
        //     return "{$timeskip} Giây";
        // });
        $grid->column('status')->display(function ($status) {

            if ($status == 'active') {
                return '<span style="color: green;">Active</span>';
            } elseif ($status == 'inactive') {
                return '<span style="color: gray;">Inactive</span>';  // Thêm màu xám cho trạng thái inactive
            } elseif ($status == 'wait') {
                return '<span style="color: orange;">Waiting</span>';
            } elseif ($status == 'failed') {
                return '<span style="color: red;">Failed</span>';
            }
            return '<span>Unknown</span>';
        });
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
        

        $grid->column('actions')->display(function () {
            // URL cho hai hành động Run và Stop
            $runUrl = route('tvcs.run', ['id' => $this->id]);
            $stopUrl = route('tvcs.stop', ['id' => $this->id]);

            // HTML cho hai nút
            $runButton = "<a href=\"{$runUrl}\" class=\"btn btn-success\">Run TVC</a>";
            $stopButton = "<a href=\"{$stopUrl}\" class=\"btn btn-danger\">Stop TVC</a>";

            // Nếu trạng thái là 'active', chỉ hiển thị nút Stop
            if ($this->status === 'active') {
                return $stopButton;
            }

            // Nếu trạng thái không phải 'active', chỉ hiển thị nút Run
            return $runButton;
        });

        // $grid->start_day('Start Day')->filter('range', 'date');
        $grid->column('duration', 'Thời hạn')->display(function () {

            // Tính toán số ngày còn lại
            $endDay = \Carbon\Carbon::parse($this->end_day);
            $today = \Carbon\Carbon::now();
            $daysLeft = $today->diffInDays($endDay, false); // false để trả về số âm nếu đã hết hạn
            if ($daysLeft < 0) {
                return "<span style='color: red;'>Đã hết hạn</span>";
            }
            return "Còn {$daysLeft} ngày";
        })->filter('range', 'duration')->width(200);

        return $grid;
    }
    // Hiển thị chi tiết TVC
    protected function detail($id)
    {
        $show = new Show(Tvc::findOrFail($id));

        $show->field('name', 'Name');
        $show->field('description', 'Description');
        $show->field('redirect_url', 'Redirect URL');
        $show->field('video_url', 'Video URL');
        $show->bookmaker()->name(__('Nhà Cái'))->as(function ($bookmaker) {
            return $bookmaker['name'] ?? '-'; // Hiển thị tên của Bookmaker hoặc dấu '-' nếu không có tên
        });
        $show->field('time_skip', 'Time Skip');
        $show->field('status', 'Status');
        $show->field('start_day', 'Start Day');
        $show->field('end_day', 'End Day');
        $show->field('duration', 'Duration (days)');
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        // Liên kết TVC với các domains
        $show->domains('Domains', function ($domain) {
            $domain->resource('/admin/domains');
            $domain->id('ID');
            $domain->domain_url('Name');
        });

        return $show;
    }

    // Hiển thị form để tạo mới hoặc chỉnh sửa TVC
    protected function form(){
        $form = new Form(new Tvc());

        $form->text('name', 'Name')->required();
        $form->textarea('description', 'Description');
        $form->url('redirect_url', 'Redirect URL')->required();

        // Trường video_url sử dụng File Manager tùy chỉnh
        $form->text('video_url', 'Video URL')->help('Click the button below to select a video');
        $form->html('<button  type="button" class="btn btn-primary"  id="open-file" >Select Video</button>');
        $form->select('bookmaker_id', __('Bookmaker'))->options(
            \App\Models\Bookmaker::all()->pluck('name', 'id')
        )->help(
            '<button type="button" id="create-bookmaker" class="btn btn-sm btn-success">Tạo mới Bookmaker</button>'
        );
        $form->number('time_skip', 'Time Skip')->min(0)->required()->default(5);
        $form->datetime('start_day', 'Start Day')->required()->default(now());
        $form->number('duration', 'Duration (in days)')->min(1)->required()->default(30);
        $form->hidden('end_day');
        $form->hidden('status')->default('wait');
        $form->checkbox('domains', __('Domains'))->options(Domain::all()->pluck('domain_url', 'id'))
            ->rules('required');

        // Thêm JavaScript vào form
        Admin::script("
                    document.getElementById('open-file').addEventListener('click', (event) => {
                        event.preventDefault();
                        window.open('/file-manager/fm-button', 'fm', 'width=1400,height=800');
                    });

                    window.fmSetLink = function(fileUrl) {
                        document.querySelector('input[name=\"video_url\"]').value = fileUrl;
                        };
                    ");
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
            if ($form->start_day && $form->duration) {
                $form->end_day = \Carbon\Carbon::parse($form->start_day)
                    ->addDays($form->duration)
                    ->toDateTimeString();
            }
        });
        // Xử lý đường dẫn video trong sự kiện saved
        $form->saved(function (Form $form) {
            $tvc = $form->model(); // Lấy model của TVC sau khi lưu
            if ($tvc->video_url) {
                $tvc->video_url = url(str_replace('public/', '', $tvc->video_url));
                $tvc->save(); // Cập nhật lại đường dẫn
            }
        });

        return $form;
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

    // Hàm tạo file VAST
    // Phương thức để chạy TVC
    public function run($id){
        $tvc = Tvc::findOrFail($id);

        // Lấy danh sách các domain liên quan
        $domains = $tvc->domains;

        if ($domains->isEmpty()) {
            admin_toastr('No domains associated with this TVC.', 'error');
            return redirect()->back();
        }

        $redirectUrl = $tvc->redirect_url;
        $videoUrl = $tvc->video_url;
        $timeSkip = $tvc->time_skip;

        // Chuẩn bị content file VAST
        $vastContent = view('vast.template', compact('redirectUrl', 'videoUrl'))->render();

        $errorDomains = []; // Mảng lưu danh sách các domain bị lỗi

        foreach ($domains as $domain) {
            $apiUrl = rtrim($domain->domain_url, '/') . '/api/upload-vast';
            $payload = [
                'vast_content' => $vastContent,
                'file_name' => $tvc->name . '.xml',
                'time_skip' => $timeSkip,
            ];

            try {
                $response = Http::post($apiUrl, $payload);

                if ($response->failed()) {
                    $errorDomains[] = $domain->domain_url; // Thêm domain vào mảng lỗi
                    \Log::error("Failed to send VAST to $apiUrl: " . $response->body());
                    $tvc->update(['status' => 'inactive']);
                } else {
                    \Log::info("VAST sent successfully to $apiUrl.");
                }
            } catch (\Exception $e) {
                $errorDomains[] = $domain->domain_url; // Thêm domain vào mảng lỗi
                \Log::error("Error sending VAST to $apiUrl: " . $e->getMessage());
                $tvc->update(['status' => 'inactive']);
            }
        }

        // Nếu có lỗi, chuyển mảng domain bị lỗi thành chuỗi và hiển thị thông báo
        if (!empty($errorDomains)) {
            $errorString = implode(', ', $errorDomains);
            \Log::error("list domain tvc error $errorString"); // Ghép các domain bị lỗi thành chuỗi
            admin_toastr("Failed to send VAST to the following domains: {$errorString}", 'error');
        } else {
            admin_toastr('VAST sent successfully to all domains.', 'success');
            $tvc->update(['status' => 'active']);
        }

        return redirect()->back();
    }


    public function stop($id){
        // Tìm chiến dịch TVC theo ID
        $tvc = Tvc::findOrFail($id);

        // Kiểm tra trạng thái, nếu đã inactive thì không cần dừng lại
        if ($tvc->status === 'inactive') {
            admin_toastr('TVC is already inactive.', 'info');
            return redirect()->back();
        }

        // Lấy danh sách các domain liên quan
        $domains = $tvc->domains;

        if ($domains->isEmpty()) {
            admin_toastr('No domains associated with this TVC.', 'error');
            return redirect()->back();
        }

        // Dữ liệu rỗng để gửi đến các web con
        $emptyPayload = [
            'vast_content' => '',
            'file_name' => '',
            'time_skip' => null,
        ];

        foreach ($domains as $domain) {
            $apiUrl = rtrim($domain->domain_url, '/') . '/api/upload-vast';

            try {
                $response = Http::post($apiUrl, $emptyPayload);

                if ($response->failed()) {
                    \Log::error("Failed to reset data on $apiUrl: " . $response->body());
                } else {
                    \Log::info("Data reset successfully on $apiUrl.");
                }
            } catch (\Exception $e) {
                \Log::error("Error resetting data on $apiUrl: " . $e->getMessage());
            }
        }

        // Cập nhật trạng thái chiến dịch TVC thành inactive
        $tvc->update(['status' => 'inactive']);
        admin_toastr('TVC has been successfully stopped.', 'success');

        return redirect()->back();
    }

}
