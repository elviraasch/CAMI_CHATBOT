<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\EmissionCalculation;
use App\Models\Project;
use App\Models\Order; 
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\ProjectRecommendationService;

class BuyerDashboardController extends Controller
{
    public function __construct(
        private readonly ProjectRecommendationService $recommendationService
    ) {
    }

    public function index()
    {
        $user = Auth::user();

        // ── Kalkulasi Emisi Terakhir ──────────────────────
        $emission = EmissionCalculation::where('user_id', $user->id)
                    ->latest()
                    ->first();  

        // ── Total yang sudah di-offset (Menggunakan Model Order & field quantity) ──
        $totalOffsetTon = Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'verified', 'completed'])
            ->sum('quantity'); // Kolom pengganti offset_ton

        $totalOffsetKg = $totalOffsetTon * 1000;

        // ── Persentase Offset ─────────────────────────────────
        $offsetPercentage = 0;
        if ($emission && $emission->total_kg > 0) {
            $offsetPercentage = min(100, ($totalOffsetKg / $emission->total_kg) * 100);
        }

        // ── Ekuivalen Pohon ───────────────────────────────────
        $treeEquivalent = $emission
            ? (int) ceil($emission->total_kg / 21.77)
            : 0;

        // ── Statistik Transaksi (Menggunakan Model Order) ────────────────────────
        $totalTransactions = Order::where('user_id', $user->id)->count();
        
        $totalSpent = Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'verified', 'completed'])
            ->sum('total_price');

        // ── Riwayat Transaksi / Order (5 terbaru) ─────────────────────
        // Variabel sengaja dialias tetap bernama $transactions agar file Blade lama kamu tidak perlu diubah
        $transactions = Order::where('user_id', $user->id)
            ->with('project')
            ->latest()
            ->take(5)
            ->get();

        $certificateOrders = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('certificate_number')
            ->with('project')
            ->latest('certificate_issued_at')
            ->take(5)
            ->get();

        // ── Rekomendasi Proyek ────────────────────────────────
        $recommendedProjects = $this->recommendationService
            ->recommend($user, $emission, 3);

        return view('main_page.dashboard-buyer.dashboard-buyer', compact(
            'emission',
            'totalOffsetKg',
            'totalOffsetTon',
            'offsetPercentage',
            'treeEquivalent',
            'totalTransactions',
            'totalSpent',
            'transactions', 
            'certificateOrders',
            'recommendedProjects',
        ));
    }

    public function transactions(Request $request)
    {
        $user = Auth::user();

        // 1. Query dasar ambil Order milik buyer dengan relasi proyek
        $query = Order::where('user_id', $user->id)->with('project');

        // Filter status transaksi (bawaan pencarian)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                ->orWhereHas('project', function($pq) use ($search) {
                    $pq->where('name', 'LIKE', "%{$search}%");
                });
            });
        }

        // Ambil data untuk tabel dengan paginasi
        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        // 2. Inisialisasi counter agregat proyek untuk Summary Cards atas
        $projectRunningCount = 0;
        $projectCompletedCount = 0;
        $projectExpiringCount = 0;

        // Ambil semua order sukses milik buyer untuk menghitung status keseluruhan proyeknya
        $allUserOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'verified', 'completed'])
            ->with('project')
            ->get();

        foreach ($allUserOrders as $ord) {
            if ($ord->project) {
                $startDate = Carbon::parse($ord->project->created_at);
                $durationMonths = (int) ($ord->project->duration_months ?? 12);
                $endDate = $startDate->copy()->addMonths($durationMonths);
                $now = Carbon::now();

                if ($now->greaterThan($endDate)) {
                    $projectCompletedCount++;
                } else {
                    $daysRemaining = (int) $now->diffInDays($endDate, false);
                    if ($daysRemaining >= 0 && $daysRemaining <= 3) {
                        $projectExpiringCount++;
                    } else {
                        $projectRunningCount++;
                    }
                }
            }
        }

        // 3. Map status tekstual untuk data tabel (Paginasi)
        $orders->getCollection()->transform(function ($order) {
            if (!$order->project) {
                $order->project_status_label = 'Proyek Terhapus';
                $order->project_status_class = 'deleted';
                return $order;
            }

            $startDate = Carbon::parse($order->project->created_at);
            $durationMonths = (int) ($order->project->duration_months ?? 12);
            $endDate = $startDate->copy()->addMonths($durationMonths);
            $now = Carbon::now();

            if ($now->greaterThan($endDate)) {
                $order->project_status_label = 'Selesai';
                $order->project_status_class = 'completed';
            } else {
                $daysRemaining = (int) $now->diffInDays($endDate, false);
                if ($daysRemaining >= 0 && $daysRemaining <= 3) {
                    $order->project_status_label = "Expired dlm {$daysRemaining} Hari";
                    $order->project_status_class = 'expiring';
                } else {
                    $order->project_status_label = 'Masih Berjalan';
                    $order->project_status_class = 'running';
                }
            }

            $order->project_end_date = $endDate->format('d M Y');
            return $order;
        });

        // 4. Bungkus statistik ringkasan akhir untuk dilempar ke Blade
        $stats = [
            'total_transactions' => Order::where('user_id', $user->id)->count(),
            'total_offset_ton'   => Order::where('user_id', $user->id)->whereIn('status', ['paid', 'verified', 'completed'])->sum('quantity'),
            'total_spent'        => Order::where('user_id', $user->id)->whereIn('status', ['paid', 'verified', 'completed'])->sum('total_price'),
            
            // Counter Project Masuk ke Stats Global
            'proj_running'       => $projectRunningCount,
            'proj_completed'     => $projectCompletedCount,
            'proj_expiring'      => $projectExpiringCount,
        ];

        return view('main_page.dashboard-buyer.transactions', compact('orders', 'stats'));
    }

    public function certificate(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);
        abort_unless($order->status === 'completed' && filled($order->certificate_number), 404);

        $order->load(['project.seller', 'certificateIssuer', 'user']);

        return view('main_page.dashboard-buyer.certificate', compact('order'));
    }
}
