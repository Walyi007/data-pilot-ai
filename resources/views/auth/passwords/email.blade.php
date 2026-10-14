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

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="input-group mb-4">
                <label for="email">{{ __('E-Mail Address') }}</label>
                <input type="email" name="email" id="email" class="input" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">{{ __('Send Password Reset Link') }}</button>
                <a href="{{ route('login') }}" class="btn btn-outline">{{ __('Already have an account?') }} {{ __('Login') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection