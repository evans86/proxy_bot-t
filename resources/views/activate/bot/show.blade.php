@extends('layouts.app', ['page' => __('Бот') . ' #' . $bot->id, 'pageSlug' => 'bots'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Модуль') }} #{{ $bot->id }}</h4>
                    <a href="{{ route('activate.bot.index') }}" class="btn btn-sm btn-primary">{{ __('К списку') }}</a>
                </div>
                <div class="card-body">
                    <p><strong>Public key:</strong> {{ $bot->public_key }}</p>
                    <p><strong>Private key:</strong> {{ \App\Support\SecretMask::mask($bot->private_key) }}</p>
                    <p><strong>Bot-t ID:</strong> {{ $bot->bot_id }}</p>
                    <p><strong>API key:</strong> {{ \App\Support\SecretMask::mask($bot->api_key) }}</p>
                    <p><strong>Link:</strong> {{ $bot->resource_link }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
