<?php

use App\Admin\Controllers\MoviesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('movies/create-hls', [MoviesController::class, 'createHlsFromAjax'])->name('movies.create-hls');


Route::post('/chunk-upload', function (Request $request) {
    $receiver = new FileReceiver('file', $request, FileReceiver::factory());

    if ($receiver->isUploaded()) {
        $save = $receiver->receive(); // Nhận từng chunk

        if ($save->isFinished()) {
            $file = $save->getFile(); // File đã hoàn tất
            $path = $file->store('uploads'); // Lưu file trong thư mục 'uploads'

            return response()->json([
                'path' => $path,
                'url' => asset($path)
            ]);
        }

        // Nếu chưa hoàn tất, trả về tiến độ
        return response()->json([], 200);
    }

    return response()->json(['error' => 'Không tìm thấy file'], 400);
});
