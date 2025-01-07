<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlyGabon - Port Harcourt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #5564a2;
        }
        .container {
            text-align: center;
            padding: 20px;
            background-color: #FFD700;
            color: #000;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
        }
        .header img {
            height: 50px;
        }
        .content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 800px;
            text-align: left;
            color: #5564a2;
        }
        .content h1 {
            color: #5564a2;
            font-size: 24px;
            text-align: center;
        }
        .content img {
            width: 100%;
            border-radius: 10px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
            color: #5564a2;
        }
        .details {
            margin-top: 20px;
        }
        .details h2 {
            font-size: 18px;
            color: #5564a2;
            text-decoration: underline;
        }
        .details ul {
            list-style-type: none;
            padding: 0;
        }
        .details ul li {
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            color: #5564a2;
        }
        .footer a {
            text-decoration: none;
            color: #007BFF;
        }
        .logos img {
            height: 50px;
        }
        .footer .logo img {
            width: 250px;
            height: 100px;
        }
        .footer .logos img {
            width: 200px;
            height: 100px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content {
                padding: 15px;
                margin: 10px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: center;
            }
            .header img {
                height: 40px;
                margin-bottom: 10px;
            }
            .content {
                padding: 15px;
                margin: 10px;
            }
            .footer {
                flex-direction: column;
                gap: 10px;
            }
            .footer .logo img, .footer .logos img {
                width: 150px;
                height: auto;
            }
        }

        @media (max-width: 480px) {
            .content p {
                font-size: 14px;
            }
            .details h2 {
                font-size: 16px;
            }
            .footer .logo img, .footer .logos img {
                width: 120px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <img src="header.png" style="width: 50%; height: 50%" alt="FlyGabon">
</div>
<div class="content">
    <p><strong>OBJET : VOL DIRECT LIBREVILLE – PORT HARCOURT</strong></p>
    <p>Chers voyageurs fréquents,</p>
    <p>Nous avons le plaisir de vous annoncer le lancement d’une nouvelle ligne directe entre Libreville et Port Harcourt, pensée pour vous offrir une solution de transport plus rapide, plus pratique et parfaitement adaptée à vos besoins de déplacements réguliers.</p>

    <div class="details">
        <h2>Détails de la nouvelle ligne :</h2>
        <ul>
            <li><strong><img src="vol.png" style="height: 15px; width: 20px" alt="Nuage" class="cloud-image"> 2 VOLS/SEMAINE</strong></li>
            <li>Fréquence Aller : Mardis et vendredis</li>
            <li>Fréquence Retour : Mercredis et vendredis</li>
            <li><img src="horloge.png" style="height: 20px; width: 20px" alt="Nuage" class="cloud-image"> <strong>1H15</strong></li>
        </ul>
    </div>

    <p>Nous avons à cœur de vous offrir des solutions sur mesure qui facilitent vos déplacements, avec la possibilité de réserver vos billets via notre adresse mail dédiée <a href="mailto:reservation@afrijet.com">reservation@afrijet.com</a>.</p>

    <p>Nous sommes impatients de continuer à vous accompagner dans vos trajets professionnels et de rendre vos déplacements plus fluides et efficaces.</p>

    <div style="text-align: left">
        <p>Bien à vous,</p>
        <p><a href="https://flygabon.online">flygabon.online</a></p>
    </div>

    <div class="footer">
        <img src="footer_logo.png" alt="Afrijet">
    </div>
</div>
</body>
</html>
