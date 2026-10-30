<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ChatbotInformationService;
use App\Services\ChatbotProjectQueryService;
use App\Services\ChatbotResultService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly ChatbotResultService $resultService,
        private readonly ChatbotProjectQueryService $projectQueryService,
        private readonly ChatbotInformationService $informationService
    ) {}

    public function send(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'session_id' => ['nullable', 'string'],
        ]);

        $userMessage = trim($validated['message']);
        
        // Mendukung Auth standar Laravel session maupun Sanctum dengan aman
        $user = auth()->user() ?? auth('sanctum')->user() ?? $request->user();

        try {
            // Query terkait proyek dapat diakses oleh guest maupun user login[cite: 26]
            $projectAnswer = $this->projectQueryService->answer($userMessage, $user);
            if ($projectAnswer !== null) {
                return response()->json([
                    'status' => 'success',
                    'reply' => $projectAnswer['text'] ?? '',
                    'data' => [$projectAnswer],
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'success',
                'reply' => 'Informasi proyek belum dapat ditampilkan. Silakan coba kembali.',
                'data' => [[
                    'text' => 'Informasi proyek belum dapat ditampilkan. Silakan coba kembali.',
                    'custom' => ['type' => 'project_catalog_error'],
                ]],
            ]);
        }

        if ($this->isOffsetSimulationRequest($userMessage)) {
            $answer = $this->completeInformationRequest(
                [],
                ['type' => 'offset_simulation_request', 'query' => $userMessage],
                $user,
                $userMessage
            );

            return response()->json([
                'status' => 'success',
                'reply' => $answer['text'] ?? '',
                'data' => [$answer],
            ]);
        }

        if ($this->isRecommendationRequest($userMessage)) {
            $rec = $this->completeRecommendation(
                ['custom' => ['type' => 'project_recommendation_request']],
                $user
            );
            return response()->json([
                'status' => 'success',
                'reply' => $rec['text'] ?? '',
                'data' => [$rec],
            ]);
            Log::info('Data Rekomendasi Guest:', ['count' => $projects->count(), 'data' => $recommendations]);
        }

        $conversationId = $validated['conversation_id'] ?? $validated['session_id'] ?? null;
        $senderId = $user ? 'user_'.$user->id : 'guest_'.session()->getId();
        if (! empty($conversationId)) {
            $senderId .= '_'.$conversationId;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(3)
                ->timeout(20)
                ->post(config('services.rasa.webhook_url'), [
                    'sender' => $senderId,
                    'message' => $userMessage,
                    'metadata' => [
                        'authenticated' => (bool) $user,
                        'role' => $user?->role,
                        'account_type' => $user?->account_category ?? 'guest',
                    ],
                ]);

            if ($response->successful()) {
                $messages = $response->json();
                if (! is_array($messages)) {
                    throw new \UnexpectedValueException('Format respons Rasa tidak valid.');
                }

                $responseData = $this->completeResponses($messages, $user, $userMessage);

                return response()->json([
                    'status' => 'success',
                    'data' => $responseData,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal terhubung ke Rasa server.',
            ], 500);

        } catch (ConnectionException $exception) {
            Log::warning('Permintaan ke Rasa gagal.', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Chatbot belum dapat dihubungi. Silakan coba lagi sebentar.',
            ], 503);
        }
    }

    private function completeResponses(array $messages, ?User $user, string $userMessage): array
    {
        $hasCalculation = collect($messages)->contains(
            fn ($message) => is_array($message)
                && ($message['custom']['type'] ?? null) === 'calculation_request'
        );

        return collect($messages)
            ->reject(fn ($message) => $hasCalculation
                && is_array($message)
                && ($message['text'] ?? null) === 'Data aktivitas sudah lengkap dan siap dihitung oleh CAMAR.')
            ->map(function ($message) use ($user, $userMessage) {
                $custom = is_array($message) ? ($message['custom'] ?? null) : null;
                if (is_array($custom) && ($custom['type'] ?? null) === 'project_recommendation_request') {
                    return $this->completeRecommendation($message, $user);
                }

                $informationTypes = [
                    'emission_explanation_request',
                    'carbon_balance_request',
                    'offset_simulation_request',
                    'project_information_request',
                    'project_verification_request',
                    'recommendation_explanation_request',
                    'project_comparison_request',
                    'purchase_disclosure_request',
                    'personal_action_plan_request',
                ];
                if (is_array($custom) && in_array($custom['type'] ?? null, $informationTypes, true)) {
                    return $this->completeInformationRequest(
                        $message,
                        $custom,
                        $user,
                        $userMessage
                    );
                }

                if (! is_array($custom) || ($custom['type'] ?? null) !== 'calculation_request') {
                    return $message;
                }

                if (! is_array($custom['data'] ?? null)) {
                    $message['text'] = 'Data perhitungan tidak valid.';
                    $message['custom'] = ['type' => 'calculation_error'];

                    return $message;
                }

                try {
                    if (! $user) {
                        $tempResult = $this->calculateTemporaryEmission($custom['data']);
                        session(['guest_emission_result' => $tempResult]);
                        
                        $message['text'] = "Hasil estimasi emisi sementara (Sesi Tamu):\n"
                            .'Total: '.$this->formatNumber($tempResult['total_kg']).' kg CO2e '
                            .'('.$this->formatNumber($tempResult['total_ton'], 6)." ton).\n"
                            .'Masuk (login) ke akun CAMAR untuk menyimpan hasil ini secara permanen ke database.';
                        $message['custom'] = array_merge(
                            ['type' => 'calculation_result'],
                            $tempResult
                        );
                        return $message;
                    }

                    // Jika sudah login, simpan ke database seperti biasa
                    $result = $this->resultService->process($user, $custom['data']);
                    $message['text'] = $this->resultText($result);
                    $message['custom'] = array_merge(
                        ['type' => 'calculation_result'],
                        $result
                    );
                } catch (InvalidArgumentException $exception) {
                    $message['text'] = $exception->getMessage();
                    $message['custom'] = ['type' => 'calculation_error'];
                } catch (Throwable $exception) {
                    report($exception);
                    $message['text'] = 'Hasil belum dapat disimpan. Silakan coba kembali.';
                    $message['custom'] = ['type' => 'calculation_error'];
                }

                return $message;
            })->values()->all();
    }

    private function calculateTemporaryEmission(array $data): array
    {
        $mode = $data['mode'] ?? 'personal';

        if (isset($data['total_kg']) && is_numeric($data['total_kg'])) {
            $totalKg = (float) $data['total_kg'];
            return [
                'calculation_id' => 'guest_session',
                'mode' => $mode,
                'scope1_kg' => (float) ($data['scope1_kg'] ?? 0),
                'scope2_kg' => (float) ($data['scope2_kg'] ?? 0),
                'scope3_kg' => (float) ($data['scope3_kg'] ?? 0),
                'total_kg' => $totalKg,
                'total_ton' => round($totalKg / 1000, 3),
                'estimated_cost' => round(($totalKg / 1000) * 150000),
                'recommendations' => [],
            ];
        }

        $guestUser = new User(['role' => 'buyer', 'account_category' => $mode]);
        try {
            $calculationService = new \App\Services\CarbonCalculationService();
            $result = $calculationService->calculate($guestUser, $data);
            $totalKg = $result['total_kg'];

            return [
                'calculation_id' => 'guest_session',
                'mode' => $result['mode'],
                'scope1_kg' => $result['scope1_kg'],
                'scope2_kg' => $result['scope2_kg'],
                'scope3_kg' => $result['scope3_kg'],
                'total_kg' => $totalKg,
                'total_ton' => round($totalKg / 1000, 3),
                'estimated_cost' => round(($totalKg / 1000) * 150000),
                'recommendations' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'calculation_id' => 'guest_session',
                'mode' => $mode,
                'scope1_kg' => 0,
                'scope2_kg' => 0,
                'scope3_kg' => 0,
                'total_kg' => 0,
                'total_ton' => 0,
                'estimated_cost' => 0,
                'recommendations' => [],
            ];
        }
    }

   private function completeRecommendation(array $message, ?User $user): array
    {
       if ($user && $user->isBuyer()) {
            try {
                $result = $this->resultService->recommendExisting($user);
                $text = ($result['source'] ?? '') === 'latest_emission'
                    ? 'Berdasarkan hasil emisi terakhirmu sebesar '
                        .$this->formatNumber($result['total_kg'] ?? 0).' kg CO2e/tahun, berikut proyek yang sesuai:'
                    : 'Kamu belum memiliki hasil emisi tersimpan. Berikut pilihan proyek awal yang tersedia.';

                $message['text'] = $text;
                $message['custom'] = array_merge(['type' => 'project_recommendation_result'], $result);
                return $message;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $guestEmission = session('guest_emission_result');
        if ($guestEmission && isset($guestEmission['total_kg'])) {
            $totalKg = $guestEmission['total_kg'];
            $requiredTon = ceil($totalKg / 1000);

            $projects = \App\Models\Project::query()->approved()->where('stock_available', '>', 0)->limit(3)->get();
            
            $recommendations = $projects->map(function ($p) use ($requiredTon) {
                $pricePerTon = (float) $p->price_per_ton;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'company_name' => $p->company_name,
                    'category' => $p->category,
                    'location' => $p->location,
                    'price_per_ton' => $pricePerTon,
                    'stock_available' => (int) $p->stock_available,
                    'required_ton' => $requiredTon,
                    'total_offset_cost' => $requiredTon * $pricePerTon,
                    'score' => 85.0, 
                    'reasons' => ['Kecocokan berdasarkan estimasi emisi sementara sesi tamu Anda.'],
                    'url' => route('projects.show', $p->id),
                ];
            })->all();

            $message['text'] = 'Berdasarkan hasil estimasi emisi sementara (' . $this->formatNumber($totalKg) . ' kg CO2e) pada sesi ini, berikut adalah rekomendasi proyek yang sesuai:';
            
            // PERBAIKAN: Sesuaikan struktur agar 'recommendations' terbaca oleh chatbot.js
            $message['custom'] = [
                'type' => 'project_recommendation_result',
                'source' => 'guest_calculation',
                'total_kg' => $totalKg,
                'recommendations' => $recommendations,
            ];
            return $message;
        }

        // Jika guest dan BELUM PERNAH menghitung emisi sama sekali, tampilkan katalog umum
        $projects = \App\Models\Project::query()->approved()->where('stock_available', '>', 0)->limit(3)->get();
        
        Log::info('Debug Proyek Guest:', [
                'total_project_found' => $projects->count(),
                'projects' => $projects->toArray()
            ]);
            
        $recommendations = $projects->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'company_name' => $p->company_name,
            'category' => $p->category,
            'location' => $p->location,
            'price_per_ton' => (float) $p->price_per_ton,
            'stock_available' => (int) $p->stock_available,
            'url' => route('projects.show', $p->id),
        ])->all();

        $message['text'] = 'Berikut adalah beberapa pilihan proyek awal CAMAR yang tersedia untuk dieksplorasi. Silakan hitung emisi terlebih dahulu atau masuk sebagai buyer untuk rekomendasi personal.';
        
        $message['custom'] = [
            'type' => 'project_recommendation_result',
            'source' => 'guest_catalog',
            'recommendations' => $recommendations,
        ];
        
        return $message;
    }

    private function completeInformationRequest(
        array $message,
        array $custom,
        ?User $user,
        string $userMessage
    ): array {
        try {
            if (! $user && in_array($custom['type'] ?? '', ['carbon_balance_request', 'emission_explanation_request', 'personal_action_plan_request'], true)) {
                $guestEmission = session('guest_emission_result');
                if (! $guestEmission) {
                    return [
                        'text' => 'Kamu belum melakukan perhitungan emisi pada sesi ini. Silakan ketik "hitung emisi" terlebih dahulu.',
                        'custom' => ['type' => 'auth_required'],
                    ];
                }
                
                if ($custom['type'] === 'personal_action_plan_request') {
                    $totalKg = $guestEmission['total_kg'] ?? 1250.00;
                    $steps = [
                        [
                            'rank' => 1,
                            'label' => 'Listrik Rumah Tangga (Scope 2)',
                            'emission_kg' => $guestEmission['scope2_kg'] ?? ($totalKg * 0.4),
                            'advice' => 'Kurangi pemakaian alat elektronik berdaya tinggi dan beralihlah ke lampu LED hemat energi.'
                        ],
                        [
                            'rank' => 2,
                            'label' => 'Kendaraan & Transportasi (Scope 1 & 3)',
                            'emission_kg' => $guestEmission['scope1_kg'] ?? ($totalKg * 0.4),
                            'advice' => 'Manfaatkan transportasi umum atau lakukan perjalanan bersama (carpooling) untuk mobilitas harian.'
                        ],
                        [
                            'rank' => 3,
                            'label' => 'Pangan & Limbah (Scope 3)',
                            'emission_kg' => $guestEmission['scope3_kg'] ?? ($totalKg * 0.2),
                            'advice' => 'Kurangi konsumsi makanan tinggi emisi seperti daging merah dan pilah sampah rumah tangga secara rutin.'
                        ]
                    ];

                    $message['text'] = "Berikut adalah rencana prioritas pengurangan emisi Anda berdasarkan estimasi sesi tamu:\n1. Listrik Rumah Tangga\n2. Transportasi\n3. Pangan & Limbah";
                    $message['custom'] = [
                        'type' => 'personal_action_plan_result',
                        'steps' => $steps,
                    ];
                    return $message;
                }

                $message['text'] = "Saldo karbon sementara (Sesi Tamu):\nTotal emisi terhitung: " . number_format($guestEmission['total_kg'], 2, ',', '.') . " kg CO2e.\n💡 Masuk (login) untuk menyimpan riwayat dan melakukan transaksi offset nyata.";
                $message['custom'] = [
                    'type' => 'carbon_balance_result',
                    'total_kg' => $guestEmission['total_kg'],
                    'offset_kg' => 0,
                    'remaining_kg' => $guestEmission['total_kg'],
                    'offset_percentage' => 0,
                ];
                return $message;
            }

            if (! $user && ($custom['type'] ?? '') === 'offset_simulation_request') {
                $guestEmission = session('guest_emission_result');
                if (! $guestEmission) {
                    return [
                        'text' => 'Kamu belum melakukan perhitungan emisi pada sesi ini. Silakan ketik "hitung emisi" terlebih dahulu.',
                        'custom' => ['type' => 'auth_required'],
                    ];
                }

                $totalKg = $guestEmission['total_kg'] ?? 1250.00;
                $plannedTon = 1.0; 
                $remainingBefore = $totalKg;
                $remainingAfter = max(0, $totalKg - ($plannedTon * 1000));
                $pricePerTon = 150000;

                $message['text'] = "Simulasi pembelian {$plannedTon} ton kredit karbon berhasil:\nSisa emisi sebelum: " . number_format($remainingBefore, 2, ',', '.') . " kg\nSisa emisi sesudah: " . number_format($remainingAfter, 2, ',', '.') . " kg";
                $message['custom'] = [
                    'type' => 'offset_simulation_result',
                    'planned_ton' => $plannedTon,
                    'remaining_before_kg' => $remainingBefore,
                    'remaining_after_kg' => $remainingAfter,
                    'estimated_total_cost' => $plannedTon * $pricePerTon,
                ];
                return $message;
            }

            // 3. Jika sudah login atau permintaan informasi umum lainnya, teruskan ke InformationService
            $answer = $this->informationService->answer(
                (string) $custom['type'],
                (string) ($custom['query'] ?? $userMessage),
                $user
            );

            return array_merge($message, $answer);
        } catch (Throwable $exception) {
            report($exception);

            $message['text'] = 'Informasi tersebut belum dapat ditampilkan. Silakan coba kembali.';
            $message['custom'] = ['type' => 'information_error'];

            return $message;
        }
    }

    private function isRecommendationRequest(string $message): bool
    {
        $message = Str::lower($message);
        return $message === '/ask_project_recommendation'
            || (Str::contains($message, 'proyek') && Str::contains($message, [
                'rekomendas', 'carikan', 'cocok', 'sesuai', 'saran', 'pilih', 'relevan',
            ]));
    }

    private function isOffsetSimulationRequest(string $message): bool
    {
        $message = Str::lower($message);
        return Str::contains($message, [
            'simulasi offset',
            'simulasikan pembelian',
            'simulasi beli',
            'simulasi kredit karbon',
            'simulasi pembelian kredit karbon',
            'simulasi'
        ]);
    }

    private function resultText(array $result): string
    {
        $mode = $result['mode'] === 'company' ? 'perusahaan' : 'individu';

        return "Hasil estimasi emisi akun {$mode}:\n"
            .'Scope 1: '.$this->formatNumber($result['scope1_kg'])." kg CO2e\n"
            .'Scope 2: '.$this->formatNumber($result['scope2_kg'])." kg CO2e\n"
            .'Scope 3: '.$this->formatNumber($result['scope3_kg'])." kg CO2e\n"
            .'Total: '.$this->formatNumber($result['total_kg']).' kg CO2e '
            .'('.$this->formatNumber($result['total_ton'], 6)." ton)\n"
            .'Estimasi biaya offset: Rp '.$this->formatNumber($result['estimated_cost'], 0);
    }

    private function formatNumber(float|int $value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }
}

