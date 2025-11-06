<?php

namespace App\Observers;

use App\Models\Candidate;

class CandidateObserver
{
    public function creating(Candidate $candidate): void
    {
        // Auto-generate unique_talent_id hanya jika kosong
        if (empty($candidate->unique_talent_id) || $candidate->unique_talent_id === 'AUTO-GENERATED') {
            $candidate->unique_talent_id = $this->generateUniqueTalentId();
        }
    }

    public function updating(Candidate $candidate): void
    {
        // Prevent manual update of unique_talent_id
        if ($candidate->isDirty('unique_talent_id') && $candidate->getOriginal('unique_talent_id')) {
            $original = $candidate->getOriginal('unique_talent_id');
            $current = $candidate->unique_talent_id;
            
            // Jika mencoba mengubah dari nilai auto-generated, kembalikan ke original
            if ($original && $current !== $original) {
                $candidate->unique_talent_id = $original;
            }
        }
    }

    private function generateUniqueTalentId(): string
    {
        $prefix = 'TalentGO-';
        
        // Query untuk mendapatkan ID terakhir dengan format TalentGO-
        $latest = Candidate::where('unique_talent_id', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(unique_talent_id) DESC, unique_talent_id DESC')
            ->first();

        if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)$/', $latest->unique_talent_id, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT); // 3 digits untuk format TalentGO-007
    }
}