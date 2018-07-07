@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-3 col-md-3 col-sm-3">
            @include('admin.components.navigation', ['active' => '/content'])
        </div>

        <div class="col-lg-9 col-md-9 col-sm-9">
            <div class="bs-component">
                <div class="card border-secondary">
                    <div class="card-header">Content</div>
                    <div class="card-body">
                        Hi
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
