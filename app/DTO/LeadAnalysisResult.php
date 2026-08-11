<?php

namespace App\DTO;

class LeadAnalysisResult
{
    public function __construct(
        public bool $isLead,
        public int $score,
        public ?float $confidence = null,
        public ?int $passengerCount = null,
        public ?int $numberOfVehicles = null,
        public ?string $vehicleType = null,
        public ?string $serviceDate = null,
        public ?string $serviceTime = null,
        public ?string $pickupLocation = null,
        public ?string $dropoffLocation = null,
        public ?string $requestType = null,
        public ?string $duration = null,
        public ?string $language = null,
        public ?string $summary = null,
        public array $contextMessageIds = [],
    ) {
        $this->score = max(0, min(100, $score));
    }

    public function toLeadAttributes(): array
    {
        return [
            'score' => $this->score,
            'confidence' => $this->confidence,
            'passenger_count' => $this->passengerCount,
            'number_of_vehicles' => $this->numberOfVehicles,
            'vehicle_type' => $this->vehicleType,
            'service_date' => $this->serviceDate,
            'service_time' => $this->serviceTime,
            'pickup_location' => $this->pickupLocation,
            'dropoff_location' => $this->dropoffLocation,
            'request_type' => $this->requestType,
            'duration' => $this->duration,
            'language' => $this->language,
            'summary' => $this->summary,
        ];
    }
}
