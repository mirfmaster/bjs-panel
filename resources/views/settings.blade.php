@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        BJS Settings
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Configure BJS credentials and integration
                    </p>
                </div>
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Back to Dashboard
                </a>
            </div>

            @if (session('status'))
                <div class="mx-4 mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings') }}" class="border-t border-gray-200">
                @csrf
                @method('PUT')
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="login_toggle" name="login_toggle" type="checkbox"
                                    {{ $settings['login_toggle'] ? 'checked' : '' }}
                                    class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="login_toggle" class="font-medium text-gray-700">
                                    Enable BJS Integration
                                </label>
                                <p class="text-gray-500">
                                    When enabled, the system will attempt to authenticate with BJS using the credentials below.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">
                                BJS Username
                            </label>
                            <div class="mt-1">
                                <input type="text" name="username" id="username"
                                    value="{{ old('username', $settings['username']) }}"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            @error('username')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                BJS Password
                            </label>
                            <div class="mt-1">
                                <input type="text" name="password" id="password"
                                    value="{{ old('password', $settings['password']) }}"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                Enter the BJS account password.
                            </p>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
