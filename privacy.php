<?php
require_once 'config.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo SITE_NAME; ?></title>
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
        .legal-table {
            width: 100%;
            margin: var(--spacing-lg) 0;
            border-collapse: collapse;
        }
        .legal-table th, .legal-table td {
            padding: var(--spacing-md);
            text-align: left;
            border: 1px solid var(--border-primary);
        }
        .legal-table th {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            font-weight: 600;
        }
        .legal-table td {
            color: var(--text-secondary);
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
            <h1>Privacy Policy</h1>
            <p class="legal-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="legal-content">
            <h2>1. Introduction</h2>
            <p>Welcome to RiftVault.gg ("we," "our," or "us"). We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and services.</p>
            <p>RiftVault.gg is operated from the United Kingdom and complies with UK data protection laws, including the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.</p>

            <div class="legal-highlight">
                <strong>Important Notice:</strong> RiftVault.gg is a fan-created website for the League of Legends Trading Card Game (Riftbound). It was created under Riot Games' "Legal Jibber Jabber" policy using assets owned by Riot Games. Riot Games does not endorse or sponsor this project.
            </div>

            <h2>2. Information We Collect</h2>
            <p>We collect and process the following types of information:</p>

            <h3>2.1 Account Information</h3>
            <ul>
                <li><strong>Username:</strong> Your chosen display name for the platform</li>
                <li><strong>Email Address:</strong> Used for account verification and communications</li>
                <li><strong>Password:</strong> Stored in encrypted format using industry-standard hashing</li>
                <li><strong>OAuth Tokens:</strong> If you connect external services (e.g., Riot Games account)</li>
            </ul>

            <h3>2.2 User-Generated Content</h3>
            <ul>
                <li><strong>Card Collection Data:</strong> Information about cards you own and wish to collect</li>
                <li><strong>Deck Builds:</strong> Custom decks you create, including names, descriptions, and card lists</li>
                <li><strong>Published Content:</strong> Any decks or content you choose to share publicly</li>
                <li><strong>Activity Data:</strong> Likes, copies, and views on community content</li>
            </ul>

            <h3>2.3 Automatically Collected Information</h3>
            <ul>
                <li><strong>Usage Data:</strong> Pages visited, features used, and time spent on the site</li>
                <li><strong>Device Information:</strong> Browser type, operating system, and device identifiers</li>
                <li><strong>Log Data:</strong> IP addresses, access times, and error logs for security purposes</li>
                <li><strong>Cookies:</strong> Session cookies for maintaining your logged-in state</li>
            </ul>

            <h2>3. How We Use Your Information</h2>
            <p>We use your information for the following purposes:</p>
            <ul>
                <li><strong>Account Management:</strong> Creating and maintaining your account</li>
                <li><strong>Service Delivery:</strong> Providing deck building, collection tracking, and community features</li>
                <li><strong>Security:</strong> Protecting against unauthorized access and malicious activity</li>
                <li><strong>Communication:</strong> Sending important service updates and notifications</li>
                <li><strong>Improvement:</strong> Analyzing usage patterns to improve our services</li>
                <li><strong>Legal Compliance:</strong> Fulfilling our legal obligations</li>
            </ul>

            <h2>4. Legal Basis for Processing (UK GDPR)</h2>
            <p>We process your personal data under the following legal bases:</p>
            <table class="legal-table">
                <thead>
                    <tr>
                        <th>Purpose</th>
                        <th>Legal Basis</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Account creation and management</td>
                        <td>Contract performance</td>
                    </tr>
                    <tr>
                        <td>Service delivery (deck builder, collection)</td>
                        <td>Contract performance</td>
                    </tr>
                    <tr>
                        <td>Security and fraud prevention</td>
                        <td>Legitimate interests</td>
                    </tr>
                    <tr>
                        <td>Service improvements</td>
                        <td>Legitimate interests</td>
                    </tr>
                    <tr>
                        <td>Marketing communications (if opted in)</td>
                        <td>Consent</td>
                    </tr>
                </tbody>
            </table>

            <h2>5. Data Storage and Security</h2>
            <p>All personal data is stored securely on servers located in the <strong>United Kingdom</strong>. We implement appropriate technical and organizational measures to protect your data, including:</p>
            <ul>
                <li>Encrypted password storage using bcrypt hashing</li>
                <li>Secure HTTPS connections for all data transmission</li>
                <li>Regular security audits and updates</li>
                <li>Access controls limiting who can view personal data</li>
                <li>Regular backups to prevent data loss</li>
            </ul>
            <p>However, no method of transmission over the Internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security.</p>

            <h2>6. Data Retention</h2>
            <p>We retain your personal data for as long as:</p>
            <ul>
                <li>Your account remains active</li>
                <li>Needed to provide you with our services</li>
                <li>Required by law or for legitimate business purposes</li>
            </ul>
            <p>When you delete your account, we will delete or anonymize your personal data within 30 days, except where we are legally required to retain it longer.</p>

            <h2>7. Data Sharing and Disclosure</h2>
            <p>We do <strong>not</strong> sell, rent, or trade your personal information. We may share your information only in the following limited circumstances:</p>
            <ul>
                <li><strong>Public Content:</strong> Decks and content you choose to publish publicly will be visible to other users</li>
                <li><strong>Legal Requirements:</strong> When required by law, court order, or governmental authority</li>
                <li><strong>Safety and Security:</strong> To protect the rights, property, or safety of RiftVault.gg, users, or others</li>
                <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets (with notice to you)</li>
                <li><strong>Service Providers:</strong> Third-party services that help us operate (e.g., hosting providers), under strict confidentiality</li>
            </ul>

            <h2>8. Your Rights Under UK GDPR</h2>
            <p>As a UK resident, you have the following rights regarding your personal data:</p>
            <ul>
                <li><strong>Right to Access:</strong> Request a copy of your personal data</li>
                <li><strong>Right to Rectification:</strong> Correct inaccurate or incomplete data</li>
                <li><strong>Right to Erasure:</strong> Request deletion of your data ("right to be forgotten")</li>
                <li><strong>Right to Restrict Processing:</strong> Limit how we use your data</li>
                <li><strong>Right to Data Portability:</strong> Receive your data in a structured, commonly used format</li>
                <li><strong>Right to Object:</strong> Object to processing based on legitimate interests</li>
                <li><strong>Right to Withdraw Consent:</strong> Where processing is based on consent</li>
                <li><strong>Right to Lodge a Complaint:</strong> File a complaint with the ICO (Information Commissioner's Office)</li>
            </ul>
            <p>To exercise any of these rights, please contact us using the information below.</p>

            <h2>9. Cookies and Tracking</h2>
            <p>We use essential cookies to:</p>
            <ul>
                <li>Keep you logged in during your session</li>
                <li>Remember your preferences</li>
                <li>Provide security features</li>
            </ul>
            <p>We do not use third-party advertising cookies or tracking pixels. You can control cookies through your browser settings, but disabling cookies may affect site functionality.</p>

            <h2>10. Third-Party Services</h2>
            <p>RiftVault.gg may integrate with third-party services, including:</p>
            <ul>
                <li><strong>Riot Games OAuth:</strong> For account connection (optional)</li>
                <li><strong>Card Data:</strong> We fetch card information from Riftmana.com API</li>
            </ul>
            <p>When you use these integrations, you are also subject to those third parties' privacy policies. We encourage you to review them.</p>

            <h2>11. Children's Privacy</h2>
            <p>RiftVault.gg is not intended for users under the age of 13. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately, and we will delete such information.</p>

            <h2>12. International Data Transfers</h2>
            <p>Your data is stored and processed in the United Kingdom. If you access our services from outside the UK, your information may be transferred to, stored, and processed in the UK under UK data protection laws.</p>

            <h2>13. Changes to This Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of any material changes by:</p>
            <ul>
                <li>Posting the new Privacy Policy on this page</li>
                <li>Updating the "Last Updated" date</li>
                <li>Sending you an email notification (for significant changes)</li>
            </ul>
            <p>Your continued use of RiftVault.gg after changes are posted constitutes acceptance of the updated policy.</p>

            <h2>14. Contact Us</h2>
            <div class="contact-box">
                <h3>Data Protection Contact</h3>
                <p>If you have questions about this Privacy Policy or wish to exercise your data rights, please contact us:</p>
                <p><strong>Email:</strong> privacy@riftvault.gg<br><strong>Website:</strong> <a href="<?php echo SITE_URL; ?>"><?php echo SITE_URL; ?></a></p>
                <p><strong>UK Supervisory Authority:</strong><br>Information Commissioner's Office (ICO)<br>Website: <a href="https://ico.org.uk" target="_blank" rel="noopener">https://ico.org.uk</a><br>Phone: 0303 123 1113</p>
            </div>

            <h2>15. Disclaimer Regarding Riot Games</h2>
            <p>RiftVault.gg is not affiliated with, endorsed by, or sponsored by Riot Games. This website was created under Riot Games' "Legal Jibber Jabber" policy. All League of Legends, Riftbound, and related trademarks, assets, and content are the property of Riot Games, Inc.</p>
            <p>This Privacy Policy governs only RiftVault.gg's practices. Riot Games has its own privacy policy available at <a href="https://www.riotgames.com/en/privacy-notice" target="_blank" rel="noopener">https://www.riotgames.com/en/privacy-notice</a>.</p>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
