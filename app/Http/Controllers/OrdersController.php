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

    public function cancel(Order $order, BJS $bjs): RedirectResponse
    {
        try {
            $bjs->cancelOrder($order->id);
            return redirect()->back()->with('success', 'Order cancelled successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    public function setStartCount(Request $request, Order $order, BJS $bjs): RedirectResponse
    {
        $validated = $request->validate([
            'start_count' => 'required|integer|min:0',
        ]);

        try {
            $bjs->setStartCount($order->id, (int) $validated['start_count']);
            $order->start_count = (int) $validated['start_count'];
            $order->save();
            return redirect()->back()->with('success', 'Start count updated successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to update start count: ' . $e->getMessage());
        }
    }

    public function setPartial(Request $request, Order $order, BJS $bjs): RedirectResponse
    {
        $validated = $request->validate([
            'remains' => 'required|integer|min:0',
        ]);

        try {
            $bjs->setPartial($order->id, (int) $validated['remains']);
            $order->remains = (int) $validated['remains'];
            $order->status = OrderStatus::PARTIAL;
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
