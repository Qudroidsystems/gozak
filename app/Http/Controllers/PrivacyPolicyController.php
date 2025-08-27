<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    public function show()
    {
        $policy = $this->getPrivacyPolicyContent();
        return response()->json([
            'title' => 'Privacy Policy',
            'content' => $policy,
            'last_updated' => 'August 27, 2025'
        ]);
    }

    private function getPrivacyPolicyContent()
    {
        return <<<'EOD'
# Privacy Policy

Last Updated: August 27, 2025

## Introduction
Our application ("App") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our App. Please read this policy carefully. If you do not agree with the terms of this Privacy Policy, please do not access the App.

## Information We Collect
We may collect the following types of information:

### Personal Information
- **Account Information**: When you register, we collect your name, email address, and password. If you use social login, we may collect information provided by the social media platform (e.g., name, email, profile picture).
- **Profile Information**: You may provide additional details such as a profile picture or other personal details through the user profile settings.
- **Address Information**: When placing orders, you may provide shipping or billing addresses.

### Non-Personal Information
- **Device Information**: We collect device-specific information such as device type, operating system, unique device identifiers, and IP address.
- **Usage Data**: We collect information about how you interact with the App, such as pages visited, features used, and time spent on the App.
- **Product Reviews**: Comments or reviews you submit about products are collected and may be publicly displayed.

### Information from Children
If your target audience includes children under 13, we comply with the Children’s Online Privacy Protection Act (COPPA). We do not knowingly collect personal information from children under 13 without verifiable parental consent. If we learn that we have collected such information without consent, we will delete it promptly.

## How We Use Your Information
We use the collected information for the following purposes:
- To provide and maintain the App’s services, including user account management, order processing, and product delivery.
- To personalize your experience, such as displaying relevant products or banners.
- To communicate with you, including sending email verification, password reset emails, or order updates.
- To improve the App by analyzing usage data and feedback.
- To comply with legal obligations and protect against fraudulent activities.

## How We Share Your Information
We may share your information in the following cases:
- **Service Providers**: We work with third-party service providers (e.g., payment processors, shipping companies) to facilitate services like order fulfillment.
- **Legal Compliance**: We may disclose information to comply with applicable laws, regulations, or legal processes.
- **Business Transfers**: In the event of a merger, acquisition, or sale of assets, your information may be transferred to the new entity.
- **Public Reviews**: Product reviews or comments you submit may be publicly displayed on the App.

We do not sell your personal information to third parties.

## Data Security
We implement reasonable security measures to protect your information, such as encryption for sensitive data and secure authentication methods. However, no method of transmission over the Internet is 100% secure, and we cannot guarantee absolute security.

## Your Data Rights
Depending on your location (e.g., under GDPR for EU residents), you may have the following rights:
- **Access**: Request access to the personal data we hold about you.
- **Correction**: Request correction of inaccurate or incomplete data.
- **Deletion**: Request deletion of your personal data.
- **Restriction**: Request restriction of processing your data.
- **Portability**: Request a copy of your data in a structured, machine-readable format.

To exercise these rights, please contact us at [Your Contact Email].

## Cookies and Tracking
The App uses cookies to enhance user experience, such as maintaining login sessions and tracking usage patterns. You can manage cookie preferences through your browser settings.

## Third-Party Links
The App may contain links to third-party websites or services. We are not responsible for the privacy practices of these third parties. Please review their privacy policies before interacting with them.

## Children’s Privacy
As noted, we comply with COPPA for users under 13. Parents or guardians can contact us to review, update, or delete their child’s information.

## Changes to This Privacy Policy
We may update this Privacy Policy from time to time. We will notify you of significant changes by posting the updated policy in the App or sending a notification to your registered email.

## Contact Us
If you have any questions about this Privacy Policy, please contact us at:
- Email: app@qudroid.co
- Address: General Hospital road, Ondo City,Nigeria

By using the App, you agree to the terms of this Privacy Policy.
EOD;
    }
}
?>