@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-3 col-md-3 col-sm-3">
            @include('admin.components.navigation', ['active' => '/users'])
        </div>

        <div class="col-lg-9 col-md-9 col-sm-9">
            <div class="bs-component">
                <div class="card border-secondary">
                    <div class="card-header">Users</div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">id</th>
                                    <th scope="col">Username</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Uploads</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{$user->id}}</td>
                                        <td>{{$user->name}}</td>
                                        <td>{{$user->email}}</td>
                                        <td>{{$broadcastCounts[$user->id]}}</td>
                                        <td>
                                            <a href="{{Request::url()}}/edit/{{$user->id}}"><span class="oi oi-cog"></span></span></a> &nbsp
                                            <a href="{{Request::url()}}/delete/{{$user->id}}"><span class="oi oi-trash"></span></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
