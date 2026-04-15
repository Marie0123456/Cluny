<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Apercu polices - Liste de depart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Parisienne&family=Kaushan+Script&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700;1,900&family=Yellowtail&family=Dancing+Script:wght@700&family=Sacramento&family=Allura&family=Alex+Brush&family=Pacifico&family=Satisfy&family=Cookie&family=Pinyon+Script&family=Tangerine:wght@700&family=Montserrat:wght@400;600;700&family=Bebas+Neue&family=Oswald:wght@500;700&family=Poppins:wght@600;700&family=Raleway:wght@600;700&family=Cormorant+Garamond:ital,wght@0,700;1,700&family=Lora:ital,wght@0,700;1,700&family=Archivo+Black&family=Anton&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            color: #111; background: #f9fafb;
            padding: 30px 40px;
        }
        h1 {
            font-size: 22px; margin-bottom: 4px;
        }
        .subtitle {
            font-size: 14px; color: #666; margin-bottom: 30px;
        }
        .sample {
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 8px; padding: 24px 28px;
            margin-bottom: 18px;
            display: flex; align-items: center; gap: 30px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .sample:hover { border-color: #F97316; box-shadow: 0 2px 8px rgba(249,115,22,0.15); }
        .label {
            flex: 0 0 220px;
            font-size: 13px; color: #666;
            border-right: 1px solid #e5e7eb; padding-right: 20px;
        }
        .label strong { display: block; font-size: 15px; color: #111; margin-bottom: 3px; }
        .demo {
            flex: 1; color: #F97316;
            line-height: 1.1; overflow: hidden;
        }
        .demo .titre-principal { display: block; }
        .demo .titre-secondaire {
            font-family: 'Montserrat', sans-serif;
            font-size: 20px; font-weight: 700; color: #111;
            margin-top: 4px; text-align: center; max-width: 400px;
        }

        h2 {
            margin-top: 40px; margin-bottom: 6px;
            font-size: 18px; color: #111;
            border-bottom: 2px solid #F97316; padding-bottom: 6px;
        }

        /* Polices titre (cursif) */
        .f-great-vibes      .titre-principal { font-family: 'Great Vibes', cursive;      font-size: 64px; }
        .f-parisienne       .titre-principal { font-family: 'Parisienne', cursive;       font-size: 54px; }
        .f-kaushan          .titre-principal { font-family: 'Kaushan Script', cursive;   font-size: 54px; }
        .f-playfair         .titre-principal { font-family: 'Playfair Display', serif;   font-size: 54px; font-style: italic; font-weight: 900; }
        .f-yellowtail       .titre-principal { font-family: 'Yellowtail', cursive;       font-size: 58px; }
        .f-dancing          .titre-principal { font-family: 'Dancing Script', cursive;   font-size: 58px; font-weight: 700; }
        .f-sacramento       .titre-principal { font-family: 'Sacramento', cursive;       font-size: 64px; }
        .f-allura           .titre-principal { font-family: 'Allura', cursive;           font-size: 64px; }
        .f-alex-brush       .titre-principal { font-family: 'Alex Brush', cursive;       font-size: 68px; }
        .f-pacifico         .titre-principal { font-family: 'Pacifico', cursive;         font-size: 54px; }
        .f-satisfy          .titre-principal { font-family: 'Satisfy', cursive;          font-size: 54px; }
        .f-cookie           .titre-principal { font-family: 'Cookie', cursive;           font-size: 60px; }
        .f-pinyon           .titre-principal { font-family: 'Pinyon Script', cursive;    font-size: 66px; }
        .f-tangerine        .titre-principal { font-family: 'Tangerine', cursive;        font-size: 80px; font-weight: 700; }

        /* Polices sous-titre - on garde Kaushan Script pour le titre principal */
        .subt-demo .titre-principal {
            font-family: 'Kaushan Script', cursive;
            font-size: 54px;
        }
        .s-montserrat       .titre-secondaire { font-family: 'Montserrat', sans-serif;    font-size: 20px; font-weight: 700; }
        .s-bebas            .titre-secondaire { font-family: 'Bebas Neue', sans-serif;    font-size: 28px; font-weight: 400; letter-spacing: 2px; }
        .s-oswald           .titre-secondaire { font-family: 'Oswald', sans-serif;        font-size: 22px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .s-poppins          .titre-secondaire { font-family: 'Poppins', sans-serif;       font-size: 20px; font-weight: 700; }
        .s-raleway          .titre-secondaire { font-family: 'Raleway', sans-serif;       font-size: 20px; font-weight: 700; letter-spacing: 1px; }
        .s-playfair         .titre-secondaire { font-family: 'Playfair Display', serif;   font-size: 22px; font-weight: 700; }
        .s-playfair-ital    .titre-secondaire { font-family: 'Playfair Display', serif;   font-size: 22px; font-weight: 700; font-style: italic; }
        .s-cormorant        .titre-secondaire { font-family: 'Cormorant Garamond', serif; font-size: 24px; font-weight: 700; }
        .s-cormorant-ital   .titre-secondaire { font-family: 'Cormorant Garamond', serif; font-size: 24px; font-weight: 700; font-style: italic; }
        .s-lora             .titre-secondaire { font-family: 'Lora', serif;               font-size: 21px; font-weight: 700; }
        .s-archivo          .titre-secondaire { font-family: 'Archivo Black', sans-serif; font-size: 20px; font-weight: 400; letter-spacing: 1px; }
        .s-anton            .titre-secondaire { font-family: 'Anton', sans-serif;         font-size: 24px; font-weight: 400; letter-spacing: 1.5px; text-transform: uppercase; }

        .current-badge {
            display: inline-block; margin-left: 8px; padding: 2px 8px;
            background: #F97316; color: white; border-radius: 12px;
            font-size: 11px; font-weight: 600;
        }
    </style>
</head>
<body>
    <h1>Apercu des polices - Start list</h1>
    <p class="subtitle">Exemple de rendu du titre et du sous-titre (nom du championnat). La police actuellement utilisee dans le PDF est marquee.</p>

    <h2>1. Titre principal - 'Liste de depart' (14 options)</h2>

    @php
        $fonts = [
            ['class' => 'f-kaushan',     'name' => 'Kaushan Script',       'style' => 'Brush stroke, dynamique et audacieux',          'current' => true],
            ['class' => 'f-parisienne',  'name' => 'Parisienne',           'style' => 'Cursive moderne, tres design et aeree'],
            ['class' => 'f-great-vibes', 'name' => 'Great Vibes',          'style' => 'Classique, elegante, invitation formelle'],
            ['class' => 'f-playfair',    'name' => 'Playfair Display Italic','style' => 'Serif italique editorial, chic'],
            ['class' => 'f-yellowtail',  'name' => 'Yellowtail',           'style' => 'Calligraphie pinceau, hauteur variable'],
            ['class' => 'f-dancing',     'name' => 'Dancing Script',       'style' => 'Ecriture manuscrite joyeuse et moderne'],
            ['class' => 'f-sacramento',  'name' => 'Sacramento',           'style' => 'Classique equilibree, fine'],
            ['class' => 'f-allura',      'name' => 'Allura',               'style' => 'Raffinee, style faire-part de mariage'],
            ['class' => 'f-alex-brush',  'name' => 'Alex Brush',           'style' => 'Effet pinceau, traits epais, artistique'],
            ['class' => 'f-pacifico',    'name' => 'Pacifico',             'style' => 'Retro fun, arrondie et friendly'],
            ['class' => 'f-satisfy',     'name' => 'Satisfy',              'style' => 'Moderne, fluide, discrete'],
            ['class' => 'f-cookie',      'name' => 'Cookie',               'style' => 'Douce, lisible, moderne'],
            ['class' => 'f-pinyon',      'name' => 'Pinyon Script',        'style' => 'Calligraphie ultra-fine, tres formelle'],
            ['class' => 'f-tangerine',   'name' => 'Tangerine',            'style' => 'Calligraphie fine et haute, elegante'],
        ];
    @endphp

    @foreach ($fonts as $f)
        <div class="sample">
            <div class="label">
                <strong>
                    {{ $f['name'] }}
                    @if ($f['current'] ?? false)
                        <span class="current-badge">actuelle</span>
                    @endif
                </strong>
                {{ $f['style'] }}
            </div>
            <div class="demo {{ $f['class'] }}">
                <span class="titre-principal">Liste de départ</span>
                <div class="titre-secondaire">CSO Club Elite</div>
            </div>
        </div>
    @endforeach

    <h2>2. Sous-titre - nom du championnat (12 options)</h2>
    <p class="subtitle">Le titre reste en Kaushan Script. Seule la police du sous-titre 'CSO Club Elite' change.</p>

    @php
        $subFonts = [
            ['class' => 's-montserrat',     'name' => 'Montserrat Bold',        'style' => 'Moderne, sans serif, tres lisible',               'current' => true],
            ['class' => 's-poppins',        'name' => 'Poppins Bold',           'style' => 'Sans serif geometrique, moderne et clean'],
            ['class' => 's-raleway',        'name' => 'Raleway Bold',           'style' => 'Sans serif fine et elegante, espacee'],
            ['class' => 's-oswald',         'name' => 'Oswald Bold Caps',       'style' => 'Condensee haute et impactante, tout capitales'],
            ['class' => 's-bebas',          'name' => 'Bebas Neue',             'style' => 'Tres condensee, affiche, sportive'],
            ['class' => 's-anton',          'name' => 'Anton Caps',             'style' => 'Condensee massive, tres impact'],
            ['class' => 's-archivo',        'name' => 'Archivo Black',          'style' => 'Sans serif epaisse, rond, puissante'],
            ['class' => 's-playfair',       'name' => 'Playfair Display',       'style' => 'Serif classique elegant, contraste fort'],
            ['class' => 's-playfair-ital',  'name' => 'Playfair Display Italic','style' => 'Serif italique, editorial et chic'],
            ['class' => 's-cormorant',      'name' => 'Cormorant Garamond',     'style' => 'Serif Garamond raffinee, style livre ancien'],
            ['class' => 's-cormorant-ital', 'name' => 'Cormorant Garamond Italic','style' => 'Serif italique, tres romantique'],
            ['class' => 's-lora',           'name' => 'Lora Bold',              'style' => 'Serif moderne lisible, discrete'],
        ];
    @endphp

    @foreach ($subFonts as $f)
        <div class="sample subt-demo {{ $f['class'] }}">
            <div class="label">
                <strong>
                    {{ $f['name'] }}
                    @if ($f['current'] ?? false)
                        <span class="current-badge">actuelle</span>
                    @endif
                </strong>
                {{ $f['style'] }}
            </div>
            <div class="demo">
                <span class="titre-principal">Liste de départ</span>
                <div class="titre-secondaire">CSO Club Elite</div>
            </div>
        </div>
    @endforeach

    <p style="margin-top: 30px; font-size: 13px; color: #666;">
        Pour changer la police utilisee dans le PDF, indique-moi ton choix pour le titre et/ou le sous-titre.
    </p>
</body>
</html>
