<?php

namespace App\Admin\Controllers;
use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Encore\Admin\Controllers\AdminController;
class ApiController extends AdminController
{
    // API: Lấy danh sách phim mới nhất
    public function latestMovies(Request $request)
    {
        $limit = $request->get('limit', 20);

        $movies = Movie::orderBy('updated_at', 'desc')
            ->take($limit)
            ->get(['id', 'title', 'slug', 'tags', 'quality', 'thump', 'poster', 'country', 'actors', 'year', 'created_at', 'updated_at'])
            ->map(function ($movie) {
                return [
                    'id' => $movie->id,
                    'title' => $movie->title,
                    'slug' => $movie->slug,
                    'tags' => $movie->tags,
                    'quality' => $movie->quality,
                    'thump' => $movie->thump,
                    'poster' => $movie->poster,
                    'country' => $movie->country,
                    'actors' => $movie->actors,
                    'year' => $movie->year,
                    'created_at' => Carbon::parse($movie->created_at)->format('d-m-Y'),
                    'updated_at' => Carbon::parse($movie->updated_at)->format('d-m-Y'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $movies,
        ]);
    }

    // API: Lấy thông tin chi tiết phim
    public function movieDetail($id)
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json([
                'success' => false,
                'message' => 'Movie not found',
            ], 404);
        }

        $formattedMovie = [
            'id' => $movie->id,
            'title' => $movie->title,
            'slug' => $movie->slug,
            'tags' => $movie->tags,
            'quality' => $movie->quality,
            'thump' => $movie->thump,
            'poster' => $movie->poster,
            'country' => $movie->country,
            'actors' => $movie->actors,
            'year' => $movie->year,
            'created_at' => Carbon::parse($movie->created_at)->format('d-m-Y'),
            'updated_at' => Carbon::parse($movie->updated_at)->format('d-m-Y'),
        ];

        return response()->json([
            'data' => [
                'movie' => $formattedMovie,
                'episodes' => $movie->episodes->map(function ($episode) {
                    return [
                        'id' => $episode->id,
                        'title' => $episode->title,
                        'video_url' => $episode->video_url,
                    ];
                }),
            ],
        ]);
    }

    // API: Tìm kiếm phim theo tên
    public function searchMovies(Request $request)
    {
        $query = $request->get('q', '');

        $movies = Movie::where('title', 'LIKE', "%$query%")
            ->with('episodes')
            ->get(['id', 'title', 'slug', 'tags', 'quality', 'thump', 'poster', 'country', 'actors', 'year', 'created_at', 'updated_at']);

        $movies = $movies->map(function ($movie) {
            return [
                'id' => $movie->id,
                'title' => $movie->title,
                'slug' => $movie->slug,
                'tags' => $movie->tags,
                'quality' => $movie->quality,
                'thump' => $movie->thump,
                'poster' => $movie->poster,
                'country' => $movie->country,
                'actors' => $movie->actors,
                'year' => $movie->year,
                'created_at' => Carbon::parse($movie->created_at)->format('d-m-Y'),
                'updated_at' => Carbon::parse($movie->updated_at)->format('d-m-Y'),
                'episodes' => $movie->episodes->map(function ($episode) {
                    return [
                        'id' => $episode->id,
                        'title' => $episode->title,
                        'video_url' => asset('storage/' . $episode->video_url),
                    ];
                }),
            ];
        });

        if (!$movies) {
            return response()->json([
                'success' => false,
                'message' => 'Movies not found',
            ], 404);
        }

        return response()->json([
            'data' => $movies,
        ]);
    }
}
