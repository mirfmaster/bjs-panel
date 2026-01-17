@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
        </div>

        <div id="stats-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach(\App\Enums\OrderStatus::cases() as $status)
            <a href="{{ route('orders.index') }}?status={{ $status->value }}" class="bg-white rounded-lg shadow p-4 border-2 {{ match($status->label()) {
                'pending' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                'inprogress' => 'bg-blue-50 border-blue-200 text-blue-700',
                'completed' => 'bg-green-50 border-green-200 text-green-700',
                'partial' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                'canceled' => 'bg-red-50 border-red-200 text-red-700',
                'processing' => 'bg-purple-50 border-purple-200 text-purple-700',
                'fail' => 'bg-red-50 border-red-200 text-red-700',
                'error' => 'bg-red-50 border-red-200 text-red-700',
            } }} hover:shadow-md transition-shadow cursor-pointer block">
                <div class="text-sm font-medium opacity-75">{{ ucfirst($status->label()) }}</div>
                <div class="text-2xl font-bold mt-1" id="count-{{ $status->value }}">Loading...</div>
            </a>
            @endforeach
        </div>

        <div class="bg-white shadow rounded-lg mb-6">
            <form method="GET" action="{{ route('orders.index') }}" class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                        <select name="service_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Services</option>
                            @foreach($services ?? [] as $service)
                            <option value="{{ $service }}" {{ request('service_id') == $service ? 'selected' : '' }}>Service {{ $service }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Status</option>
                            @foreach(\App\Enums\OrderStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ ucfirst($status->label()) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="User or Link..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Filter
                    </button>
                    <a href="{{ route('orders.index') }}" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white shadow overflow-hidden rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remains</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($orders as $order)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $order->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">@{{ $order->user }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($order->link)
                                <a href="{{ $order->link }}" target="_blank" rel="nofollow noreferrer" class="text-indigo-600 hover:text-indigo-900">
                                    {{ Str::limit($order->link, 30) }}
                                </a>
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->start_count ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->remains ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->order_created_at ? $order->order_created_at->format('M d, H:i') : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">No orders found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('/orders/stats', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(stats => {
            Object.keys(stats).forEach(status => {
                const statusValue = {
                    'pending': 0,
                    'inprogress': 1,
                    'completed': 2,
                    'partial': 3,
                    'canceled': 4,
                    'processing': 5,
                    'fail': 6,
                    'error': 7
                }[status] || 0;
                const countElement = document.getElementById('count-' + statusValue);
                if (countElement) {
                    countElement.textContent = stats[status];
                }
            });
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
});
</script>
@endpush
