<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Http;
use Log;

class PostController extends Controller
{
    public function __construct(protected Post $post)
    {
        
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info('Opentelemetry Test', ['id' => 1]);

        $response = Http::get('http://192.168.0.18:8001/api/v1/posts');

        Log::info('HTTP REQUEST SUCCESS');

        return $this->post->with('user')->paginate($request->input('perPage'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required'],
            'description' => ['sometimes'],
            'content' => ['required'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        return $this->post->create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->post->with('user')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => ['required'],
            'description' => ['sometimes'],
            'content' => ['required'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $this->post->findOrFail($id)->update($validated);


        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->post->delete($id);

        return response()->noContent();
    }
}
