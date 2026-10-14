@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>{{ __('Reset Password') }}</h2>
    </div>
    <div class="card-body">
        @if(session('status'))
            <div class="alert alert-success mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="input-group mb-4">
                <label for="email">{{ __('E-Mail Address') }}</label>
                <input type="email" name="email" id="email" class="input" value="{{ old('email') }}" required>
                @error('email')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="input-group mb-4">
                <label for="password">{{ __('Password') }}</label>
                <input type="password" name="password" id="password" class="input" required>
                @error('password')
                    <p class="text-sm text-danger-500 mt-1>{{ $message }}</p>
                @enderror
            </div>

            <div class="input-group mb-4">
                <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="input" required>
                @error('password_confirmation')
                    <p class="text-sm text-danger-500 mt-1>{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">{{ __('Reset Password') }}</button>
                <a href="{{ route('login') }}" class="btn btn-outline">{{ __('Remember your password?') }} {{ __('Login') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection