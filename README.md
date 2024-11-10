## About This

This hybrid starter kit ([see front-end repo](https://github.com/ErikliPizza/QuasarHybridAuth)) includes comprehensive backend functionality, leveraging **Laravel Sanctum** for secure, scalable authentication. The integration supports both **session-based SPA authentication** and **token-based API authentication**, making it an ideal foundation for building robust web and mobile applications. This starter kit is designed to help developers quickly implement authentication features, enhance security, and simplify development.

## Key Components

### Database Structure (Migrations)

The starter kit includes migrations for setting up essential tables:

1. **Users Table**: Stores user data, including credentials and TFA status.
2. **Sessions Table**: Tracks user sessions.
3. **Personal Access Tokens Table**: Handles API token storage and management.
4. **Verification Codes Table**: Supports two-factor authentication (2FA) and password resets.
5. **Registration Verifications Table**: Handles email verification for registration.

### Models

- **User Model**:
    - Supports API tokens, notifications, and a gravatar attribute.
    - Contains hidden and fillable attributes.
- **VerificationCode Model**:
    - Includes methods for checking expiration and user association.
- **RegistrationVerification Model**:
    - Contains contact verification and expiration handling.

## Why Keep it Hybrid

This backend supports both **SPA and Token-Based authentication** through **Laravel Sanctum**, enabling:

- **Flexible authentication methods** for different parts of the application.
- **Enhanced security** through CSRF protection for SPAs and token control for external API access.

### Authentication Methods Supported:

1. **SPA Authentication**:
    - Session-based, ideal for seamless authentication with CSRF protection.
2. **Token-Based Authentication**:
    - Useful for third-party integrations and mobile apps.

## Additional Features and Services

### Included Features:
- **Enums and Validation Logic**: Common validation logic to ensure data consistency.
- **Notification Service**: Handles email verification for 2FA and password reset using queued notifications.
- **Response Macros**: Custom macros to standardize JSON responses.

### Customization Note:
Developers should review and modify included features as needed. Extra tables or services can be customized or removed based on the project requirements.

## CORS Policy and API Considerations

### CORS Settings:
Ensure your `config/cors.php` allows specific origins for local development. The `config/sanctum.php` is set up to support both web and mobile applications.

**Important**:
- Ensure API routes are protected by Sanctum middleware (`auth:sanctum`).
- Validate `.env` variables for consistency between local and production environments.
### Necessary Environment Variables
- **For SPA Auth** set your session driver to cookie: ``SESSION_DRIVER=cookie``
- **2FA**: ``TWO_FACTOR_AUTH=true``

## Credits
- A tiny thanks to [@remzikilnc](https://github.com/remzikilnc) for contributing the response macros and service implementation.
