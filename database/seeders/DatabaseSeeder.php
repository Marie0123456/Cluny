<?php

namespace Database\Seeders;

use App\Enums\Discipline;
use App\Enums\Role;
use App\Models\Cavalier;
use App\Models\Cheval;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Epreuve;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Utilisateurs ──
        $admin = User::firstOrCreate(
            ['email' => 'admin@equimanage.fr'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => Role::ADMIN,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@equimanage.fr'],
            [
                'name' => 'Utilisateur Test',
                'password' => Hash::make('password'),
                'role' => Role::USER,
            ]
        );

        // ── Concours (2 futurs + 1 passé) ──
        $concoursCSO = Concours::firstOrCreate(
            ['nom' => 'CSO National de Cluny'],
            [
                'date_debut' => now()->addDays(10),
                'date_fin' => now()->addDays(12),
                'discipline' => Discipline::CSO,
                'type_ffe_sif' => false,
                'type_ffe_compet' => true,
                'grand_national' => true,
            ]
        );

        $concoursDressage = Concours::firstOrCreate(
            ['nom' => 'Dressage Printemps Cluny'],
            [
                'date_debut' => now()->addDays(30),
                'date_fin' => now()->addDays(32),
                'discipline' => Discipline::DRESSAGE,
                'type_ffe_sif' => true,
                'type_ffe_compet' => false,
                'grand_national' => false,
            ]
        );

        $concoursPasse = Concours::firstOrCreate(
            ['nom' => 'Open Hiver Cluny'],
            [
                'date_debut' => now()->subDays(20),
                'date_fin' => now()->subDays(18),
                'discipline' => Discipline::OPEN,
                'type_ffe_sif' => false,
                'type_ffe_compet' => true,
                'grand_national' => false,
            ]
        );

        // Associer les concours aux utilisateurs
        $admin->concours()->syncWithoutDetaching([$concoursCSO->id, $concoursDressage->id, $concoursPasse->id]);
        $user->concours()->syncWithoutDetaching([$concoursCSO->id, $concoursPasse->id]);

        // ── Épreuves ──
        $epreuves = [];

        // Épreuves CSO
        foreach ([
            ['numero' => 1, 'nom' => 'Pro 1 Grand Prix', 'prix' => 50.00],
            ['numero' => 2, 'nom' => 'Pro 2 Vitesse', 'prix' => 45.00],
            ['numero' => 3, 'nom' => 'Amateur 1 Grand Prix', 'prix' => 35.00],
            ['numero' => 4, 'nom' => 'Préparatoire', 'prix' => 20.00],
        ] as $ep) {
            $epreuves[] = Epreuve::firstOrCreate(
                ['concours_id' => $concoursCSO->id, 'numero' => $ep['numero']],
                ['nom' => $ep['nom'], 'date' => $concoursCSO->date_debut, 'prix' => $ep['prix']]
            );
        }

        // Épreuves Dressage
        foreach ([
            ['numero' => 1, 'nom' => 'Pro 1 Reprise Libre', 'prix' => 50.00],
            ['numero' => 2, 'nom' => 'Amateur 1 Reprise Imposée', 'prix' => 35.00],
            ['numero' => 3, 'nom' => 'Warm-up', 'prix' => 15.00],
        ] as $ep) {
            Epreuve::firstOrCreate(
                ['concours_id' => $concoursDressage->id, 'numero' => $ep['numero']],
                ['nom' => $ep['nom'], 'date' => $concoursDressage->date_debut, 'prix' => $ep['prix']]
            );
        }

        // Épreuves concours passé
        foreach ([
            ['numero' => 1, 'nom' => 'Pro 2 Barrage', 'prix' => 45.00],
            ['numero' => 2, 'nom' => 'Amateur 2 Vitesse', 'prix' => 30.00],
        ] as $ep) {
            Epreuve::firstOrCreate(
                ['concours_id' => $concoursPasse->id, 'numero' => $ep['numero']],
                ['nom' => $ep['nom'], 'date' => $concoursPasse->date_debut, 'prix' => $ep['prix']]
            );
        }

        // ── Cavaliers ──
        $cavaliers = [];
        foreach ([
            ['nom' => 'Dupont', 'prenom' => 'Marie', 'num_licence' => '0012345', 'club' => 'CE de Cluny', 'cre' => 'BFC', 'departement' => 'Saône-et-Loire', 'num_departement' => '71'],
            ['nom' => 'Martin', 'prenom' => 'Lucas', 'num_licence' => '0067890', 'club' => 'Haras de Lyon', 'cre' => 'ARA', 'departement' => 'Rhône', 'num_departement' => '69'],
            ['nom' => 'Leroy', 'prenom' => 'Camille', 'num_licence' => '0054321', 'club' => 'CE de Dijon', 'cre' => 'BFC', 'departement' => 'Côte-d\'Or', 'num_departement' => '21'],
            ['nom' => 'Bernard', 'prenom' => 'Hugo', 'num_licence' => '0098765', 'club' => 'Poney Club du Lac', 'cre' => 'ARA', 'departement' => 'Ain', 'num_departement' => '01'],
        ] as $cav) {
            $cavaliers[] = Cavalier::firstOrCreate(
                ['num_licence' => $cav['num_licence']],
                $cav
            );
        }

        // ── Chevaux ──
        $chevaux = [];
        foreach ([
            ['nom' => 'Donatello du Bois', 'num_sire' => 'SIR001234', 'age' => 9, 'sexe' => 'Hongre', 'robe' => 'Bai', 'race' => 'Selle Français'],
            ['nom' => 'Étoile de Nuit', 'num_sire' => 'SIR005678', 'age' => 7, 'sexe' => 'Jument', 'robe' => 'Noir', 'race' => 'KWPN'],
            ['nom' => 'Flash Royal', 'num_sire' => 'SIR009012', 'age' => 10, 'sexe' => 'Hongre', 'robe' => 'Alezan', 'race' => 'Selle Français'],
            ['nom' => 'Galante du Château', 'num_sire' => 'SIR003456', 'age' => 8, 'sexe' => 'Jument', 'robe' => 'Gris', 'race' => 'Anglo-Arabe'],
        ] as $ch) {
            $chevaux[] = Cheval::firstOrCreate(
                ['num_sire' => $ch['num_sire']],
                $ch
            );
        }

        // ── Engagements (cavaliers + chevaux dans les épreuves CSO) ──
        foreach ($epreuves as $i => $epreuve) {
            foreach ($cavaliers as $j => $cavalier) {
                Engagement::firstOrCreate(
                    [
                        'epreuve_id' => $epreuve->id,
                        'cavalier_id' => $cavalier->id,
                        'cheval_id' => $chevaux[$j]->id,
                    ],
                    [
                        'numero_depart' => ($j + 1) + ($i * 10),
                        'role_cavalier' => null,
                        'dept_groom' => null,
                        'role_cheval' => null,
                        'is_invitation' => $j === 0,
                        'is_non_partant' => false,
                    ]
                );
            }
        }

        // ── Produits ──
        foreach ([
            ['nom' => 'Box journée', 'prix_ttc' => 25.00, 'tva' => 20.00, 'actif' => true],
            ['nom' => 'Box compétition (3 jours)', 'prix_ttc' => 60.00, 'tva' => 20.00, 'actif' => true],
            ['nom' => 'Foin (botte)', 'prix_ttc' => 8.00, 'tva' => 5.50, 'actif' => true],
            ['nom' => 'Électricité (forfait)', 'prix_ttc' => 15.00, 'tva' => 20.00, 'actif' => true],
            ['nom' => 'Douche cheval', 'prix_ttc' => 5.00, 'tva' => 20.00, 'actif' => false],
        ] as $prod) {
            Produit::firstOrCreate(
                ['nom' => $prod['nom']],
                $prod
            );
        }
    }
}
