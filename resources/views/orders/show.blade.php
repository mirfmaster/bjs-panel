@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('orders.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900 mb-2 inline-block">← Back to Orders</a>
                <h1 class="text-2xl font-bold text-gray-900">Order #{{ $order->id }}</h1>
            </div>
        </div>

        @session('success')
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endsession

        @session('error')
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
        @endsession

        <div class="bg-white shadow overflow-hidden rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Order Details</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">User</dt>
                        <dd class="mt-1 text-sm text-gray-900">@{{ $order->user }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Link</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($order->link)
                            <a href="{{ $order->link }}" target="_blank" rel="nofollow noreferrer" class="text-indigo-600 hover:text-indigo-900">
                                {{ $order->link }}
                            </a>
                            @else
                            -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Service</dt>
                        <dd class="mt-1 text-sm text-gray-900">Service {{ $order->service_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ match($order->status->label()) {
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'inprogress' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'partial' => 'bg-yellow-100 text-yellow-800',
                                    'canceled' => 'bg-red-100 text-red-800',
                                    'processing' => 'bg-purple-100 text-purple-800',
                                    'fail' => 'bg-red-100 text-red-800',
                                    'error' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                } }}">
                                {{ ucfirst($order->status->label()) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->order_created_at ? $order->order_created_at->format('M d, Y H:i:s') : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Start Count</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->start_count ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Count</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->count }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Remains</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->remains ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Charge</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->charge ? '$' . number_format($order->charge, 2) : '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($order->order_cancel_reason)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg mb-6 px-4 py-3">
            <h4 class="text-sm font-medium text-yellow-800 mb-1">Cancel Reason</h4>
            <p class="text-sm text-yellow-700">{{ $order->order_cancel_reason }}</p>
        </div>
        @endif

        @if($order->order_fail_reason)
        <div class="bg-red-50 border border-red-200 rounded-lg mb-6 px-4 py-3">
            <h4 class="text-sm font-medium text-red-800 mb-1">Fail Reason</h4>
            <p class="text-sm text-red-700">{{ $order->order_fail_reason }}</p>
        </div>
        @endif

        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Actions</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6 space-y-4">
                <form method="POST" action="{{ route('orders.cancel', $order) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Cancel Order
                        </button>
                    </div>
                </form>

                <div class="border-t border-gray-200 pt-4">
                    <form method="POST" action="{{ route('orders.set-start-count', $order) }}" class="flex items-end gap-3">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Set Start Count</label>
                            <input type="number" name="start_count" min="0" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <form method="POST" action="{{ route('orders.set-partial', $order) }}" class="flex items-end gap-3">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Set Partial (Remains)</label>
                            <input type="number" name="remains" min="0" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                Mark Partial
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <form method="POST" action="{{ route('orders.set-remains', $order) }}" class="flex items-end gap-3">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Update Remains</label>
                            <input type="number" name="remains" min="0" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
