<?php
require_once 'includes/config.php';
initUserSession();

$page_title = 'Créer un compte';
$msg = '';
$type = 'error';

// Si déjà connecté, redirection
if (isset($_SESSION['user'])) {
    header('Location: mon-compte.php');
    exit;
}

// Valeurs à réafficher en cas d'erreur (pour ne pas perdre la saisie
// et rouvrir directement la bonne étape côté JS)
$old = [
    'prenom' => '',
    'nom'    => '',
    'email'  => '',
    'tel'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $msg = 'Session invalide. Rechargez la page et réessayez.';
    } else {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tel = trim($_POST['tel'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        $old = [
            'prenom' => $prenom,
            'nom'    => $nom,
            'email'  => $email,
            'tel'    => $tel,
        ];

        if ($prenom && $nom && $email && $password && strlen($password) >= 6) {
            if ($password !== $password_confirm) {
                $msg = 'Les mots de passe ne correspondent pas.';
            } else {
            try {
                $db = getDB();

                // Vérifier si l'email existe déjà
                $check = $db->prepare("SELECT id FROM users WHERE email=?");
                $check->execute([$email]);
                if ($check->fetch()) {
                    $msg = 'Cet email est déjà utilisé.';
                } else {
                    // Créer le nouvel utilisateur
                    $hashed_pw = password_hash($password, PASSWORD_BCRYPT);
                    $db->prepare("INSERT INTO users (prenom, nom, email, tel, password) VALUES (?, ?, ?, ?, ?)")
                       ->execute([$prenom, $nom, $email, $tel, $hashed_pw]);

                    // Récupérer l'utilisateur créé
                    $user = $db->prepare("SELECT * FROM users WHERE email=?");
                    $user->execute([$email]);
                    $user_data = $user->fetch();

                    // Connecter l'utilisateur
                    $_SESSION['user'] = [
                        'id' => $user_data['id'],
                        'nom' => $user_data['nom'],
                        'prenom' => $user_data['prenom'],
                        'email' => $user_data['email']
                    ];

                    header('Location: mon-compte.php');
                    exit;
                }
            } catch (Exception $e) {
                $msg = 'Erreur lors de l\'inscription.';
            }
        }
    } else {
        $msg = 'Veuillez remplir tous les champs (mot de passe minimum 6 caractères).';
    }
    }
}

require_once 'includes/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="page-hero-stack">
      <span class="section-tag section-tag-soft">Inscription</span>
      <h1><span>Créer un compte</span></h1>
      <p>Rejoignez FYTYA-G et suivez vos commandes</p>
    </div>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width: 560px;">
    <?php if ($msg): ?>
    <div class="alert alert-<?=$type==='success'?'success':'error'?>" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
      <?=icon($type==='success'?'check-circle':'x-circle', 18)?>
      <span><?=$msg?></span>
    </div>
    <?php endif; ?>

    <div class="checkout-box" style="padding:32px;">

      <!-- ─── Indicateur d'étapes ─── -->
      <div class="signup-steps" aria-hidden="true">
        <div class="signup-step active" data-step-indicator="1">
          <span class="signup-step-num">1</span>
          <span class="signup-step-label">Identité</span>
        </div>
        <div class="signup-step-line"></div>
        <div class="signup-step" data-step-indicator="2">
          <span class="signup-step-num">2</span>
          <span class="signup-step-label">Contact</span>
        </div>
        <div class="signup-step-line"></div>
        <div class="signup-step" data-step-indicator="3">
          <span class="signup-step-num">3</span>
          <span class="signup-step-label">Sécurité</span>
        </div>
      </div>

      <form method="POST" action="register.php" id="registerForm" novalidate>
        <?=csrfField()?>

        <!-- ─── Étape 1 : Prénom + Nom ─── -->
        <fieldset class="signup-fieldset" data-step="1">
          <div class="field-grid-2">
            <div class="form-group">
              <label>Prénom *</label>
              <input type="text" name="prenom" placeholder="Konan" value="<?=htmlspecialchars($old['prenom'])?>" required>
            </div>
            <div class="form-group">
              <label>Nom *</label>
              <input type="text" name="nom" placeholder="Diallo" value="<?=htmlspecialchars($old['nom'])?>" required>
            </div>
          </div>

          <div class="signup-actions">
            <span></span>
            <button type="button" class="btn-primary signup-next">Suivant</button>
          </div>
        </fieldset>

        <!-- ─── Étape 2 : Email + Téléphone ─── -->
        <fieldset class="signup-fieldset" data-step="2" hidden>
          <div class="field-grid-2">
            <div class="form-group">
              <label>Email *</label>
              <input type="email" name="email" placeholder="vous@mail.com" value="<?=htmlspecialchars($old['email'])?>" required>
            </div>
            <div class="form-group">
              <label>Téléphone</label>
              <input type="tel" name="tel" placeholder="+225 07 00 00 00" value="<?=htmlspecialchars($old['tel'])?>">
            </div>
          </div>

          <div class="signup-actions">
            <button type="button" class="btn-outline signup-prev">Précédent</button>
            <button type="button" class="btn-primary signup-next">Suivant</button>
          </div>
        </fieldset>

        <!-- ─── Étape 3 : Mot de passe + Confirmation ─── -->
        <fieldset class="signup-fieldset" data-step="3" hidden>
          <div class="field-grid-2">
            <div class="form-group">
              <label>Mot de passe (min. 6 caractères) *</label>
              <input type="password" name="password" placeholder="••••••" minlength="6" required>
            </div>
            <div class="form-group">
              <label>Confirmez le mot de passe *</label>
              <input type="password" name="password_confirm" placeholder="••••••" minlength="6" required>
            </div>
          </div>

          <div class="signup-actions">
            <button type="button" class="btn-outline signup-prev">Précédent</button>
            <button type="submit" class="btn-primary">Créer mon compte</button>
          </div>
        </fieldset>
      </form>

      <p style="text-align:center;color:var(--g600);font-size:14px;margin-top:12px">
        Déjà un compte ? <a href="login.php" style="color:var(--orange);font-weight:600">Se connecter</a>
      </p>
    </div>
  </div>
</section>

<style>
/* ─── Indicateur d'étapes ─── */
.signup-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 28px;
}
.signup-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  opacity: .45;
  transition: opacity var(--tr);
}
.signup-step.active,
.signup-step.done {
  opacity: 1;
}
.signup-step-num {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--fh);
  font-weight: 800;
  font-size: 13px;
  color: var(--g600);
  background: var(--g100);
  border: 1.5px solid var(--g200);
  transition: all var(--tr);
}
.signup-step.active .signup-step-num {
  background: var(--grad-fire);
  color: #fff;
  border-color: transparent;
  box-shadow: var(--sh-orange-sm);
}
.signup-step.done .signup-step-num {
  background: var(--o100);
  color: var(--o600);
  border-color: var(--o200);
}
.signup-step-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--g600);
  white-space: nowrap;
}
.signup-step-line {
  width: 36px;
  height: 2px;
  background: var(--g200);
  margin-bottom: 18px;
}

/* ─── Fieldsets étapes ─── */
.signup-fieldset {
  border: none;
  padding: 0;
  margin: 0;
  animation: fadeUp .35s ease both;
}
.signup-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 8px;
}
.signup-actions .btn-primary,
.signup-actions .btn-outline {
  min-width: 130px;
  text-align: center;
}

@media (max-width: 600px) {
  .signup-step-label { display: none; }
  .signup-actions .btn-primary,
  .signup-actions .btn-outline {
    min-width: 0;
    flex: 1;
  }
}
</style>

<script>
(function () {
  var form = document.getElementById('registerForm');
  if (!form) return;

  var steps = Array.prototype.slice.call(form.querySelectorAll('.signup-fieldset'));
  var indicators = Array.prototype.slice.call(document.querySelectorAll('[data-step-indicator]'));
  var current = 1;

  function showStep(n) {
    steps.forEach(function (fs) {
      var stepNum = parseInt(fs.getAttribute('data-step'), 10);
      fs.hidden = stepNum !== n;
    });
    indicators.forEach(function (ind) {
      var num = parseInt(ind.getAttribute('data-step-indicator'), 10);
      ind.classList.remove('active', 'done');
      if (num === n) ind.classList.add('active');
      else if (num < n) ind.classList.add('done');
    });
    current = n;

    // Replace le focus sur le premier champ de l'étape visible
    var firstInput = form.querySelector('.signup-fieldset[data-step="' + n + '"] input');
    if (firstInput) firstInput.focus({ preventScroll: true });
  }

  function validateStep(n) {
    var fs = form.querySelector('.signup-fieldset[data-step="' + n + '"]');
    var inputs = fs.querySelectorAll('input');
    var valid = true;
    inputs.forEach(function (input) {
      if (!input.checkValidity()) {
        input.reportValidity();
        valid = false;
      }
    });

    // Vérification supplémentaire à l'étape 3 : les mots de passe doivent correspondre
    if (valid && n === 3) {
      var pwd = fs.querySelector('input[name="password"]');
      var pwdConfirm = fs.querySelector('input[name="password_confirm"]');
      if (pwd.value !== pwdConfirm.value) {
        pwdConfirm.setCustomValidity('Les mots de passe ne correspondent pas.');
        pwdConfirm.reportValidity();
        valid = false;
      } else {
        pwdConfirm.setCustomValidity('');
      }
    }

    return valid;
  }

  form.querySelectorAll('.signup-next').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (validateStep(current)) {
        showStep(current + 1);
      }
    });
  });

  form.querySelectorAll('.signup-prev').forEach(function (btn) {
    btn.addEventListener('click', function () {
      showStep(current - 1);
    });
  });

  form.addEventListener('submit', function (e) {
    // Sécurité : revalide toutes les étapes avant l'envoi final
    for (var i = 1; i <= steps.length; i++) {
      if (!validateStep(i)) {
        e.preventDefault();
        showStep(i);
        return;
      }
    }
  });

  <?php if ($msg): ?>
  // S'il y a une erreur serveur, on rouvre l'étape la plus pertinente
  showStep(<?= ($msg === 'Les mots de passe ne correspondent pas.') ? 3 : 1 ?>);
  <?php endif; ?>
})();
</script>

<?php require_once 'includes/footer.php'; ?>