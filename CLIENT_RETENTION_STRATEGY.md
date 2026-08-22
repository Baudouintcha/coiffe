# 🔐 STRATÉGIE DE RÉTENTION CLIENT — ÉVITER LES CONTACTS DIRECTS

**Problème** : Après le 1er RDV, client et coiffeur se contactent directement (WhatsApp, SMS, appel) et contournent la plateforme  
**Impact** : Perte de commission, perte de données, perte de contrôle  
**Solution** : Implémenter 7 mécanismes de rétention  

---

## 🎯 PROBLÈME ACTUEL

```
Scénario actuel :
┌─────────────┐
│   Client    │  Prend RDV #1
│             │  via Domizi
└──────┬──────┘
       │
       ▼
┌─────────────────────────────┐
│ Premier RDV confirme        │
│ • Client reçoit tel coiffeur│
│ • Coiffeur reçoit tel client│
└──────┬──────────────────────┘
       │
       ▼
❌ PROBLÈME : Client appelle coiffeur directement
   • "Salut, on peut faire RDV directement ?"
   • Pas de commission plateforme
   • Pas de trace de RDV
   • Pas de protection consommateur
```

---

## ✅ SOLUTIONS DE RÉTENTION

### SOLUTION 1 : Masquer les Numéros de Téléphone

**Concept** : Le client et coiffeur NE VOIENT jamais le vrai numéro l'un de l'autre

#### Implémentation

**Étape 1 : Créer système de "Virtual Numbers"**

```sql
ALTER TABLE rendez_vous ADD COLUMN (
  client_virtual_number VARCHAR(20),
  coiffeur_virtual_number VARCHAR(20),
  contact_expire_at DATETIME
);
```

**Étape 2 : Générer numéros virtuels**

```php
// Quand RDV est confirmé
function generateVirtualNumbers($rdv_id, $client_id, $coiffeur_id) {
    $virtual_client = "0X1" . str_pad($rdv_id, 6, "0", STR_PAD_LEFT);   // 01XXXXXX
    $virtual_coiffeur = "0X2" . str_pad($rdv_id, 6, "0", STR_PAD_LEFT); // 02XXXXXX
    
    // Enregistrer dans BD
    $pdo->prepare("
        UPDATE rendez_vous 
        SET client_virtual_number = ?, 
            coiffeur_virtual_number = ?,
            contact_expire_at = DATE_ADD(NOW(), INTERVAL 2 DAYS)
        WHERE id = ?
    ")->execute([$virtual_client, $virtual_coiffeur, $rdv_id]);
    
    return [$virtual_client, $virtual_coiffeur];
}
```

**Étape 3 : Afficher aux utilisateurs**

```
CLIENT VOIT :
"Coiffeur contact : 01600001"
"Disponible jusqu'au : 25/08/2026 14:00"

COIFFEUR VOIT :
"Client contact : 02600001"
"Disponible jusqu'au : 25/08/2026 14:00"
```

**Étape 4 : Rediriger appels/SMS**

Avec Twilio ou service VoIP :
```php
// Quand client appelle 01600001 → Redirige vers coiffeur
// Avec numéro masqué, logs de durée, enregistrement
```

**Avantages** :
✅ Client/coiffeur ne connaissent pas le numéro réel  
✅ Numéros virtuels valables que 2-3 jours  
✅ Plateforme trace TOUS les appels  
✅ Impossible de contourner après expiration  

**Coût** : Twilio (€0.02-0.10 par appel) ou alternative gratuite

---

### SOLUTION 2 : Messagerie Interne Obligatoire

**Concept** : Toute communication passe par la plateforme, pas de téléphone direct

#### Implémentation

**Étape 1 : Créer table de messagerie**

```sql
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rdv_id INT,
    sender_id INT,          -- Client ou Coiffeur
    sender_role ENUM('client', 'coiffeur'),
    message TEXT,
    timestamp DATETIME,
    lu TINYINT(1) DEFAULT 0,
    attachement VARCHAR(255)
);
```

**Étape 2 : Bloquer l'affichage du téléphone**

```php
// Dans la vue client/coiffeur
// AVANT : 
echo "Téléphone coiffeur : " . $coiffeur['telephone']; // ❌ BLOQUÉ

// APRÈS :
if ($rdv['statut_rdv'] == 'confirme') {
    echo "Contactez via la plateforme"; // ✅ Message interne uniquement
}
```

**Étape 3 : Interface de chat**

```html
<div class="messaging-panel">
    <div class="messages-history">
        <!-- Les messages apparaissent ici -->
    </div>
    <textarea id="message-input" placeholder="Votre message..."></textarea>
    <button onclick="sendMessage()">Envoyer</button>
</div>
```

**Avantages** :
✅ Plateforme contrôle 100% communication  
✅ Trace complète de toutes discussions  
✅ Peut modérer/signaler messages  
✅ Notifications push dans l'appli  

**Désavantages** :
❌ Les utilisateurs veulent souvent le téléphone direct  
❌ Si serveur down → Pas de communication

---

### SOLUTION 3 : Commission/Fee Invisible

**Concept** : Le client paye PLUS s'il veut contourner (économiquement dissuasif)

#### Implémentation

**Montants de base** :
```
- RDV via plateforme : 6000 FCFA
- RDV contact direct : 7500 FCFA (25% plus cher)
- La différence va à la plateforme/coiffeur bonus
```

**Code** :
```php
function calculatePrice($rdv_id, $contact_method) {
    $base_price = 6000;
    
    if ($contact_method === 'direct') {
        return $base_price * 1.25;  // 25% markup
    }
    return $base_price;
}
```

**Affichage du RDV** :
```
Premier RDV via Domizi : 6000 F
Les prochains RDV directs : 7500 F

👉 Vous économisez 1500 F en restant sur la plateforme !
```

**Avantages** :
✅ Incite à rester sur plateforme  
✅ Augmente revenu si contournement  
✅ Psychologiquement puissant  

**Désavantages** :
❌ Peut frustrer les utilisateurs  
❌ Pas de vraie protection contre contournement

---

### SOLUTION 4 : Limite de Numéro (Revelation Progressive)

**Concept** : Le numéro n'est révélé que progressivement, avec conditions

#### Implémentation

**Étape 1 : Révéler après RDV complété**

```php
// RDV en attente → Numéro masqué
if ($rdv['statut_rdv'] == 'en_attente') {
    echo "Numéro : ••••••••••";
    echo "(Visible après 1er RDV)";
}

// RDV terminé → Numéro visible
if ($rdv['statut_rdv'] == 'termine') {
    echo "Numéro coiffeur : " . $coiffeur['telephone'];
}
```

**Étape 2 : Exiger 3 RDV avant accès direct**

```sql
ALTER TABLE users ADD COLUMN (
    nb_rdv_completed INT DEFAULT 0,
    can_contact_direct TINYINT(1) DEFAULT 0
);

-- Débloquer après 3 RDV
UPDATE users 
SET can_contact_direct = 1 
WHERE nb_rdv_completed >= 3;
```

**Affichage** :
```
Client voit :
"Vous avez terminé 1/3 RDV pour débloquer contact direct"

Après 3 RDV :
"✅ Contact direct débloqué"
```

**Avantages** :
✅ Force au moins 3 RDV via plateforme  
✅ Commission garantie  
✅ Relation établie avant contact direct  

**Désavantages** :
❌ Peut perdre clients impatients

---

### SOLUTION 5 : Loyalty Points / Rewards

**Concept** : Récompenser les utilisateurs qui restent sur la plateforme

#### Implémentation

**Système de points** :
```
Chaque RDV via plateforme : +10 points
1 point = 100 FCFA remise future
Les points expirent après 90 jours
```

**Code** :
```php
// Chaque RDV confirmé
$points = $montant_rdv / 1000;  // 1 point par 1000 FCFA

$pdo->prepare("
    INSERT INTO loyalty_points (user_id, points, expires_at)
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 90 DAY))
")->execute([$client_id, $points]);
```

**Affichage** :
```
Vos points : 45 points = 4500 F remise

Prochains RDV :
☐ Via plateforme → Gagnez 10 points
☐ Contact direct → Perdez 5 points

Votre remise reste active 90 jours
```

**Avantages** :
✅ Psychologiquement puissant  
✅ Accumulation crée habitude  
✅ Augmente lifetime value client  

**Désavantages** :
❌ Complexité marketing  
❌ Coût de gestion des points

---

### SOLUTION 6 : Contrat/Conditions Explicites

**Concept** : Faire signer un accord sur les conséquences du contournement

#### Implémentation

**À la première connexion** :
```html
<div class="modal-legal">
    <h3>Conditions d'utilisation de Domizi</h3>
    
    <p>En confirmant un RDV, vous acceptez :</p>
    
    <ul>
        <li>✓ Les RDV doivent passer par la plateforme</li>
        <li>✓ Contact direct = non couvert par assurance</li>
        <li>✓ Contournement = compte suspendu 30j</li>
        <li>✓ Disputes résolues via plateforme uniquement</li>
    </ul>
    
    <label>
        <input type="checkbox" required>
        J'accepte les conditions
    </label>
    
    <button onclick="acceptTerms()">Continuer</button>
</div>
```

**Base de données** :
```sql
ALTER TABLE users ADD COLUMN (
    terms_accepted DATETIME,
    terms_version INT
);
```

**Avantages** :
✅ Couverture légale  
✅ Légitimité claire  
✅ Base pour sanctions  

**Désavantages** :
❌ Pas d'application technique réelle  
❌ Dépend de bonne foi utilisateurs

---

### SOLUTION 7 : Système d'Escrow (Paiement Bloqué)

**Concept** : L'argent reste bloqué sur plateforme, libéré seulement après RDV via plateforme

#### Implémentation

**Flux de paiement** :
```
1. Client paye 6000 FCFA
2. Argent = BLOQUÉ (pas crédité au coiffeur)
3. RDV confirmé via plateforme
4. Après RDV, client valide
5. Argent libéré au coiffeur (5700 F) + 300 F commission

Si contact direct :
   → Argent bloqué indéfiniment
   → Refund après 90j
```

**Code** :
```php
function blockPayment($client_id, $montant, $rdv_id) {
    $pdo->prepare("
        INSERT INTO solde_gele (user_id, montant, rdv_id, expire_at)
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 90 DAY))
    ")->execute([$client_id, $montant, $rdv_id]);
}

// Débloquer après validation
function releasePayment($rdv_id) {
    $rdv = getRDV($rdv_id);
    $montant = $rdv['montant_total'];
    $commission = $montant * 0.05;
    $coiffeur_credit = $montant - $commission;
    
    // Créditer coiffeur
    $pdo->prepare("
        UPDATE users 
        SET solde = solde + ?
        WHERE id = ?
    ")->execute([$coiffeur_credit, $rdv['coiffeur_id']]);
}
```

**Affichage** :
```
Paiement : 6000 F
État : ⏳ EN ATTENTE

Votre paiement sera confirmé après le RDV.
Cela garantit qualité et protection.
```

**Avantages** :
✅ FORCE le RDV via plateforme  
✅ Coiffeur n'est pas payé avant  
✅ Qualité garantie  

**Désavantages** :
❌ Complexité financière  
❌ Besoin de licence/régulation  
❌ Frictions possibles

---

## 🎯 STRATÉGIE RECOMMANDÉE (COMBINAISON)

### Pour Domizi : Implémenter les 3 premiers

```
1. ✅ SOLUTION 1 : Virtual Numbers (court terme)
   └─ Numéros masqués, valables 2-3 jours
   └─ Implémentation : 1 jour
   └─ Coût : Twilio €50-100/mois ou gratuit (Vonage)

2. ✅ SOLUTION 4 : Révélation Progressive
   └─ Numéro visible après 1er RDV terminé
   └─ Force minimum 1 RDV via plateforme
   └─ Implémentation : 2 jours

3. ✅ SOLUTION 5 : Loyalty Points
   └─ Récompenser fidélité client
   └─ Points = Remises futures
   └─ Implémentation : 3 jours
```

### Roadmap d'implémentation

```
SEMAINE 1 : Solution 4 (Progressive Reveal)
   • Masquer numéro sauf après 1er RDV
   • Simple à implémenter
   • Impact immédiat

SEMAINE 2 : Solution 5 (Loyalty Points)
   • Système de récompense
   • Dashboard client avec points
   • Gamification

SEMAINE 3 : Solution 1 (Virtual Numbers)
   • Intégrer Twilio/Vonage
   • Redirection d'appels masquée
   • Log complet des contacts
```

---

## 💰 IMPACT FINANCIER

### Sans rétention (actuellement)

```
100 clients acquis
├─ RDV #1 : 100 × 6000 F = 600,000 F
├─ RDV #2 : 30 × 6000 F = 180,000 F (70% churn vers direct)
├─ Commission (5%) : 39,000 F
└─ Lifetime : 39,000 F par client
```

### Avec rétention (après implémentation)

```
100 clients acquis
├─ RDV #1 : 100 × 6000 F = 600,000 F
├─ RDV #2 : 80 × 6000 F = 480,000 F (20% churn seulement)
├─ RDV #3 : 70 × 6000 F = 420,000 F
├─ Commission (5%) : 75,000 F + loyalty boost
└─ Lifetime : 75,000 F par client (+92%)
```

**Gain annuel avec 1000 clients/mois** :
```
(75,000 - 39,000) × 12,000 = 432,000,000 F supplémentaires
```

---

## 📋 CHECKLIST D'IMPLÉMENTATION

### MVP (Minimal Viable Product) — Semaine 1

- [ ] Masquer numéros par défaut
- [ ] Afficher seulement après 1er RDV terminé
- [ ] Ajouter messages "via messagerie plateforme"
- [ ] Tester avec 5 utilisateurs

### Phase 2 — Semaine 2-3

- [ ] Implémenter loyalty points
- [ ] Dashboard points client
- [ ] Intégrer virtual numbers (Twilio)

### Phase 3 — Semaine 4

- [ ] A/B testing avec groupes
- [ ] Analytics rétention
- [ ] Optimisation

---

## 🚀 CODE À AJOUTER MAINTENANT

### 1. Migration BD

```sql
-- Ajouter à install_db.php
ALTER TABLE rendez_vous ADD COLUMN (
    client_virtual_number VARCHAR(20),
    coiffeur_virtual_number VARCHAR(20),
    contact_expire_at DATETIME,
    phone_revealed_at DATETIME
);

ALTER TABLE users ADD COLUMN (
    nb_rdv_completed INT DEFAULT 0,
    loyalty_points DECIMAL(10,2) DEFAULT 0
);

CREATE TABLE loyalty_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    points DECIMAL(10,2),
    expires_at DATETIME,
    used_at DATETIME
);
```

### 2. Fonction pour masquer numéros

```php
// Ajouter à security/config.php
function shouldShowPhone($user_id, $rdv_id) {
    // Ne montre le numéro que si :
    // 1. RDV est terminé
    // 2. Utilisateur a complété au moins 1 RDV
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as terminated
        FROM rendez_vous
        WHERE (client_id = ? OR prestataire_id = ?)
        AND statut_rdv = 'termine'
    ");
    $stmt->execute([$user_id, $user_id]);
    $result = $stmt->fetch();
    
    return $result['terminated'] >= 1;
}

function maskPhone($phone) {
    return substr($phone, 0, 3) . "****" . substr($phone, -2);
}
```

### 3. Affichage conditionnel

```php
// Dans la vue RDV
<?php
if (shouldShowPhone($current_user_id, $rdv_id)) {
    echo "Coiffeur : " . $coiffeur['telephone'];
} else {
    echo "Coiffeur : " . maskPhone($coiffeur['telephone']);
    echo " (Visible après 1er RDV complété)";
    echo "👉 <a href='/messagerie'>Contactez via messagerie</a>";
}
?>
```

---

## ✅ CONCLUSION

**Le système ACTUEL n'a pas de rétention.** Vous pouvez perdre 60-70% des clients après le 1er RDV.

**Solutions implémentables maintenant** :
1. ✅ Masquer numéros (30 min)
2. ✅ Révélation progressive (1h)
3. ✅ Loyalty points (4h)
4. ✅ Virtual numbers (Twilio, 2h)

**Impact estimé** : +92% lifetime value par client

**Recommandation** : Implémenter #1 + #2 cette semaine. C'est le plus rapide et plus efficace.
