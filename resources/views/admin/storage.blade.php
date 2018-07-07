@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-3 col-md-3 col-sm-3">
            @include('admin.components.navigation', ['active' => '/storage'])
        </div>

        <div class="col-lg-9 col-md-9 col-sm-9">
            <div class="bs-component">
                <div class="card border-secondary">
                    <div class="card-header">Storage</div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th scope="col">Filepath</th>
                                <th scope="col">Size</th>
                                <th scope="col">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($dirs as $dir)
                                <tr>
                                    <td><a href="{{Request::url()}}?path={{$dir}}"><span class="oi oi-folder"> &nbsp {{$dir}}</span></a></td>
                                    <td></td>
                                    <td><a href="{{Request::url()}}/delete/{{$dir}}"><span class="oi oi-trash"></span></a></td>
                                </tr>
                            @endforeach

                            @foreach($files as $file)
                                <tr>
                                    <td><span class="oi oi-file"> &nbsp {{$file}}</span></td>
                                    <td></td>
                                    <td><a href="{{Request::url()}}/delete/{{$file}}"><span class="oi oi-trash"></span></a></td>
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
