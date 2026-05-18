<?php 
// Lancement intelligent de la session avant d'inclure le header si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php'; 

// Définition de la page de destination selon le statut de connexion
// Si connecté, on l'envoie vers la suite (ex: mes_rendezvous.php), sinon vers l'inscription
$lien_bouton = isset($_SESSION['id_user']) ? "mes_rendezvous.php" : "inscription.php";
?>

<main>
    <section class="py-5 text-center container">
        <div class="row py-lg-5">
            <div class="col-lg-6 col-md-8 mx-auto">
                <h1 class="display-4 fw-bold">Coiffe_Chez_Toi</h1>
                <p class="lead text-light"> Coiffe chez toi est un site/app qui vous permet d'avoir un coiffeur à
                    votre disposition chez vous et ainsi il vous est possible de vous rendre
                    beau chez vous . Avec un coiffeur doué et qui a une technique de coupe bien maitrisée pour
                    vous rendre beau chez vous .
                    Coiffe chez toi vous permet de prendre un rendez-vous ,
                    d'avoir votre coiffeur et votre coiffure chez vous sans vous déplacer
                    et celà juste en quelques clics .</p>
                <p>
                    <a href="<?php echo $lien_bouton; ?>" class="btn btn-gold btn-lg my-2 px-5">Prenez rendez-vous</a>
                </p>
            </div>
        </div>
    </section>

    <div class="album py-5">
        <div class="container">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">

                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="images/ara.jpg" class="card-img-top" style="height:250px; object-fit:cover;">
                        <div class="card-body">
                            <h5>SISCO</h5>
                            <p class="card-text">SISCO Coupe basse la coupe classique de la vieille école .C'est la plus particulière de toutes
                                et est adaptée à tout type de tête et tout les genres. Entretenez regulierement
                                chaque deux semaines pour garder frais et surtout brossez regulièrement . Cette coupe peut varier
                                selon la quantité de cheveux que vous voulez . Peut être accompagnée de teinte de couleurs variées (noire,
                                blanc,or etc)</p>
                            <a href="<?php echo $lien_bouton; ?>" class="btn btn-gold w-100">Prendre RDV</a>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="images/bas.jpg" class="card-img-top" style="height:250px; object-fit:cover;">
                        <div class="card-body">
                            <h5>LOW FADE</h5>
                            <p class="card-text">LOW fade est la forme de dégradée la plus demandée par les hommes car en plus d'être
                                moderne elle est très discrète . Entretenez régulierement
                                chaque deux semaines pour garder frais et surtout brossez regulièrement . Cette coupe peut varier
                                selon la quantité de cheveux que vous voulez . Peut être accompagnée de teinte de couleurs variées (noire,
                                blanc,or etc) .</p>
                            <a href="<?php echo $lien_bouton; ?>" class="btn btn-gold w-100">Prendre RDV</a>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="images/burst.jpg" class="card-img-top" style="height:250px; object-fit:cover;">
                        <div class="card-body">
                            <h5>BURST FADE</h5>
                            <p class="card-text">Burst Fade coupe dégradée en démi cercle autour de l'oreille . Audacieuse , moderne et met en valeur
                                la forme de votre crâne et de votre machoire . Adaptée à presque toute les formes de visages (rond,carré
                                ovale , allongé) . Entretenez régulierement
                                chaque deux semaines pour garder frais.Peut être accompagnée de teinte de couleurs variées (noire,
                                blanc,or etc) .</p>
                            <a href="<?php echo $lien_bouton; ?>" class="btn btn-gold w-100">Prendre RDV</a>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="images/coupe.jpg" class="card-img-top" style="height:250px; object-fit:cover;">
                        <div class="card-body">
                            <h5>HIGH FADE</h5>
                            <p class="card-text"> High fade ou dégradé haut : coupe homme où le fondu (dégradé) commence haut
                                au niveau de la tempe . Look moderne, net et très structuré . Entretenez regulierement
                                chaque deux semaines pour garder frais .Idéale pour visage carrés ou rond. soyez conscient que cette
                                coupe demande qu'on rase une grande partie sur les cotés .Peut être accompagnée de teinte de couleurs variées (noire,
                                blanc,or etc) .</p>
                            <a href="<?php echo $lien_bouton; ?>" class="btn btn-gold w-100">Prendre RDV</a>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="images/dame.jpg" class="card-img-top" style="height:250px; object-fit:cover;">
                        <div class="card-body">
                            <h5>TWA (Femme)</h5>
                            <p class="card-text">Coupe dégradée dame moderne.TWA(Teeny Weeny Afro) communément appelée afro est une coupe dégradée dame très moderne et chic qui peut avoir
                                plusieurs variations et comme ici peut être teinté et degradé et de manière raisonnable . Requiert des cheveux crépus
                                pour un rendu plus optimal et de l'entretien pour les ondulés .</p>
                            <a href="<?php echo $lien_bouton; ?>" class="btn btn-gold w-100">Prendre RDV</a>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="images/taperr.jpg" class="card-img-top" style="height:250px; object-fit:cover;">
                        <div class="card-body">
                            <h5>TAPER</h5>
                            <p class="card-text">Mélange de diminution et dégradé .Taper ou dégradé progressif est une coupe mixte qui mélange deux techniques : la diminution et le dégradé
                                progressif et peu avoir plusieurs variantes selon la taille de vos cheveux , la forme de votre tête aussi .
                                Adaptée à tout types de visages et tout les sexes .Entretenez régulierement
                                chaque deux semaines pour garder frais . </p>
                            <a href="<?php echo $lien_bouton; ?>" class="btn btn-gold w-100">Prendre RDV</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <section class="py-5 bg-dark">
        <div class="container">
            <h2 class="text-center mb-5" style="color: var(--gold);">Avis & Témoignages</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card p-3 border-warning">
                        <p class="fst-italic">"Le Burst Fade est parfait !"</p>
                        <footer class="text-warning">- Marc A.</footer>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>