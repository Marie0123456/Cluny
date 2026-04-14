<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $championnat->discipline->value }} - {{ $championnat->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111; padding: 24px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #111; padding-bottom: 14px; }
        .region { font-size: 16px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #333; }
        h1 { font-size: 20px; margin-top: 6px; font-weight: 600; color: #444; letter-spacing: 0.5px; }
        h2 { font-size: 42px; margin-top: 8px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f0f0f0; text-align: left; padding: 8px 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #111; }
        td { padding: 10px; border-bottom: 1px solid #ddd; font-size: 16px; }
        td.rank { width: 70px; font-size: 22px; font-weight: bold; text-align: center; }
        td.rank-1 { color: #b8860b; }
        td.rank-2 { color: #7a7a7a; }
        td.rank-3 { color: #a05a2c; }
        .cavalier { font-weight: 600; }
        .cheval { color: #333; font-style: italic; }
        .club { color: #666; font-size: 14px; }
        .empty { text-align: center; color: #888; padding: 40px 0; font-style: italic; }
        .no-print { margin-top: 30px; text-align: center; }
        .no-print button { padding: 10px 24px; background: #4f46e5; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; }
        .no-print button:hover { background: #4338ca; }
        @media print {
            .no-print { display: none; }
            body { padding: 10mm; }
        }
        @page { margin: 10mm; size: A4; }
    </style>
</head>
<body>
    <div class="header">
        <div class="region">Championnat Régional BOURGOGNE FRANCHE-COMTE</div>
        <h2>{{ $championnat->nom }}</h2>
        <h1>{{ mb_strtoupper($championnat->discipline->value) }}</h1>
    </div>

    @if ($classement->isEmpty())
        <div class="empty">Aucun résultat classé pour ce championnat.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Cavalier</th>
                    <th>Cheval</th>
                    <th>Club</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($classement as $entry)
                    <tr>
                        <td class="rank rank-{{ $entry['rang'] }}">{{ $entry['rang'] }}</td>
                        <td class="cavalier">{{ trim($entry['cavalier_prenom'] . ' ' . $entry['cavalier_nom']) }}</td>
                        <td class="cheval">{{ $entry['cheval_nom'] }}</td>
                        <td class="club">{{ $entry['club'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="no-print">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
