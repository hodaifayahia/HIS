<?php

namespace App\Services;

use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FournisseurService
{
    public function getAll(?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Fournisseur::with('contacts');

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function getActive(): Collection
    {
        return Fournisseur::active()->with('contacts')->get();
    }

    public function findById(int $id): ?Fournisseur
    {
        return Fournisseur::with('contacts')->find($id);
    }

    public function create(array $data): Fournisseur
    {
        $fournisseur = Fournisseur::create($data);

        if (isset($data['contacts']) && is_array($data['contacts'])) {
            $this->createContacts($fournisseur, $data['contacts']);
        }

        return $fournisseur->load('contacts');
    }

    public function update(Fournisseur $fournisseur, array $data): Fournisseur
    {
        $fournisseur->update($data);

        if (isset($data['contacts']) && is_array($data['contacts'])) {
            $this->updateContacts($fournisseur, $data['contacts']);
        }

        return $fournisseur->load('contacts');
    }

    public function delete(Fournisseur $fournisseur): bool
    {
        return $fournisseur->delete();
    }

    public function search(string $query): Collection
    {
        return Fournisseur::where('company_name', 'like', "%{$query}%")
            ->orWhere('contact_person', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->with('contacts')
            ->get();
    }

    private function createContacts(Fournisseur $fournisseur, array $contacts): void
    {
        foreach ($contacts as $contactData) {
            $fournisseur->contacts()->create($contactData);
        }
    }

    private function updateContacts(Fournisseur $fournisseur, array $contacts): void
    {
        // Delete existing contacts not in the new list
        $existingContactIds = collect($contacts)->pluck('id')->filter()->toArray();
        $fournisseur->contacts()->whereNotIn('id', $existingContactIds)->delete();

        // Update or create contacts
        foreach ($contacts as $contactData) {
            if (isset($contactData['id'])) {
                $contact = $fournisseur->contacts()->find($contactData['id']);
                if ($contact) {
                    $contact->update($contactData);
                }
            } else {
                $fournisseur->contacts()->create($contactData);
            }
        }
    }
}
