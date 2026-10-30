@extends('main_page.layout.app')

@section('title', 'Kalkulator Karbon')
@section('description', 'Hitung jejak karbon Anda berdasarkan GHG Protocol, ISO 14064-1:2018, IPCC 2006, dan Pedoman KLHK Buku II Vol.I 2012')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/calculator.css') }}">
@endpush

@section('content')

{{-- =====================================================
    HERO
     ===================================================== --}}
<section class="calculator-hero" id="calculator">
    <div class="calculator-hero-bg">
        <picture>
            <source type="image/webp" srcset="{{ asset('images/gunung0-960.webp') }} 960w, {{ asset('images/gunung0-1920.webp') }} 1920w" sizes="100vw">
            <img src="{{ asset('images/gunung0.png') }}" alt="" width="2816" height="1536" fetchpriority="high" decoding="async">
        </picture>
        <div class="hero-overlay"></div>
    </div>
    <div class="container">
        <div class="calculator-hero-content">
            <span class="hero-chip"><i class="fas fa-leaf"></i> Carbon Footprint Calculator</span>
            <h1 class="calculator-hero-title">Kalkulator Emisi Karbon</h1>
            
        </div>
    </div>
</section>

{{-- =====================================================
     MAIN CALCULATOR
     ===================================================== --}}
<main class="calculator-page">
    <div class="container">

        @if(session('warning'))
            <div role="alert" style="
                margin-bottom:24px;
                padding:14px 18px;
                border:1px solid #f4c95d;
                border-radius:12px;
                background:#fff8df;
                color:#72510d;
                font-weight:600;
            ">
                <i class="fas fa-circle-info" style="margin-right:8px;"></i>
                {{ session('warning') }}
            </div>
        @endif

        {{-- ── SCOPE/STEP TAB HEADER ──────────────────────────────────── --}}
        
        @auth
            @if(Auth::user()->account_category === 'company')
                @php $userRole = 'company'; @endphp
            @else
                @php $userRole = 'personal'; @endphp
            @endif 
        @else
            @php $userRole = 'personal'; @endphp
        @endauth

        <datalist id="airport-options"></datalist>

        {{-- ================================================================
             PERSONAL CALCULATOR
             ================================================================ --}}
        @if($userRole === 'personal')

        <div class="calculator-profile-card">
            <div class="calculator-profile-icon"><i class="fas fa-user"></i></div>
            <div>
                <span class="calculator-profile-kicker">Kalkulator Buyer Individu</span>
                <h2>Hitung emisi Jejak Karbon</h2>
                <p>Isi aktivitas pada setiap scope, lalu tinjau hasil dan simpan kalkulasi ke dashboard.</p>
            </div>
            <span class="calculator-profile-status"><i class="fas fa-shield-alt"></i> Data tersimpan aman</span>
        </div>

        {{-- Step Tab Nav: Personal --}}
        <div class="step-tab-nav" id="personalNav">
            <button class="step-tab-btn active" data-step="p1">
                <span class="tab-number">1</span>
                <span class="tab-label">Scope 1</span>
            </button>
            <div class="tab-connector"></div>
            <button class="step-tab-btn" data-step="p2">
                <span class="tab-number">2</span>
                <span class="tab-label">Scope 2</span>
            </button>
            <div class="tab-connector"></div>
            <button class="step-tab-btn" data-step="p3">
                <span class="tab-number">3</span>
                <span class="tab-label">Scope 3</span>
            </button>
            <div class="tab-connector"></div>
            <button class="step-tab-btn" data-step="p4">
                <span class="tab-number">4</span>
                <span class="tab-label">Hasil</span>
            </button>
        </div>

        {{-- ── PERSONAL STEP 1: ENERGI & KENDARAAN ── --}}
        <div class="step-pane active" id="p-step-1">

            <div class="scope-label-banner scope1-banner">
                <i class="fas fa-fire"></i>
                Scope 1 — Emisi Langsung
                <span>Energi rumah tangga dan kendaraan yang Anda gunakan langsung</span>
            </div>

            {{-- ENERGI RUMAH TANGGA --}}
            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_energy" aria-expanded="false" aria-controls="p_energy-content">
                    <div class="module-icon scope-green"><i class="fas fa-home"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-green">Pembakaran Stasioner</div>
                        <h2 class="module-title">Konsumsi Energi Rumah Tangga</h2>
                        <p class="module-subtitle">Bahan bakar dapur & genset · Scope 1</p>
                    </div>
                    <div class="module-preview" id="prev_p_energy">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_energy-content">

                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi</span>
                    </div>

                    <div id="rows_p_energy">
                        <div class="entry-row" data-group="p_energy">
                            <div class="entry-fields">
                                <div class="input-group">
                                    <label class="input-label">Jenis Bahan Bakar</label>
                                    <select class="input-field select-field" name="p_energy_fuel[]" onchange="calcLive()">
                                        <option value="">-- Pilih Bahan Bakar --</option>
                                        <option value="lpg">LPG (Gas Tabung)</option>
                                         <option value="cng">CNG (Gas Alam)</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Konsumsi Bulanan</label>
                                    <div class="input-with-unit">
                                        <input type="number" class="input-field" name="p_energy_qty[]" placeholder="0" min="0" step="0.01" oninput="calcLive()">
                                        <span class="input-unit unit-dynamic" data-for="p_energy_fuel">kg/bln</span>
                                    </div>
                                </div>
                                <div class="input-group ef-display-group">
                                    <label class="input-label">Faktor Emisi</label>
                                    <div class="ef-chip" data-ef-group="p_energy">—</div>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-row" data-remove-row title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-row" data-add-row="p_energy">
                        <i class="fas fa-plus"></i> Tambah Bahan Bakar
                    </button>

                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Energi RT:</span>
                        <span class="preview-value" id="sub_p_energy">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            {{-- KENDARAAN PRIBADI --}}
            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_vehicle" aria-expanded="false" aria-controls="p_vehicle-content">
                    <div class="module-icon scope-orange"><i class="fas fa-car"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-orange">Pembakaran Bergerak</div>
                        <h2 class="module-title">Kendaraan Pribadi</h2>
                        <p class="module-subtitle">Jarak tempuh bulanan · Scope 1</p>
                    </div>
                    <div class="module-preview" id="prev_p_vehicle">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_vehicle-content">

                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi berbasis jarak tempuh</span>
                    </div>

                    <div id="rows_p_vehicle">
                        <div class="entry-row" data-group="p_vehicle">
                            <div class="entry-fields">
                                <div class="input-group">
                                    <label class="input-label">Jenis Kendaraan</label>
                                    <select class="input-field select-field" name="p_vehicle_type[]" onchange="updateVehicleFuel(this); calcLive()">
                                        <option value="">-- Pilih Kendaraan --</option>
                                        <option value="car_petrol">Mobil Pribadi</option>
                                        <option value="motorcycle">Sepeda Motor</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Jenis Bahan Bakar</label>
                                    <select class="input-field select-field" name="p_vehicle_fuel[]" onchange="calcLive()">
                                        <option value="">-- Pilih BBM --</option>
                                        <option value="ron98">Bensin RON98</option>
                                        <option value="ron92">Bensin RON92</option>
                                        <option value="ron90">Bensin RON90</option>
                                        <option value="ron88">Bensin RON88</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Jarak Tempuh Bulanan</label>
                                    <div class="input-with-unit">
                                        <input type="number" class="input-field" name="p_vehicle_km[]" placeholder="0" min="0" step="1" oninput="calcLive()">
                                        <span class="input-unit">km/bln</span>
                                    </div>
                                </div>
                                <div class="input-group ef-display-group">
                                    <label class="input-label">Faktor Emisi</label>
                                    <div class="ef-chip" data-ef-group="p_vehicle">—</div>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-row" data-remove-row title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-row" data-add-row="p_vehicle">
                        <i class="fas fa-plus"></i> Tambah Kendaraan
                    </button>

                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Kendaraan:</span>
                        <span class="preview-value" id="sub_p_vehicle">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            {{-- LISTRIK PLN --}}
            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_electricity" aria-expanded="false" aria-controls="p_electricity-content">
                    <div class="module-icon scope-blue"><i class="fas fa-bolt"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-blue">Scope 2</div>
                        <h2 class="module-title">Listrik Jaringan Nasional (PLN)</h2>
                        <p class="module-subtitle">Emisi tidak langsung dari pembelian listrik</p>
                    </div>
                    <div class="module-preview" id="prev_p_electricity">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_electricity-content">

                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi Scope 2</span>
                    </div>

                    <div class="input-row-grid">
                        <div class="input-group">
                            <label class="input-label">Konsumsi Listrik Bulanan</label>
                            <div class="input-with-unit">
                                <input type="number" class="input-field" id="p_elec_kwh" placeholder="0" min="0" step="0.01" oninput="calcLive()">
                                <span class="input-unit">kWh/bln</span>
                            </div>
                            <span class="input-hint">Lihat tagihan PLN Anda</span>
                        </div>
                        <div class="input-group ef-display-group">
                            <label class="input-label">Faktor Emisi</label>
                            <div class="ef-chip">0,80 kgCO₂e/kWh<br><small>Grid Jawa-Bali</small></div>
                        </div>
                    </div>

                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Listrik:</span>
                        <span class="preview-value" id="sub_p_electricity">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            {{-- STEP NAV --}}
            <div class="step-pane-nav">
                <div></div>
                <button type="button" class="btn-step-next" data-switch-tab-prefix="p" data-switch-tab-step="2" data-switch-tab-total="4">
                    Scope 2 - Listrik <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ── PERSONAL STEP 2: TRANSPORTASI UMUM ── --}}
        <div class="step-pane" id="p-step-2">

            <div class="scope-label-banner scope2-banner">
                <i class="fas fa-bolt"></i>
                Scope 2 - Listrik
                <span>Konsumsi listrik dari jaringan PLN atau sumber energi tidak langsung</span>
            </div>

            <div id="personal-scope2-modules"></div>

            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_transit" aria-expanded="false" aria-controls="p_transit-content">
                    <div class="module-icon scope-teal"><i class="fas fa-bus"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-teal">Scope 3</div>
                        <h2 class="module-title">Transportasi Umum</h2>
                        <p class="module-subtitle">Bus, kereta, ojol, dan pesawat</p>
                    </div>
                    <div class="module-preview" id="prev_p_transit">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_transit-content">

                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi berbasis penumpang-km</span>
                    </div>

                    <div id="rows_p_transit">
                        <div class="entry-row" data-group="p_transit">
                            <div class="entry-fields">
                                <div class="input-group">
                                    <label class="input-label">Moda Transportasi</label>
                                    <select class="input-field select-field" name="p_transit_mode[]" onchange="updateTransitModeFields(this); updateEfChip(this,'p_transit'); calcLive()">
                                        <option value="">-- Pilih Moda --</option>
                                        <option value="train">Kereta</option>
                                        <option value="plane">Pesawat</option>
                                    </select>
                                </div>
                                <div class="input-group transit-flight-only" hidden>
                                    <label class="input-label">Kelas Kabin</label>
                                    <select class="input-field select-field" name="p_transit_class[]" onchange="updateEfChip(this,'p_transit'); calcLive()">
                                        <option value="economy">Ekonomi</option>
                                        <option value="business">Bisnis</option>
                                        <option value="first">First Class</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Jumlah Pax</label>
                                    <input type="number" class="input-field" name="p_transit_pax[]" placeholder="1" min="0" step="1" oninput="calcLive()">
                                </div>
                                <div class="input-group transit-flight-only" hidden>
                                    <label class="input-label">Asal Penerbangan</label>
                                    <input type="text" class="input-field airport-input" name="p_transit_origin[]" list="airport-options" placeholder="IATA atau nama bandara" oninput="updateFlightRoute(this); calcLive()" onchange="normaliseAirportField(this); updateFlightRoute(this); calcLive()" onblur="normaliseAirportField(this); updateFlightRoute(this); calcLive()">
                                </div>
                                <div class="input-group transit-flight-only" hidden>
                                    <label class="input-label">Tujuan Penerbangan</label>
                                    <input type="text" class="input-field airport-input" name="p_transit_destination[]" list="airport-options" placeholder="IATA atau nama bandara" oninput="updateFlightRoute(this); calcLive()" onchange="normaliseAirportField(this); updateFlightRoute(this); calcLive()" onblur="normaliseAirportField(this); updateFlightRoute(this); calcLive()">
                                </div>
                                <div class="input-group">
                                    <label class="input-label transit-distance-label">Jarak Bulanan</label>
                                    <div class="input-with-unit">
                                        <input type="number" class="input-field" name="p_transit_km[]" placeholder="0" min="0" step="0.1" oninput="calcLive()">
                                        <span class="input-unit transit-distance-unit">km/bln</span>
                                    </div>
                                    <span class="input-hint flight-distance-hint transit-flight-only" hidden>Pilih asal dan tujuan bandara</span>
                                </div>
                                <div class="input-group ef-display-group">
                                    <label class="input-label">Faktor Emisi</label>
                                    <div class="ef-chip" data-ef-group="p_transit">—</div>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-row" data-remove-row title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-row" data-add-row="p_transit">
                        <i class="fas fa-plus"></i> Tambah Transportasi
                    </button>

                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Transportasi Umum:</span>
                        <span class="preview-value" id="sub_p_transit">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_flight" aria-expanded="false" aria-controls="p_flight-content">
                    <div class="module-icon scope-teal"><i class="fas fa-plane"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-teal">Scope 3</div>
                        <h2 class="module-title">Penerbangan</h2>
                        <p class="module-subtitle">Jarak otomatis dari asal dan tujuan bandara</p>
                    </div>
                    <div class="module-preview" id="prev_p_flight">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_flight-content">

                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi</span>
                    </div>

                    <div id="rows_p_flight">
                        <div class="entry-row" data-group="p_flight">
                            <div class="entry-fields">
                                <div class="input-group">
                                    <label class="input-label">Kelas Kabin</label>
                                    <select class="input-field select-field" name="p_flight_class[]" onchange="updateEfChip(this,'p_flight'); calcLive()">
                                        <option value="">-- Pilih Kelas --</option>
                                        <option value="economy">Ekonomi</option>
                                        <option value="business">Bisnis</option>
                                        <option value="first">First Class</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Jumlah Pax</label>
                                    <input type="number" class="input-field" name="p_flight_pax[]" placeholder="0" min="0" step="1" oninput="calcLive()">
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Asal Penerbangan</label>
                                    <input type="text" class="input-field airport-input" name="p_flight_origin[]" list="airport-options" placeholder="IATA atau nama bandara" oninput="updateFlightRoute(this); calcLive()" onchange="normaliseAirportField(this); updateFlightRoute(this); calcLive()" onblur="normaliseAirportField(this); updateFlightRoute(this); calcLive()">
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Tujuan Penerbangan</label>
                                    <input type="text" class="input-field airport-input" name="p_flight_destination[]" list="airport-options" placeholder="IATA atau nama bandara" oninput="updateFlightRoute(this); calcLive()" onchange="normaliseAirportField(this); updateFlightRoute(this); calcLive()" onblur="normaliseAirportField(this); updateFlightRoute(this); calcLive()">
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Jarak Penerbangan</label>
                                    <div class="input-with-unit">
                                        <input type="number" class="input-field flight-distance" name="p_flight_km[]" placeholder="0" min="0" step="0.01" readonly>
                                        <span class="input-unit">km</span>
                                    </div>
                                    <span class="input-hint flight-distance-hint">Pilih asal dan tujuan bandara</span>
                                </div>
                                <div class="input-group ef-display-group">
                                    <label class="input-label">Faktor Emisi</label>
                                    <div class="ef-chip" data-ef-group="p_flight">—</div>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-row" data-remove-row title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-row" data-add-row="p_flight">
                        <i class="fas fa-plus"></i> Tambah Rute Penerbangan
                    </button>

                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Penerbangan:</span>
                        <span class="preview-value" id="sub_p_flight">0</span>
                        <span class="preview-unit">kg CO₂e</span>
                    </div>
                </div>
            </div>

            <div class="step-pane-nav">
                <button type="button" class="btn-step-prev" data-switch-tab-prefix="p" data-switch-tab-step="1" data-switch-tab-total="4">
                    <i class="fas fa-arrow-left"></i> Scope 1
                </button>
                <button type="button" class="btn-step-next" data-switch-tab-prefix="p" data-switch-tab-step="3" data-switch-tab-total="4">
                    Scope 3 <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ── PERSONAL STEP 3: PANGAN & SAMPAH ── --}}
        <div class="step-pane" id="p-step-3">

            <div class="scope-label-banner scope3-banner">
                <i class="fas fa-globe"></i>
                Scope 3 — Emisi Tidak Langsung Lainnya
                <span>Transportasi umum, pesawat, konsumsi pangan, penggunaan air, dan limbah</span>
            </div>

            <div id="personal-scope3-modules"></div>

            {{-- PANGAN --}}
            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_food" aria-expanded="false" aria-controls="p_food-content">
                    <div class="module-icon scope-green"><i class="fas fa-utensils"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-green">Scope 3</div>
                        <h2 class="module-title">Produk & Konsumsi Pangan</h2>
                        <p class="module-subtitle">Emisi rantai hulu dari konsumsi makanan</p>
                    </div>
                    <div class="module-preview" id="prev_p_food">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_food-content">

                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi Scope 3</span>
                    </div>

                    <div id="rows_p_food">
                        <div class="entry-row" data-group="p_food">
                            <div class="entry-fields">
                                <div class="input-group">
                                    <label class="input-label">Jenis Komoditas</label>
                                    <select class="input-field select-field" name="p_food_type[]" onchange="calcLive()">
                                        <option value="">-- Pilih Pangan --</option>
                                        <option value="beef">Daging Sapi / Kambing</option>
                                        <option value="poultry">Daging Ayam / Unggas</option>
                                        <option value="fish">Ikan / Seafood</option>
                                        <option value="veg">Sayuran </option>
                                        <option value="egg">Telur</option>
                                        <option value="fruits">Buah-buahan</option>
                                        <option value="dairy">Susu & Produk Susu</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label class="input-label">Berat Konsumsi Bulanan</label>
                                    <div class="input-with-unit">
                                        <input type="number" class="input-field" name="p_food_kg[]" placeholder="0" min="0" step="0.1" oninput="calcLive()">
                                        <span class="input-unit">kg/bln</span>
                                    </div>
                                </div>
                                <div class="input-group ef-display-group">
                                    <label class="input-label">Faktor Emisi</label>
                                    <div class="ef-chip" data-ef-group="p_food">—</div>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-row" data-remove-row title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-row" data-add-row="p_food">
                        <i class="fas fa-plus"></i> Tambah Komoditas
                    </button>

                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Pangan:</span>
                        <span class="preview-value" id="sub_p_food">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            {{-- AIR BERSIH --}}
            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_water" aria-expanded="false" aria-controls="p_water-content">
                    <div class="module-icon scope-blue"><i class="fas fa-tint"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-blue">Scope 3</div>
                        <h2 class="module-title">Penggunaan Air Bersih</h2>
                        <p class="module-subtitle">Air PDAM / sumur terpompa</p>
                    </div>
                    <div class="module-preview" id="prev_p_water">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_water-content">
                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi</span>
                    </div>
                    <div class="input-row-grid">
                        <div class="input-group">
                            <label class="input-label">Volume Penggunaan Air Bulanan</label>
                            <div class="input-with-unit">
                                <input type="number" class="input-field" id="p_water_m3" placeholder="0" min="0" step="0.1" oninput="calcLive()">
                                <span class="input-unit">m³/bln</span>
                            </div>
                        </div>
                        <div class="input-group ef-display-group">
                            <label class="input-label">Faktor Emisi</label>
                            <div class="ef-chip" data-ef-group="p_water">—</div>
                        </div>
                    </div>
                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Air:</span>
                        <span class="preview-value" id="sub_p_water">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            {{-- SAMPAH --}}
            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="p_waste" aria-expanded="false" aria-controls="p_waste-content">
                    <div class="module-icon scope-orange"><i class="fas fa-trash-alt"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-orange">Scope 3</div>
                        <h2 class="module-title">Sampah & Limbah Domestik</h2>
                        <p class="module-subtitle">Sampah campuran RT ke TPA</p>
                    </div>
                    <div class="module-preview" id="prev_p_waste">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="p_waste-content">
                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor emisi</span>
                    </div>
                    <div class="input-row-grid">
                        <div class="input-group">
                            <label class="input-label">Estimasi Berat Sampah Bulanan</label>
                            <div class="input-with-unit">
                                <input type="number" class="input-field" id="p_waste_kg" placeholder="0" min="0" step="0.1" oninput="calcLive()">
                                <span class="input-unit">kg/bln</span>
                            </div>
                        </div>
                        <div class="input-group ef-display-group">
                            <label class="input-label">Faktor Emisi</label>
                            <div class="ef-chip" data-ef-group="p_waste">—</div>
                        </div>
                    </div>
                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Sampah:</span>
                        <span class="preview-value" id="sub_p_waste">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            {{-- HITUNG BUTTON --}}
            <div class="action-buttons">
                <button type="button" class="btn-calculate" data-calculate-mode="personal">
                    <i class="fas fa-calculator"></i> Hitung Total Jejak Karbon
                </button>
                <button type="button" class="btn-reset" data-reset-calculator>
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>

            <div class="step-pane-nav">
                <button type="button" class="btn-step-prev" data-switch-tab-prefix="p" data-switch-tab-step="2" data-switch-tab-total="4">
                    <i class="fas fa-arrow-left"></i> Scope 2
                </button>
                <div></div>
            </div>
        </div>

        @endif {{-- end personal --}}


        {{-- ================================================================
             COMPANY CALCULATOR
             ================================================================ --}}
        @if($userRole === 'company')

        {{-- Step Tab Nav: Company --}}
        <div class="step-tab-nav" id="companyNav">
            <button class="step-tab-btn active" data-step="c1">
                <span class="tab-number">1</span>
                <span class="tab-label">Scope 1</span>
            </button>
            <div class="tab-connector"></div>
            <button class="step-tab-btn" data-step="c2">
                <span class="tab-number">2</span>
                <span class="tab-label">Scope 2</span>
            </button>
            <div class="tab-connector"></div>
            <button class="step-tab-btn" data-step="c3">
                <span class="tab-number">3</span>
                <span class="tab-label">Scope 3</span>
            </button>
            <div class="tab-connector"></div>
            <button class="step-tab-btn" data-step="c4">
                <span class="tab-number">4</span>
                <span class="tab-label">Hasil</span>
            </button>
        </div>

        {{-- ── COMPANY SCOPE 1 ──────────────────────────────────────────── --}}
        <div class="step-pane active" id="c-step-1">

            <div class="scope-label-banner scope1-banner">
                <i class="fas fa-fire"></i>
                Scope 1 — Emisi Langsung
            </div>

            {{-- Scope 1 – Sub Step Tab --}}
            <div class="sub-step-tabs">
                <button class="sub-tab-btn active" data-sub-tab-group="s1" data-sub-tab-key="stat">
                    <i class="fas fa-fire-alt"></i> Step 1: Pembakaran Stasioner
                </button>
                <button class="sub-tab-btn" data-sub-tab-group="s1" data-sub-tab-key="mobile">
                    <i class="fas fa-car-side"></i> Step 2: Pembakaran Bergerak
                </button>
            </div>

            {{-- S1 Sub: Stasioner --}}
            <div class="sub-tab-pane active" id="s1-stat">
                <div class="calc-module">
                    <button type="button" class="module-header" data-module-toggle="c_stat" aria-expanded="false" aria-controls="c_stat-content">
                        <div class="module-icon scope-orange"><i class="fas fa-industry"></i></div>
                        <div class="module-info">
                            <div class="module-badge badge-orange">Stasioner</div>
                            <h2 class="module-title">Pembakaran Stasioner</h2>
                            <p class="module-subtitle">Aset tetap: pabrik, boiler, genset, kantor</p>
                        </div>
                        <div class="module-preview" id="prev_c_stat">0 <span>kg CO₂e</span></div>
                        <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                    </button>
                    <div class="module-content" id="c_stat-content">

                        <div class="ref-info-bar">
                            <i class="fas fa-info-circle"></i>
                            <span>Faktor Emisi Pembakaran Stasioner</span>
                        </div>

                        <div id="rows_c_stat">
                            <div class="entry-row" data-group="c_stat">
                                <div class="entry-fields">
                                    <div class="input-group">
                                        <label class="input-label">Jenis Bahan Bakar</label>
                                        <select class="input-field select-field" name="c_stat_fuel[]" onchange="updateEfChip(this, 'c_stat'); calcLive()">
                                            <option value="">-- Pilih Bahan Bakar --</option>
                                            <option value="solar_cn53">Minyak Solar CN53</option>
                                            <option value="solar_cn51">Minyak Solar CN51</option>
                                            <option value="solar_cn48">Minyak Solar CN48</option>
                                            <option value="diesel">Minyak Diesel</option>
                                            <option value="ron98">Bensin RON98</option>
                                            <option value="ron92">Bensin RON92</option>
                                            <option value="ron90">Bensin RON90</option>
                                            <option value="ron88">Bensin RON88</option>
                                            <option value="lpg">LPG (Liquid Petroleum Gas)</option>
                                            <option value="coal_bit">Batubara (Coal)</option>
                                            <option value="natgas">Gas Alam</option>
                                            <option value="avtur">Avtur</option>
                                            <option value="kerosene">Minyak Tanah</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Konsumsi Bahan Bakar</label>
                                        <div class="input-with-unit">
                                            <input type="number" class="input-field" name="c_stat_qty[]" placeholder="0" min="0" step="0.01" oninput="calcLive()">
                                            <span class="input-unit unit-stat">Nm³</span>
                                        </div>
                                    </div>
                                    <div class="input-group ef-display-group">
                                        <label class="input-label">Faktor Emisi</label>
                                        <div class="ef-chip" data-ef-group="c_stat">—</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-row" data-remove-row title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                            </div>
                        </div>

                        <button type="button" class="btn-add-row" data-add-row="c_stat">
                            <i class="fas fa-plus"></i> Tambah Bahan Bakar
                        </button>

                        <div class="scope-preview">
                            <span class="preview-label">Sub-total Stasioner:</span>
                            <span class="preview-value" id="sub_c_stat">0</span>
                            <span class="preview-unit">kg CO₂e/tahun</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- S1 Sub: Mobile --}}
            <div class="sub-tab-pane" id="s1-mobile">
                <div class="calc-module">
                    <button type="button" class="module-header" data-module-toggle="c_mobile" aria-expanded="false" aria-controls="c_mobile-content">
                        <div class="module-icon scope-orange"><i class="fas fa-truck"></i></div>
                        <div class="module-info">
                            <div class="module-badge badge-orange">Bergerak</div>
                            <h2 class="module-title">Pembakaran Bergerak — Kendaraan Operasional</h2>
                            <p class="module-subtitle">Armada kendaraan yang dimiliki/dikontrol perusahaan</p>
                        </div>
                        <div class="module-preview" id="prev_c_mobile">0 <span>kg CO₂e</span></div>
                        <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                    </button>
                    <div class="module-content" id="c_mobile-content">

                        <div class="ref-info-bar">
                            <i class="fas fa-info-circle"></i>
                            <span>Faktor Emisi Pembakaran Bergerak</span>
                        </div>

                        {{-- Sub-sub toggle: pemakaian BBM vs jarak --}}
                        <div class="method-toggle-row">
                            <button class="method-btn active" id="methodFuelBtn" data-method="fuel">
                                <i class="fas fa-gas-pump"></i> Berdasarkan Pemakaian BBM
                            </button>
                            <button class="method-btn" id="methodDistBtn" data-method="dist">
                                <i class="fas fa-road"></i> Berdasarkan Jarak Tempuh
                            </button>
                        </div>

                        {{-- Method: BBM --}}
                        <div id="method-fuel" class="method-panel active">
                            <div id="rows_c_mobile_fuel">
                                <div class="entry-row" data-group="c_mobile_fuel">
                                    <div class="entry-fields">
                                        <div class="input-group">
                                            <label class="input-label">Jenis Bahan Bakar</label>
                                            <select class="input-field select-field" name="c_mobile_fuel_type[]" onchange="updateEfChip(this,'c_mobile_fuel'); calcLive()">
                                                <option value="">-- Pilih BBM --</option>
                                                <option value="solar_cn53">Minyak Solar CN53</option>
                                                <option value="solar_cn51">Minyak Solar CN51</option>
                                                <option value="solar_cn48">Minyak Solar CN48</option>
                                                <option value="diesel">Minyak Diesel</option>
                                                <option value="ron98">Bensin RON98</option>
                                                <option value="ron92">Bensin RON92</option>
                                                <option value="ron90">Bensin RON90</option>
                                                <option value="ron88">Bensin RON88</option>
                                                <option value="avtur">Avtur (Jet Kerosene)</option>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label class="input-label">Konsumsi BBM</label>
                                            <div class="input-with-unit">
                                                <input type="number" class="input-field" name="c_mobile_fuel_qty[]" placeholder="0" min="0" step="0.01" oninput="calcLive()">
                                                <span class="input-unit">Liter/thn</span>
                                            </div>
                                        </div>
                                        <div class="input-group ef-display-group">
                                            <label class="input-label">Faktor Emisi</label>
                                            <div class="ef-chip" data-ef-group="c_mobile_fuel">—</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-remove-row" data-remove-row aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn-add-row" data-add-row="c_mobile_fuel">
                                <i class="fas fa-plus"></i> Tambah Kendaraan
                            </button>
                        </div>

                        {{-- Method: Jarak --}}
                        <div id="method-dist" class="method-panel">
                            <div id="rows_c_mobile_dist">
                                <div class="entry-row" data-group="c_mobile_dist">
                                    <div class="entry-fields">
                                        <div class="input-group">
                                            <label class="input-label">Jenis Bahan Bakar</label>
                                            <select class="input-field select-field" name="c_mobile_dist_fuel[]" onchange="updateEfChip(this,'c_mobile_dist'); calcLive()">
                                                <option value="">-- Pilih BBM --</option>
                                                <option value="solar_cn53">Minyak Solar CN53</option>
                                                <option value="solar_cn51">Minyak Solar CN51</option>
                                                <option value="solar_cn48">Minyak Solar CN48</option>
                                                <option value="diesel">Minyak Diesel</option>
                                                <option value="ron98">Bensin RON98</option>
                                                <option value="ron92">Bensin RON92</option>
                                                <option value="ron90">Bensin RON90</option>
                                                <option value="ron88">Bensin RON88</option>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label class="input-label">Jarak Tempuh</label>
                                            <div class="input-with-unit">
                                                <input type="number" class="input-field" name="c_mobile_dist_km[]" placeholder="0" min="0" step="1" oninput="calcLive()">
                                                <span class="input-unit">km/thn</span>
                                            </div>
                                        </div>
                                        <div class="input-group ef-display-group">
                                            <label class="input-label">Faktor Emisi</label>
                                            <div class="ef-chip" data-ef-group="c_mobile_dist">—</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-remove-row" data-remove-row aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn-add-row" data-add-row="c_mobile_dist">
                                <i class="fas fa-plus"></i> Tambah Kendaraan
                            </button>
                        </div>

                        <div class="scope-preview">
                            <span class="preview-label">Sub-total Bergerak:</span>
                            <span class="preview-value" id="sub_c_mobile">0</span>
                            <span class="preview-unit">kg CO₂e/tahun</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="step-pane-nav">
                <div></div>
                <button type="button" class="btn-step-next" data-switch-tab-prefix="c" data-switch-tab-step="2" data-switch-tab-total="4">
                    Scope 2 — Listrik <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ── COMPANY SCOPE 2 ──────────────────────────────────────────── --}}
        <div class="step-pane" id="c-step-2">

            <div class="scope-label-banner scope2-banner">
                <i class="fas fa-bolt"></i>
                Scope 2 — Emisi Tidak Langsung Energi
            </div>

            <div class="calc-module">
                <button type="button" class="module-header" data-module-toggle="c_elec" aria-expanded="false" aria-controls="c_elec-content">
                    <div class="module-icon scope-blue"><i class="fas fa-bolt"></i></div>
                    <div class="module-info">
                        <div class="module-badge badge-blue">Scope 2</div>
                        <h2 class="module-title">Listrik PLN</h2>
                        <p class="module-subtitle">Pilih grid wilayah sesuai lokasi perusahaan</p>
                    </div>
                    <div class="module-preview" id="prev_c_elec">0 <span>kg CO₂e</span></div>
                    <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                </button>
                <div class="module-content" id="c_elec-content">

                    <div class="ref-info-bar">
                        <i class="fas fa-info-circle"></i>
                        <span>Faktor Emisi Listrik PLN Grid Jawa-Bali</span>
                    </div>

                    <div id="rows_c_elec">
                        <div class="entry-row" data-group="c_elec">
                            <div class="entry-fields">
                                <div class="input-group">
                                    <label class="input-label">Jumlah Pemakaian</label>
                                    <div class="input-with-unit">
                                        <input type="number" class="input-field" name="c_elec_kwh[]" placeholder="0" min="0" step="0.01" oninput="calcLive()">
                                        <span class="input-unit">kWh/thn</span>
                                    </div>
                                </div>
                                <div class="input-group ef-display-group">
                                    <label class="input-label">Faktor Emisi</label>
                                    <div class="ef-chip" data-ef-group="c_elec">—</div>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-row" data-remove-row aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <button type="button" class="btn-add-row" data-add-row="c_elec">
                        <i class="fas fa-plus"></i> Tambah Sumber Listrik
                    </button>

                    <div class="scope-preview">
                        <span class="preview-label">Sub-total Scope 2:</span>
                        <span class="preview-value" id="sub_c_elec">0</span>
                        <span class="preview-unit">kg CO₂e/tahun</span>
                    </div>
                </div>
            </div>

            <div class="step-pane-nav">
                <button type="button" class="btn-step-prev" data-switch-tab-prefix="c" data-switch-tab-step="1" data-switch-tab-total="4">
                    <i class="fas fa-arrow-left"></i> Scope 1
                </button>
                <button type="button" class="btn-step-next" data-switch-tab-prefix="c" data-switch-tab-step="3" data-switch-tab-total="4">
                    Scope 3 <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ── COMPANY SCOPE 3 ──────────────────────────────────────────── --}}
        <div class="step-pane" id="c-step-3">

            <div class="scope-label-banner scope3-banner">
                <i class="fas fa-globe"></i>
                Scope 3 — Emisi Nilai Rantai
                <span></span>
            </div>

            {{-- Scope 3 Sub-Step Tabs --}}
            <div class="sub-step-tabs">
                <button class="sub-tab-btn active" data-sub-tab-group="s3" data-sub-tab-key="flight">
                    <i class="fas fa-plane"></i> Step 1: Pesawat
                </button>
                <button class="sub-tab-btn" data-sub-tab-group="s3" data-sub-tab-key="hotel">
                    <i class="fas fa-hotel"></i> Step 2: Hotel
                </button>
                <button class="sub-tab-btn" data-sub-tab-group="s3" data-sub-tab-key="train">
                    <i class="fas fa-train"></i> Step 3: Kereta
                </button>
            </div>

            {{-- S3: Pesawat --}}
            <div class="sub-tab-pane active" id="s3-flight">
                <div class="calc-module">
                    <button type="button" class="module-header" data-module-toggle="c_flight" aria-expanded="false" aria-controls="c_flight-content">
                        <div class="module-icon scope-teal"><i class="fas fa-plane"></i></div>
                        <div class="module-info">
                            <div class="module-badge badge-teal">Cat.6 Bisnis</div>
                            <h2 class="module-title">Penerbangan Dinas Karyawan</h2>
                            <p class="module-subtitle">Penerbangan dinas karyawan</p>
                        </div>
                        <div class="module-preview" id="prev_c_flight">0 <span>kg CO₂e</span></div>
                        <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                    </button>
                    <div class="module-content" id="c_flight-content">
                        <div class="ref-info-bar">
                            <i class="fas fa-info-circle"></i>
                            <span>Faktor Emisi Penerbangan</span>
                        </div>
                        <div id="rows_c_flight">
                            <div class="entry-row" data-group="c_flight">
                                <div class="entry-fields">
                                    <div class="input-group">
                                        <label class="input-label">Kelas Kabin</label>
                                        <select class="input-field select-field" name="c_flight_class[]" onchange="updateEfChip(this,'c_flight'); calcLive()">
                                            <option value="">-- Pilih Kelas --</option>
                                            <option value="economy">Ekonomi</option>
                                            <option value="business">Bisnis</option>
                                            <option value="first">First Class</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Jumlah Pax</label>
                                        <input type="number" class="input-field" name="c_flight_pax[]" placeholder="0" min="0" step="1" oninput="calcLive()">
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Asal Penerbangan</label>
                                        <input type="text" class="input-field airport-input" name="c_flight_origin[]" list="airport-options" placeholder="IATA atau nama bandara" oninput="updateFlightRoute(this); calcLive()" onchange="normaliseAirportField(this); updateFlightRoute(this); calcLive()" onblur="normaliseAirportField(this); updateFlightRoute(this); calcLive()">
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Tujuan Penerbangan</label>
                                        <input type="text" class="input-field airport-input" name="c_flight_destination[]" list="airport-options" placeholder="IATA atau nama bandara" oninput="updateFlightRoute(this); calcLive()" onchange="normaliseAirportField(this); updateFlightRoute(this); calcLive()" onblur="normaliseAirportField(this); updateFlightRoute(this); calcLive()">
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Jarak Penerbangan</label>
                                        <div class="input-with-unit">
                                            <input type="number" class="input-field flight-distance" name="c_flight_km[]" placeholder="0" min="0" step="0.01" readonly>
                                            <span class="input-unit">km</span>
                                        </div>
                                        <span class="input-hint flight-distance-hint">Pilih asal dan tujuan bandara</span>
                                    </div>
                                    <div class="input-group ef-display-group">
                                        <label class="input-label">Faktor Emisi</label>
                                        <div class="ef-chip" data-ef-group="c_flight">—</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-row" data-remove-row aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-row" data-add-row="c_flight">
                            <i class="fas fa-plus"></i> Tambah Rute Penerbangan
                        </button>
                        <div class="scope-preview">
                            <span class="preview-label">Sub-total Pesawat:</span>
                            <span class="preview-value" id="sub_c_flight">0</span>
                            <span class="preview-unit">kg CO₂e</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- S3: Hotel --}}
            <div class="sub-tab-pane" id="s3-hotel">
                <div class="calc-module">
                    <button type="button" class="module-header" data-module-toggle="c_hotel" aria-expanded="false" aria-controls="c_hotel-content">
                        <div class="module-icon scope-teal"><i class="fas fa-hotel"></i></div>
                        <div class="module-info">
                            <div class="module-badge badge-teal">Cat.6 Hotel</div>
                            <h2 class="module-title">Akomodasi Hotel</h2>
                            <p class="module-subtitle">Penginapan perjalanan dinas</p>
                        </div>
                        <div class="module-preview" id="prev_c_hotel">0 <span>kg CO₂e</span></div>
                        <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                    </button>
                    <div class="module-content" id="c_hotel-content">
                        <div class="ref-info-bar">
                            <i class="fas fa-info-circle"></i>
                            <span>GHG Protocol Scope 3 Cat.6 · EF: 49,37 kgCO₂e/kamar·malam (Bank Indonesia 2026, DEFRA 2025)</span>
                        </div>
                        <div id="rows_c_hotel">
                            <div class="entry-row" data-group="c_hotel">
                                <div class="entry-fields">
                                    <div class="input-group">
                                        <label class="input-label">Jumlah Hari Menginap</label>
                                        <div class="input-with-unit">
                                            <input type="number" class="input-field" name="c_hotel_nights[]" placeholder="0" min="0" step="1" oninput="calcLive()">
                                            <span class="input-unit">malam</span>
                                        </div>
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Jumlah Kamar</label>
                                        <input type="number" class="input-field" name="c_hotel_rooms[]" placeholder="0" min="0" step="1" oninput="calcLive()">
                                    </div>
                                    <div class="input-group ef-display-group">
                                        <label class="input-label">Faktor Emisi</label>
                                        <div class="ef-chip">49,37 kgCO₂e/kamar·malam<br><small>Bank Indonesia 2026 · DEFRA 2025</small></div>
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-row" data-remove-row aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-row" data-add-row="c_hotel">
                            <i class="fas fa-plus"></i> Tambah Hotel
                        </button>
                        <div class="scope-preview">
                            <span class="preview-label">Sub-total Hotel:</span>
                            <span class="preview-value" id="sub_c_hotel">0</span>
                            <span class="preview-unit">kg CO₂e</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- S3: Kereta --}}
            <div class="sub-tab-pane" id="s3-train">
                <div class="calc-module">
                    <button type="button" class="module-header" data-module-toggle="c_train" aria-expanded="false" aria-controls="c_train-content">
                        <div class="module-icon scope-teal"><i class="fas fa-train"></i></div>
                        <div class="module-info">
                            <div class="module-badge badge-teal">Cat.6 Kereta</div>
                            <h2 class="module-title">Perjalanan Kereta</h2>
                            <p class="module-subtitle">Perjalanan dinas via kereta api</p>
                        </div>
                        <div class="module-preview" id="prev_c_train">0 <span>kg CO₂e</span></div>
                        <div class="module-toggle"><i class="fas fa-chevron-down"></i></div>
                    </button>
                    <div class="module-content" id="c_train-content">
                        <div class="ref-info-bar">
                            <i class="fas fa-info-circle"></i>
                            <span>GHG Protocol Scope 3 Cat.6 · Buku II Vol I KLHK 2012 · kgCO₂e/penumpang·km</span>
                        </div>
                        <div id="rows_c_train">
                            <div class="entry-row" data-group="c_train">
                                <div class="entry-fields">
                                    <div class="input-group">
                                        <label class="input-label">Kelas Kereta</label>
                                        <select class="input-field select-field" name="c_train_class[]" onchange="updateEfChip(this,'c_train'); calcLive()">
                                            <option value="">-- Pilih Kelas --</option>
                                            <option value="ekonomi">Ekonomi</option>
                                            <option value="bisnis">Bisnis</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label class="input-label">Jarak Tempuh</label>
                                        <div class="input-with-unit">
                                            <input type="number" class="input-field" name="c_train_km[]" placeholder="0" min="0" step="1" oninput="calcLive()">
                                            <span class="input-unit">km</span>
                                        </div>
                                    </div>
                                    <div class="input-group ef-display-group">
                                        <label class="input-label">Faktor Emisi</label>
                                        <div class="ef-chip" data-ef-group="c_train">—</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-row" data-remove-row aria-label="Hapus baris"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-row" data-add-row="c_train">
                            <i class="fas fa-plus"></i> Tambah Perjalanan Kereta
                        </button>
                        <div class="scope-preview">
                            <span class="preview-label">Sub-total Kereta:</span>
                            <span class="preview-value" id="sub_c_train">0</span>
                            <span class="preview-unit">kg CO₂e</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="button" class="btn-calculate" data-calculate-mode="company">
                    <i class="fas fa-calculator"></i> Hitung Total Emisi Perusahaan
                </button>
                <button type="button" class="btn-reset" data-reset-calculator>
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>

            <div class="step-pane-nav">
                <button type="button" class="btn-step-prev" data-switch-tab-prefix="c" data-switch-tab-step="2" data-switch-tab-total="4">
                    <i class="fas fa-arrow-left"></i> Scope 2
                </button>
                <button type="button" class="btn-step-next" data-switch-tab-prefix="c" data-switch-tab-step="4" data-switch-tab-total="4">
                    Lihat Hasil <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ── COMPANY STEP 4: HASIL ──────────────────────────────────── --}}
        <div class="step-pane" id="c-step-4">
            <div class="scope-label-banner result-banner">
                <i class="fas fa-chart-bar"></i>
                Hasil Kalkulasi Emisi Perusahaan
            </div>

            {{-- Result placeholder (filled by JS) --}}
            <div id="result-box-company" class="result-box"></div>

            <div class="step-pane-nav">
                <button type="button" class="btn-step-prev" data-switch-tab-prefix="c" data-switch-tab-step="3" data-switch-tab-total="4">
                    <i class="fas fa-arrow-left"></i> Scope 3
                </button>
                <div></div>
            </div>
        </div>

        @endif {{-- end company --}}

        {{-- ── SHARED RESULT BOX (personal) ───────────────────────────── --}}
        @if($userRole === 'personal')
{{-- ── SHARED RESULT BOX (Dinamis & Interaktif) ───────────────────────────── --}}
        <div class="step-pane" id="p-step-4">
        <div class="scope-label-banner result-banner">
            <i class="fas fa-chart-bar"></i>
            Hasil Kalkulasi Emisi Individu
            <span>Ringkasan tahunan berdasarkan seluruh data Scope 1, 2, dan 3</span>
        </div>
        <div class="result-box" id="result-box" style="display:none; margin-top: 30px;">
            <h3 class="result-title"><i class="fas fa-chart-bar"></i> Hasil Kalkulasi Emisi Anda</h3>

            {{-- Grid Angka Ringkasan --}}
            <div class="result-grid">
                <div class="result-item">
                    <div class="result-label">Total Emisi Anda</div>
                    <div class="result-value" id="hasil-emisi">0 kg CO₂e</div>
                    <div class="result-unit">Berdasarkan data input</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Estimasi Biaya Offset</div>
                    <div class="result-value" id="hasil-biaya" style="color: #124170;">Rp 0</div>
                    <div class="result-unit">Mulai dari Rp 150.000 / ton</div>
                </div>
                <div class="result-item result-item-total">
                    <div class="result-label">Rekomendasi Proyek</div>
                    <div class="result-value" id="rekomendasi-proyek" style="font-size: 1.15rem; padding-top: 10px; color: #26667F;">-</div>
                    <div class="result-unit">Proyek mitigasi terverifikasi</div>
                </div>
            </div>

            {{-- Diagram Distribusi Emisi per Kategori --}}
            <div class="distribution-section" id="distribution-personal">
                <div class="distribution-title">Distribusi Emisi per Kategori</div>
                <div id="distribution-bars"></div>
            </div>

            {{-- Footer Informasi Pohon Setara --}}
            <div class="result-footer" id="result-footer-personal">
                <p class="result-info">
                    Setara dengan <strong id="tree-equivalent">0</strong> pohon yang harus ditanam untuk mengompensasi emisi ini selama 30 tahun (Asumsi serapan 21.77 kg CO₂/pohon/tahun).
                </p>
            </div>

            {{-- KONTROL E-COMMERCE: Tombol Aksi Pembelian & Penyimpanan --}}
            <div class="save-action">
                @Auth
                    <button type="button" class="btn-calculate" data-save-calculation style="display:inline-flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <i class="fas fa-cloud-upload-alt"></i> Simpan Hasil ke Dashboard
                    </button>
                @else
                    {{-- Jika statusnya Guest, kunci tombol simpan dan arahkan ke halaman register/login --}}
                    <a href="{{ route('register') }}" class="btn-calculate" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; background: linear-gradient(135deg, #E8734A, #c05a2e);">
                        <i class="fas fa-user-plus"></i> Daftar Akun untuk Simpan Hasil
                    </a>
                @endauth
                
                <a href="{{ route('projects.index') }}" class="btn-calculate" style="text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-seedling"></i> Cari Proyek & Lakukan Offset
                </a>
                <button type="button" class="btn-reset" data-reset-calculator>
                    <i class="fas fa-redo"></i> Hitung Ulang
                </button>
            </div>

            <div class="result-disclaimer">
                <i class="fas fa-info-circle"></i>
                Kalkulasi menggunakan faktor emisi dari GHG Protocol, ISO 14064-1:2018, IPCC 2006 Guidelines, dan Pedoman Inventarisasi GRK Nasional Buku II Volume I KLHK 2012.
            </div>
        </div>
        <div class="step-pane-nav">
            <button type="button" class="btn-step-prev" data-switch-tab-prefix="p" data-switch-tab-step="3" data-switch-tab-total="4">
                <i class="fas fa-arrow-left"></i> Scope 3
            </button>
            <div></div>
        </div>
        </div>
        @endif

    </div>
</main>

@endsection

@push('scripts')
<script>
    // Definisikan key SEBELUM file calculator.js dimuat agar terbaca sempurna
    window.CARBON_STORAGE_KEY = @auth "{{ 'carbon_history_user_' . Auth::id() }}" @else null @endauth;
</script>

<script src="{{ asset('js/calculator.js') }}"></script>
@endpush
