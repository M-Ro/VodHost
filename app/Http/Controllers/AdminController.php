<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function users(Request $request)
    {
        /* Get offset */
        $offset = 0;

        $data = $request->all();
        if (array_key_exists('offset', $data)) {
            $offset = $data['offset'];
            if($offset < 0) {
                $offset = 0;
            }
        }

        $users = $this->getUsers($offset);
        $broadcastCounts = $this->broadcastCountForUsers($users);

        return view('admin.users', [
            'users' => $users,
            'broadcastCounts' => $broadcastCounts
        ]);
    }

    /**
     * Fetch collection of users by offset
     * @param $offset
     * @return mixed
     */
    private function getUsers($offset)
    {
        return User::orderBy('id')->offset($offset)->limit(50)->get();
    }

    /**
     * Fetch uploaded video content for each user in collection
     * @param $users
     */
    private function broadcastCountForUsers($users)
    {
        $user_ids = [];
        foreach ($users as $user) {
            $user_ids[] = $user->id;
        }

        // FIXME implement properly later, for now just stubs 0
        $broadcastCounts = [];
        foreach ($users as $user) {
            $broadcastCounts[$user->id] = 0;
        }

        return $broadcastCounts;
    }

    public function content()
    {
        return view('admin.content');
    }

    public function storage(Request $request)
    {
        $path = '/';
        $data = $request->all();
        if (array_key_exists('path', $data)) {
            $path = $data['path'];
        }

        $dirs = Storage::disk('s3')->directories($path);
        $files = Storage::disk('s3')->files($path);

        return view('admin.storage', [
            'dirs' => $dirs,
            'files' => $files
        ]);
    }
}
