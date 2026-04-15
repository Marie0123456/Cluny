<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Start List - {{ $titre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111; padding: 12px 14px; }

        /* En-tete */
        .entete-banner { width: 100%; margin-bottom: 18px; }
        .entete-banner img { width: 100%; display: block; }

        .header {
            display: flex; align-items: center; gap: 20px;
            border-bottom: 3px solid #111; padding-bottom: 14px; margin-bottom: 18px;
        }
        .header .logo { flex: 0 0 auto; }
        .header .logo img { max-height: 90px; max-width: 180px; display: block; }
        .header .titles { flex: 1; text-align: center; }
        .header .region {
            font-size: 14px; font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; color: #333;
        }
        .header .titre {
            font-size: 30px; margin-top: 4px; font-weight: 700;
            letter-spacing: 0.5px; color: #111;
        }
        .header .date {
            font-size: 15px; margin-top: 4px; color: #555; font-style: italic;
        }

        /* Tableau */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #111; color: #fff; text-align: left;
            padding: 7px 8px; font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.5px;
            white-space: nowrap;
        }
        tbody td {
            padding: 7px 8px; border-bottom: 1px solid #e5e7eb;
            font-size: 13.5px;
        }
        tbody tr:nth-child(even) { background: #f9fafb; }

        td.num {
            width: 48px; text-align: center;
            font-weight: bold; font-size: 16px; color: #111;
        }
        td.num-ffe {
            width: 55px; text-align: center;
            font-size: 12px; color: #555; font-family: 'Courier New', monospace;
        }
        td.cavalier, td.cheval, td.club { white-space: nowrap; }
        td.cavalier { font-weight: 600; }
        td.cheval { color: #333; font-style: italic; }
        td.club { color: #666; font-size: 13px; }

        .empty { text-align: center; color: #888; padding: 40px 0; font-style: italic; }

        /* Pied de page */
        .footer {
            margin-top: 30px; text-align: center;
            font-size: 11px; color: #999;
            border-top: 1px solid #e5e7eb; padding-top: 10px;
        }

        /* Boutons a l'ecran (caches a l'impression) */
        .no-print { margin-top: 30px; text-align: center; }
        .no-print button {
            padding: 10px 24px; background: #4f46e5; color: white;
            border: none; border-radius: 4px; font-size: 14px; cursor: pointer;
            margin: 0 5px;
        }
        .no-print button:hover { background: #4338ca; }
        .no-print button.secondary { background: #6b7280; }
        .no-print button.secondary:hover { background: #4b5563; }

        @media print {
            .no-print { display: none; }
            body { padding: 6mm 7mm; }
            tbody tr { break-inside: avoid; }
            thead { display: table-header-group; } /* repete l'entete sur chaque page */
        }
        /* margin: 0 supprime les en-tetes/pieds de page du navigateur
           (URL, titre, date, numero de page). Le padding du body compense. */
        @page { margin: 0; size: A4; }
    </style>
</head>
<body>
    {{-- En-tete optionnelle (bandeau large) si l'image existe dans public/ --}}
    @php
        $banner = public_path('startlist-header.png');
        $bannerExists = file_exists($banner);
    @endphp
    @if ($bannerExists)
        <div class="entete-banner">
            <img src="{{ asset('startlist-header.png') }}" alt="En-tete">
        </div>
    @endif

    {{-- Header: logo + titre + date --}}
    <div class="header">
        <div class="logo">
            <img src="{{ asset('logo-equivallee-grand-format.png') }}" alt="Equivallee">
        </div>
        <div class="titles">
            <div class="region">Championnat Régional BOURGOGNE FRANCHE-COMTE</div>
            <div class="titre">{{ $titre }}</div>
            <div class="date">{{ $date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</div>
        </div>
    </div>

    {{-- Start list --}}
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
    @endif

    <div class="footer">
        {{ count($rows) }} partant{{ count($rows) > 1 ? 's' : '' }}
    </div>

    <div class="no-print">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        <button type="button" class="secondary" onclick="window.history.back()">Retour</button>
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
