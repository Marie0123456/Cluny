<?php

namespace App\Http\Controllers;

use App\Models\Cavalier;
use App\Models\Championnat;
use App\Models\ChampionnatExclusion;
use App\Models\ChampionnatResultat;
use App\Models\Cheval;
use App\Models\ClientFacturation;
use App\Models\CommandeRetrait;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Epreuve;
use App\Models\Modification;
use App\Models\Produit;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{

    public function backup(Concours $concours)
    {
        $fileName = 'backup_' . str_replace(' ', '_', $concours->nom) . '_' . now()->format('Y-m-d_His') . '.json';
        $jsonContent = $this->buildBackupJson($concours);

        return response($jsonContent, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function restore(Request $request, Concours $concours)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:json,txt',
        ]);

        $jsonContent = file_get_contents($request->file('backup_file')->getRealPath());

        $data = json_decode($jsonContent, true);
        if (!$data || !isset($data['concours'])) {
            return back()->with('error', 'Le fichier backup.json est invalide.');
        }

        // Increase time limit for large restores (default 30s is not enough)
        set_time_limit(300);
        DB::disableQueryLog();

        DB::transaction(function () use ($concours, $data) {
            // Purge existing data for this concours
            $this->purgeConcoursData($concours);

            // Restore data
            $this->restoreFromData($concours, $data);
        });

        return redirect()->route('concours.epreuves.index', $concours)
            ->with('success', 'Concours restauré avec succès depuis la sauvegarde.');
    }

    private function buildBackupJson(Concours $concours): string
    {
        // Load all related data
        $epreuves = $concours->epreuves()->get();
        $epreuveIds = $epreuves->pluck('id');

        $engagements = Engagement::whereIn('epreuve_id', $epreuveIds)
            ->get();

        $cavalierIds = $engagements->pluck('cavalier_id')->unique();
        $chevalIds = $engagements->pluck('cheval_id')->unique();

        $cavaliers = Cavalier::whereIn('id', $cavalierIds)->get();
        $chevaux = Cheval::whereIn('id', $chevalIds)->get();

        $modifications = $concours->modifications()
            ->get();

        // Collect additional cavalier/cheval IDs from modifications
        $modCavalierIds = $modifications->pluck('ancien_cavalier_id')
            ->merge($modifications->pluck('nouveau_cavalier_id'))
            ->filter()
            ->unique();
        $modChevalIds = $modifications->pluck('ancien_cheval_id')
            ->merge($modifications->pluck('nouveau_cheval_id'))
            ->filter()
            ->unique();

        $extraCavaliers = Cavalier::whereIn('id', $modCavalierIds->diff($cavalierIds))->get();
        $cavaliers = $cavaliers->merge($extraCavaliers);

        $extraChevaux = Cheval::whereIn('id', $modChevalIds->diff($chevalIds))->get();
        $chevaux = $chevaux->merge($extraChevaux);

        $ventes = $concours->ventes()->get();
        $venteLignes = VenteLigne::whereIn('vente_id', $ventes->pluck('id'))->get();

        // Collect produit IDs from vente lignes
        $produitIds = $venteLignes->pluck('produit_id')->unique();
        $produits = Produit::whereIn('id', $produitIds)->get();

        // Client facturation IDs from ventes + modifications
        $clientIds = $ventes->pluck('client_facturation_id')
            ->merge($modifications->pluck('client_facturation_id'))
            ->filter()
            ->unique();
        $clients = ClientFacturation::whereIn('id', $clientIds)->get();

        $championnats = $concours->championnats()->get();
        $championnatIds = $championnats->pluck('id');
        $resultats = ChampionnatResultat::whereIn('championnat_id', $championnatIds)->get();
        $exclusions = ChampionnatExclusion::whereIn('championnat_id', $championnatIds)->get();

        $commandeRetraits = CommandeRetrait::where('concours_id', $concours->id)->get();

        $data = [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'concours' => $concours->toArray(),
            'epreuves' => $epreuves->toArray(),
            'cavaliers' => $cavaliers->toArray(),
            'chevaux' => $chevaux->toArray(),
            'engagements' => $engagements->toArray(),
            'modifications' => $modifications->toArray(),
            'ventes' => $ventes->toArray(),
            'vente_lignes' => $venteLignes->toArray(),
            'produits' => $produits->toArray(),
            'clients_facturation' => $clients->toArray(),
            'championnats' => $championnats->toArray(),
            'championnat_resultats' => $resultats->toArray(),
            'championnat_exclusions' => $exclusions->toArray(),
            'commande_retraits' => $commandeRetraits->toArray(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function purgeConcoursData(Concours $concours): void
    {
        // Delete in correct order (children first)
        $championnatIds = $concours->championnats()->pluck('id');
        ChampionnatResultat::whereIn('championnat_id', $championnatIds)->delete();
        ChampionnatExclusion::whereIn('championnat_id', $championnatIds)->delete();
        $concours->championnats()->delete();

        $venteIds = $concours->ventes()->pluck('id');
        VenteLigne::whereIn('vente_id', $venteIds)->delete();
        $concours->ventes()->delete();

        CommandeRetrait::where('concours_id', $concours->id)->delete();

        $concours->modifications()->delete();
        $concours->engagements()->delete();
        $concours->epreuves()->delete();
    }

    private function restoreFromData(Concours $concours, array $data): void
    {
        // Maps old IDs → new IDs
        $epreuveMap = [];
        $cavalierMap = [];
        $chevalMap = [];
        $engagementMap = [];
        $venteMap = [];
        $produitMap = [];
        $clientMap = [];
        $championnatMap = [];

        // Restore cavaliers (upsert by licence or nom+prenom)
        foreach ($data['cavaliers'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);

            if (!empty($row['num_licence'])) {
                $cavalier = Cavalier::firstOrCreate(
                    ['num_licence' => $row['num_licence']],
                    $row
                );
            } else {
                $cavalier = Cavalier::firstOrCreate(
                    ['nom' => $row['nom'], 'prenom' => $row['prenom']],
                    $row
                );
            }
            $cavalierMap[$oldId] = $cavalier->id;
        }

        // Restore chevaux (upsert by SIRE or nom)
        foreach ($data['chevaux'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);

            if (!empty($row['num_sire'])) {
                $cheval = Cheval::firstOrCreate(
                    ['num_sire' => $row['num_sire']],
                    $row
                );
            } else {
                $cheval = Cheval::firstOrCreate(
                    ['nom' => $row['nom']],
                    $row
                );
            }
            $chevalMap[$oldId] = $cheval->id;
        }

        // Restore produits (upsert by nom)
        foreach ($data['produits'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $produit = Produit::firstOrCreate(['nom' => $row['nom']], $row);
            $produitMap[$oldId] = $produit->id;
        }

        // Restore clients facturation (upsert by nom)
        foreach ($data['clients_facturation'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $client = ClientFacturation::updateOrCreateByNom($row['nom'], $row);
            $clientMap[$oldId] = $client->id;
        }

        // Restore epreuves
        foreach ($data['epreuves'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['concours_id'] = $concours->id;
            $epreuve = Epreuve::create($row);
            $epreuveMap[$oldId] = $epreuve->id;
        }

        // Restore engagements
        foreach ($data['engagements'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['epreuve_id'] = $epreuveMap[$row['epreuve_id']] ?? null;
            $row['cavalier_id'] = $cavalierMap[$row['cavalier_id']] ?? null;
            $row['cheval_id'] = $chevalMap[$row['cheval_id']] ?? null;
            if (!$row['epreuve_id']) continue;
            $engagement = Engagement::create($row);
            $engagementMap[$oldId] = $engagement->id;
        }

        // Restore modifications
        foreach ($data['modifications'] ?? [] as $row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['concours_id'] = $concours->id;
            $row['engagement_id'] = $engagementMap[$row['engagement_id']] ?? null;
            $row['ancien_cheval_id'] = isset($row['ancien_cheval_id']) ? ($chevalMap[$row['ancien_cheval_id']] ?? null) : null;
            $row['nouveau_cheval_id'] = isset($row['nouveau_cheval_id']) ? ($chevalMap[$row['nouveau_cheval_id']] ?? null) : null;
            $row['ancien_cavalier_id'] = isset($row['ancien_cavalier_id']) ? ($cavalierMap[$row['ancien_cavalier_id']] ?? null) : null;
            $row['nouveau_cavalier_id'] = isset($row['nouveau_cavalier_id']) ? ($cavalierMap[$row['nouveau_cavalier_id']] ?? null) : null;
            $row['client_facturation_id'] = isset($row['client_facturation_id']) ? ($clientMap[$row['client_facturation_id']] ?? null) : null;
            $row['linked_modification_id'] = null; // Will be re-linked in a second pass if needed
            Modification::create($row);
        }

        // Restore ventes
        foreach ($data['ventes'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['concours_id'] = $concours->id;
            $row['client_facturation_id'] = isset($row['client_facturation_id']) ? ($clientMap[$row['client_facturation_id']] ?? null) : null;
            $vente = Vente::create($row);
            $venteMap[$oldId] = $vente->id;
        }

        // Restore vente lignes
        foreach ($data['vente_lignes'] ?? [] as $row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['vente_id'] = $venteMap[$row['vente_id']] ?? null;
            $row['produit_id'] = $produitMap[$row['produit_id']] ?? null;
            if (!$row['vente_id'] || !$row['produit_id']) continue;
            VenteLigne::create($row);
        }

        // Restore championnats
        foreach ($data['championnats'] ?? [] as $row) {
            $oldId = $row['id'];
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['concours_id'] = $concours->id;
            $row['epreuve1_id'] = $epreuveMap[$row['epreuve1_id']] ?? null;
            $row['epreuve2_id'] = isset($row['epreuve2_id']) ? ($epreuveMap[$row['epreuve2_id']] ?? null) : null;
            $championnat = Championnat::create($row);
            $championnatMap[$oldId] = $championnat->id;
        }

        // Restore championnat resultats
        foreach ($data['championnat_resultats'] ?? [] as $row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['championnat_id'] = $championnatMap[$row['championnat_id']] ?? null;
            $row['epreuve_id'] = isset($row['epreuve_id']) ? ($epreuveMap[$row['epreuve_id']] ?? null) : null;
            $row['cavalier_id'] = isset($row['cavalier_id']) ? ($cavalierMap[$row['cavalier_id']] ?? null) : null;
            $row['cheval_id'] = isset($row['cheval_id']) ? ($chevalMap[$row['cheval_id']] ?? null) : null;
            if (!$row['championnat_id']) continue;
            ChampionnatResultat::create($row);
        }

        // Restore championnat exclusions
        foreach ($data['championnat_exclusions'] ?? [] as $row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['championnat_id'] = $championnatMap[$row['championnat_id']] ?? null;
            $row['cavalier_id'] = isset($row['cavalier_id']) ? ($cavalierMap[$row['cavalier_id']] ?? null) : null;
            $row['cheval_id'] = isset($row['cheval_id']) ? ($chevalMap[$row['cheval_id']] ?? null) : null;
            if (!$row['championnat_id']) continue;
            ChampionnatExclusion::create($row);
        }

        // Restore commande retraits
        foreach ($data['commande_retraits'] ?? [] as $row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['concours_id'] = $concours->id;
            CommandeRetrait::create($row);
        }
    }

}
