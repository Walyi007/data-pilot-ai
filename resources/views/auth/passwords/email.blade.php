@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Reset Password') }}</h2>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">
                    {{ __('E-Mail Address') }}
                </label>
                <input type="email"
                       name="email"
                       id="email"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       value="{{ old('email') }}"
                       required autofocus>
                @error('email')
                    <span class="text-red-500 text-xs italic">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    {{ __('Send Password Reset Link') }}
                </button>

                <a class="text-sm text-blue-500 hover:underline" href="{{ route('login') }}">
                    {{ __('Already have an account?') }} {{ __('Login') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection