import unittest
from unittest.mock import Mock

from rasa_sdk.executor import CollectingDispatcher

from actions.actions import (
    ActionCarbonBalance,
    ActionCompareProjects,
    ActionExplainLatestEmission,
    ActionExplainRecommendation,
    ActionPersonalActionPlan,
    ActionPurchaseDisclosure,
    ActionProjectInformation,
    ActionProjectVerification,
    ActionSimulateOffsetPurchase,
)


class InformationActionTest(unittest.TestCase):
    def make_tracker(self, text):
        tracker = Mock()
        tracker.latest_message = {"text": text}
        return tracker

    def assert_request(self, action, text, expected_type):
        dispatcher = CollectingDispatcher()

        events = action.run(dispatcher, self.make_tracker(text), {})

        self.assertEqual(events, [])
        self.assertEqual(dispatcher.messages[0]["custom"]["type"], expected_type)
        self.assertEqual(dispatcher.messages[0]["custom"]["query"], text)

    def test_latest_emission_request(self):
        self.assert_request(
            ActionExplainLatestEmission(),
            "jelaskan hasil emisi saya",
            "emission_explanation_request",
        )

    def test_project_information_request(self):
        self.assert_request(
            ActionProjectInformation(),
            "di mana proyek Mangrove Blue Carbon Riau",
            "project_information_request",
        )

    def test_project_verification_request(self):
        self.assert_request(
            ActionProjectVerification(),
            "apakah proyek Mangrove Blue Carbon Riau memiliki sertifikat",
            "project_verification_request",
        )

    def test_recommendation_explanation_request(self):
        self.assert_request(
            ActionExplainRecommendation(),
            "mengapa proyek ini direkomendasikan",
            "recommendation_explanation_request",
        )

    def test_carbon_balance_request(self):
        self.assert_request(
            ActionCarbonBalance(),
            "berapa sisa emisi saya",
            "carbon_balance_request",
        )

    def test_offset_simulation_request(self):
        self.assert_request(
            ActionSimulateOffsetPurchase(),
            "kalau beli 2 ton proyek mangrove sisa emisinya berapa",
            "offset_simulation_request",
        )

    def test_project_comparison_request(self):
        self.assert_request(
            ActionCompareProjects(),
            "bandingkan proyek mangrove dan proyek surya",
            "project_comparison_request",
        )

    def test_personal_action_plan_request(self):
        self.assert_request(
            ActionPersonalActionPlan(),
            "buatkan rencana pengurangan emisi saya",
            "personal_action_plan_request",
        )

    def test_purchase_disclosure_request(self):
        self.assert_request(
            ActionPurchaseDisclosure(),
            "jelaskan biaya risiko dan ketentuan proyek mangrove",
            "purchase_disclosure_request",
        )


if __name__ == "__main__":
    unittest.main()
