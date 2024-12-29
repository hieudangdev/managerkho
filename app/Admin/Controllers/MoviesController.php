<?php

namespace App\Admin\Controllers;

use App\Models\Movie;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Admin;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Input;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Media\Video as FFMpegVideo;

class MoviesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Movies';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Movie());
        $grid->column('id', 'ID');
        $grid->column('title', 'Title');
        $grid->column('slug', __('Slug'));
        $grid->column('thump', __('Thumbnail'))->image('', 100, 100);
        $grid->column('quality', 'Quality');
        $grid->column('episodes_count', 'Tập phim')->display(function () {
            $episodes = $this->episodes()->count(); // Đếm số lượng tập phim liên quan
            if ($episodes > 0) {
                return $episodes . ' Tập';
            } else {
                return 'Chưa có tập nào';
            }
        });
        // Thêm button "Upload"
        $grid->column('categories', 'Thể Loại')->display(function () {
            return $this->categories->pluck('name')->join(', ');
        });
        $grid->column('hls_link', 'HLS Link')->display(function () {
            // URL cho hai hành động Run và Stop
            $runUrl = route('movie.upload', ['id' => $this->id]);
            // HTML cho hai nút
            $runButton = "<a href=\"{$runUrl}\" class=\"\"><i class=\"fa fa-upload\"></i></a>";

            if (empty($this->hls_link)) {
                return $runButton;
            }
            // Nếu trạng thái không phải 'active', chỉ hiển thị nút Run
            return $this->hls_link;
        })->link();

        $grid->column('country', __('Country'));
        $grid->column('actors', __('Actors'));
        $grid->column('year', __('Year'));
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
        $show = new Show(Movie::findOrFail($id));

        $show->field('title', 'Title');
        $show->field('slug', 'Slug');
        $show->field('tags', 'Tags');
        $show->field('quality', 'Quality');
        $show->episodes('Episodes', function ($episode) {
            $episode->resource('/kho8k/episodes');
            $episode->title('Title');
            $episode->video_url('Video URL')->display(function ($videoUrl) {
                return '<a href="' . url('storage/' . $videoUrl) . '" target="_blank">Xem Video</a>';
            });
        });
        $show->field('hls_link', 'HLS Link');
        $show->field('thump', __('Thumbnail'))->image();
        $show->field('poster', __('Poster'))->image();
        $show->field('country', __('Country'));
        $show->field('actors', __('Actors'));
        $show->field('year', __('Year'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        return $show;
    }

    protected function form()
    {
        $form = new Form(new Movie());

        $form->tab('Thông tin Movie', function ($form) {
            $form->text('title', 'Title')->required();
            $form->text('slug', 'Slug');
            $form->tagsinput('tags', 'Tags');
            $form->multipleSelect('categories', __('Thể loại'))->options(
                \App\Models\Category::all()->pluck('name', 'id')
            );
            $form->select('quality', 'Quality')->options([
                '360p' => '360p',
                '480p' => '480p',
                '720p' => '720p',
                '1080p' => '1080p',
                '2k' => '2k',
                '4k' => '4k',
            ]);
            $form->file('thump', 'Thumbnail');
            $form->image('poster', 'Poster');
            $form->text('country', 'Quốc gia');
            $form->text('actors', 'Diễn viên');
            $form->number('year', 'Năm');
            $form->largefile('video_path', 'Video Url');
            $form->text('hls_link', 'HLS Link');
            $form->text('video_path', 'Video URL')->help('Chọn file mp4 hoặc Dán link m3u8 vào đây !!!');
            $form->html('<button  type="button" class="btn btn-primary"  id="open-file" >Select Video</button>');

            // Nút Tạo HLS
            $form->html('<button type="button" class="btn btn-success" id="generate-hls">Tạo HLS</button>
                <span id="loading-spinner" style="display:none; margin-left:10px;">
                loadding
                </span>');
        });

        $form->tab('Tập phim', function ($form) {
            $form->hasMany('episodes', 'Episodes', function (Form\NestedForm $form) {
                $form->text('title', 'Title')->required();
                $form->number('episode_number', 'Episode Number')->required();
                $form->file('video_url', 'Video')
                    ->rules('nullable|file|mimes:mp4,avi,mov') // Cho phép nullable nếu không tải video mới
                    ->help('If no video is selected, the previous video will be kept.');
            });
        });


        Admin::script($this->ajaxScript());
        Admin::script($this->generateTagsScript());

        // Tạo slug từ title trước khi lưu
        $form->saving(function (Form $form) {

            // Gán giá trị mặc định cho tags từ title nếu rỗng
            if (empty($form->tags)) {
                $form->tags = \Str::slug($form->title, ',');
            }

            if (empty($form->slug)) {
                $form->slug = \Str::slug($form->title);
                $form->video_path = \Storage::url('uploads/' . str_replace('_', '/', $form->video_path));
            }
        });

        return $form;
    }
    protected function generateTagsScript()
    {
        return <<<SCRIPT
        // Hàm chuyển đổi tiếng Việt sang không dấu
        function removeVietnameseTones(str) {
            return str
                .normalize('NFD') // Chuẩn hóa chuỗi Unicode
                .replace(/[\u0300-\u036f]/g, '') // Xóa dấu
                .replace(/đ/g, 'd').replace(/Đ/g, 'D'); // Thay thế chữ đ
        }

        // Lắng nghe sự kiện khi trường Title thay đổi
        $(document).on('input', 'input[name="title"]', function () {
            var title = $(this).val(); // Lấy giá trị Title
            if (title) {
                // Chuyển Title sang dạng slug mà vẫn giữ nguyên toàn bộ giá trị
                var slug = removeVietnameseTones(title) // Bỏ dấu tiếng Việt
                    .toLowerCase()                    // Chuyển thành chữ thường
                    .trim()                           // Xóa khoảng trắng thừa
                    .replace(/[^a-z0-9\s-]/g, '')     // Xóa ký tự đặc biệt
                    .replace(/\s+/g, '-');            // Thay khoảng trắng bằng gạch ngang
                $('input[name="tags"]').tagsinput('removeAll'); // Xóa hết tags cũ
                $('input[name="tags"]').tagsinput('add', slug); // Thêm tags mới dạng slug
            }
        });
        SCRIPT;
    }

    protected function ajaxScript()
    {
        return <<<SCRIPT
            // Mở File Manager
            document.getElementById('open-file').addEventListener('click', (event) => {
                event.preventDefault();

                window.open('/file-manager/fm-button', 'fm', 'width=1400,height=800');
            });

            // Nhận đường dẫn từ File Manager
            window.fmSetLink = function(fileUrl) {
                document.querySelector('input[name="video_path"]').value = fileUrl;
            }
                // Xử lý khi nhấn nút Tạo HLS
            document.getElementById('generate-hls').addEventListener('click', function () {
                let videoPath = document.querySelector('input[name="video_path"]').value;
                if (!videoPath) {
                    alert('Vui lòng chọn video trước khi tạo HLS.');
                    return;
                }
                 // Hiển thị spinner
                document.getElementById('loading-spinner').style.display = 'inline-block';
                // Gửi AJAX
                $.ajax({
                    url: '/api/movies/create-hls',
                    type: 'POST',
                    data: {
                        _token: LA.token, // Token CSRF
                        video: videoPath
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            // Điền link HLS vào input
                            document.querySelector('input[name="hls_link"]').value = response.hls_link;

                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    },
                    error: function () {
                        alert('Đã xảy ra lỗi khi gửi yêu cầu.');
                    },
                     complete: function () {
                    // Ẩn spinner khi kết thúc
                    document.getElementById('loading-spinner').style.display = 'none';
                }
                });
            });
    SCRIPT;
    }
    public function createHlsFromAjax()
    {
        $videoPath = request('video'); // Lấy đường dẫn từ input video
        // Nếu là file .m3u8, xử lý bằng hàm copyM3U8ToLocal
        if (str_ends_with($videoPath, '.m3u8')) {
            $hlsLink = $this->copyM3U8ToLocal($videoPath);

            if ($hlsLink) {
                return response()->json([
                    'status' => 'success',
                    'hls_link' => $hlsLink,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể tải file .m3u8 từ nguồn.'
                ]);
            }
        }

        // Chuyển đổi URL thành đường dẫn tuyệt đối trên hệ thống
        // $videoPath = public_path(str_replace('/storage', 'storage', $videoPath));
        $videoPath = str_replace(url('/storage'), storage_path('app/public'), $videoPath);
        // Kiểm tra đường dẫn hợp lệ và video có tồn tại không
        if (!$videoPath || !file_exists($videoPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Đường dẫn video không hợp lệ hoặc video không tồn tại.'
            ]);
        }

        // Tạo HLS từ video MP4
        $hlsLink = $this->createHls($videoPath);

        if ($hlsLink) {
            return response()->json([
                'status' => 'success',
                'hls_link' => $hlsLink
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể tạo HLS từ video này.'
            ]);
        }
    }

    public function createHls($videoPath)
    {
        // Lấy tên file mà không có phần mở rộng
        $hlsDir = public_path('storage/videos/hls/' . pathinfo($videoPath, PATHINFO_FILENAME));
        $hlsFile = "{$hlsDir}/index.m3u8";

        // // Kiểm tra xem thư mục HLS đã tồn tại chưa

        if (is_dir($hlsDir) && file_exists($hlsDir . '/index.m3u8')) {
            // Nếu đã có file HLS, trả về link HLS hiện tại
            return asset('storage/videos/hls/' . pathinfo($videoPath, PATHINFO_FILENAME) . '/index.m3u8');
        }

        // Tạo thư mục HLS nếu chưa tồn tại
        if (!is_dir($hlsDir)) {
            mkdir($hlsDir, 0755, true);
        }
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open($videoPath);

        $format = new X264();
        $format->setAdditionalParameters([
            '-c:v',
            'copy', // Copy video codec gốc
            '-c:a',
            'copy', // Copy audio codec gốc
            '-hls_time',
            '10', // Đặt thời gian mỗi segment là 10 giây
            '-hls_playlist_type',
            'vod' // Đặt loại playlist là video on demand
        ]);
        $video->save($format, $hlsFile);
        return asset('storage/videos/hls/' . pathinfo($videoPath, PATHINFO_FILENAME) . '/index.m3u8');
        // Kiểm tra nếu lệnh thành công

    }

    public function copyM3U8ToLocal($m3u8Url)
    {
        // Đường dẫn thư mục lưu HLS
        $hlsDir = public_path('storage/videos/hls/' . md5($m3u8Url));
        $hlsFile = "{$hlsDir}/index.m3u8";

        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($hlsDir)) {
            mkdir($hlsDir, 0755, true);
        }

        // Tải nội dung file .m3u8 nguồn
        $playlistContent = @file_get_contents($m3u8Url);

        if (!$playlistContent) {
            return false; // Không thể tải file .m3u8
        }

        // Phân tích nội dung .m3u8 và tải phân đoạn
        $lines = explode("\n", $playlistContent);
        $newPlaylist = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Giữ nguyên các dòng metadata
            if (empty($line) || str_starts_with($line, '#')) {
                $newPlaylist[] = $line;
                continue;
            }

            // Tải file .ts
            $segmentUrl = $this->resolveUrl($m3u8Url, $line);
            $segmentContent = @file_get_contents($segmentUrl);

            if (!$segmentContent) {
                return false; // Lỗi khi tải phân đoạn
            }

            // Lưu file .ts vào thư mục
            $segmentName = basename($line);
            $segmentPath = "{$hlsDir}/{$segmentName}";
            file_put_contents($segmentPath, $segmentContent);

            // Thêm phân đoạn vào danh sách mới
            $newPlaylist[] = $segmentName;
        }

        // Tạo file .m3u8 mới
        file_put_contents($hlsFile, implode("\n", $newPlaylist));

        // Trả về link HLS mới
        return asset('storage/videos/hls/' . md5($m3u8Url) . '/index.m3u8');
    }

    // Hàm resolve URL đầy đủ từ đường dẫn tương đối
    private function resolveUrl($baseUrl, $relativePath)
    {
        $baseParts = parse_url($baseUrl);
        if (str_starts_with($relativePath, 'http')) {
            return $relativePath;
        }
        $baseDir = dirname($baseParts['scheme'] . '://' . $baseParts['host'] . $baseParts['path']);
        return rtrim($baseDir, '/') . '/' . ltrim($relativePath, '/');
    }
}
