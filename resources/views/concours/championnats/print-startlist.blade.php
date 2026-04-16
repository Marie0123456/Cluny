<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste de depart - {{ $titre }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pinyon+Script&family=Cormorant+Garamond:ital,wght@1,500;1,700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Force l'impression des couleurs de fond (sinon les entetes orange
           apparaissent en noir lors de l'export PDF). */
        html, body, table, thead, tr, th, td, .accent-bar {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        body {
            font-family: 'Montserrat', Arial, sans-serif; color: #111;
            padding: 12px 14px;
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* En-tete: titre a gauche, logo a droite */
        .header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 20px; margin-bottom: 18px; padding-bottom: 10px;
        }
        .header .titles { flex: 1; min-width: 0; }
        .header .title-group { display: inline-block; }
        .header .titre-cursif {
            font-family: 'Pinyon Script', 'Brush Script MT', cursive;
            font-size: 72px; line-height: 1.1; color: #F97316;
            letter-spacing: 0.5px;
        }
        .header .titre {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic; font-weight: 700;
            font-size: 28px; color: #111;
            margin-top: 6px; letter-spacing: 0.5px;
            text-align: center;
        }
        .header .date {
            font-size: 14px; color: #666; margin-top: 2px; font-style: italic;
            text-align: center;
        }
        .header .logo { flex: 0 0 auto; }
        .header .logo img { max-height: 110px; max-width: 200px; display: block; }

        /* Separateur orange decoratif */
        .accent-bar {
            height: 4px; width: 100%;
            background: linear-gradient(90deg, #F97316 0%, #FB923C 50%, transparent 100%);
            border-radius: 2px; margin-bottom: 14px;
        }

        /* Tableau */
        .table-wrap { flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #F97316; color: #fff; text-align: left;
            padding: 9px 10px; font-size: 11px;
            text-transform: uppercase; letter-spacing: 1px;
            font-weight: 600; white-space: nowrap;
        }
        thead th:first-child { border-top-left-radius: 4px; }
        thead th:last-child { border-top-right-radius: 4px; }

        tbody td {
            padding: 7px 10px; border-bottom: 1px solid #f3f4f6;
            font-size: 13.5px;
        }
        tbody tr:nth-child(even) { background: #fff7ed; }
        tbody tr:hover { background: #fed7aa; }

        td.num {
            width: 48px; text-align: center;
            font-weight: 700; font-size: 16px; color: #F97316;
        }
        td.num-ffe {
            width: 55px; text-align: center;
            font-size: 12px; color: #888; font-family: 'Courier New', monospace;
        }
        td.cavalier, td.cheval, td.club { white-space: nowrap; }
        td.cavalier { font-weight: 600; color: #111; }
        td.cheval { color: #444; font-style: italic; }
        td.club { color: #777; font-size: 12.5px; }

        .empty {
            text-align: center; color: #888;
            padding: 40px 0; font-style: italic;
        }

        /* Compteur partants */
        .compteur {
            margin-top: 10px; text-align: right;
            font-size: 12px; color: #999; font-style: italic;
        }

        /* Footer bandeau logos partenaires */
        .footer-logos {
            margin-top: auto; padding-top: 20px;
        }
        .footer-logos img {
            width: 100%; max-height: 130px;
            object-fit: contain; display: block;
        }

        /* Boutons a l'ecran (caches a l'impression) */
        .no-print { margin-top: 30px; text-align: center; }
        .no-print button {
            padding: 10px 24px; background: #F97316; color: white;
            border: none; border-radius: 4px; font-size: 14px; cursor: pointer;
            margin: 0 5px;
            font-family: 'Montserrat', sans-serif; font-weight: 600;
        }
        .no-print button:hover { background: #EA580C; }
        .no-print button.secondary { background: #6b7280; }
        .no-print button.secondary:hover { background: #4b5563; }

        @media print {
            .no-print { display: none; }
            body { padding: 6mm 8mm; }
            tbody tr { break-inside: avoid; }
            thead { display: table-header-group; }
        }
        /* Supprime les entetes/pieds du navigateur */
        @page { margin: 0; size: A4; }
    </style>
</head>
<body>
    {{-- Header: titre cursif + titre + date a gauche ; logo Equivallee a droite --}}
    <div class="header">
        <div class="titles">
            <div class="title-group">
                <div class="titre-cursif">Liste de départ</div>
                <div class="titre">{{ $titre }}</div>
                <div class="date">{{ $date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</div>
            </div>
        </div>
        <div class="logo">
            <img src="{{ asset('logo-equivallee-grand-format.png') }}" alt="Equivallee">
        </div>
    </div>

    <div class="accent-bar"></div>

    {{-- Tableau des partants --}}
    <div class="table-wrap">
        @if (empty($rows))
            <div class="empty">Aucune ligne dans le fichier CSV.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 48px; text-align: center;">N° depart</th>
                        <th style="width: 55px; text-align: center;">N° FFE</th>
                        <th>Cavalier</th>
                        <th>Cheval</th>
                        <th>Club</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr>
                            <td class="num">{{ $r['numero'] ?: ($loop->index + 1) }}</td>
                            <td class="num-ffe">{{ $r['numero_ffe'] }}</td>
                            <td class="cavalier">{{ $r['cavalier'] }}</td>
                            <td class="cheval">{{ $r['cheval'] }}</td>
                            <td class="club">{{ $r['club'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="compteur">
                {{ count($rows) }} partant{{ count($rows) > 1 ? 's' : '' }}
            </div>
        @endif
    </div>

    {{-- Bandeau logos partenaires en bas de page (si image deposee par l'utilisateur) --}}
    @php
        $footerLogos = public_path('startlist-footer-logos.png');
        $footerLogosExists = file_exists($footerLogos);
    @endphp
    @if ($footerLogosExists)
        <div class="footer-logos">
            <img src="{{ asset('startlist-footer-logos.png') }}" alt="Partenaires">
        </div>
    @endif

    <div class="no-print">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        <button type="button" class="secondary" onclick="window.history.back()">Retour</button>
    </div>

    <script>
        // Attendre le chargement des fonts avant d'imprimer
        window.addEventListener('load', function() {
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(function() { window.print(); });
            } else {
                window.print();
            }
        });
    </script>
</body>
</html>
