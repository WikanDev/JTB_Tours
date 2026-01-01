<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Assignment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\OrdersQueryExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\WorkSchedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    
    public function index(Request $request)
    {
        $user = Auth::user();

        
        if (in_array($user->role, ['admin', 'super_admin', 'staff'])) {
            return $this->adminReport($request);
        }

        
        if (in_array($user->role, ['driver', 'guide'])) {
            return $this->personalReport($request);
        }

        abort(403, 'Laporan tidak tersedia untuk role Anda.');
    }

    
    
    

    private function adminReport(Request $request)
    {
        $year  = $request->query('year', date('Y'));
        $month = $request->query('month', null);

        
        $ordersQuery = Order::select(
                DB::raw("MONTH(pickup_time) as month"),
                DB::raw("COUNT(*) as total")
            )
            ->whereYear('pickup_time', $year)
            ->groupBy(DB::raw("MONTH(pickup_time)"))
            ->orderBy('month')
            ->get();

        $ordersPerMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $ordersQuery->firstWhere('month', $m);
            $ordersPerMonth[$m] = $row ? (int) $row->total : 0;
        }

        
        $assignAccepted = Assignment::select(
                DB::raw("MONTH(assigned_at) as month"),
                DB::raw("COUNT(*) as total")
            )
            ->where('status', 'accepted')
            ->whereYear('assigned_at', $year)
            ->groupBy(DB::raw("MONTH(assigned_at)"))
            ->get();

        $acceptedPerMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $assignAccepted->firstWhere('month', $m);
            $acceptedPerMonth[$m] = $row ? (int) $row->total : 0;
        }

        
        // 1. All products
        $products = Product::select('id', 'name')->get();

        // 2. Aggregate usage by product & month
        $stats = DB::table('assignments')
            ->join('orders', 'assignments.order_id', '=', 'orders.id')
            ->select('orders.product_id', DB::raw('MONTH(assignments.assigned_at) as month'), DB::raw('COUNT(assignments.id) as total'))
            ->whereYear('assignments.assigned_at', $year)
            ->whereIn('assignments.status', ['accepted', 'in_progress', 'completed'])
            ->groupBy('orders.product_id', DB::raw('MONTH(assignments.assigned_at)'))
            ->get();

        // 3. Transform for Chart
        $productUsageData = [];
        foreach ($products as $p) {
            $monthlyData = array_fill(1, 12, 0); // keys 1..12
            foreach ($stats as $s) {
                if ($s->product_id == $p->id) {
                    $monthlyData[$s->month] = (int)$s->total;
                }
            }
            $productUsageData[] = [
                'name' => $p->name,
                'data' => array_values($monthlyData) // 0..11 index
            ];
        }

        
        $assignmentsByStatus = Assignment::select('status', DB::raw('count(*) as total'))
            ->when($year, function ($q) use ($year) {
                $q->whereYear('assigned_at', $year);
            })
            ->when($month, function ($q) use ($month) {
                if ($month) {
                    $q->whereMonth('assigned_at', $month);
                }
            })
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        return view('reports.admin', compact(
            'year',
            'month',
            'ordersPerMonth',
            'acceptedPerMonth',
            'acceptedPerMonth',
            'productUsageData', // Changed from productUsage
            'assignmentsByStatus'
        ));
    }

    
    
    

    private function personalReport(Request $request)
    {
        $user = Auth::user();

        
        $start = $request->filled('start')
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfMonth();

        $end = $request->filled('end')
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfMonth();

        
        $query = Assignment::with(['order.product', 'order'])
            ->where(function ($q) use ($user) {
                if ($user->role === 'driver') {
                    $q->where('driver_id', $user->id);
                } elseif ($user->role === 'guide') {
                    $q->where('guide_id', $user->id);
                }
            })
            ->whereBetween('assigned_at', [$start, $end]);

        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->orderBy('assigned_at', 'desc')->get();

        
        $summary = [
            'total'     => $assignments->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'accepted'  => $assignments->where('status', 'accepted')->count(),
            'pending'   => $assignments->where('status', 'pending')->count(),
            'declined'  => $assignments->where('status', 'declined')->count(),
        ];

        
        
        $usedHours = 0;
        $totalHours = 0;

        
        $curr = $start->copy()->startOfMonth(); 
        $limit = $end->copy()->startOfMonth();

        while ($curr->lte($limit)) {
            $m = $curr->month;
            $y = $curr->year;

            
            $ws = WorkSchedule::where('user_id', $user->id)
                ->where('year', $y)
                ->where('month', $m)
                ->first();

            if ($ws) {
                $usedHours += (float) $ws->used_hours;
                $totalHours += (float) $ws->total_hours; 
            } else {
                
                $monthlyLimit = $user->monthly_work_limit ?? 200;
                $totalHours += $monthlyLimit;

                
                
                $monthStart = $curr->copy()->startOfMonth();
                $monthEnd   = $curr->copy()->endOfMonth();
                
                $manualMinutes = 0;
                
                
                $inMonth = $assignments->filter(function($a) use ($monthStart, $monthEnd) {
                     return $a->status === 'completed' && 
                            $a->assigned_at >= $monthStart && 
                            $a->assigned_at <= $monthEnd;
                });

                foreach ($inMonth as $a) {
                    if ($a->workstart && $a->workend) {
                        $manualMinutes += Carbon::parse($a->workstart)
                            ->diffInMinutes(Carbon::parse($a->workend));
                    }
                }
                $usedHours += round($manualMinutes / 60, 1);
            }

            $curr->addMonth();
        }

        
        if ($totalHours == 0) $totalHours = 200;

        $usagePercent = $totalHours > 0
            ? min(100, round(($usedHours / $totalHours) * 100))
            : 0;

        
        $chartData = $assignments
            ->groupBy(function ($item) {
                return Carbon::parse($item->assigned_at)->format('Y-m-d');
            })
            ->map(function ($day, $date) {
                return [
                    'date'      => $date,
                    'completed' => $day->where('status', 'completed')->count(),
                    'total'     => $day->count(),
                ];
            })
            ->values()
            ->toArray();

        return view('reports.personal', compact(
            'assignments',
            'summary',
            'chartData',
            'start',
            'end',
            'user',
            'usedHours',
            'totalHours',
            'usagePercent'
        ));
    }

    
    
    

    public function exportExcel(Request $request)
    {
        $this->authorizeExport();

        $year = $request->query('year', date('Y'));
        $month = $request->query('month', null);
        $fileName = "orders_{$year}" . ($month ? "_{$month}" : "") . ".xlsx";

        return Excel::download(new OrdersQueryExport($year, $month), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizeExport();

        $year = $request->query('year', date('Y'));
        $month = $request->query('month', null);

        $orders = Order::with('product')
            ->when($year, function ($q) use ($year) {
                $q->whereYear('pickup_time', $year);
            })
            ->when($month, function ($q) use ($month) {
                if ($month) {
                    $q->whereMonth('pickup_time', $month);
                }
            })
            ->orderBy('pickup_time', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.pdf_orders', compact('orders', 'year', 'month'))
            ->setPaper('a4', 'portrait');

        $fileName = "orders_report_{$year}" . ($month ? "_{$month}" : "") . ".pdf";
        return $pdf->download($fileName);
    }

    
    
    

    public function exportPersonalPdf(Request $request)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['driver', 'guide']), 403);

        $start = Carbon::parse($request->start ?? now()->startOfMonth());
        $end   = Carbon::parse($request->end ?? now()->endOfMonth());

        $assignments = Assignment::with(['order', 'order.product'])
            ->where(function ($q) use ($user) {
                if ($user->role === 'driver') {
                    $q->where('driver_id', $user->id);
                }
                if ($user->role === 'guide') {
                    $q->where('guide_id', $user->id);
                }
            })
            ->whereBetween('assigned_at', [$start, $end])
            ->orderBy('assigned_at', 'desc')
            ->get();

        $summary = [
            'total'     => $assignments->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
        ];

        $pdf = Pdf::loadView('reports.pdf_personal', compact(
                'assignments',
                'summary',
                'user',
                'start',
                'end'
            ))
            ->setPaper('a4', 'portrait');

        $fileName = "laporan-{$user->role}-{$user->name}_{$start->format('Y-m-d')}_{$end->format('Y-m-d')}.pdf";
        return $pdf->download($fileName);
    }

    public function exportPersonalExcel(Request $request)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['driver', 'guide']), 403);

        $start = Carbon::parse($request->start ?? now()->startOfMonth());
        $end   = Carbon::parse($request->end ?? now()->endOfMonth());

        $assignments = Assignment::with(['order', 'order.product'])
            ->where(function ($q) use ($user) {
                if ($user->role === 'driver') {
                    $q->where('driver_id', $user->id);
                }
                if ($user->role === 'guide') {
                    $q->where('guide_id', $user->id);
                }
            })
            ->whereBetween('assigned_at', [$start, $end])
            ->orderBy('assigned_at', 'desc')
            ->get();

        $headers = ['ID', 'Customer', 'Product', 'Pickup', 'Status', 'Durasi (Menit)', 'Catatan'];
        $rows = [];

        foreach ($assignments as $a) {
            $order = $a->order;
            $pickup = $order && $order->pickup_time
                ? Carbon::parse($order->pickup_time)->format('d M Y H:i')
                : '-';

            $duration = '-';
            if ($a->workstart && $a->workend) {
                $duration = Carbon::parse($a->workstart)
                    ->diffInMinutes(Carbon::parse($a->workend));
            }

            $rows[] = [
                $a->id,
                $order?->customer_name ?? '-',
                $order?->product?->name ?? '-',
                $pickup,
                ucfirst($a->status),
                $duration,
                $a->note ?? '-',
            ];
        }

        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');//output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        $filename = "laporan-{$user->role}-{$user->name}_{$start->format('Y-m-d')}_{$end->format('Y-m-d')}.csv";
        return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv']);
    }

    
    
    

    protected function authorizeExport()
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin', 'staff'])) {
            abort(403, 'Akses ditolak.');
        }
        return true;
    }
}
