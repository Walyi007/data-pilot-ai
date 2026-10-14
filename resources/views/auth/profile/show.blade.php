@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>{{ __('My Profile') }}</h2>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
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

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf

            <div class="input-group mb-4">
                <label for="name">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" class="input" value="{{ old('name', $user->name) }}" required autofocus>
                @error('name')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="input-group mb-4">
                <label for="email">{{ __('E-Mail Address') }}</label>
                <input type="email" name="email" id="email" class="input" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="input-group mb-4">
                <label for="password">{{ __('New Password') }}</label>
                <input type="password" name="password" id="password" class="input" placeholder="{{ __('Leave blank if you do not wish to change your password') }}">
                @error('password')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="input-group mb-4">
                <label for="password_confirmation">{{ __('Confirm New Password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="input" placeholder="{{ __('Leave blank if you do not wish to change your password') }}">
                @error('password_confirmation')
                    <p class="text-sm text-danger-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">{{ __('Update Profile') }}</button>
                <a href="{{ route('dataset.import') }}" class="btn btn-outline">{{ __('Back to Import') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection