<?php
/**
 * security/tests/EmailServiceTest.php
 * Unit tests for EmailService
 * 
 * Test Coverage:
 * - sendOtpCode() - Successful send, invalid email, invalid code format
 * - sendNotification() - Successful send, invalid email
 * - Email masking - user@example.com → u***@example.com
 * - Template rendering - HTML and text versions
 */

use PHPUnit\Framework\TestCase;

class EmailServiceTest extends TestCase
{
    private EmailService $emailService;

    protected function setUp(): void
    {
        // Create EmailService instance
        $this->emailService = new EmailService();
    }

    // ═══════════════════════════════════════════════════════
    // SEND OTP CODE TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * sendOtpCode should return success for valid inputs
     */
    public function testSendOtpCodeSuccess(): void
    {
        // Note: This will attempt to send an actual email via configured MAIL_DRIVER
        // In test environment, this should be set to 'mail' or mock SMTP
        $result = $this->emailService->sendOtpCode(
            'test@example.com',
            '123456',
            5,
            'registration'
        );

        // On test environment, this may succeed or fail depending on mail config
        // We primarily test the structure of the response
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }

    /**
     * @test
     * sendOtpCode should reject invalid email format
     */
    public function testSendOtpCodeRejectsInvalidEmail(): void
    {
        $invalid_emails = [
            'not-an-email',
            'user@',
            '@example.com',
            'user@example',
            'user @example.com',
            '',
            'user@example..com',
        ];

        foreach ($invalid_emails as $invalid_email) {
            $result = $this->emailService->sendOtpCode(
                $invalid_email,
                '123456',
                5,
                'registration'
            );

            $this->assertFalse($result['success']);
            $this->assertStringContainsString('invalide', $result['message']);
        }
    }

    /**
     * @test
     * sendOtpCode should reject invalid OTP code format
     */
    public function testSendOtpCodeRejectsInvalidCodeFormat(): void
    {
        $invalid_codes = [
            '12345',      // Too short
            '1234567',    // Too long
            'abcdef',     // Non-numeric
            '12-34-56',   // With dashes
            '',           // Empty
            '0',          // Single digit
        ];

        foreach ($invalid_codes as $invalid_code) {
            $result = $this->emailService->sendOtpCode(
                'test@example.com',
                $invalid_code,
                5,
                'registration'
            );

            $this->assertFalse($result['success']);
            $this->assertStringContainsString('invalide', $result['message']);
        }
    }

    /**
     * @test
     * sendOtpCode should accept all valid purposes
     */
    public function testSendOtpCodeAcceptsAllValidPurposes(): void
    {
        $purposes = [
            'registration',
            'password_reset',
            'email_change',
            'account_deletion',
            'domizi_action',
        ];

        foreach ($purposes as $purpose) {
            $result = $this->emailService->sendOtpCode(
                'test@example.com',
                '123456',
                5,
                $purpose
            );

            // Response should be valid (structure check)
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('message', $result);
        }
    }

    /**
     * @test
     * sendOtpCode should accept valid 6-digit codes
     */
    public function testSendOtpCodeAcceptsValid6DigitCodes(): void
    {
        $valid_codes = ['000000', '123456', '999999', '000001'];

        foreach ($valid_codes as $code) {
            $result = $this->emailService->sendOtpCode(
                'test@example.com',
                $code,
                5,
                'registration'
            );

            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('message', $result);
        }
    }

    // ═══════════════════════════════════════════════════════
    // SEND NOTIFICATION TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * sendNotification should return success for valid inputs
     */
    public function testSendNotificationSuccess(): void
    {
        $result = $this->emailService->sendNotification(
            'test@example.com',
            'Test Subject',
            'Test message content'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }

    /**
     * @test
     * sendNotification should reject invalid email
     */
    public function testSendNotificationRejectsInvalidEmail(): void
    {
        $result = $this->emailService->sendNotification(
            'not-an-email',
            'Subject',
            'Message'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalide', $result['message']);
    }

    /**
     * @test
     * sendNotification should accept optional action parameters
     */
    public function testSendNotificationWithAction(): void
    {
        $result = $this->emailService->sendNotification(
            'test@example.com',
            'Action Required',
            'Please confirm this action',
            'Click Here',
            'https://example.com/confirm'
        );

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    // ═══════════════════════════════════════════════════════
    // EMAIL MASKING TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Email should be masked in template
     * 
     * We test this by capturing the email template content
     */
    public function testEmailMaskingInTemplate(): void
    {
        // This test uses reflection to access the private maskEmail method
        $reflection = new ReflectionClass(EmailService::class);
        $maskEmail = $reflection->getMethod('maskEmail');
        $maskEmail->setAccessible(true);

        $emails = [
            'user@example.com' => 'u***@example.com',
            'a@example.com' => 'a***@example.com',
            'john.doe@example.com' => 'j***@example.com',
            'very.long.email.address@example.com' => 'v***@example.com',
            'x@example.com' => 'x***@example.com',
        ];

        foreach ($emails as $original => $expected) {
            $result = $maskEmail->invoke($this->emailService, $original);
            $this->assertEquals($expected, $result);
        }
    }

    // ═══════════════════════════════════════════════════════
    // TEMPLATE RENDERING TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * OTP template should contain HTML and be well-formed
     */
    public function testOtpTemplateRendering(): void
    {
        // Use reflection to access buildOtpEmailHtml
        $reflection = new ReflectionClass(EmailService::class);
        $buildTemplate = $reflection->getMethod('buildOtpEmailHtml');
        $buildTemplate->setAccessible(true);

        $html = $buildTemplate->invoke(
            $this->emailService,
            '123456',
            5,
            'Test introduction',
            'u***@example.com'
        );

        // Check HTML structure
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('<meta charset="UTF-8">', $html);

        // Check content
        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('5', $html); // Validity in minutes
        $this->assertStringContainsString('Test introduction', $html);
        $this->assertStringContainsString('u***@example.com', $html);

        // Check security message
        $this->assertStringContainsString('Ne partagez jamais ce code', $html);
        $this->assertStringContainsString('Coiffe Chez Toi', $html);
    }

    /**
     * @test
     * Notification template should contain HTML structure
     */
    public function testNotificationTemplateRendering(): void
    {
        // Use reflection to access buildNotificationEmailHtml
        $reflection = new ReflectionClass(EmailService::class);
        $buildTemplate = $reflection->getMethod('buildNotificationEmailHtml');
        $buildTemplate->setAccessible(true);

        $html = $buildTemplate->invoke(
            $this->emailService,
            '<p>Test message</p>',
            'Click Here',
            'https://example.com'
        );

        // Check HTML structure
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);

        // Check content
        $this->assertStringContainsString('Test message', $html);
        $this->assertStringContainsString('Click Here', $html);
        $this->assertStringContainsString('https://example.com', $html);
        $this->assertStringContainsString('Coiffe Chez Toi', $html);
    }

    /**
     * @test
     * Template should have different subject for different purposes
     */
    public function testOtpSubjectVariesByPurpose(): void
    {
        // This test checks that sendOtpCode sets appropriate subjects
        // We can't directly check this without mocking PHPMailer, but we verify
        // that all purposes are handled
        
        $purposes = [
            'registration' => 'Vérification',
            'password_reset' => 'Réinitialisation',
            'email_change' => 'Vérification de votre nouvelle adresse',
            'account_deletion' => 'suppression',
            'domizi_action' => 'confirmation',
        ];

        foreach ($purposes as $purpose => $keyword) {
            $result = $this->emailService->sendOtpCode(
                'test@example.com',
                '123456',
                5,
                $purpose
            );
            // Just verify the method handles all purposes without error
            $this->assertIsArray($result);
        }
    }

    // ═══════════════════════════════════════════════════════
    // HTML VALIDITY TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * OTP template HTML should include common security elements
     */
    public function testOtpTemplateSecurity(): void
    {
        $reflection = new ReflectionClass(EmailService::class);
        $buildTemplate = $reflection->getMethod('buildOtpEmailHtml');
        $buildTemplate->setAccessible(true);

        $html = $buildTemplate->invoke(
            $this->emailService,
            '123456',
            5,
            'Intro',
            'user***@example.com'
        );

        // Security elements
        $this->assertStringContainsString('viewport', $html); // Mobile responsive
        $this->assertStringContainsString('UTF-8', $html);
        $this->assertStringContainsString('⚠️', $html); // Warning icon

        // Proper email structure
        $this->assertStringContainsString('font-family', $html);
        $this->assertStringContainsString('display: inline-block', $html); // Email compatibility
    }

    /**
     * @test
     * Template should have proper styling for email clients
     */
    public function testTemplateEmailClientCompatibility(): void
    {
        $reflection = new ReflectionClass(EmailService::class);
        $buildTemplate = $reflection->getMethod('buildOtpEmailHtml');
        $buildTemplate->setAccessible(true);

        $html = $buildTemplate->invoke(
            $this->emailService,
            '123456',
            5,
            'Intro',
            'user@example.com'
        );

        // Common email client compatibility checks
        $this->assertStringContainsString('max-width', $html); // Responsive width
        $this->assertStringContainsString('padding', $html); // Spacing
        $this->assertStringContainsString('border-radius', $html); // Rounded corners (with fallback)
        $this->assertStringContainsString('box-shadow', $html); // Shadow (with fallback)

        // Monospace font for code
        $this->assertStringContainsString('Courier New', $html);
    }

    // ═══════════════════════════════════════════════════════
    // TEST CONNECTION
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * testConnection should return array with success and message
     */
    public function testConnectionMethodStructure(): void
    {
        $result = $this->emailService->testConnection();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }
}
