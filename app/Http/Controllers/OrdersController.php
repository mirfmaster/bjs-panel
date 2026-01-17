<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\BJS;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrdersController extends Controller
{
    public function index(Request $request): View
    {
        $bjs = app(BJS::class);
        $services = $bjs->getServices();

        $query = Order::query();

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->input('service_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', OrderStatus::PENDING);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('user', 'like', "%{$search}%")
                    ->orWhere('link', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('order_created_at', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('orders.index', compact('orders', 'services'));
    }

    public function stats(): JsonResponse
    {
        $stats = [];
        foreach (OrderStatus::cases() as $status) {
            $stats[$status->label()] = Order::where('status', $status)->count();
        }

        return response()->json($stats);
    }

    public function show(Order $order): View
    {
        $bjs = app(BJS::class);
        $services = $bjs->getServices();

        return view('orders.show', compact('order', 'services'));
    }

    public function startOrder(Request $request, Order $order, BJS $bjs): RedirectResponse
    {
        $validated = $request->validate([
            'start_count' => 'required|integer|min:0',
        ]);

        try {
            $bjs->setStartCount($order->id, (int) $validated['start_count']);

            $order->start_count = (int) $validated['start_count'];
            $order->status = OrderStatus::INPROGRESS;
            $order->processed_by = auth()->id();
            $order->processed_at = now();
            $order->save();

            return redirect()->back()->with('success', 'Order started successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to start order: ' . $e->getMessage());
        }
    }

    public function complete(Order $order): RedirectResponse
    {
        try {
            $order->status = OrderStatus::COMPLETED;
            $order->remains = 0;
            $order->save();

            return redirect()->back()->with('success', 'Order completed successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to complete order: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, Order $order, BJS $bjs): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $bjs->cancelOrder($order->id);

            $order->status = OrderStatus::CANCELED;
            $order->order_cancel_reason = $validated['reason'] ?? null;
            $order->save();

            return redirect()->back()->with('success', 'Order cancelled successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    public function partial(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'remains' => 'required|integer|min:0',
        ]);

        try {
            $order->status = OrderStatus::PARTIAL;
            $order->remains = (int) $validated['remains'];
            $order->save();

            return redirect()->back()->with('success', 'Order marked as partial successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to set partial: ' . $e->getMessage());
        }
    }

    public function setRemains(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'remains' => 'required|integer|min:0',
        ]);

        $order->remains = (int) $validated['remains'];
        $order->save();

        return redirect()->back()->with('success', 'Remains updated successfully');
    }
}
