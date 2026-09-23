import unittest
from unittest.mock import Mock

from rasa_sdk.executor import CollectingDispatcher

from actions.actions import (
    ActionRecommendProjects,
    ActionSubmitCalculation,
    _airport_code_result,
    _electricity_grid_result,
    _number_result,
    _option_result,
)


class ActionValidationTest(unittest.TestCase):
    def test_project_recommendation_requests_saved_result_without_calculating(self):
        tracker = Mock()
        tracker.latest_message = {
            "metadata": {
                "authenticated": True,
                "role": "buyer",
                "account_type": "personal",
            }
        }
        dispatcher = CollectingDispatcher()

        events = ActionRecommendProjects().run(dispatcher, tracker, {})

        self.assertEqual(events, [])
        self.assertEqual(
            dispatcher.messages[0]["custom"],
            {"type": "project_recommendation_request"},
        )

    def test_project_recommendation_requires_buyer(self):
        tracker = Mock()
        tracker.latest_message = {"metadata": {"authenticated": False}}
        dispatcher = CollectingDispatcher()

        ActionRecommendProjects().run(dispatcher, tracker, {})

        self.assertIn("Masuk sebagai buyer", dispatcher.messages[0]["text"])
        self.assertEqual(dispatcher.messages[0]["custom"], {})

    def test_number_with_supported_unit_is_accepted(self):
        dispatcher = CollectingDispatcher()

        result = _number_result(
            "personal_electricity_kwh", "155 kWh", dispatcher, "155 kWh"
        )

        self.assertEqual(result, {"personal_electricity_kwh": 155.0})
        self.assertEqual(dispatcher.messages, [])

    def test_invalid_number_suffix_is_rejected(self):
        dispatcher = CollectingDispatcher()

        result = _number_result(
            "personal_electricity_kwh", "155b", dispatcher, "155 kWh"
        )

        self.assertEqual(result, {"personal_electricity_kwh": None})
        self.assertTrue(dispatcher.messages)

    def test_indonesian_thousands_separator_is_supported(self):
        dispatcher = CollectingDispatcher()

        result = _number_result(
            "company_electricity_kwh", "10.000 kWh", dispatcher, "10000 kWh"
        )

        self.assertEqual(result, {"company_electricity_kwh": 10000.0})

    def test_option_alias_is_normalised(self):
        dispatcher = CollectingDispatcher()

        result = _option_result(
            "company_electricity_grid",
            "Jawa-Bali",
            {"jawa_bali": ["jawa bali"]},
            dispatcher,
            "Pilihan tidak valid.",
        )

        self.assertEqual(result, {"company_electricity_grid": "jawa_bali"})

    def test_airport_code_is_normalised(self):
        dispatcher = CollectingDispatcher()

        result = _airport_code_result(
            "company_flight_origin", "cgk", dispatcher
        )

        self.assertEqual(result, {"company_flight_origin": "CGK"})
        self.assertEqual(dispatcher.messages, [])

    def test_invalid_airport_code_is_rejected(self):
        dispatcher = CollectingDispatcher()

        result = _airport_code_result(
            "company_flight_destination", "12345", dispatcher
        )

        self.assertEqual(result, {"company_flight_destination": None})
        self.assertTrue(dispatcher.messages)

    def test_airport_city_or_name_is_accepted(self):
        dispatcher = CollectingDispatcher()

        city = _airport_code_result("company_flight_origin", "Jakarta", dispatcher)
        airport = _airport_code_result(
            "company_flight_destination", "Bandara Ngurah Rai", dispatcher
        )

        self.assertEqual(city, {"company_flight_origin": "Jakarta"})
        self.assertEqual(
            airport, {"company_flight_destination": "Bandara Ngurah Rai"}
        )
        self.assertEqual(dispatcher.messages, [])

    def test_electricity_city_is_mapped_to_calculator_grid(self):
        dispatcher = CollectingDispatcher()

        jakarta = _electricity_grid_result(
            "company_electricity_grid", "Jakarta Selatan", dispatcher
        )
        palembang = _electricity_grid_result(
            "company_electricity_grid", "Kota Palembang", dispatcher
        )

        self.assertEqual(jakarta, {"company_electricity_grid": "jawa_bali"})
        self.assertEqual(palembang, {"company_electricity_grid": "sumatra"})
        self.assertEqual(dispatcher.messages, [])

    def test_other_electricity_region_requests_a_place_name(self):
        dispatcher = CollectingDispatcher()

        result = _electricity_grid_result(
            "company_electricity_grid", "Lainnya", dispatcher
        )

        self.assertEqual(result, {"company_electricity_grid": None})
        self.assertIn("tulis nama", dispatcher.messages[0]["text"])

    def test_personal_form_submits_all_activity_categories(self):
        values = {
            "account_type": "personal",
            "personal_energy_fuel": "lpg",
            "personal_energy_qty": 10,
            "personal_vehicle_type": "car_petrol",
            "personal_vehicle_fuel": "ron92",
            "personal_vehicle_km": 500,
            "personal_electricity_kwh": 100,
            "personal_transit_mode": "train",
            "personal_transit_km": 100,
            "personal_food_type": "beef",
            "personal_food_kg": 2,
            "personal_water_m3": 10,
            "personal_waste_kg": 5,
        }
        tracker = Mock()
        tracker.get_slot.side_effect = values.get
        dispatcher = CollectingDispatcher()

        ActionSubmitCalculation().run(dispatcher, tracker, {})

        request = dispatcher.messages[0]["custom"]
        self.assertEqual(request["type"], "calculation_request")
        self.assertEqual(request["account_type"], "personal")
        self.assertEqual(request["data"]["energy_qty"], 10)
        self.assertEqual(request["data"]["vehicle_km"], 500)
        self.assertEqual(request["data"]["electricity_kwh"], 100)
        self.assertEqual(request["data"]["transit_km"], 100)
        self.assertEqual(request["data"]["food_kg"], 2)
        self.assertEqual(request["data"]["water_m3"], 10)
        self.assertEqual(request["data"]["waste_kg"], 5)

    def test_company_form_submits_all_scope_categories(self):
        values = {
            "account_type": "company",
            "company_stationary_fuel": "diesel",
            "company_stationary_qty": 1000,
            "company_mobile_fuel": "ron92",
            "company_mobile_fuel_qty": 100,
            "company_mobile_distance_fuel": "diesel",
            "company_mobile_km": 1000,
            "company_electricity_grid": "sumatra",
            "company_electricity_kwh": 1000,
            "company_flight_class": "economy",
            "company_flight_pax": 2,
            "company_flight_origin": "CGK",
            "company_flight_destination": "DPS",
            "company_hotel_nights": 2,
            "company_hotel_rooms": 3,
            "company_train_class": "ekonomi",
            "company_train_km": 1000,
        }
        tracker = Mock()
        tracker.get_slot.side_effect = values.get
        dispatcher = CollectingDispatcher()

        ActionSubmitCalculation().run(dispatcher, tracker, {})

        request = dispatcher.messages[0]["custom"]
        self.assertEqual(request["type"], "calculation_request")
        self.assertEqual(request["account_type"], "company")
        self.assertEqual(request["data"]["stationary_qty"], 1000)
        self.assertEqual(request["data"]["mobile_fuel_qty"], 100)
        self.assertEqual(request["data"]["mobile_km"], 1000)
        self.assertEqual(request["data"]["electricity_kwh"], 1000)
        self.assertEqual(request["data"]["flight_pax"], 2)
        self.assertEqual(request["data"]["flight_origin"], "CGK")
        self.assertEqual(request["data"]["flight_destination"], "DPS")
        self.assertEqual(request["data"]["hotel_nights"], 2)
        self.assertEqual(request["data"]["train_km"], 1000)


if __name__ == "__main__":
    unittest.main()
