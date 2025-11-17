<?php
require_once 'config.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .legal-container {
            max-width: 900px;
            margin: 0 auto;
            padding: var(--spacing-2xl) var(--spacing-xl);
        }
        .legal-header {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-2xl);
            text-align: center;
        }
        .legal-header h1 {
            margin: 0 0 var(--spacing-md) 0;
            font-size: 2.5rem;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .legal-updated {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .legal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl);
        }
        .legal-content h2 {
            color: var(--text-primary);
            margin: var(--spacing-2xl) 0 var(--spacing-lg) 0;
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid var(--border-primary);
        }
        .legal-content h2:first-child {
            margin-top: 0;
        }
        .legal-content h3 {
            color: var(--text-primary);
            margin: var(--spacing-xl) 0 var(--spacing-md) 0;
        }
        .legal-content p {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: var(--spacing-lg);
        }
        .legal-content ul, .legal-content ol {
            color: var(--text-secondary);
            margin: var(--spacing-md) 0 var(--spacing-lg) var(--spacing-xl);
            line-height: 1.8;
        }
        .legal-content li {
            margin-bottom: var(--spacing-sm);
        }
        .legal-content strong {
            color: var(--text-primary);
        }
        .legal-highlight {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid var(--accent-primary);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin: var(--spacing-lg) 0;
        }
        .legal-warning {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin: var(--spacing-lg) 0;
        }
        .contact-box {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-md);
            padding: var(--spacing-xl);
            margin: var(--spacing-2xl) 0;
        }
        .contact-box h3 {
            margin-top: 0;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--accent-primary);
            text-decoration: none;
            margin-bottom: var(--spacing-xl);
            transition: all var(--transition-base);
        }
        .back-link:hover {
            color: var(--accent-secondary);
            transform: translateX(-4px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="legal-container">
        <a href="index.php" class="back-link">← Back to Home</a>

        <div class="legal-header">
            <h1>Terms of Service</h1>
            <p class="legal-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="legal-content">
            <h2>1. Agreement to Terms</h2>
            <p>By accessing or using RiftVault.gg ("the Service," "we," "our," or "us"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you may not access or use the Service.</p>
            <p>These Terms constitute a legally binding agreement between you and RiftVault.gg. Please read them carefully.</p>

            <div class="legal-highlight">
                <strong>Important Notice:</strong> RiftVault.gg is a fan-created website for the League of Legends Trading Card Game (Riftbound). It was created under Riot Games' "Legal Jibber Jabber" policy using assets owned by Riot Games. Riot Games does not endorse or sponsor this project.
            </div>

            <h2>2. Eligibility</h2>
            <p>You must be at least 13 years old to use this Service. By using the Service, you represent and warrant that you:</p>
            <ul>
                <li>Are at least 13 years of age</li>
                <li>Have the legal capacity to enter into these Terms</li>
                <li>Will comply with these Terms and all applicable laws</li>
            </ul>
            <p>If you are under 18, you represent that you have reviewed these Terms with your parent or guardian and they have agreed to these Terms on your behalf.</p>

            <h2>3. User Accounts</h2>

            <h3>3.1 Account Creation</h3>
            <p>To access certain features of the Service, you must create an account. You agree to:</p>
            <ul>
                <li>Provide accurate, current, and complete information during registration</li>
                <li>Maintain and promptly update your account information</li>
                <li>Maintain the security of your password and account</li>
                <li>Notify us immediately of any unauthorized use of your account</li>
                <li>Accept responsibility for all activities that occur under your account</li>
            </ul>

            <h3>3.2 Account Security</h3>
            <p>You are responsible for maintaining the confidentiality of your account credentials. We cannot and will not be liable for any loss or damage arising from your failure to maintain account security.</p>

            <h3>3.3 Account Termination</h3>
            <p>You may delete your account at any time through your account settings. We reserve the right to suspend or terminate your account at our discretion if you violate these Terms.</p>

            <h2>4. Acceptable Use Policy</h2>
            <p>You agree NOT to use the Service to:</p>
            <ul>
                <li>Violate any applicable laws or regulations</li>
                <li>Infringe upon the intellectual property rights of others</li>
                <li>Harass, abuse, threaten, or intimidate other users</li>
                <li>Post spam, unauthorized advertising, or promotional content</li>
                <li>Distribute malware, viruses, or harmful code</li>
                <li>Attempt to gain unauthorized access to the Service or other users' accounts</li>
                <li>Scrape, crawl, or use automated tools to access the Service without permission</li>
                <li>Impersonate another person or entity</li>
                <li>Post content that is illegal, obscene, defamatory, or hateful</li>
                <li>Interfere with or disrupt the Service or servers</li>
            </ul>

            <div class="legal-warning">
                <strong>Warning:</strong> Violation of this Acceptable Use Policy may result in immediate termination of your account and may be reported to appropriate authorities.
            </div>

            <h2>5. User-Generated Content</h2>

            <h3>5.1 Content Ownership</h3>
            <p>You retain ownership of any content you create and publish on RiftVault.gg, including:</p>
            <ul>
                <li>Deck builds and configurations</li>
                <li>Deck names and descriptions</li>
                <li>Collection data</li>
                <li>Comments and community contributions</li>
            </ul>

            <h3>5.2 Content License</h3>
            <p>By publishing content on RiftVault.gg, you grant us a worldwide, non-exclusive, royalty-free license to:</p>
            <ul>
                <li>Display, reproduce, and distribute your content on the Service</li>
                <li>Allow other users to view and copy your published decks</li>
                <li>Create derivative works for the purpose of displaying or distributing your content</li>
            </ul>
            <p>This license continues even if you stop using the Service, but you may delete your content at any time.</p>

            <h3>5.3 Content Standards</h3>
            <p>All user-generated content must comply with our Acceptable Use Policy. We reserve the right to remove any content that violates these Terms or that we deem inappropriate.</p>

            <h2>6. Intellectual Property Rights</h2>

            <h3>6.1 Riot Games Content</h3>
            <p>RiftVault.gg was created under Riot Games' "Legal Jibber Jabber" policy. All League of Legends, Riftbound, and related content, including but not limited to:</p>
            <ul>
                <li>Card images and artwork</li>
                <li>Game logos and trademarks</li>
                <li>Champion names and characters</li>
                <li>Game mechanics and terminology</li>
            </ul>
            <p>...are the exclusive property of Riot Games, Inc. We do not claim ownership of any Riot Games content.</p>

            <h3>6.2 RiftVault.gg Content</h3>
            <p>The Service itself, including its design, features, code, and original content (excluding user-generated content and Riot Games assets), is owned by RiftVault.gg and protected by copyright, trademark, and other intellectual property laws.</p>

            <h3>6.3 Riot Games Legal Jibber Jabber</h3>
            <p>This project exists under Riot Games' "Legal Jibber Jabber" policy, which can be found at: <a href="https://www.riotgames.com/en/legal" target="_blank" rel="noopener">https://www.riotgames.com/en/legal</a></p>

            <h2>7. Third-Party Services</h2>
            <p>The Service may integrate with or link to third-party services, including:</p>
            <ul>
                <li>Riot Games OAuth authentication</li>
                <li>Riftmana.com card database</li>
                <li>Other community resources</li>
            </ul>
            <p>We are not responsible for the content, privacy policies, or practices of any third-party services. Your use of third-party services is at your own risk.</p>

            <h2>8. Disclaimer of Warranties</h2>
            <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO:</p>
            <ul>
                <li>Warranties of merchantability, fitness for a particular purpose, or non-infringement</li>
                <li>Warranties that the Service will be uninterrupted, secure, or error-free</li>
                <li>Warranties regarding the accuracy, reliability, or completeness of content</li>
            </ul>
            <p>We do not warrant that defects will be corrected or that the Service is free from viruses or other harmful components.</p>

            <h2>9. Limitation of Liability</h2>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY UK LAW:</p>
            <ul>
                <li>RiftVault.gg shall not be liable for any indirect, incidental, special, consequential, or punitive damages</li>
                <li>Our total liability for any claims related to the Service shall not exceed £100 GBP</li>
                <li>We are not liable for any loss of data, profits, revenue, or business opportunities</li>
                <li>We are not liable for actions or content of third parties, including other users</li>
            </ul>
            <p>Nothing in these Terms excludes or limits our liability for death or personal injury caused by our negligence, fraud, or fraudulent misrepresentation.</p>

            <h2>10. Indemnification</h2>
            <p>You agree to indemnify and hold harmless RiftVault.gg, its operators, and affiliates from any claims, damages, losses, liabilities, and expenses (including legal fees) arising from:</p>
            <ul>
                <li>Your use of the Service</li>
                <li>Your violation of these Terms</li>
                <li>Your violation of any rights of another person or entity</li>
                <li>Your user-generated content</li>
            </ul>

            <h2>11. Privacy</h2>
            <p>Your use of the Service is also governed by our Privacy Policy, which is incorporated into these Terms by reference. Please review our <a href="privacy.php">Privacy Policy</a> to understand our data practices.</p>

            <h2>12. Modifications to the Service</h2>
            <p>We reserve the right to:</p>
            <ul>
                <li>Modify, suspend, or discontinue the Service at any time</li>
                <li>Change features, functionality, or content</li>
                <li>Impose limits on certain features or restrict access to parts of the Service</li>
            </ul>
            <p>We are not liable to you or any third party for any modification, suspension, or discontinuance of the Service.</p>

            <h2>13. Changes to Terms</h2>
            <p>We may update these Terms from time to time. When we do, we will:</p>
            <ul>
                <li>Post the updated Terms on this page</li>
                <li>Update the "Last Updated" date</li>
                <li>Notify you via email or Service notification for material changes</li>
            </ul>
            <p>Your continued use of the Service after changes are posted constitutes acceptance of the updated Terms. If you do not agree to the updated Terms, you must stop using the Service.</p>

            <h2>14. Termination</h2>

            <h3>14.1 Termination by You</h3>
            <p>You may terminate your account at any time by deleting it through your account settings.</p>

            <h3>14.2 Termination by Us</h3>
            <p>We may suspend or terminate your account immediately, without prior notice, if:</p>
            <ul>
                <li>You breach these Terms</li>
                <li>Your conduct harms or could harm other users or the Service</li>
                <li>We are required to do so by law</li>
                <li>We cease operating the Service</li>
            </ul>

            <h3>14.3 Effects of Termination</h3>
            <p>Upon termination:</p>
            <ul>
                <li>Your right to access the Service immediately ceases</li>
                <li>We may delete your account and content (subject to our Privacy Policy)</li>
                <li>Provisions of these Terms that should survive termination will continue to apply</li>
            </ul>

            <h2>15. Governing Law and Disputes</h2>

            <h3>15.1 Governing Law</h3>
            <p>These Terms are governed by and construed in accordance with the laws of England and Wales, without regard to conflict of law principles.</p>

            <h3>15.2 Dispute Resolution</h3>
            <p>Any dispute arising from these Terms or the Service shall be subject to the exclusive jurisdiction of the courts of England and Wales.</p>

            <h3>15.3 Informal Resolution</h3>
            <p>Before filing any formal legal claim, you agree to first contact us to attempt to resolve the dispute informally.</p>

            <h2>16. Severability</h2>
            <p>If any provision of these Terms is found to be invalid or unenforceable, that provision shall be limited or eliminated to the minimum extent necessary, and the remaining provisions shall remain in full force and effect.</p>

            <h2>17. Entire Agreement</h2>
            <p>These Terms, together with our Privacy Policy, constitute the entire agreement between you and RiftVault.gg regarding the Service and supersede all prior agreements and understandings.</p>

            <h2>18. No Waiver</h2>
            <p>Our failure to enforce any right or provision of these Terms shall not constitute a waiver of such right or provision.</p>

            <h2>19. Assignment</h2>
            <p>You may not assign or transfer these Terms or your account without our prior written consent. We may assign these Terms without restriction.</p>

            <h2>20. Contact Information</h2>
            <div class="contact-box">
                <h3>Questions About These Terms?</h3>
                <p>If you have any questions about these Terms of Service, please contact us:</p>
                <p>
                    <strong>Email:</strong> legal@riftvault.gg<br>
                    <strong>Website:</strong> <a href="<?php echo SITE_URL; ?>"><?php echo SITE_URL; ?></a>
                </p>
            </div>

            <h2>21. Acknowledgment</h2>
            <p>By using RiftVault.gg, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>

            <div class="legal-highlight">
                <strong>Riot Games Disclaimer:</strong> RiftVault.gg is not affiliated with, endorsed by, or sponsored by Riot Games. This is a fan-made project created under Riot Games' Legal Jibber Jabber policy. League of Legends, Riftbound, and all related content are trademarks and copyrights of Riot Games, Inc.
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
