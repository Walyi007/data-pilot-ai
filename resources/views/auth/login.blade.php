@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>{{ __('Login') }}</h2>
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

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="input-group mb-4">
                <label for="email">{{ __('E-Mail Address') }}</label>
                <input type="email" name="email" id="email" class="input" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="input-group mb-4">
                <label for="password">{{ __('Password') }}</label>
                <input type="password" name="password" id="password" class="input" required>
                @error('password')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="form-checkbox">
                    <label for="remember" class="ml-2 text-sm text-gray-500">{{ __('Remember Me') }}</label>
                </div>

                <a href="{{ route('password.request') }}" class="text-sm text-blue-500 hover:underline">{{ __('Forgot Your Password?') }}</a>
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">{{ __('Login') }}</button>
                <a href="{{ route('register') }}" class="btn btn-outline">{{ __('Register') }} - {{ __('Need an account?') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection