<?php

namespace App\Services\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\Specialization;
use App\Models\User;

class FicheNavetteService
{
    public function getServicesWithDoctors()
    {
        return Service::with(['users' => function ($query) {
            $query->where('is_active', true)
                  ->whereHas('roles', function ($roleQuery) {
                      $roleQuery->where('name', 'doctor');
                  });
        }])
        ->where('is_active', true)
        ->get();
    }

    public function getPrestationsByService($serviceId)
    {
        return Prestation::with(['service', 'specialization'])
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->get();
    }

    public function getPackagesByService($serviceId)
    {
        return PrestationPackage::with(['items.prestation'])
            ->whereHas('items.prestation', function ($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->get();
    }

    public function getDoctorsForPrestation($prestationId)
    {
        $prestation = Prestation::with('service')->find($prestationId);
        
        if (!$prestation) {
            return collect();
        }

        return User::whereHas('roles', function ($query) {
            $query->where('name', 'doctor');
        })
        ->where('is_active', true)
        ->where(function ($query) use ($prestation) {
            $query->whereHas('services', function ($serviceQuery) use ($prestation) {
                $serviceQuery->where('service_id', $prestation->service_id);
            })
            ->orWhereHas('specializations', function ($specQuery) use ($prestation) {
                $specQuery->where('specialization_id', $prestation->specialization_id);
            });
        })
        ->get();
    }

    public function createFicheNavetteWithItems($data)
    {
        $ficheNavette = ficheNavette::create([
            'patient_id' => $data['patient_id'],
            'reference' => $this->generateReference(),
            'status' => 'pending',
            'total_amount' => 0,
            'created_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $totalAmount = 0;

        // Process prestations
        if (isset($data['prestations'])) {
            $totalAmount += $this->addPrestationsToFiche($ficheNavette, $data['prestations'], 'prestation');
        }

        // Process dependencies
        if (isset($data['dependencies'])) {
            $totalAmount += $this->addPrestationsToFiche($ficheNavette, $data['dependencies'], 'dependency');
        }

        // Process custom prestations
        if (isset($data['customPrestations'])) {
            $totalAmount += $this->addPrestationsToFiche($ficheNavette, $data['customPrestations'], 'custom');
        }

        $ficheNavette->update(['total_amount' => $totalAmount]);

        return $ficheNavette;
    }

    private function addPrestationsToFiche($ficheNavette, $prestations, $type)
    {
        $total = 0;

        foreach ($prestations as $prestationData) {
            $prestation = Prestation::find($prestationData['id']);
            $quantity = $prestationData['quantity'] ?? 1;
            $itemTotal = $prestation->public_price * $quantity;
            $total += $itemTotal;

            ficheNavetteItem::create([
                'fiche_navette_id' => $ficheNavette->id,
                'prestation_id' => $prestation->id,
                'doctor_id' => $prestationData['doctor_id'] ?? null,
                'quantity' => $quantity,
                'default_payment_type' => $prestation->default_payment_type ?? null,
                'unit_price' => $prestation->public_price,
                'total_price' => $itemTotal,
                'final_price' => $itemTotal,
                'patient_share' => $itemTotal,
                'type' => $type
            ]);
        }

        return $total;
    }

    private function generateReference(): string
    {
        $prefix = 'FN';
        $date = now()->format('Ymd');
        $sequence = ficheNavette::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . sprintf('%04d', $sequence);
    }

    public function getTodayAndUnpaidPatients(array $filters = [])
    {
        $query = ficheNavette::with(['patient', 'items.prestation.service', 'creator', 'items.prestation']);

        // Include fiches that are either for today OR not paid
        $query->where(function ($q) {
            $q->whereDate('fiche_date', today())
              ->orWhere('status', '!=', 'payed');
        });

        // Filter by service (prestation -> service_id)
        if (!empty($filters['service_id'])) {
            $serviceId = $filters['service_id'];
            $query->whereHas('items.prestation', function ($q) use ($serviceId) {
                $q->where('service_id', $serviceId);
            });
        }

        // Filter by patient name (Firstname or Lastname)
        if (!empty($filters['patient_name'])) {
            $name = '%' . $filters['patient_name'] . '%';
            $query->whereHas('patient', function ($q) use ($name) {
                $q->where('Firstname', 'like', $name)
                  ->orWhere('Lastname', 'like', $name);
            });
        }

        // Filter by fiche status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // Sorting & pagination
        $query = $query->orderBy('fiche_date', 'desc')->orderBy('created_at', 'desc');

        if (!empty($filters['per_page'])) {
            return $query->paginate((int)$filters['per_page']);
        }

        return $query->get();
    }
}
