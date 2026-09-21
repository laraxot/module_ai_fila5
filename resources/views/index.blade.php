<?php

declare(strict_types=1);
?>
@extends('ai::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {!! config('ai.name') !!}
    </p>
@endsection
