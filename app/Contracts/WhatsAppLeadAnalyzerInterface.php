<?php

namespace App\Contracts;

use App\DTO\LeadAnalysisResult;
use App\Models\WhatsAppGroupMessage;

interface WhatsAppLeadAnalyzerInterface
{
    public function analyze(WhatsAppGroupMessage $message): LeadAnalysisResult;
}
