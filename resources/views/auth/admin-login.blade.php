@extends('layouts.app', ['class' => 'login-page', 'page' => __('Вход в панель'), 'contentClass' => 'login-page'])

@section('content')
    <div class="col-lg-4 col-md-6 ml-auto mr-auto">
        <form class="form" method="post" action="{{ route('admin.login.store') }}">
            @csrf

            <div class="card card-login card-white">
                <div class="card-header">
                    <img src="{{ asset('black') }}/img/card-primary.png" alt="">
                    <h1 class="card-title">{{ __('Proxy') }}</h1>
                    <p class="description text-center">{{ __('Учётные данные из .env (не из БД)') }}</p>
                </div>
                <div class="card-body">
                    <div class="input-group{{ $errors->has('username') ? ' has-danger' : '' }}">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <i class="tim-icons icon-user-run"></i>
                            </div>
                        </div>
                        <input type="text" name="username" value="{{ old('username') }}" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" placeholder="{{ __('Username') }}" autocomplete="username" required autofocus>
                        @include('alerts.feedback', ['field' => 'username'])
                    </div>
                    <div class="input-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <i class="tim-icons icon-lock-circle"></i>
                            </div>
                        </div>
                        <input type="password" placeholder="{{ __('Password') }}" name="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" autocomplete="current-password" required>
                        @include('alerts.feedback', ['field' => 'password'])
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-lg btn-block mb-3">{{ __('LOGIN') }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection
