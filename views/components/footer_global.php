<?php
/**
 * views/components/footer_global.php — Footer client global
 * Design System v2.0 | CL §22
 *
 * Variable attendue :
 *   $page_root — chemin racine (ex: '/coiffons')
 */
$page_root = $page_root ?? '/coiffons';
?>
<footer class="cct-footer" role="contentinfo">
    <div class="container">
        <div class="row g-4">

            <!-- Colonne Brand -->
            <div class="col-12 col-md-4">
                <div class="footer-brand">Coiffe Chez Toi</div>
                <p class="footer-desc">
                    L'élégance à domicile au Bénin. Nous connectons les meilleurs talents
                    avec ceux qui exigent l'excellence.
                </p>
            </div>

            <!-- Colonne Navigation -->
            <div class="col-6 col-md-2">
                <div class="footer-section-title">Navigation</div>
                <a href="<?= $page_root ?>/index.php"                         class="footer-link">Accueil</a>
                <a href="<?= $page_root ?>/filter/annuaire_coiffeurs.php"      class="footer-link">Annuaire</a>
                <a href="<?= $page_root ?>/client/mes_rendezvous.php"          class="footer-link">Mes RDV</a>
                <a href="<?= $page_root ?>/client/catalogue.php"               class="footer-link">Catalogue</a>
            </div>

            <!-- Colonne Légal -->
            <div class="col-6 col-md-2">
                <div class="footer-section-title">Légal</div>
                <a href="#" class="footer-link">À propos</a>
                <a href="#" class="footer-link">Confidentialité</a>
                <a href="#" class="footer-link">Conditions</a>
            </div>

            <!-- Colonne Contact -->
            <div class="col-12 col-md-4 text-md-end">
                <div class="footer-section-title">Contact</div>
                <p style="color:rgba(255,255,255,0.35);font-size:0.82rem;margin-bottom:12px;">
                    contact@coiffecheztoi.bj
                </p>
                <div class="footer-social">
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                </div>
            </div>

        </div>

        <hr class="divider-gold" style="margin-top:2rem;">

        <div class="footer-bottom">
            &copy; <?= date('Y') ?> Coiffe Chez Toi &middot; Une branche Domizi
        </div>
    </div>
</footer>
<?php unset($page_root); ?>
