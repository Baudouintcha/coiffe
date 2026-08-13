# OTP Centralisé — Executable Tasks

## Task 1: Apply Email Actions Database Migration
**Type**: Leaf Task (no dependencies)
**Status**: not_started
**Description**: Execute the migration to create the email_actions table in the database.

### Implementation Details
- Execute: `security/migrations/002_create_email_actions_table.sql`
- Verify table created with all columns and indexes
- Verify foreign key constraint to users table
- Table should have: id, user_id, email_address, action_type, purpose, subject, body_preview, status, error_message, retry_count, sent_at, delivered_at, opened_at, clicked_at, bounced_at, failed_at, created_at, updated_at, metadata
- Indexes required: user_id, email_address, action_type, purpose, status, created_at, sent_at
- UNIQUE index on (user_id, purpose, created_at)

### Verification
- Query: `SHOW CREATE TABLE email_actions;` should return table structure
- Query: `SELECT * FROM email_actions;` should return 0 rows
- No errors in application logs

---

## Task 2: Add Email Logging Method to EmailService
**Type**: Leaf Task (no dependencies)
**Status**: not_started
**Description**: Add a method to log email actions to the email_actions table.

### Implementation Details
**Add method**: `logEmailAction()` to `src/Services/EmailService.php`

```php
/**
 * Logs an email action to the email_actions table.
 * 
 * @param int|null $user_id User ID (NULL for system emails)
 * @param string $email_address Recipient email
 * @param string $action_type Type of email (otp_code, notification, etc.)
 * @param string|null $purpose OTP purpose (registration, password_reset, etc.)
 * @param string $subject Email subject
 * @param string|null $body_preview First 500 chars of body
 * @param string $status Status (pending, sent, delivered, failed, etc.)
 * @param string|null $error_message Error message if failed
 * @param array|null $metadata Additional tracking data (JSON)
 * @return bool Success/failure
 */
private function logEmailAction(
    ?int $user_id,
    string $email_address,
    string $action_type,
    ?string $purpose,
    string $subject,
    ?string $body_preview,
    string $status,
    ?string $error_message = null,
    ?array $metadata = null
): bool
```

**Constructor requirements**:
- Inject PDO database connection into EmailService constructor
- Store as private property: `$pdo`
- Update calls: `new EmailService($pdo)` where EmailService is instantiated

### Verification
- Method exists and is callable
- Can insert records into email_actions table
- No compilation errors

---

## Task 3: Modify sendOtpCode() to Log Actions
**Type**: Leaf Task (depends on: Task 2)
**Status**: not_started
**Description**: Update sendOtpCode() method to log email actions before and after sending.

### Implementation Details
**In `src/Services/EmailService.php`, method `sendOtpCode()`**:

1. **Before sending**: Log as "pending"
   - Call: `$this->logEmailAction($user_id, $to_email, 'otp_code', $purpose, $subject, $body_preview, 'pending')`
   - Note: Need $user_id parameter (pass from caller or retrieve from email)

2. **On success**: Log as "sent"
   - Call: `$this->logEmailAction($user_id, $to_email, 'otp_code', $purpose, $subject, $body_preview, 'sent')`

3. **On failure**: Log as "failed" with error
   - Call: `$this->logEmailAction($user_id, $to_email, 'otp_code', $purpose, $subject, $body_preview, 'failed', $error_message)`

### Callers to update
**Files that call `sendOtpCode()`**:
- `access/inscription.php` — Pass user_id after account creation
- `access/password_reset.php` — Get user_id from email lookup
- `modifier_profil.php` — Use current logged-in user_id
- `profil.php` — Use current logged-in user_id
- `access/verify_otp.php` — If resend logic exists

### Verification
- No compilation errors
- Records inserted into email_actions table on sendOtpCode() calls
- Status transitions: pending → sent (success) or pending → failed (error)

---

## Task 4: Modify sendNotification() to Log Actions
**Type**: Leaf Task (depends on: Task 2)
**Status**: not_started
**Description**: Update sendNotification() method to log email actions.

### Implementation Details
**In `src/Services/EmailService.php`, method `sendNotification()`**:

1. **Before sending**: Log as "pending"
   - Call: `$this->logEmailAction($user_id, $to_email, 'notification', null, $subject, $body_preview, 'pending')`
   - Note: Pass $user_id as parameter (default null for system emails)

2. **On success**: Log as "sent"
   - Call: `$this->logEmailAction($user_id, $to_email, 'notification', null, $subject, $body_preview, 'sent')`

3. **On failure**: Log as "failed" with error
   - Call: `$this->logEmailAction($user_id, $to_email, 'notification', null, $subject, $body_preview, 'failed', $error_message)`

### Method signature update
```php
public function sendNotification(
    string $to_email, 
    string $subject, 
    string $message, 
    string $action_text = '', 
    string $action_url = '',
    ?int $user_id = null  // NEW parameter
): array
```

### Verification
- No compilation errors
- Records inserted into email_actions table on sendNotification() calls
- Status transitions logged correctly

---

## Task 5: Update OtpService to Pass User ID to EmailService
**Type**: Leaf Task (depends on: Task 3)
**Status**: not_started
**Description**: Modify OtpService to pass user_id when sending OTP emails.

### Implementation Details
**In `src/Services/OtpService.php`**:

1. **Method**: `generate()` - After generating OTP code
   - Update call to EmailService::sendOtpCode() to include user_id
   - Signature: `$emailService->sendOtpCode($to_email, $code, $validity, $purpose, $user_id)`

2. **Update EmailService::sendOtpCode()** signature to accept $user_id:
   ```php
   public function sendOtpCode(
       string $to_email, 
       string $otp_code, 
       int $validity = 5, 
       string $purpose = 'registration',
       ?int $user_id = null  // NEW parameter
   ): array
   ```

### Verification
- OtpService passes user_id when calling sendOtpCode()
- Email logging includes correct user_id
- No regression in OTP generation/sending

---

## Task 6: Test Email Logging End-to-End
**Type**: Leaf Task (depends on: Task 3, Task 4, Task 5)
**Status**: not_started
**Description**: Test the complete email logging pipeline with real OTP sends.

### Test Cases
1. **OTP Send Logging**
   - Register new account → OTP generated → Email sent → Check email_actions table
   - Expected: Record with action_type='otp_code', purpose='registration', status='sent'

2. **Error Logging**
   - Attempt to send to invalid email → Should log as 'failed' with error_message

3. **Password Reset Logging**
   - Request password reset → OTP sent → Check email_actions table
   - Expected: Record with action_type='otp_code', purpose='password_reset', status='sent'

4. **Email Change Logging**
   - Request email change → OTP sent to new email → Check email_actions table
   - Expected: Record with action_type='otp_code', purpose='email_change', status='sent'

5. **Account Deletion Logging**
   - Request account deletion → OTP sent → Check email_actions table
   - Expected: Record with action_type='otp_code', purpose='account_deletion', status='sent'

6. **Notification Logging**
   - Send test notification → Check email_actions table
   - Expected: Record with action_type='notification', status='sent'

### Verification Queries
```sql
-- Check all OTP logs
SELECT id, user_id, email_address, action_type, purpose, status, created_at 
FROM email_actions 
WHERE action_type = 'otp_code' 
ORDER BY created_at DESC;

-- Check failed sends
SELECT id, email_address, action_type, status, error_message 
FROM email_actions 
WHERE status = 'failed';

-- Check by purpose
SELECT id, user_id, purpose, status, created_at 
FROM email_actions 
WHERE purpose = 'registration' 
ORDER BY created_at DESC;
```

### Success Criteria
- All email actions logged to database
- Status transitions correct (pending → sent/failed)
- user_id correctly associated with logs
- Error messages captured for failed sends
- No application errors during logging

---

## Summary

| Task | Type | Status | Dependencies |
|------|------|--------|--------------|
| 1. Apply Email Actions Migration | Leaf | not_started | None |
| 2. Add logEmailAction() Method | Leaf | not_started | None |
| 3. Modify sendOtpCode() Logging | Leaf | not_started | Task 2 |
| 4. Modify sendNotification() Logging | Leaf | not_started | Task 2 |
| 5. Update OtpService User ID | Leaf | not_started | Task 3 |
| 6. Test Email Logging E2E | Leaf | not_started | Task 3, 4, 5 |

**Total Tasks**: 6 (all leaf tasks, 2 independent + 4 dependent)
**Execution Order**: Tasks 1 & 2 can run in parallel, then 3, 4 (parallel), then 5, then 6
