# Phase 2 OTP Integration — Testing Guide

## Pre-Test Setup

### 1. Database Verification
```sql
-- Check if otp_codes table exists
SELECT TABLE_NAME FROM information_schema.TABLES 
WHERE TABLE_NAME = 'otp_codes' AND TABLE_SCHEMA = 'domizi';

-- Verify columns
DESCRIBE otp_codes;

-- Expected columns:
-- id, user_id, purpose, code_hash, expires_at, attempts, used_at, created_at
```

### 2. Email Configuration
- Check `.env` for MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- Verify SMTP/mail driver is configured
- Test sending: Use `security/test_otp.php` if available

### 3. Composer Dependencies
```bash
cd /xampp/htdocs/coiffons
composer install  # Ensure PHPMailer is installed
```

---

## Test Scenarios

### TEST 1: Registration with OTP

**Prerequisite**: Fresh test account email (not in database)

**Steps**:
1. Navigate to `http://localhost/coiffons/index.php?page=register&role=client`
2. Fill registration form:
   - Name: "Test"
   - First Name: "User"
   - Sex: "Homme"
   - Email: `test_otp_$(date +%s)@example.com` (unique)
   - Phone: "+229 96 000 0000"
   - City: Select any city
   - District: Select any district
   - Password: "TestPassword123"
   - Photo: Optional
3. Click "CRÉER MON COMPTE"
4. **Expected Result**: 
   - Account created (id_user in session)
   - OTP verification page shown
   - Email received with 6-digit code
   - Masked email displayed

**Sub-test 1a: Valid OTP**
1. Check email for OTP code
2. Enter 6 digits in fields
3. Click "VÉRIFIER"
4. **Expected Result**: 
   - Success message
   - Redirect to dashboard
   - email_verifie = 1 in database
   - Session contains email_verified = true

**Sub-test 1b: Invalid OTP**
1. Enter wrong 6-digit code (e.g., 000000)
2. Click "VÉRIFIER"
3. **Expected Result**: 
   - Error message: "Code invalide ou expiré"
   - Stay on OTP form
   - Can retry

**Sub-test 1c: Brute Force (5 Attempts)**
1. Enter wrong code 5 times
2. On 6th attempt: **Expected Result**:
   - Error: "Code invalide ou expiré" (generic)
   - OTP invalidated
   - "Renvoyer le code" still available

**Sub-test 1d: Resend OTP (Rate Limiting)**
1. Click "Renvoyer le code" immediately
2. **Expected Result**: 
   - Button shows cooldown (60 seconds)
   - Or shows error about rate limiting
3. Wait 60+ seconds
4. Click again
5. **Expected Result**: 
   - New code sent
   - New email received
   - Code is different from first

---

### TEST 2: Password Reset

**Prerequisite**: Existing account email

**Scenario 2a: Valid Email**
1. Navigate to `http://localhost/coiffons/access/password_reset.php`
2. Enter valid registered email
3. Click "ENVOYER UN CODE"
4. **Expected Result**:
   - Page shows OTP input (Step 2)
   - Email received with 6-digit code

**Scenario 2b: Invalid Email**
1. From password reset page, enter non-existent email
2. Click "ENVOYER UN CODE"
3. **Expected Result**: 
   - Error message: "Aucun compte trouvé avec cet email"
   - Stay on email input form

**Scenario 2c: Verify OTP and Reset Password**
1. From Step 2 (after valid email)
2. Enter OTP from email
3. Click "VÉRIFIER"
4. **Expected Result**:
   - Page advances to Step 3 (password form)
   - Shows new password input fields

**Scenario 2d: Set New Password**
1. On Step 3, enter new password:
   - New Password: "NewSecurePass123"
   - Confirm Password: "NewSecurePass123"
2. Click "RÉINITIALISER MOT DE PASSE"
3. **Expected Result**:
   - Database password updated
   - Redirect to connexion.php
   - Success message shown on login page

**Scenario 2e: Login with New Password**
1. Use new password to login
2. **Expected Result**: 
   - Login successful
   - Redirect to dashboard

**Scenario 2f: Wrong Password Confirmation**
1. On Step 3:
   - New Password: "NewSecurePass123"
   - Confirm Password: "DifferentPassword456"
2. Click submit
3. **Expected Result**: 
   - Error message: "Les mots de passe ne correspondent pas"
   - Stay on form

---

### TEST 3: Email Change

**Prerequisite**: Logged-in user

**Scenario 3a: Request Email Change**
1. Navigate to `http://localhost/coiffons/modifier_profil.php`
2. Click "CHANGER MON ADRESSE EMAIL"
3. Form expands showing new email input
4. Enter new email: `newemail_$(date +%s)@example.com`
5. Click "ENVOYER CODE"
6. **Expected Result**:
   - OTP verification section shown
   - Email sent to NEW email address
   - Session contains: email_change_step=2, email_change_new_email

**Scenario 3b: Verify OTP for Email Change**
1. Check new email for OTP code
2. Enter 6 digits in OTP fields
3. Click "VÉRIFIER"
4. **Expected Result**:
   - Success message: "Votre adresse email a été mise à jour avec succès!"
   - Database email updated
   - Session cleared

**Scenario 3c: Cancel Email Change**
1. On OTP verification screen
2. Click "ANNULER"
3. **Expected Result**:
   - Return to profile page
   - Email NOT changed
   - Session state cleared

**Scenario 3d: Invalid New Email Format**
1. Click "CHANGER MON ADRESSE EMAIL"
2. Enter invalid email: "not-an-email"
3. Click "ENVOYER CODE"
4. **Expected Result**: 
   - Error: "Adresse email invalide"
   - Stay on form

**Scenario 3e: Duplicate Email**
1. Try to change to already-used email
2. Click "ENVOYER CODE"
3. **Expected Result**: 
   - Error: "Cet email est déjà utilisé par un autre compte"
   - Stay on form

---

### TEST 4: Account Deletion

**Prerequisite**: Test user account (can create new one), logged-in

**Scenario 4a: Request Deletion (Step 1)**
1. Navigate to `http://localhost/coiffons/profil.php`
2. Scroll to "ZONE DE DANGER // SUPPRESSION DU COMPTE"
3. Click "DEMANDER LA SUPPRESSION DE MON COMPTE"
4. Form expands
5. Click "ENVOYER UN CODE DE VÉRIFICATION"
6. **Expected Result**:
   - OTP verification section shown
   - Email sent to user
   - Session: account_deletion_step=2

**Scenario 4b: Verify Deletion OTP (Step 2)**
1. Check email for OTP code
2. Enter 6 digits
3. Click "VÉRIFIER"
4. **Expected Result**:
   - Page advances to Step 3
   - Shows final warning dialog
   - Shows reason textarea

**Scenario 4c: Final Confirmation (Step 3)**
1. Optionally enter reason: "Testing OTP system"
2. Click "SUPPRIMER MON COMPTE"
3. Confirm dialog: "Êtes-vous absolument sûr(e)?"
4. Click "OK"
5. **Expected Result**:
   - Account deleted from users table
   - Entry added to suppressions_comptes table:
     - nom_utilisateur, email_utilisateur, role_utilisateur, raison, timestamp
   - Session destroyed
   - Redirect to home page

**Scenario 4d: Verify Account Deletion**
1. Try to login with deleted account email
2. **Expected Result**: 
   - Login fails (no account)
   - Error message shown

**Scenario 4e: Check Audit Trail**
```sql
SELECT * FROM suppressions_comptes 
WHERE email_utilisateur = 'test@example.com' 
ORDER BY id DESC LIMIT 1;
```
**Expected**: Entry exists with correct data

**Scenario 4f: Cancel Deletion (at Step 2)**
1. On OTP verification page (Step 2)
2. Click "ANNULER" button
3. **Expected Result**:
   - Return to profile page
   - Account NOT deleted
   - session account_deletion_step cleared

---

## Security Tests

### TEST 5: Brute Force Protection

**Steps**:
1. Go to registration page
2. Fill form and submit → Get OTP verification page
3. Intentionally enter wrong code 5 times:
   - First 4: "Code invalide ou expiré"
   - 5th attempt: Still shows error
   - 6th attempt: Still shows error
4. Check database:
   ```sql
   SELECT * FROM otp_codes WHERE user_id = ? AND purpose = 'registration';
   ```
5. **Expected**: `used_at IS NOT NULL` after 5 attempts (invalidated)

### TEST 6: Rate Limiting

**Setup**: Create two test accounts

**Account 1**:
1. Register and go to OTP form
2. Immediately click "Renvoyer le code" twice in quick succession
3. **Expected**: Second request blocked with rate limit message

**Account 2**:
1. Request password reset for account 1
2. Immediately try password reset again for same account
3. **Expected**: Second request blocked (rate limit per user+purpose)

### TEST 7: OTP Expiration

**Steps**:
1. Register with test email → Get OTP
2. Wait 5+ minutes
3. Try to submit expired OTP
4. **Expected**: Error "Code invalide ou expiré"

### TEST 8: CSRF Token Validation

**Browser Tools** (Developer Console):
1. Go to registration page
2. Inspect form → Note _csrf_token value
3. Intercept request → Modify _csrf_token value
4. Submit form
5. **Expected**: CSRF error or form rejection

### TEST 9: Email Security

**Check logs** (if available):
1. Verify OTP codes are NOT logged in production logs
2. Only email delivery status should be logged
3. Codes should only exist in:
   - Email message
   - otp_codes.code_hash (hashed)

---

## Edge Cases & Error Handling

### TEST 10: Session Timeout

**Steps**:
1. Start password reset
2. Enter email → Get OTP form
3. Wait for session to expire (typically 24-30 min)
4. Try to enter OTP
5. **Expected**: 
   - Session invalid message
   - Redirect to login or restart reset

### TEST 11: Concurrent OTP Requests

**Setup**: Two browser windows

**Window 1**:
1. Start registration
2. Get first OTP code

**Window 2** (same email if possible):
1. Try to register with same email
2. **Expected**: Email already used error OR unique constraint on (user_id, purpose)

### TEST 12: Mobile Responsiveness

**Tests on Mobile Browser**:
1. OTP input fields remain visible
2. Keyboard opens automatically (inputmode="numeric")
3. Buttons clickable (touch targets >= 44x44px)
4. Countdown timer visible
5. Error messages clear and readable

---

## Data Verification Checklist

After each test, verify database state:

### Registration:
```sql
SELECT id, email, email_verifie, email_verified_at, otp_verified_at 
FROM users WHERE email = 'test@example.com';
```
- ✓ email_verifie = 1 after OTP verification
- ✓ email_verified_at has timestamp

### OTP Codes:
```sql
SELECT * FROM otp_codes WHERE user_id = ? ORDER BY created_at DESC;
```
- ✓ code_hash is hashed (not plain text)
- ✓ expires_at is 5 minutes in future (created_at + 5 min)
- ✓ attempts incrementing on wrong code
- ✓ used_at populated after verification

### Password Reset:
```sql
SELECT id, password FROM users WHERE email = 'test@example.com';
```
- ✓ Password hash changed after reset

### Email Change:
```sql
SELECT id, email FROM users WHERE email = 'newemail@example.com';
```
- ✓ Email column updated to new address

### Account Deletion:
```sql
-- Should NOT find deleted user
SELECT id FROM users WHERE email = 'deleted@example.com';

-- Should find deletion log
SELECT * FROM suppressions_comptes 
WHERE email_utilisateur = 'deleted@example.com';
```

---

## Performance Tests

### TEST 13: Load Testing

**Steps**:
1. Generate 10 OTP requests simultaneously
2. Monitor response times
3. **Expected**: All complete within 2-3 seconds
4. Check database for race conditions

---

## Rollback / Fallback Testing

### TEST 14: Email Failure Handling

**Setup**: Temporarily misconfigure email

**Steps**:
1. Change MAIL_FROM_ADDRESS to invalid
2. Try registration → Should show error
3. Account should be rolled back (deleted)
4. User can retry with correct setup

---

## Final Sign-Off Checklist

- [ ] All 4 flows tested (Registration, Password Reset, Email Change, Deletion)
- [ ] OTP generation working (codes sent via email)
- [ ] OTP verification working (codes validated correctly)
- [ ] Brute force protection working (5 attempts)
- [ ] Rate limiting working (60s cooldown, 3/hour)
- [ ] UI responsive on mobile/desktop
- [ ] Error messages clear and helpful
- [ ] Database audit trail populated
- [ ] CSRF protection validated
- [ ] Code not logged in plain text
- [ ] Counters and timers working
- [ ] Resend buttons functional with cooldown
- [ ] Password reset linked from login
- [ ] Email change accessible from profile
- [ ] Deletion OTP-protected
- [ ] All redirects working correctly
- [ ] Session management secure
- [ ] No sensitive data in URLs/logs

---

## Known Limitations & Future Improvements

### Current Version
- Email-only delivery (SMS optional future)
- 5-minute OTP validity fixed (could be configurable)
- No admin dashboard for OTP management

### Future Enhancements
- SMS OTP option
- WhatsApp OTP integration
- Biometric OTP verification
- Hardware token support
- OTP backup codes
- Admin panel for OTP audit logs

---

## Support & Debugging

### Common Issues & Solutions

**Issue**: "SMTP Error"
- Check SMTP configuration in .env
- Verify mail server running (e.g., Postfix)
- Check email logs for delivery failures

**Issue**: "OTP not received"
- Check spam folder
- Verify MAIL_FROM_ADDRESS is trusted
- Check server logs for send errors

**Issue**: "Rate limit error on resend"
- This is expected - wait 60 seconds between requests
- Or use different user/purpose combination

**Issue**: "CSRF token mismatch"
- Clear cookies and try again
- Ensure cookies enabled
- Check csrf.php is loaded

---

## Test Report Template

```
TEST DATE: ____________________
TESTER: ________________________
ENVIRONMENT: [DEV/STAGING/PROD]

REGISTRATION FLOW:
  [ ] OTP Generation: PASS / FAIL
  [ ] Email Delivery: PASS / FAIL
  [ ] Verification: PASS / FAIL
  [ ] Brute Force: PASS / FAIL
  [ ] Resend: PASS / FAIL
  [ ] Database Update: PASS / FAIL

PASSWORD RESET:
  [ ] Email Validation: PASS / FAIL
  [ ] OTP Delivery: PASS / FAIL
  [ ] OTP Verification: PASS / FAIL
  [ ] Password Update: PASS / FAIL
  [ ] Login with New PW: PASS / FAIL

EMAIL CHANGE:
  [ ] Request: PASS / FAIL
  [ ] OTP Delivery: PASS / FAIL
  [ ] Verification: PASS / FAIL
  [ ] Database Update: PASS / FAIL

ACCOUNT DELETION:
  [ ] Request: PASS / FAIL
  [ ] OTP Verification: PASS / FAIL
  [ ] Final Confirmation: PASS / FAIL
  [ ] Database Deletion: PASS / FAIL
  [ ] Audit Trail: PASS / FAIL

SECURITY:
  [ ] CSRF Protection: PASS / FAIL
  [ ] Rate Limiting: PASS / FAIL
  [ ] Brute Force: PASS / FAIL
  [ ] Code Hashing: PASS / FAIL
  [ ] Session Security: PASS / FAIL

ISSUES FOUND:
_____________________________________________
_____________________________________________

NOTES:
_____________________________________________
_____________________________________________

SIGN-OFF: _________________________ DATE: _________
```

---

**Phase 2 Testing Complete** ✓
