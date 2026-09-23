import re
from typing import Any, Dict, List, Text

from rasa_sdk import Action, FormValidationAction, Tracker
from rasa_sdk.events import AllSlotsReset, FollowupAction, SlotSet
from rasa_sdk.executor import CollectingDispatcher


PERSONAL_SLOTS = [
    "personal_energy_fuel",
    "personal_energy_qty",
    "personal_vehicle_type",
    "personal_vehicle_fuel",
    "personal_vehicle_km",
    "personal_electricity_kwh",
    "personal_transit_mode",
    "personal_transit_km",
    "personal_food_type",
    "personal_food_kg",
    "personal_water_m3",
    "personal_waste_kg",
]

COMPANY_SLOTS = [
    "company_stationary_fuel",
    "company_stationary_qty",
    "company_mobile_method",
    "company_mobile_fuel",
    "company_mobile_fuel_qty",
    "company_mobile_distance_fuel",
    "company_mobile_km",
    "company_electricity_grid",
    "company_electricity_kwh",
    "company_flight_class",
    "company_flight_pax",
    "company_flight_origin",
    "company_flight_destination",
    "company_hotel_nights",
    "company_hotel_rooms",
    "company_train_class",
    "company_train_km",
]

CALCULATION_SLOTS = PERSONAL_SLOTS + COMPANY_SLOTS
NONE_ALIASES = ["none", "tidak ada", "tidak menggunakan", "lewati", "skip", "nol", "0"]

ELECTRICITY_REGION_ALIASES = {
    "jawa_bali": [
        "jawa bali", "jawa", "bali", "jakarta", "dki jakarta", "jakarta pusat",
        "jakarta utara", "jakarta selatan", "jakarta timur", "jakarta barat",
        "banten", "tangerang", "serang", "jawa barat", "bandung", "bekasi",
        "bogor", "depok", "cirebon", "tasikmalaya", "jawa tengah", "semarang",
        "surakarta", "solo", "yogyakarta", "jogja", "jawa timur", "surabaya",
        "malang", "kediri", "madiun", "jember", "banyuwangi", "denpasar",
    ],
    "sumatra": [
        "sumatra", "sumatera", "aceh", "banda aceh", "sumatera utara", "medan",
        "riau", "pekanbaru", "kepulauan riau", "kepri", "batam", "sumatera barat",
        "padang", "jambi", "bengkulu", "sumatera selatan", "palembang", "lampung",
        "bandar lampung", "bangka belitung", "pangkal pinang",
    ],
    "kalimantan": [
        "kalimantan", "kalimantan barat", "pontianak", "kalimantan tengah",
        "palangkaraya", "palangka raya", "kalimantan selatan", "banjarmasin",
        "kalimantan timur", "samarinda", "balikpapan", "kalimantan utara", "tarakan",
    ],
    "sulawesi": [
        "sulawesi", "sulawesi utara", "manado", "gorontalo", "sulawesi tengah",
        "palu", "sulawesi barat", "mamuju", "sulawesi selatan", "makassar",
        "sulawesi tenggara", "kendari",
    ],
}


def _normalise(value: Any) -> str:
    text = str(value).strip().casefold()
    text = text.replace("_", " ").replace("-", " ").replace("–", " ")
    return re.sub(r"\s+", " ", text)


def _option_result(
    slot_name: str,
    slot_value: Any,
    options: Dict[str, List[str]],
    dispatcher: CollectingDispatcher,
    error_message: str,
) -> Dict[str, Any]:
    if slot_value is None:
        return {slot_name: None}

    value = _normalise(slot_value)
    for canonical, aliases in options.items():
        candidates = [_normalise(canonical), *[_normalise(alias) for alias in aliases]]
        if value in candidates:
            return {slot_name: canonical}

    dispatcher.utter_message(text=error_message)
    return {slot_name: None}


def _number_result(
    slot_name: str,
    slot_value: Any,
    dispatcher: CollectingDispatcher,
    unit_examples: str,
    integer: bool = False,
) -> Dict[str, Any]:
    if slot_value is None:
        return {slot_name: None}

    value = str(slot_value).strip().casefold()
    match = re.fullmatch(
        r"([0-9]+(?:[.,][0-9]+)*)\s*"
        r"(?:kwh|km|kg|m3|m³|liter|l|orang|penumpang|malam|kamar)?",
        value,
    )
    if not match:
        dispatcher.utter_message(
            text=f"Masukkan angka nol atau positif. Contoh: {unit_examples}."
        )
        return {slot_name: None}

    raw_number = match.group(1)
    if "." in raw_number and "," in raw_number:
        normalised_number = raw_number.replace(".", "").replace(",", ".")
    elif "." in raw_number and all(
        len(group) == 3 for group in raw_number.split(".")[1:]
    ):
        normalised_number = raw_number.replace(".", "")
    else:
        normalised_number = raw_number.replace(",", ".")

    number = float(normalised_number)
    if integer and not number.is_integer():
        dispatcher.utter_message(text="Nilai ini harus berupa bilangan bulat.")
        return {slot_name: None}

    return {slot_name: int(number) if integer else number}


def _airport_code_result(
    slot_name: str,
    slot_value: Any,
    dispatcher: CollectingDispatcher,
) -> Dict[str, Any]:
    if slot_value is None:
        return {slot_name: None}

    value = re.sub(r"\s+", " ", str(slot_value).strip())
    code_match = re.fullmatch(r"([A-Za-z]{3})(?:\s*-\s*.+)?", value)
    if code_match:
        return {slot_name: code_match.group(1).upper()}

    if len(value) < 2 or len(value) > 120 or not any(char.isalpha() for char in value):
        dispatcher.utter_message(
            text=(
                "Masukkan kode IATA, nama kota/daerah, atau nama bandara. "
                "Contoh: CGK, Jakarta, atau Bandara Soekarno-Hatta."
            )
        )
        return {slot_name: None}

    return {slot_name: value}


def _electricity_grid_result(
    slot_name: str,
    slot_value: Any,
    dispatcher: CollectingDispatcher,
) -> Dict[str, Any]:
    if slot_value is None:
        return {slot_name: None}

    value = _normalise(slot_value)
    if value in [_normalise(alias) for alias in NONE_ALIASES]:
        return {slot_name: "none"}

    if value in {"lainnya", "daerah lain", "wilayah lain", "kota lain"}:
        dispatcher.utter_message(
            text="Silakan tulis nama kota, kabupaten, provinsi, atau pulaunya. Contoh: Jakarta, Palembang, atau Kalimantan Timur."
        )
        return {slot_name: None}

    value = re.sub(r"^(?:kota|kabupaten|provinsi|pulau)\s+", "", value)
    for canonical, aliases in ELECTRICITY_REGION_ALIASES.items():
        normalised_aliases = sorted(
            {_normalise(alias) for alias in aliases}, key=len, reverse=True
        )
        if value in normalised_aliases:
            return {slot_name: canonical}
        if any(re.search(rf"\b{re.escape(alias)}\b", value) for alias in normalised_aliases):
            return {slot_name: canonical}

    dispatcher.utter_message(
        text=(
            "Daerah itu belum dapat dipetakan. Tulis nama provinsi/kota lain atau "
            "pilih jaringan Jawa-Bali, Sumatra, Kalimantan, atau Sulawesi."
        )
    )
    return {slot_name: None}


def _without(slots: List[Text], *removed: str) -> List[Text]:
    return [slot for slot in slots if slot not in removed]


def _reset_events() -> List[Any]:
    return [AllSlotsReset()]


class ActionStartAccountCalculation(Action):
    def name(self) -> Text:
        return "action_start_account_calculation"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Any]:
        metadata = tracker.latest_message.get("metadata") or {}
        account_type = metadata.get("account_type")

        if (
            not metadata.get("authenticated")
            or metadata.get("role") != "buyer"
            or account_type not in {"personal", "company"}
        ):
            dispatcher.utter_message(
                text=(
                    "Silakan masuk menggunakan akun buyer individu atau perusahaan "
                    "agar Cami dapat memilih kalkulator dan menyimpan hasilmu."
                )
            )
            return _reset_events()

        events: List[Any] = _reset_events()
        events.extend([
            SlotSet("account_type", account_type),
        ])

        if account_type == "company":
            dispatcher.utter_message(
                text=(
                    "Akun perusahaan terdeteksi. Cami akan mengumpulkan data "
                    "Scope 1, Scope 2, dan Scope 3. Pilih “Tidak ada” atau masukkan "
                    "0 untuk aktivitas yang tidak digunakan."
                )
            )
            events.append(FollowupAction("company_calculation_form"))
        else:
            dispatcher.utter_message(
                text=(
                    "Akun individu terdeteksi. Cami akan menghitung energi rumah "
                    "tangga, kendaraan, listrik, transportasi umum, pangan, air, "
                    "dan sampah dalam periode tahunan."
                )
            )
            events.append(FollowupAction("personal_calculation_form"))

        return events


class ValidatePersonalCalculationForm(FormValidationAction):
    def name(self) -> Text:
        return "validate_personal_calculation_form"

    async def required_slots(
        self,
        domain_slots: List[Text],
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Text]:
        slots = list(domain_slots)
        if tracker.get_slot("personal_energy_fuel") == "none":
            slots = _without(slots, "personal_energy_qty")
        if tracker.get_slot("personal_vehicle_type") == "none":
            slots = _without(slots, "personal_vehicle_fuel", "personal_vehicle_km")
        if tracker.get_slot("personal_transit_mode") == "none":
            slots = _without(slots, "personal_transit_km")
        if tracker.get_slot("personal_food_type") == "none":
            slots = _without(slots, "personal_food_kg")
        return slots

    def validate_personal_energy_fuel(self, value, dispatcher, tracker, domain):
        return _option_result(
            "personal_energy_fuel",
            value,
            {
                "lpg": ["gas tabung"],
                "cng": ["gas alam", "gas pipa"],
                "wood": ["kayu", "kayu bakar", "biomassa"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih LPG, CNG, kayu bakar, atau Tidak ada.",
        )

    def validate_personal_energy_qty(self, value, dispatcher, tracker, domain):
        return _number_result("personal_energy_qty", value, dispatcher, "12 kg")

    def validate_personal_vehicle_type(self, value, dispatcher, tracker, domain):
        return _option_result(
            "personal_vehicle_type",
            value,
            {
                "car_petrol": ["mobil bensin", "mobil pribadi bensin"],
                "car_diesel": ["mobil diesel", "mobil pribadi diesel"],
                "motorcycle": ["motor", "sepeda motor"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih Mobil bensin, Mobil diesel, Sepeda motor, atau Tidak ada.",
        )

    def validate_personal_vehicle_fuel(self, value, dispatcher, tracker, domain):
        if value is not None and _normalise(value) in {"diesel", "solar", "minyak diesel"}:
            if tracker.get_slot("personal_vehicle_type") != "car_diesel":
                dispatcher.utter_message(
                    text="Diesel hanya untuk Mobil diesel. Pilih RON atau Listrik untuk kendaraan ini."
                )
                return {"personal_vehicle_fuel": None}
        return _option_result(
            "personal_vehicle_fuel",
            value,
            {
                "ron98": ["ron 98", "bensin ron98"],
                "ron92": ["ron 92", "pertamax", "bensin ron92"],
                "ron90": ["ron 90", "pertalite", "bensin ron90"],
                "ron88": ["ron 88", "premium", "bensin ron88"],
                "diesel": ["solar", "minyak diesel"],
                "listrik": ["kendaraan listrik"],
            },
            dispatcher,
            "Pilih RON98, RON92, RON90, RON88, Diesel, atau Listrik.",
        )

    def validate_personal_vehicle_km(self, value, dispatcher, tracker, domain):
        return _number_result("personal_vehicle_km", value, dispatcher, "500 km")

    def validate_personal_electricity_kwh(self, value, dispatcher, tracker, domain):
        return _number_result("personal_electricity_kwh", value, dispatcher, "155 kWh")

    def validate_personal_transit_mode(self, value, dispatcher, tracker, domain):
        return _option_result(
            "personal_transit_mode",
            value,
            {
                "flight_dom": ["pesawat domestik", "penerbangan domestik"],
                "flight_int_short": ["pesawat internasional pendek"],
                "flight_int_long": ["pesawat internasional panjang"],
                "train": ["kereta", "krl", "kereta api"],
                "bus": ["bus", "angkot", "taksi", "ojek online"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih Pesawat domestik, Pesawat internasional, Kereta, Bus, atau Tidak ada.",
        )

    def validate_personal_transit_km(self, value, dispatcher, tracker, domain):
        return _number_result("personal_transit_km", value, dispatcher, "120 km")

    def validate_personal_food_type(self, value, dispatcher, tracker, domain):
        return _option_result(
            "personal_food_type",
            value,
            {
                "beef": ["sapi", "kambing", "daging sapi"],
                "poultry": ["ayam", "unggas", "daging ayam"],
                "fish": ["ikan", "seafood"],
                "veg": ["sayur", "sayuran", "buah", "nabati"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih Sapi/kambing, Ayam, Ikan, Nabati, atau Tidak ada.",
        )

    def validate_personal_food_kg(self, value, dispatcher, tracker, domain):
        return _number_result("personal_food_kg", value, dispatcher, "5 kg")

    def validate_personal_water_m3(self, value, dispatcher, tracker, domain):
        return _number_result("personal_water_m3", value, dispatcher, "10 m3")

    def validate_personal_waste_kg(self, value, dispatcher, tracker, domain):
        return _number_result("personal_waste_kg", value, dispatcher, "20 kg")


class ValidateCompanyCalculationForm(FormValidationAction):
    def name(self) -> Text:
        return "validate_company_calculation_form"

    async def required_slots(
        self,
        domain_slots: List[Text],
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Text]:
        slots = list(domain_slots)
        if tracker.get_slot("company_stationary_fuel") == "none":
            slots = _without(slots, "company_stationary_qty")

        method = tracker.get_slot("company_mobile_method")
        if method == "none":
            slots = _without(
                slots,
                "company_mobile_fuel",
                "company_mobile_fuel_qty",
                "company_mobile_distance_fuel",
                "company_mobile_km",
            )
        elif method == "fuel":
            slots = _without(slots, "company_mobile_distance_fuel", "company_mobile_km")
        elif method == "distance":
            slots = _without(slots, "company_mobile_fuel", "company_mobile_fuel_qty")

        if tracker.get_slot("company_electricity_grid") == "none":
            slots = _without(slots, "company_electricity_kwh")
        if tracker.get_slot("company_flight_class") == "none":
            slots = _without(
                slots,
                "company_flight_pax",
                "company_flight_origin",
                "company_flight_destination",
            )
        if float(tracker.get_slot("company_hotel_nights") or 0) == 0:
            slots = _without(slots, "company_hotel_rooms")
        if tracker.get_slot("company_train_class") == "none":
            slots = _without(slots, "company_train_km")
        return slots

    def validate_company_stationary_fuel(self, value, dispatcher, tracker, domain):
        return _option_result(
            "company_stationary_fuel",
            value,
            {
                "solar_cn53": ["solar cn53", "cn53"],
                "solar_cn51": ["solar cn51", "cn51"],
                "solar_cn48": ["solar cn48", "cn48", "solar"],
                "diesel": ["minyak diesel"],
                "ron98": ["ron 98"],
                "ron92": ["ron 92", "pertamax"],
                "ron90": ["ron 90", "pertalite"],
                "ron88": ["ron 88", "premium"],
                "lpg": ["gas lpg"],
                "coal_bit": ["batubara", "bituminous coal"],
                "coal_briket": ["briket", "briket batubara"],
                "charcoal": ["arang"],
                "natgas": ["gas alam"],
                "lgv": ["gas kendaraan"],
                "lng": ["gas alam cair"],
                "avtur": ["jet kerosene"],
                "kerosene": ["minyak tanah"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih bahan bakar yang tersedia pada tombol atau pilih Tidak ada.",
        )

    def validate_company_stationary_qty(self, value, dispatcher, tracker, domain):
        return _number_result("company_stationary_qty", value, dispatcher, "1000 liter")

    def validate_company_mobile_method(self, value, dispatcher, tracker, domain):
        return _option_result(
            "company_mobile_method",
            value,
            {
                "fuel": ["konsumsi bbm", "bbm", "liter"],
                "distance": ["jarak", "kilometer", "km"],
                "both": ["keduanya", "bbm dan jarak"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih Berdasarkan BBM, Berdasarkan jarak, Keduanya, atau Tidak ada.",
        )

    def validate_company_mobile_fuel(self, value, dispatcher, tracker, domain):
        return _option_result(
            "company_mobile_fuel",
            value,
            {
                "solar_cn53": ["solar cn53", "cn53"],
                "solar_cn51": ["solar cn51", "cn51"],
                "solar_cn48": ["solar cn48", "cn48", "solar"],
                "diesel": ["minyak diesel"],
                "ron98": ["ron 98"],
                "ron92": ["ron 92", "pertamax"],
                "ron90": ["ron 90", "pertalite"],
                "ron88": ["ron 88", "premium"],
                "avtur": ["jet kerosene"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih Solar, Diesel, RON98, RON92, RON90, RON88, Avtur, atau Tidak ada.",
        )

    def validate_company_mobile_fuel_qty(self, value, dispatcher, tracker, domain):
        return _number_result("company_mobile_fuel_qty", value, dispatcher, "2000 liter")

    def validate_company_mobile_distance_fuel(self, value, dispatcher, tracker, domain):
        return _option_result(
            "company_mobile_distance_fuel",
            value,
            {
                "solar_cn53": ["solar cn53", "cn53"],
                "solar_cn51": ["solar cn51", "cn51"],
                "solar_cn48": ["solar cn48", "cn48", "solar"],
                "diesel": ["minyak diesel"],
                "ron98": ["ron 98"],
                "ron92": ["ron 92", "pertamax"],
                "ron90": ["ron 90", "pertalite"],
                "ron88": ["ron 88", "premium"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih Solar, Diesel, RON98, RON92, RON90, RON88, atau Tidak ada.",
        )

    def validate_company_mobile_km(self, value, dispatcher, tracker, domain):
        return _number_result("company_mobile_km", value, dispatcher, "12000 km")

    def validate_company_electricity_grid(self, value, dispatcher, tracker, domain):
        return _electricity_grid_result("company_electricity_grid", value, dispatcher)

    def validate_company_electricity_kwh(self, value, dispatcher, tracker, domain):
        return _number_result("company_electricity_kwh", value, dispatcher, "50000 kWh")

    def validate_company_flight_class(self, value, dispatcher, tracker, domain):
        return _option_result(
            "company_flight_class",
            value,
            {
                "economy": ["ekonomi"],
                "business": ["bisnis"],
                "first": ["first class", "kelas satu"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih Ekonomi, Bisnis, First Class, atau Tidak ada.",
        )

    def validate_company_flight_pax(self, value, dispatcher, tracker, domain):
        return _number_result(
            "company_flight_pax", value, dispatcher, "10 orang", integer=True
        )

    def validate_company_flight_origin(self, value, dispatcher, tracker, domain):
        return _airport_code_result("company_flight_origin", value, dispatcher)

    def validate_company_flight_destination(self, value, dispatcher, tracker, domain):
        return _airport_code_result("company_flight_destination", value, dispatcher)

    def validate_company_hotel_nights(self, value, dispatcher, tracker, domain):
        return _number_result(
            "company_hotel_nights", value, dispatcher, "10 malam", integer=True
        )

    def validate_company_hotel_rooms(self, value, dispatcher, tracker, domain):
        return _number_result(
            "company_hotel_rooms", value, dispatcher, "5 kamar", integer=True
        )

    def validate_company_train_class(self, value, dispatcher, tracker, domain):
        return _option_result(
            "company_train_class",
            value,
            {
                "ekonomi": [],
                "bisnis": [],
                "eksekutif": [],
                "panoramic": [],
                "luxury": ["mewah"],
                "priority": ["prioritas"],
                "compartment": ["kompartemen"],
                "none": NONE_ALIASES,
            },
            dispatcher,
            "Pilih kelas kereta yang tersedia atau Tidak ada.",
        )

    def validate_company_train_km(self, value, dispatcher, tracker, domain):
        return _number_result("company_train_km", value, dispatcher, "800 km")


class ActionSubmitCalculation(Action):
    def name(self) -> Text:
        return "action_submit_calculation"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Any]:
        account_type = tracker.get_slot("account_type")
        if account_type not in {"personal", "company"}:
            dispatcher.utter_message(
                text="Konteks akun tidak ditemukan. Silakan mulai perhitungan kembali."
            )
            return []

        if account_type == "company":
            data = {
                "stationary_fuel": tracker.get_slot("company_stationary_fuel") or "none",
                "stationary_qty": tracker.get_slot("company_stationary_qty") or 0,
                "mobile_fuel": tracker.get_slot("company_mobile_fuel") or "none",
                "mobile_fuel_qty": tracker.get_slot("company_mobile_fuel_qty") or 0,
                "mobile_distance_fuel": tracker.get_slot("company_mobile_distance_fuel") or "none",
                "mobile_km": tracker.get_slot("company_mobile_km") or 0,
                "electricity_grid": tracker.get_slot("company_electricity_grid") or "none",
                "electricity_kwh": tracker.get_slot("company_electricity_kwh") or 0,
                "flight_class": tracker.get_slot("company_flight_class") or "none",
                "flight_pax": tracker.get_slot("company_flight_pax") or 0,
                "flight_origin": tracker.get_slot("company_flight_origin") or "",
                "flight_destination": tracker.get_slot("company_flight_destination") or "",
                "hotel_nights": tracker.get_slot("company_hotel_nights") or 0,
                "hotel_rooms": tracker.get_slot("company_hotel_rooms") or 0,
                "train_class": tracker.get_slot("company_train_class") or "none",
                "train_km": tracker.get_slot("company_train_km") or 0,
            }
        else:
            data = {
                "energy_fuel": tracker.get_slot("personal_energy_fuel") or "none",
                "energy_qty": tracker.get_slot("personal_energy_qty") or 0,
                "vehicle_type": tracker.get_slot("personal_vehicle_type") or "none",
                "vehicle_fuel": tracker.get_slot("personal_vehicle_fuel") or "none",
                "vehicle_km": tracker.get_slot("personal_vehicle_km") or 0,
                "electricity_kwh": tracker.get_slot("personal_electricity_kwh") or 0,
                "transit_mode": tracker.get_slot("personal_transit_mode") or "none",
                "transit_km": tracker.get_slot("personal_transit_km") or 0,
                "food_type": tracker.get_slot("personal_food_type") or "none",
                "food_kg": tracker.get_slot("personal_food_kg") or 0,
                "water_m3": tracker.get_slot("personal_water_m3") or 0,
                "waste_kg": tracker.get_slot("personal_waste_kg") or 0,
            }

        dispatcher.utter_message(
            text="Data aktivitas sudah lengkap dan siap dihitung oleh CAMAR.",
            json_message={
                "type": "calculation_request",
                "account_type": account_type,
                "data": data,
            },
        )
        return []


class ActionResetCalculation(Action):
    def name(self) -> Text:
        return "action_reset_calculation"

    def run(self, dispatcher, tracker, domain):
        return _reset_events()


class ActionRecommendProjects(Action):
    def name(self) -> Text:
        return "action_recommend_projects"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any],
    ) -> List[Any]:
        metadata = tracker.latest_message.get("metadata") or {}
        if (
            not metadata.get("authenticated")
            or metadata.get("role") != "buyer"
            or metadata.get("account_type") not in {"personal", "company"}
        ):
            dispatcher.utter_message(
                text="Masuk sebagai buyer terlebih dahulu untuk melihat rekomendasi proyekmu."
            )
            return []

        dispatcher.utter_message(
            json_message={"type": "project_recommendation_request"}
        )
        return []
