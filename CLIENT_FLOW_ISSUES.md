# 🔴 PROBLÈMES CRITIQUES - CLIENT FLOW

**Date**: 14 Août 2026  
**Sévérité**: 🔴 CRITIQUE - Bloque le flux client complet  
**Fichiers impliqués**: 8+ fichiers

---

## 📋 LISTE DES PROBLÈMES

### PROBLÈME 1: Status "Vérification en Attente" sur les Cards
**Fichier**: `/filter/annuaire_coiffeurs.php`  
**Ligne**: ~700-750 (section cards)  
**Sévérité**: 🟡 MAJEURE

#### Problème:
Les cards affichent un statut "Vérification en Attente" qui ne se met pas à jour même après approbation.

#### Cause:
Les cards affichent probablement `is_approved` ou une colonne statique qui ne reflète pas l'état réel de la BD.

#### Solution:
Vérifier que les cards affichent dynamiquement `is_approved = 1` au lieu d'un statut hardcodé.

```php
// À chercher dans le HTML des cards:
❌ MAUVAIS:
<span>Vérification en Attente</span>

✅ BON:
<?php if ($coiffeur['is_approved'] == 1): ?>
    <span class="badge bg-success">Approuvé</span>
<?php endif; ?>
```

---

### PROBLÈME 2: Pas de Bouton "Réserver cette Coupe" sur le Profil Coiffeur
**Fichier**: `/coiffeurs/profil_public.php` OU `/coiffeurs/profil_coiffeurs.php`  
**Sévérité**: 🔴 CRITIQUE

#### Problème:
Quand on clique sur le profil d'un coiffeur pour voir ses prestations, il n'y a pas de bouton "Réserver".

#### Impact:
- ❌ Utilisateurs non connectés: Impossible de réserver
- ❌ Utilisateurs connectés: Impossible de lancer une réservation
- ❌ Flux complètement bloqué

#### Solution Requise:
Ajouter un bouton "Réserver cette Coupe" ou "Prendre RDV" qui:

**Si NON connecté**:
```php
// Rediriger vers login avec URL de retour
header("Location: /coiffons/access/connexion.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
```

**Si connecté**:
```php
// Ouvrir le formulaire de réservation
window.location.href = '/coiffons/client/creer_rendezvous.php?coiffeur_id=' + <?php echo $coiffeur_id; ?>;
```

---

### PROBLÈME 3: Écran Blanc Derrière la Photo de Profil du Coiffeur
**Fichier**: `/coiffeurs/profil_public.php` OU `/coiffeurs/profil_coiffeurs.php`  
**Sévérité**: 🟡 MAJEURE

#### Problème:
Un gros écran blanc apparaît derrière la photo de profil du coiffeur au lieu d'une couleur/gradient approprié.

#### Cause:
L'arrière-plan n'a pas de couleur CSS définie ou utilise `background-color: white`.

#### Solution:
Modifier le CSS:

```css
/* AVANT - MAUVAIS */
.coiffeur-profile-cover {
    background-color: white;  /* ❌ Blanc pur */
}

/* APRÈS - BON */
.coiffeur-profile-cover {
    background: linear-gradient(135deg, var(--dark-2) 0%, var(--dark-3) 100%);
    background-color: var(--dark-2);  /* Fallback */
}

/* OU si utilisant image */
.coiffeur-profile-bg {
    background: linear-gradient(rgba(10,10,10,0.7), rgba(10,10,10,0.7)),
                url('..../bg-image.jpg') center/cover;
}
```

---

### PROBLÈME 4: Blanc en Arrière-Plan des Carrousels (Client Connecté)
**Fichier**: `/views/client/dashboard.php` OU `/views/components/hero_capsule.php`  
**Sévérité**: 🟡 MAJEURE

#### Problème:
Quand un client connecté voit l'écran d'accueil, les images du carrousel ont du blanc en arrière-plan.

#### Cause:
Le conteneur du carrousel n'a pas de `background-color` ou n'a pas de classe CSS appropriée.

#### Solution:
Ajouter CSS au conteneur du carrousel:

```css
.carousel-container {
    background: linear-gradient(135deg, var(--dark-2) 0%, var(--dark-3) 100%);
    background-color: var(--dark-2);  /* Fallback */
}

.carousel-item {
    background-color: var(--dark-2);  /* Pas de blanc */
    min-height: 400px;
}

.carousel-item img {
    object-fit: cover;
    background-color: var(--dark-2);  /* Fallback pour images non trouvées */
}
```

---

### PROBLÈME 5: Coiffeurs de la Même Ville N'Apparaissent Pas au Dashboard Client
**Fichier**: `/views/client/dashboard.php`  
**Sévérité**: 🔴 CRITIQUE

#### Problème:
Client connecté à Abomey-Calavi ne voit pas les coiffeurs de cette ville.

#### Cause Probable:
1. **Filtre SQL incorrecte** - `WHERE is_approved = 1 AND ville = ?` ne correspond pas
2. **Session ville incorrecte** - `$_SESSION['id_ville']` n'est pas défini
3. **Données en BD** - Coiffeurs n'ont pas la ville correctement définie
4. **Join table incorrecte** - Les coiffeurs ne sont pas linkés à la table villes

#### Diagnostic:
```sql
-- Vérifier les coiffeurs de Abomey-Calavi
SELECT id, nom, prenom, ville, is_approved FROM users 
WHERE role = 'coiffeur' AND ville = (
    SELECT id FROM villes WHERE nom_ville LIKE '%Abomey%'
);

-- Vérifier la ville ID
SELECT id, nom_ville FROM villes WHERE nom_ville LIKE '%Abomey%';

-- Vérifier l'ID de la ville en session
-- À afficher dans le dashboard client
```

#### Solutions:

**Solution 1: Vérifier la Session**
Fichier: `/views/client/dashboard.php`
```php
<?php
// DEBUG: Afficher la ville en session
echo "<!-- DEBUG: id_ville = " . $_SESSION['id_ville'] . " -->";
echo "<!-- DEBUG: id_quartier = " . $_SESSION['id_quartier'] . " -->";
?>
```

**Solution 2: Query Correcte**
Fichier: `/views/client/dashboard.php`
```php
$sql = "SELECT u.*, v.nom_ville, q.nom_quartier
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        WHERE u.role = 'coiffeur'
          AND u.is_approved = 1
          AND u.abonnement_status = 1
          AND u.ville = ?
        ORDER BY note_moyenne DESC, nb_avis DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['id_ville']]);
$coiffeurs = $stmt->fetchAll();
```

**Solution 3: Fallback si Pas de Session**
```php
if (!isset($_SESSION['id_ville']) || $_SESSION['id_ville'] == 0) {
    // Afficher message "Sélectionnez votre zone"
    echo "Veuillez sélectionner votre zone pour voir les coiffeurs disponibles";
} else {
    // Afficher coiffeurs de la zone
}
```

---

### PROBLÈME 6: Pas de Card "Client" pour le Coiffeur Après RDV Confirmé
**Fichier**: `/coiffeurs/valider_rendezvous.php` OU `/views/coiffeur/dashboard.php`  
**Sévérité**: 🔴 CRITIQUE

#### Problème:
Après qu'un coiffeur confirme un RDV, il n'y a pas de card affichant:
- Nom + Prénom du client
- Photo du client
- Téléphone/WhatsApp du client
- Détails du RDV (date, heure, prestation)
- Bouton WhatsApp pour contacter le client

#### Solution Requise:
Créer une nouvelle card ou widget "Rendez-vous Confirmé" dans le dashboard coiffeur.

**Fichier à modifier**: `/views/coiffeur/dashboard.php`

**Code à ajouter**:
```php
<?php
// Récupérer les RDV confirmés aujourd'hui/demain
$rdv_confirmes = $pdo->prepare("
    SELECT r.*, 
           uc.nom, uc.prenom, uc.photo_profil, uc.telephone,
           p.nom_style, p.prix
    FROM rendez_vous r
    JOIN users uc ON r.client_id = uc.id
    JOIN prestations p ON r.coiffure_id = p.id_prestation
    WHERE r.coiffeur_id = ?
      AND r.statut_rdv = 'confirme'
      AND DATE(r.date_rdv) >= CURDATE()
    ORDER BY r.date_rdv ASC
");
$rdv_confirmes->execute([$_SESSION['id_user']]);
$rdvs = $rdv_confirmes->fetchAll();
?>

<!-- SECTION: RDV Confirmés -->
<div class="section-rdv-confirmes">
    <h3>📋 Rendez-vous Confirmés</h3>
    
    <?php if (empty($rdvs)): ?>
        <p class="text-muted">Aucun rendez-vous confirmé</p>
    <?php else: ?>
        <?php foreach ($rdvs as $rdv): ?>
            <div class="card-rdv-confirme">
                <!-- Avatar Client -->
                <div class="rdv-avatar">
                    <?php if ($rdv['photo_profil']): ?>
                        <img src="<?php echo htmlspecialchars($rdv['photo_profil']); ?>" 
                             alt="<?php echo htmlspecialchars($rdv['nom']); ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($rdv['prenom'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info Client & RDV -->
                <div class="rdv-info">
                    <h4><?php echo htmlspecialchars($rdv['nom'] . ' ' . $rdv['prenom']); ?></h4>
                    <p class="rdv-details">
                        <i class="bi bi-scissors"></i> <?php echo htmlspecialchars($rdv['nom_style']); ?><br>
                        <i class="bi bi-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($rdv['date_rdv'])); ?><br>
                        <i class="bi bi-phone"></i> <?php echo htmlspecialchars($rdv['telephone']); ?>
                    </p>
                </div>
                
                <!-- Boutons Action -->
                <div class="rdv-actions">
                    <!-- Bouton WhatsApp -->
                    <?php
                    $whatsapp_num = preg_replace('/[^0-9]/', '', $rdv['telephone']);
                    if (strlen($whatsapp_num) < 10) $whatsapp_num = '229' . $whatsapp_num;
                    $msg = "Bonjour " . $rdv['prenom'] . ", je confirme votre RDV pour " . 
                           date('d/m à H:i', strtotime($rdv['date_rdv'])) . " ✂️";
                    ?>
                    <a href="https://wa.me/<?php echo $whatsapp_num; ?>?text=<?php echo urlencode($msg); ?>" 
                       target="_blank" 
                       class="btn btn-whatsapp btn-sm">
                        <i class="bi bi-whatsapp"></i> Contacter
                    </a>
                    
                    <!-- Bouton Statut -->
                    <a href="/coiffons/coiffeurs/valider_rendezvous.php?id=<?php echo $rdv['id']; ?>&action=terminer"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-check"></i> Terminé
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.card-rdv-confirme {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(212,175,55,0.2);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    display: flex;
    gap: 16px;
    align-items: center;
}

.rdv-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.rdv-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: rgba(212,175,55,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: var(--gold);
}

.rdv-info {
    flex: 1;
}

.rdv-info h4 {
    margin: 0;
    font-weight: 700;
}

.rdv-details {
    font-size: 0.85rem;
    color: #ccc;
    margin: 8px 0 0;
    line-height: 1.6;
}

.rdv-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.btn-whatsapp {
    background-color: #25D366;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.8rem;
}

.btn-whatsapp:hover {
    background-color: #1ebd54;
}
</style>
```

---

## 🎯 PRIORITÉS

### 🔴 IMMÉDIAT (Bloque le flux):
1. **Problème 5** - Coiffeurs ne s'affichent pas au dashboard client
2. **Problème 2** - Pas de bouton "Réserver"
3. **Problème 6** - Pas de card client pour coiffeur

### 🟡 URGENT (Mauvaise UX):
1. **Problème 3** - Écran blanc derrière photo
2. **Problème 4** - Blanc en carrousels
3. **Problème 1** - Status pas à jour

---

## 📁 FICHIERS À MODIFIER

| Problème | Fichier | Action |
|----------|---------|--------|
| 1 | `/filter/annuaire_coiffeurs.php` | Vérifier affichage status |
| 2 | `/coiffeurs/profil_public.php` | Ajouter bouton Réserver |
| 3 | `/coiffeurs/profil_public.php` | Ajouter CSS background |
| 4 | `/views/client/dashboard.php` | Ajouter CSS carrousel |
| 5 | `/views/client/dashboard.php` | Vérifier query SQL + session |
| 6 | `/views/coiffeur/dashboard.php` | Ajouter card RDV confirmés |

---

## ✅ CHECKLIST

- [ ] Problème 1: Status dynamique sur cards
- [ ] Problème 2: Bouton réserver sur profil
- [ ] Problème 3: Background derrière photo
- [ ] Problème 4: Background carrousels
- [ ] Problème 5: Coiffeurs affichés au dashboard
- [ ] Problème 6: Card client au dashboard coiffeur

---

**STATUS**: 🔴 CRITIQUE  
**IMPACT**: Flux client complètement bloqué  
**TEMPS ESTIMÉ**: 60-90 minutes de corrections

Voulez-vous que je commence par laquelle? 🚀
