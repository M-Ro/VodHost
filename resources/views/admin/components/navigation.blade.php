@php
    $sections = [
        "/" => "Dashboard",
        "/users" => "Users",
        "/content" => "Content",
        "/storage" => "Storage",
    ];
@endphp

<div class="bs-component">
    <ul class="list-group">
        @foreach($sections as $url => $name)
            <a href="/administration{{$url}}" class="list-group-item list-group-item-action @if($active==$url) active @endif">{{ $name }}</a>
        @endforeach
    </ul>
</div>
